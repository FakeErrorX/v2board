<?php

namespace App\Payments;

use App\Services\PipraPay as PipraPayService;

class PipraPay 
{
    private array $config;

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
        $pipra = new PipraPayService(
            $this->config['api_key'],
            $this->config['base_url'],
            $this->config['currency'] ?? 'BDT'
        );

        $amount = $order['total_amount'] / 100; // Convert from cents to main currency unit

        $paymentData = [
            'full_name' => $order['user_name'] ?? 'User',
            'email_mobile' => $order['user_email'] ?? 'user@example.com',
            'amount' => $amount,
            'metadata' => [
                'order_id' => $order['trade_no'],
                'user_id' => $order['user_id'] ?? null,
                'plan_id' => $order['plan_id'] ?? null
            ],
            'redirect_url' => $order['return_url'],
            'cancel_url' => $order['return_url'] . '?status=cancel',
            'webhook_url' => $order['notify_url']
        ];

        $response = $pipra->createCharge($paymentData);

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
        }

        abort(500, $errorMessage);
    }

    public function notify($params)
    {
        $pipra = new PipraPayService(
            $this->config['api_key'],
            $this->config['base_url'],
            $this->config['currency'] ?? 'BDT'
        );

        // Handle webhook/IPN notification
        $webhookData = $pipra->handleWebhook($this->config['api_key']);

        if (!$webhookData['status']) {
            return false;
        }

        $data = $webhookData['data'];

        // Verify the payment status
        if (isset($data['pp_id'])) {
            $verification = $pipra->verifyPayment($data['pp_id']);
            
            if (isset($verification['status']) && $verification['status'] === true) {
                $paymentData = $verification['data'] ?? $verification;
                
                // Check if payment is successful
                if (isset($paymentData['payment_status']) && 
                    strtolower($paymentData['payment_status']) === 'success') {
                    
                    return [
                        'trade_no' => $paymentData['metadata']['order_id'] ?? $data['metadata']['order_id'] ?? null,
                        'callback_no' => $data['pp_id'],
                        'verify_data' => $paymentData
                    ];
                }
            }
        }

        return false;
    }

    /**
     * Get supported currencies for this payment method
     */
    public function getSupportedCurrencies()
    {
        return ['BDT', 'USD', 'EUR', 'GBP']; // Add more as supported by PipraPay
    }

    /**
     * Validate configuration
     */
    public function validateConfig()
    {
        $required = ['api_key', 'base_url'];
        
        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                throw new \Exception("PipraPay configuration missing required field: {$field}");
            }
        }

        return true;
    }
}
