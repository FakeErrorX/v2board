<?php

namespace App\Http\Controllers\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\TicketSave;
use App\Http\Requests\User\TicketWithdraw;
use App\Jobs\SendTelegramJob;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Services\TelegramService;
use App\Services\TicketService;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Utils\Dict;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function fetch(Request $request)
    {
        $userId = $request->user['id'];
        $ticketId = $request->input('id');

        if ($ticketId) {
            $ticket = Ticket::where('id', $ticketId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $ticket['message'] = TicketMessage::where('ticket_id', $ticket->id)->get();
            for ($i = 0; $i < count($ticket['message']); $i++) {
                if ($ticket['message'][$i]['user_id'] !== $ticket->user_id) {
                    $ticket['message'][$i]['is_me'] = false;
                } else {
                    $ticket['message'][$i]['is_me'] = true;
                }
            }

            return response(['data' => $ticket]);

        }
        $ticket = Ticket::where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->get();
        return response([
            'data' => $ticket
        ]);
    }

    public function save(TicketSave $request)
    {
        try {
            DB::beginTransaction();
            if ((int)Ticket::where('status', 0)->where('user_id', $request->user['id'])->lockForUpdate()->count()) {
                throw new \Exception(__('There are other unresolved tickets'));
            }

            // Get ticket status
            $ticketStatus = config('v2board.ticket_status', 0);

            switch ($ticketStatus) {
                case 0:
                    // Fully open, no ticket restrictions
                    break;
                case 1:
                    // Limited to users with paid orders only
                    $hasOrder = Order::where('user_id', $request->user['id'])
                        ->whereIn('status', [3, 4])
                        ->exists();

                    if (!$hasOrder) {
                        throw new \Exception(__('Please purchase a plan first'));
                    }
                    break;
                case 2:
                    // Completely prohibit all tickets
                    throw new \Exception(__('Current plan does not allow creating tickets'));
                    break;
                default:
                    // Handle unknown status
                    throw new \Exception(__('Unknown ticket status'));
            }

            $ticketData = $request->only(['subject', 'level']) + ['user_id' => $request->user['id']];
            $ticket = Ticket::create($ticketData);

            TicketMessage::create([
                'user_id' => $request->user['id'],
                'ticket_id' => $ticket->id,
                'message' => $request->input('message')
            ]);

            DB::commit();
            $this->sendNotify($ticket, $request->input('message'),$request->user['id']);
            return response([
                'data' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    public function reply(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, __('Invalid parameter'));
        }
        if (empty($request->input('message'))) {
            abort(500, __('Message cannot be empty'));
        }
        $ticket = Ticket::where('id', $request->input('id'))
            ->where('user_id', $request->user['id'])
            ->first();
        if (!$ticket) {
            abort(500, __('Ticket does not exist'));
        }
        if ($ticket->status) {
            abort(500, __('The ticket is closed and cannot be replied'));
        }
        if ($request->user['id'] == $this->getLastMessage($ticket->id)->user_id) {
            abort(500, __('Please wait for the technical enginneer to reply'));
        }
        $ticketService = new TicketService();
        if (
			!$ticketService->reply(
				$ticket,
				$request->input('message'),
				$request->user['id']
			)
		) {
            abort(500, __('Ticket reply failed'));
        }
        $this->sendNotify($ticket, $request->input('message'), $request->user['id']);
        return response([
            'data' => true
        ]);
    }


    public function close(Request $request)
    {
        if (empty($request->input('id'))) {
            abort(500, __('Invalid parameter'));
        }
        $ticket = Ticket::where('id', $request->input('id'))
            ->where('user_id', $request->user['id'])
            ->first();
        if (!$ticket) {
            abort(500, __('Ticket does not exist'));
        }
        $ticket->status = 1;
        if (!$ticket->save()) {
            abort(500, __('Close failed'));
        }
        return response([
            'data' => true
        ]);
    }

    private function getLastMessage($ticketId)
    {
        return TicketMessage::where('ticket_id', $ticketId)
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function withdraw(TicketWithdraw $request)
    {
        if ((int)config('v2board.withdraw_close_enable', 0)) {
            abort(500, 'user.ticket.withdraw.not_support_withdraw');
        }
        if (
			!in_array(
				$request->input('withdraw_method'),
				config(
					'v2board.commission_withdraw_method',
					Dict::WITHDRAW_METHOD_WHITELIST_DEFAULT
				)
			)
		) {
            abort(500, __('Unsupported withdrawal method'));
        }
        $user = User::find($request->user['id']);
        $limit = config('v2board.commission_withdraw_limit', 100);
        if ($limit > ($user->commission_balance / 100)) {
            abort(500, __('The current required minimum withdrawal commission is :limit', ['limit' => $limit]));
        }
        DB::beginTransaction();
        $subject = __('[Commission Withdrawal Request] This ticket is opened by the system');
        $ticket = Ticket::create([
            'subject' => $subject,
            'level' => 2,
            'user_id' => $request->user['id']
        ]);
        if (!$ticket) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }
        $message = sprintf(
			"%s\r\n%s",
            __('Withdrawal method') . "：" . $request->input('withdraw_method'),
            __('Withdrawal account') . "：" . $request->input('withdraw_account')
        );
        $ticketMessage = TicketMessage::create([
            'user_id' => $request->user['id'],
            'ticket_id' => $ticket->id,
            'message' => $message
        ]);
        if (!$ticketMessage) {
            DB::rollback();
            abort(500, __('Failed to open ticket'));
        }
        DB::commit();
        $this->sendNotify($ticket, $message);
        return response([
            'data' => true
        ]);
    }

    private function sendNotify(Ticket $ticket, string $message, $userid = null)
	{
		$telegramService = new TelegramService();
		if (!empty($userid)) {
			$user = User::find($userid);

			if ($user) {
				$transfer_enable = $this->getFlowData($user->transfer_enable); // Total traffic
				$remaining_traffic = $this->getFlowData($user->transfer_enable - $user->u - $user->d); // Remaining traffic
				$u = $this->getFlowData($user->u); // Upload
				$d = $this->getFlowData($user->d); // Download
				$expired_at = date("Y-m-d h:m:s", $user->expired_at); // Expiration time
				if (isset($_SERVER['HTTP_X_REAL_IP'])) {
				$ip_address = $_SERVER['HTTP_X_REAL_IP'];
				} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
				} else {
					$ip_address = $_SERVER['REMOTE_ADDR'];
				}

				$api_url = "http://ip-api.com/json/{$ip_address}?fields=520191&lang=zh-CN";
				$response = file_get_contents($api_url);
				$user_location = json_decode($response, true);
				if ($user_location && $user_location['status'] === 'success') {
					$location =  $user_location['city'] . ", " . $user_location['country'];
				} else {
					$location =  "Unable to determine user address";
				}

				$plan = Plan::where('id', $user->plan_id)->first();
				$planName = $plan ? $plan->name : 'Plan information not found'; // Check if plan data is available

				$money = $user->balance / 100;
				$affmoney = $user->commission_balance / 100;
				$telegramService->sendMessageWithAdmin("📮Ticket Alert #{$ticket->id}\n———————————————\nEmail:\n`{$user->email}`\nUser Location:\n`{$location}`\nIP:\n{$ip_address}\nPlan & Traffic:\n`{$planName} of {$transfer_enable}/{$remaining_traffic}`\nUpload/Download:\n`{$u}/{$d}`\nExpiry Time:\n`{$expired_at}`\nBalance/Commission Balance:\n`{$money}/{$affmoney}`\nSubject:\n`{$ticket->subject}`\nContent:\n {$message} ", true);
			} else {
				// Handle case where user data is not found
				$telegramService->sendMessageWithAdmin("User data not found for user ID: {$userid}", true);
			}
		} else {
			$telegramService->sendMessageWithAdmin("📮Ticket Alert #{$ticket->id}\n———————————————\nSubject:\n`{$ticket->subject}`\nContent:\n {$message} ", true);
		}
	}

    private function getFlowData($b)
    {
        $g = $b / (1024 * 1024 * 1024); // Convert traffic data
        $m = $b / (1024 * 1024);
        if ($g >= 1) {
            $text = round($g, 2) . "GB";
        } else {
            $text = round($m, 2) . "MB";
        }
        return $text;
    }
}
