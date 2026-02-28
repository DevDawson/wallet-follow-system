<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PesapalService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    protected $verifySsl;

    public function __construct()
    {
        $this->consumerKey = config('services.pesapal.consumer_key');
        $this->consumerSecret = config('services.pesapal.consumer_secret');

        $this->baseUrl = config('services.pesapal.mode') === 'live'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';

        $this->verifySsl = config('services.pesapal.mode') === 'live';
    }

    /**
     * Get OAuth Token
     */
    protected function getAccessToken()
    {
        $response = Http::withOptions([
            'verify' => $this->verifySsl,
        ])->post($this->baseUrl . '/api/Auth/RequestToken', [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ]);

        if ($response->failed()) {
            throw new \Exception('PesaPal Auth Failed: ' . $response->body());
        }

        return $response->json()['token'];
    }

    /**
     * Register IPN URL
     */
    protected function registerIpn($token)
    {
        $response = Http::withOptions([
            'verify' => $this->verifySsl,
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/URLSetup/RegisterIPN', [
            'url' => config('services.pesapal.ipn_url'),
            'ipn_notification_type' => 'GET',
        ]);

        if ($response->failed()) {
            throw new \Exception('IPN Registration Failed: ' . $response->body());
        }

        return $response->json()['ipn_id'];
    }

    /**
     * Initiate Payment Properly (v3)
     */
    public function initiatePayment($amount, $reference, $description, $firstName, $lastName, $email, $phone = '')
    {
        $token = $this->getAccessToken();
        $ipnId = $this->registerIpn($token);

        $payload = [
            "id" => $reference,
            "currency" => "TZS",
            "amount" => $amount,
            "description" => $description,
            "callback_url" => config('services.pesapal.callback_url'),
            "notification_id" => $ipnId,
            "billing_address" => [
                "email_address" => $email,
                "phone_number" => $phone ?: "0700000000",
                "country_code" => "TZ",
                "first_name" => $firstName ?: "Customer",
                "last_name" => $lastName ?: "User",
            ]
        ];

        $response = Http::withOptions([
            'verify' => $this->verifySsl,
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $payload);

        if ($response->failed()) {
            throw new \Exception('Submit Order Failed: ' . $response->body());
        }

        return $response->json(); 
        // returns redirect_url + order_tracking_id
    }

    /**
     * Query Payment Status
     */
    public function queryPaymentStatus($merchantReference, $trackingId)
    {
        $token = $this->getAccessToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/Transactions/GetTransactionStatus', [
            'merchant_reference' => $merchantReference,
            'order_tracking_id' => $trackingId,
        ]);

        if ($response->failed()) {
            throw new \Exception('Status Check Failed: ' . $response->body());
        }

        return $response->json();
    }
}