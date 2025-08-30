<?php

namespace App\Payments;

class PipraPay 
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'description' => 'Your PipraPay API Key',
                'type' => 'input',
            ],
            'base_url' => [
                'label' => 'Base URL',
                'description' => 'PipraPay API Base URL (e.g., https://sandbox.piprapay.com or https://api.piprapay.com)',
                'type' => 'input',
            ],
            'currency' => [
                'label' => 'Currency',
                'description' => 'Currency code (e.g., BDT, USD)',
                'type' => 'input',
            ]
        ];
    }

    public function pay($order)
    {
        $amount = $order['total_amount'] / 100; // Convert from cents to main currency unit

        // Get the payment UUID for verification URL
        $paymentUuid = null;
        if (isset($this->config['uuid'])) {
            $paymentUuid = $this->config['uuid'];
        }

        $paymentData = [
            'full_name' => $order['user_name'] ?? 'User',
            'email_mobile' => $order['user_email'] ?? 'user@example.com',
            'amount' => $amount,
            'currency' => $this->config['currency'] ?? 'BDT',
            'return_type' => 'redirect', // Required field for PipraPay
            'metadata' => [
                'order_id' => $order['trade_no'],
                'user_id' => $order['user_id'] ?? null,
                'plan_id' => $order['plan_id'] ?? null,
                'payment_uuid' => $paymentUuid
            ],
            'redirect_url' => $order['return_url'] . '?trade_no=' . $order['trade_no'] . '&verify=1',
            'cancel_url' => $order['return_url'] . '?status=cancel&trade_no=' . $order['trade_no'],
            'webhook_url' => $order['notify_url']
        ];

        $response = $this->post('/api/create-charge', $paymentData);

        if (isset($response['status']) && $response['status'] === true && isset($response['pp_url'])) {
            return [
                'type' => 1, // URL redirect type
                'data' => $response['pp_url']
            ];
        }

        // Handle error response
        $errorMessage = 'Payment creation failed';
        if (isset($response['error'])) {
            $errorMessage .= ': ' . $response['error'];
        } elseif (isset($response['message'])) {
            $errorMessage .= ': ' . $response['message'];
        } elseif (isset($response['errors'])) {
            $errorMessage .= ': ' . json_encode($response['errors']);
        }

        abort(500, $errorMessage);
    }

    public function notify($params)
    {
        try {
            // Get request data
            $data = request()->all();
            $headers = request()->headers->all();
            
            // Check if this is a webhook call (has API key header) or return URL call
            $received_key = request()->header('mh-piprapay-api-key');
            $isWebhook = !empty($received_key);
            
            \Log::info('PipraPay notify called', [
                'is_webhook' => $isWebhook,
                'params' => $params,
                'data' => $data,
                'headers' => array_keys($headers)
            ]);
            
            if ($isWebhook) {
                // Webhook validation
                if ($received_key !== $this->config['api_key']) {
                    \Log::warning('PipraPay webhook: Invalid API key', [
                        'received_key' => substr($received_key, 0, 6) . '...',
                        'expected_key' => substr($this->config['api_key'], 0, 6) . '...'
                    ]);
                    return false;
                }
                
                if (empty($data) || !isset($data['pp_id'])) {
                    \Log::warning('PipraPay webhook: Missing pp_id', $data);
                    return false;
                }
                
                $pp_id = $data['pp_id'];
                \Log::info('PipraPay webhook processing', ['pp_id' => $pp_id]);
            } else {
                // Return URL call - check for pp_id in URL parameters or POST data
                $pp_id = $params['pp_id'] ?? $data['pp_id'] ?? request()->get('pp_id') ?? null;
                
                if (empty($pp_id)) {
                    // If no pp_id, this might be a general return - check if we have invoice_id
                    $invoice_id = $params['invoice_id'] ?? $data['invoice_id'] ?? request()->get('invoice_id') ?? null;
                    if ($invoice_id) {
                        // PipraPay sometimes returns invoice_id instead of pp_id on return URL
                        $pp_id = $invoice_id;
                    }
                }
                
                if (empty($pp_id)) {
                    // If we still don't have pp_id, check if we have trade_no and can verify by order
                    $trade_no = $params['trade_no'] ?? $data['trade_no'] ?? request()->get('trade_no') ?? null;
                    if ($trade_no) {
                        \Log::info('PipraPay return: No pp_id but have trade_no, attempting order-based verification', [
                            'trade_no' => $trade_no
                        ]);
                        return $this->verifyByOrderId($trade_no);
                    }
                    
                    \Log::warning('PipraPay return: Missing pp_id or invoice_id', [
                        'params' => $params,
                        'data' => $data,
                        'query' => request()->query()
                    ]);
                    return false;
                }
                
                \Log::info('PipraPay return URL processing', ['pp_id' => $pp_id]);
            }

            // Verify the payment status using pp_id
            $verification = $this->post('/api/verify-payments', ['pp_id' => $pp_id]);
            
            if (isset($verification['status']) && $verification['status'] === true) {
                $paymentData = $verification['data'] ?? $verification;
                
                // Check if payment is successful
                if (isset($paymentData['payment_status']) && 
                    strtolower($paymentData['payment_status']) === 'success') {
                    
                    $trade_no = null;
                    
                    // Try to get trade_no from metadata
                    if (isset($paymentData['metadata']['order_id'])) {
                        $trade_no = $paymentData['metadata']['order_id'];
                    } elseif (isset($data['metadata']['order_id'])) {
                        $trade_no = $data['metadata']['order_id'];
                    }
                    
                    if (empty($trade_no)) {
                        \Log::error('PipraPay: Could not extract trade_no from payment data', [
                            'pp_id' => $pp_id,
                            'payment_data' => $paymentData,
                            'webhook_data' => $data
                        ]);
                        return false;
                    }
                    
                    \Log::info('PipraPay payment verified successfully', [
                        'pp_id' => $pp_id,
                        'trade_no' => $trade_no,
                        'is_webhook' => $isWebhook
                    ]);
                    
                    return [
                        'trade_no' => $trade_no,
                        'callback_no' => $pp_id
                    ];
                } else {
                    \Log::warning('PipraPay: Payment not successful', [
                        'pp_id' => $pp_id,
                        'payment_status' => $paymentData['payment_status'] ?? 'unknown',
                        'payment_data' => $paymentData
                    ]);
                }
            } else {
                \Log::error('PipraPay: Payment verification failed', [
                    'pp_id' => $pp_id,
                    'verification' => $verification
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('PipraPay notify error: ' . $e->getMessage(), [
                'params' => $params,
                'exception' => $e->getTraceAsString()
            ]);
        }

        return false;
    }

    /**
     * Verify payment by order ID when we don't have pp_id
     * This is used when users return from payment but we don't get pp_id in URL
     */
    private function verifyByOrderId($trade_no)
    {
        try {
            // We need to check if the order exists and if it's already paid
            $order = \App\Models\Order::where('trade_no', $trade_no)->first();
            if (!$order) {
                \Log::error('PipraPay verifyByOrderId: Order not found', ['trade_no' => $trade_no]);
                return false;
            }
            
            // If order is already paid, return success
            if ($order->status == 1) {
                \Log::info('PipraPay verifyByOrderId: Order already paid', ['trade_no' => $trade_no]);
                return [
                    'trade_no' => $trade_no,
                    'callback_no' => 'already_paid'
                ];
            }
            
            \Log::info('PipraPay verifyByOrderId: Order verification triggered, waiting for webhook or manual check', [
                'trade_no' => $trade_no,
                'order_status' => $order->status
            ]);
            
            // For now, we'll return false and let the periodic check handle it
            // In a production environment, you might want to query PipraPay for recent transactions
            return false;
            
        } catch (\Exception $e) {
            \Log::error('PipraPay verifyByOrderId error: ' . $e->getMessage(), [
                'trade_no' => $trade_no,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Check payment status for a specific order
     * This can be called by frontend when user returns from payment
     */
    public function checkPaymentStatus($tradeNo)
    {
        try {
            // Find the order to get metadata
            $order = \App\Models\Order::where('trade_no', $tradeNo)->first();
            if (!$order) {
                \Log::error('PipraPay checkPaymentStatus: Order not found', ['trade_no' => $tradeNo]);
                return false;
            }

            // We need to get the pp_id somehow. In a real scenario, we'd store this when creating the payment
            // For now, let's try to get recent payments and find our order
            // This is not ideal, but PipraPay doesn't provide a way to query by order_id directly
            
            \Log::info('PipraPay: Checking payment status for order', ['trade_no' => $tradeNo]);
            
            // Since we can't directly query by trade_no, we return true to let webhook handle it
            // The frontend should call this periodically until payment is confirmed
            return true;
            
        } catch (\Exception $e) {
            \Log::error('PipraPay checkPaymentStatus error: ' . $e->getMessage(), [
                'trade_no' => $tradeNo,
                'exception' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function post($endpoint, $data)
    {
        $url = rtrim($this->config['base_url'], '/') . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'content-type: application/json',
            'mh-piprapay-api-key: ' . $this->config['api_key']
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            \Log::error('PipraPay cURL error: ' . $error);
            return ['status' => false, 'error' => 'Connection error: ' . $error];
        }

        if ($httpCode !== 200) {
            \Log::error('PipraPay API error', [
                'http_code' => $httpCode,
                'response' => $response,
                'endpoint' => $endpoint,
                'data' => $data
            ]);
            return ['status' => false, 'error' => 'HTTP ' . $httpCode . ': ' . $response];
        }

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('PipraPay JSON decode error: ' . json_last_error_msg());
            return ['status' => false, 'error' => 'Invalid JSON response'];
        }

        return $result;
    }
}
