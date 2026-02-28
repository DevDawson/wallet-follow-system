<?php

namespace App\Services;

use League\OAuth1\Client\Credentials\ClientCredentials;
use League\OAuth1\Client\Credentials\TemporaryCredentials;
use League\OAuth1\Client\Credentials\TokenCredentials;
use League\OAuth1\Client\Server\Server;
use League\OAuth1\Client\Server\User;

class PesapalServer extends Server
{
    protected $callbackUrl;

    public function __construct(ClientCredentials $consumerCredentials, $callbackUrl)
    {
        parent::__construct($consumerCredentials);
        $this->callbackUrl = $callbackUrl;
    }

    protected function getBaseApiUrl()
    {
        $mode = config('services.pesapal.mode', 'sandbox');
        return $mode === 'live'
            ? 'https://pay.pesapal.com/v3'
            : 'https://cybqa.pesapal.com/pesapalv3';
    }

    public function urlSubmitOrder()
    {
        return $this->getBaseApiUrl() . '/api/Transactions/SubmitOrderRequest';
    }

    public function urlTransactionStatus()
    {
        return $this->getBaseApiUrl() . '/api/Transactions/GetTransactionStatus';
    }

    // Overrides for OAuth1 - not used directly
    public function urlTemporaryCredentials()
    {
        return $this->urlSubmitOrder();
    }

    public function urlAuthorization()
    {
        return $this->urlSubmitOrder();
    }

    public function urlTokenCredentials()
    {
        return $this->urlSubmitOrder();
    }

    public function urlUserDetails()
    {
        return $this->urlTransactionStatus();
    }

    public function userDetails($data, TokenCredentials $tokenCredentials)
    {
        return new User();
    }

    public function userUid($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    public function userEmail($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    public function userScreenName($data, TokenCredentials $tokenCredentials)
    {
        return null;
    }

    /**
     * Tayarisha vigezo vya ombi la malipo na usaini kwa OAuth 1.0
     */
    public function prepareOrderData($amount, $reference, $description, $firstName, $lastName, $email, $phone = '')
    {
        // Vigezo vya malipo kulingana na maelekezo ya PesaPal v3
        $orderData = [
            'id' => $reference,
            'currency' => 'TZS',
            'amount' => $amount,
            'description' => $description,
            'callback_url' => $this->callbackUrl,
            'notification_id' => '', // Unaweza kuweka IPN URL ikiwa una
            'branch' => 'Laravel App',
            // Maelezo ya mteja
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email_address' => $email,
            'phone_number' => $phone,
        ];

        return $orderData;
    }

    /**
     * Tengeneza signature ya OAuth kwa ajili ya header ya Authorization
     */
    public function generateOAuthHeader($method, $url, $params = [])
    {
        $credentials = $this->getClientCredentials();
        
        $oauthParams = [
            'oauth_consumer_key' => $credentials->getIdentifier(),
            'oauth_nonce' => $this->generateNonce(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0',
        ];

        // Kwa POST, vigezo vinaweza kuwa kwenye body, lakini OAuth inahitaji vigezo vyote kwa signature
        $allParams = array_merge($params, $oauthParams);
        ksort($allParams);

        $baseString = strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(http_build_query($allParams, '', '&', PHP_QUERY_RFC3986));

        $signingKey = rawurlencode($credentials->getSecret()) . '&';
        $signature = base64_encode(hash_hmac('sha1', $baseString, $signingKey, true));

        $oauthParams['oauth_signature'] = $signature;

        // Tengeneza header ya Authorization
        $headerParts = [];
        foreach ($oauthParams as $key => $value) {
            $headerParts[] = $key . '="' . rawurlencode($value) . '"';
        }

        return 'OAuth ' . implode(', ', $headerParts);
    }

    protected function generateNonce($length = 32)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nonce = '';
        for ($i = 0; $i < $length; $i++) {
            $nonce .= $characters[random_int(0, strlen($characters) - 1)];
        }
        return $nonce;
    }
}