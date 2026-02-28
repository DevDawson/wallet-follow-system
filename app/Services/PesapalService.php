<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

        Log::info('PesaPal Service Initialized', [
            'base_url' => $this->baseUrl,
            'mode' => config('services.pesapal.mode'),
            'verify_ssl' => $this->verifySsl
        ]);
    }

    protected function getAccessToken()
    {
        return Cache::remember('pesapal_token', 300, function () {
            Log::info('Requesting PesaPal token from: ' . $this->baseUrl . '/api/Auth/RequestToken');

            $response = Http::withOptions([
                'verify' => $this->verifySsl,
            ])->post($this->baseUrl . '/api/Auth/RequestToken', [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            Log::info('Token Response Status: ' . $response->status());
            Log::info('Token Response Body: ' . $response->body());

            if ($response->failed()) {
                throw new \Exception('Pesapal Auth Failed: ' . $response->body());
            }

            return $response->json()['token'];
        });
    }

    protected function registerIpn($token)
    {
        Log::info('Registering IPN with URL: ' . config('services.pesapal.ipn_url'));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/URLSetup/RegisterIPN', [
            'url' => config('services.pesapal.ipn_url'),
            'ipn_notification_type' => 'GET',
        ]);

        Log::info('IPN Registration Status: ' . $response->status());
        Log::info('IPN Registration Body: ' . $response->body());

        if ($response->failed()) {
            throw new \Exception('IPN Registration Failed: ' . $response->body());
        }

        return $response->json()['ipn_id'];
    }

    public function initiatePayment($amount, $reference, $description, $firstName, $lastName, $email, $phone = '')
    {
        Log::info('Initiating Payment', [
            'amount' => $amount,
            'reference' => $reference,
            'email' => $email
        ]);

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

        Log::info('SubmitOrderRequest Payload:', $payload);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/Transactions/SubmitOrderRequest', $payload);

        Log::info('SubmitOrder Response Status: ' . $response->status());
        Log::info('SubmitOrder Response Body: ' . $response->body());

        if ($response->failed()) {
            throw new \Exception('Submit Order Failed: ' . $response->body());
        }

        return $response->json();
    }

    public function queryPaymentStatus($merchantReference, $trackingId)
    {
        Log::info('Querying Payment Status', [
            'merchant_reference' => $merchantReference,
            'tracking_id' => $trackingId
        ]);

        $token = $this->getAccessToken();

        $url = $this->baseUrl . '/api/Transactions/GetTransactionStatus';
        $payload = [
            'merchant_reference' => $merchantReference,
            'transaction_tracking_id' => $trackingId,  // 🔥 HILI NDIYO JINA SAHIHI
        ];

        Log::info('Status Check URL: ' . $url);
        Log::info('Status Check Payload:', $payload);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        Log::info('Status Check Response Status: ' . $response->status());
        Log::info('Status Check Response Body: ' . $response->body());

        if ($response->failed()) {
            throw new \Exception('Status Check Failed: ' . $response->body());
        }

        return $response->json();
    }
}