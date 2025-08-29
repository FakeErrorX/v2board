<?php
namespace App\Services;


use App\Jobs\SendEmailJob;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TicketService {
    public function reply($ticket, $message, $userId)
    {
        DB::beginTransaction();
        $ticketMessage = TicketMessage::create([
            'user_id' => $userId,
            'ticket_id' => $ticket->id,
            'message' => $message
        ]);
        if ($userId !== $ticket->user_id) {
            $ticket->reply_status = 1;
        } else {
            $ticket->reply_status = 0;
        }
        if (!$ticketMessage || !$ticket->save()) {
            DB::rollback();
            return false;
        }
        DB::commit();
        return $ticketMessage;
    }

    public function replyByAdmin($ticketId, $message, $userId):void
    {
        $ticket = Ticket::where('id', $ticketId)
            ->first();
        if (!$ticket) {
            abort(500, 'Ticket does not exist');
        }
        
        DB::beginTransaction();
        $ticketMessage = TicketMessage::create([
            'user_id' => $userId,
            'ticket_id' => $ticket->id,
            'message' => $message
        ]);
        $ticket->status = 0;
        if ($userId !== $ticket->user_id) {
            $ticket->reply_status = 1;
        } else {
            $ticket->reply_status = 0;
        }
        $ticket->touch();
        if (!$ticketMessage || !$ticket->save()) {
            DB::rollback();
            abort(500, 'Ticket reply failed');
        }
        DB::commit();
        $this->sendEmailNotify($ticket, $ticketMessage);
    }

    // No duplicate notifications within half an hour
    private function sendEmailNotify(Ticket $ticket, TicketMessage $ticketMessage)
    {
        $user = User::find($ticket->user_id);
        $cacheKey = 'ticket_sendEmailNotify_' . $ticket->user_id;
        if (!Cache::get($cacheKey)) {
            Cache::put($cacheKey, 1, 1800);
            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => 'Your ticket on ' . config('v2board.app_name', 'V2Board') . ' has received a reply',
                'template_name' => 'notify',
                'template_value' => [
                    'name' => config('v2board.app_name', 'V2Board'),
                    'url' => config('v2board.app_url'),
                    'content' => "Subject: {$ticket->subject}\r\nReply: {$ticketMessage->message}"
                ]
            ]);
        }
    }
}
