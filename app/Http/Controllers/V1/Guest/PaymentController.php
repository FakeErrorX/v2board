<?php

namespace App\Http\Controllers\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function notify($method, $uuid, Request $request)
    {
        try {
            $paymentService = new PaymentService($method, null, $uuid);
            $verify = $paymentService->notify($request->input());
            if (!$verify) abort(500, 'verify error');
            if (!$this->handle($verify['trade_no'], $verify['callback_no'])) {
                abort(500, 'handle error');
            }
            return(isset($verify['custom_result']) ? $verify['custom_result'] : 'success');
        } catch (\Exception $e) {
            abort(500, 'fail');
        }
    }

    public function verify($method, $uuid, Request $request)
    {
        try {
            $paymentService = new PaymentService($method, null, $uuid);
            
            // For PipraPay, we'll trigger a verification check
            if ($method === 'PipraPay') {
                $trade_no = $request->input('trade_no') ?? $request->get('trade_no');
                if (!$trade_no) {
                    return response()->json(['status' => false, 'message' => 'Missing trade_no']);
                }
                
                // Check if order exists and is pending
                $order = Order::where('trade_no', $trade_no)->first();
                if (!$order) {
                    return response()->json(['status' => false, 'message' => 'Order not found']);
                }
                
                if ($order->status == 1) {
                    return response()->json(['status' => true, 'message' => 'Payment already confirmed']);
                }
                
                // Try to verify with PipraPay
                $verify = $paymentService->notify($request->input());
                if ($verify) {
                    if ($this->handle($verify['trade_no'], $verify['callback_no'])) {
                        return response()->json(['status' => true, 'message' => 'Payment verified and processed']);
                    }
                }
                
                return response()->json(['status' => false, 'message' => 'Payment not yet confirmed']);
            }
            
            return response()->json(['status' => false, 'message' => 'Verification not supported for this payment method']);
        } catch (\Exception $e) {
            \Log::error('Payment verification error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Verification failed']);
        }
    }

    private function handle($tradeNo, $callbackNo)
    {
        $order = Order::where('trade_no', $tradeNo)->first();
        if (!$order) {
            abort(500, 'order is not found');
        }
        if ($order->status !== 0) return true;
        $orderService = new OrderService($order);
        if (!$orderService->paid($callbackNo)) {
            return false;
        }
        $telegramService = new TelegramService();
        $message = sprintf(
            "💰Successfully received %s\n———————————————\nOrder number: %s",
            $order->total_amount / 100,
            $order->trade_no
        );
        $telegramService->sendMessageWithAdmin($message);
        return true;
    }
}
