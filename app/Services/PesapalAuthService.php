<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PesapalAuthService
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
        $this->verifySsl = (config('services.pesapal.mode') === 'live');
    }

    public function getAccessToken()
    {
        return Cache::remember('pesapal_access_token', 3500, function () {
            $url = $this->baseUrl . '/api/Auth/RequestToken';

            $response = Http::withOptions([
                'verify' => $this->verifySsl,
            ])->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'consumer_key' => $this->consumerKey,
                'consumer_secret' => $this->consumerSecret,
            ]);

            if ($response->failed()) {
                \Log::error('PesaPal token error: ' . $response->body());
                throw new \Exception('Failed to get PesaPal access token: ' . $response->body());
            }

            $data = $response->json();
            
            if (!isset($data['token'])) {
                throw new \Exception('PesaPal did not return token: ' . json_encode($data));
            }

            return $data['token'];
        });
    }
}