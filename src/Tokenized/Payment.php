<?php

namespace Arafatkn\LaravelBkash\Tokenized;

use Arafatkn\LaravelBkash\Exceptions\TokenGrantException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Payment
{
    protected string $baseUrl;
    protected string $appKey;
    protected string $appSecret;
    protected string $username;
    protected string $password;
    protected string $tokenCacheKey = 'bkash_tokenized_token';
    protected string $refreshTokenCacheKey = 'bkash_tokenized_refresh_token';

    public function __construct()
    {
        $sandbox = config('bkash.sandbox', true);

        $this->baseUrl   = $sandbox
            ? config('bkash.sandbox_base_url')
            : config('bkash.live_base_url');

        $this->appKey    = config('bkash.app_key');
        $this->appSecret = config('bkash.app_secret');
        $this->username  = config('bkash.username');
        $this->password  = config('bkash.password');
    }

    // -------------------------------------------------------------------------
    // Token
    // -------------------------------------------------------------------------

    public function grantToken(): array
    {
        $url     = "{$this->baseUrl}/checkout/token/grant";
        $headers = [
            'Content-Type' => 'application/json',
            'username'     => $this->username,
            'password'     => $this->password,
        ];
        $body = [
            'app_key'    => $this->appKey,
            'app_secret' => $this->appSecret,
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        $this->log('POST', $url, $body, $response);

        return $response->json();
    }

    public function getToken(): string
    {
        if (Cache::has($this->tokenCacheKey)) {
            return Cache::get($this->tokenCacheKey);
        }

        if (Cache::has($this->refreshTokenCacheKey)) {
            $data = $this->refreshToken(Cache::get($this->refreshTokenCacheKey));
        } else {
            $data = $this->grantToken();
        }

        if (empty($data['id_token'])) {
            throw new TokenGrantException();
        }

        // Prioritize API-reported expires_in; fall back to 1 hour. Subtract 10s to avoid last-second expiry errors.
        $idTokenLifetime = ((int) ($data['expires_in'] ?? 3600)) - 10;

        Cache::put($this->tokenCacheKey, $data['id_token'], $idTokenLifetime);
        Cache::put($this->refreshTokenCacheKey, $data['refresh_token'], 86400 * 7); // 7 days

        return $data['id_token'];
    }

    public function refreshToken(string $refreshToken): array
    {
        $url     = "{$this->baseUrl}/checkout/token/refresh";
        $headers = [
            'Content-Type' => 'application/json',
            'username'     => $this->username,
            'password'     => $this->password,
        ];
        $body = [
            'app_key'       => $this->appKey,
            'app_secret'    => $this->appSecret,
            'refresh_token' => $refreshToken,
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        $this->log('POST', $url, $body, $response);

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // Payment
    // -------------------------------------------------------------------------

    public function createPayment(
        string $amount,
        string $merchantInvoiceNumber,
        string $callbackURL,
        string $currency = null,
        string $intent = null
    ): array {
        $url  = "{$this->baseUrl}/checkout/create";
        $body = [
            'mode'                  => '0011',
            'payerReference'        => ' ',
            'callbackURL'           => $callbackURL,
            'amount'                => $amount,
            'currency'              => $currency ?? config('bkash.default_currency', 'BDT'),
            'intent'                => $intent ?? config('bkash.default_intent', 'sale'),
            'merchantInvoiceNumber' => $merchantInvoiceNumber,
        ];

        $response = Http::withHeaders($this->authHeaders())->post($url, $body);

        $this->log('POST', $url, $body, $response);

        return $response->json();
    }

    public function executePayment(string $paymentID): array
    {
        $url  = "{$this->baseUrl}/checkout/execute";
        $body = ['paymentID' => $paymentID];

        $response = Http::withHeaders($this->authHeaders())->post($url, $body);

        $this->log('POST', $url, $body, $response);

        return $response->json();
    }

    public function queryPayment(string $paymentID): array
    {
        $url    = "{$this->baseUrl}/checkout/payment/status";
        $params = ['paymentID' => $paymentID];

        $response = Http::withHeaders($this->authHeaders())->get($url, $params);

        $this->log('GET', $url, $params, $response);

        return $response->json();
    }

    public function searchTransaction(string $trxID): array
    {
        $url    = "{$this->baseUrl}/checkout/general/searchTransaction";
        $params = ['trxID' => $trxID];

        $response = Http::withHeaders($this->authHeaders())->get($url, $params);

        $this->log('GET', $url, $params, $response);

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // Refund
    // -------------------------------------------------------------------------

    public function refundTransaction(
        string $paymentID,
        string $trxID,
        string $amount,
        string $reason,
        string $sku = 'NA'
    ): array {
        $url  = "{$this->baseUrl}/checkout/payment/refund";
        $body = [
            'paymentID' => $paymentID,
            'trxID'     => $trxID,
            'amount'    => $amount,
            'reason'    => $reason,
            'sku'       => $sku,
        ];

        $response = Http::withHeaders($this->authHeaders())->post($url, $body);

        $this->log('POST', $url, $body, $response);

        return $response->json();
    }

    public function queryRefund(
        string $paymentID,
        string $trxID,
        string $amount,
        string $reason,
        string $sku = 'NA'
    ): array {
        $url    = "{$this->baseUrl}/checkout/payment/refund";
        $params = [
            'paymentID' => $paymentID,
            'trxID'     => $trxID,
            'amount'    => $amount,
            'reason'    => $reason,
            'sku'       => $sku,
        ];

        $response = Http::withHeaders($this->authHeaders())->get($url, $params);

        $this->log('GET', $url, $params, $response);

        return $response->json();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function authHeaders(): array
    {
        return [
            'Content-Type'  => 'application/json',
            'Authorization' => $this->getToken(),
            'X-APP-Key'     => $this->appKey,
        ];
    }

    protected function log(string $method, string $url, array $payload, Response $response): void
    {
        if (config('bkash.debug', false)) {
            Log::debug('bKash Request', [
                'method'  => $method,
                'url'     => $url,
                'payload' => $payload,
            ]);

            Log::debug('bKash Response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        }
    }
}
