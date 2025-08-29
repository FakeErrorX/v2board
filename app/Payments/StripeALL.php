<?php

namespace App\Payments;
use Stripe\Stripe;
use App\Models\User;

class StripeALL {
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'currency' => [
                'label' => 'Currency Unit',
                'description' => 'Please use three-letter code compliant with ISO 4217 standard, e.g. GBP',
                'type' => 'input',
            ],
            'stripe_sk_live' => [
                'label' => 'SK_LIVE',
                'description' => '',
                'type' => 'input',
            ],
                        'webhook_key' => [
                'label' => 'WebHook Key Signature',
                'description' => '',
                'type' => 'input',
            ],
            'payment_method_types' => [
                'label' => 'Payment Methods',
                'description' => 'Please enter alipay, wechat_pay, cards',
        ];
    }
    
    public function pay($order)
    {
        $currency = $this->config['currency'];
        $exchange = $this->exchange('CNY', strtoupper($currency));
        if (!$exchange) {
            throw new abort('Currency conversion API failed', 500);
        }
        //jump url
        $jumpUrl = null;
        $actionType = 0;
        $stripe = new \Stripe\StripeClient($this->config['stripe_sk_live']);
        // Get user email
        $userEmail = $this->getUserEmail($order['user_id']);
        if ($this->config['payment_method'] != "cards"){
        $stripePaymentMethod = $stripe->paymentMethods->create([
            'type' => $this->config['payment_method'],
        ]);
        // Prepare payment intent base parameters
        $params = [
            'amount' => floor($order['total_amount'] * $exchange),
            'currency' => $currency,
            'confirm' => true,
            'payment_method' => $stripePaymentMethod->id,
            'automatic_payment_methods' => ['enabled' => true],
            'statement_descriptor' => 'user-#' . $order['user_id'] . '-' . substr($order['trade_no'], -8),
            'metadata' => [
                'user_id' => $order['user_id'],
                'customer_email' => $userEmail,
                'out_trade_no' => $order['trade_no']
            ],
            'return_url' => $order['return_url']
        ];

        // If payment method is wechat_pay, add corresponding payment method options
        if ($this->config['payment_method'] === 'wechat_pay') {
            $params['payment_method_options'] = [
                'wechat_pay' => [
                    'client' => 'web'
                ],
            ];
        }
        //Updated to support latest paymentIntents method, Sources API will be completely replaced this year
        $stripeIntents = $stripe->paymentIntents->create($params);

        $nextAction = null;
        
        if (!$stripeIntents['next_action']) {
            throw new abort(__('Payment gateway request failed'));
        }else {
            $nextAction = $stripeIntents['next_action'];
        }

        switch ($this->config['payment_method']){
            case "alipay":
                if (isset($nextAction['alipay_handle_redirect'])){
                    $jumpUrl = $nextAction['alipay_handle_redirect']['url'];
                    $actionType = 1;
                }else {
                    throw new abort('unable get Alipay redirect url', 500);
                }
                break;
            case "wechat_pay":
                if (isset($nextAction['wechat_pay_display_qr_code'])){
                    $jumpUrl = $nextAction['wechat_pay_display_qr_code']['data'];
                }else {
                    throw new abort('unable get WeChat Pay redirect url', 500);
                }
        }
    } else {
        $creditCheckOut = $stripe->checkout->sessions->create([
            'success_url' => $order['return_url'],
            'client_reference_id' => $order['trade_no'],
            'payment_method_types' => ['card'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => floor($order['total_amount'] * $exchange),
                        'product_data' => [
                            'name' => 'user-#' . $order['user_id'] . '-' . substr($order['trade_no'], -8),
                        ]
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'invoice_creation' => ['enabled' => true],
            'phone_number_collection' => ['enabled' => false],
            'customer_email' => $userEmail, 
        ]);
        $jumpUrl = $creditCheckOut['url'];
        $actionType = 1;
    }

        return [
            'type' => $actionType,
            'data' => $jumpUrl
        ];
    }

    public function notify($params)
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                request()->getContent() ?: json_encode($_POST),
                $_SERVER['HTTP_STRIPE_SIGNATURE'],
                $this->config['stripe_webhook_key']
            );
        } catch (\Stripe\Error\SignatureVerification $e) {
            abort(400);
        }
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $object = $event->data->object;
                if ($object->status === 'succeeded') {
                    if (!isset($object->metadata->out_trade_no)) {
                        return('order error');
                    }
                    $metaData = $object->metadata;
                    $tradeNo = $metaData->out_trade_no;
                    return [
                        'trade_no' => $tradeNo,
                        'callback_no' => $object->id
                    ];
                }
                break;
            case 'checkout.session.completed':
                    $object = $event->data->object;
                    if ($object->payment_status === 'paid') {
                        return [
                            'trade_no' => $object->client_reference_id,
                            'callback_no' => $object->payment_intent
                        ];
                    }
                    break;
                case 'checkout.session.async_payment_succeeded':
                    $object = $event->data->object;
                    return [
                        'trade_no' => $object->client_reference_id,
                        'callback_no' => $object->payment_intent
                    ];
                    break;
            default:
                throw new abort('webhook events are not supported');
        }
        return('success');
    }
    // Currency conversion API
    private function exchange($from, $to)
    {
        try {
            $url = "https://api.exchangerate-api.com/v4/latest/{$from}";
            $result = file_get_contents($url);
            $result = json_decode($result, true);

                        $response = file_get_contents($url);
            $data = json_decode($response, true);
            
            // If conversion succeeds, return result
            if (isset($data['success']) && $data['success']) {
        } catch (\Exception $e) {
            // If API fails, call the second API
            return $this->backupExchange($from, $to);
        }
    }

    // Second currency conversion API method
    private function backupExchange($from, $to)
    {
        try {
            $url = "https://api.frankfurter.app/latest?from={$from}&to={$to}";
            $result = file_get_contents($url);
            $result = json_decode($result, true);

            // If conversion succeeds, return result
            if (isset($result['rates'][$to])) {
                return $result['rates'][$to];
            } else {
                throw new \Exception("Second currency API fails");
            }
        } catch (\Exception $e) {
            // If all APIs fail, throw exception
            throw new \Exception("All currency conversion APIs fail");
        }
    }
    // Get email from user
    private function getUserEmail($userId)
    {
        $user = User::find($userId);
        return $user ? $user->email : null;
    }
}
