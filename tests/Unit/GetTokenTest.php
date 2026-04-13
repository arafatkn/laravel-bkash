<?php

namespace Arafatkn\LaravelBkash\Tests\Unit;

use Arafatkn\LaravelBkash\Exceptions\TokenGrantException;
use Arafatkn\LaravelBkash\Tests\TestCase;
use Arafatkn\LaravelBkash\Tokenized\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GetTokenTest extends TestCase
{
    private string $sandboxUrl = 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_returns_cached_token_without_hitting_api(): void
    {
        Cache::put('bkash_tokenized_token', 'cached_id_token', 3590);

        Http::fake();

        $token = app(Payment::class)->getToken();

        $this->assertSame('cached_id_token', $token);
        Http::assertNothingSent();
    }

    public function test_calls_grant_token_when_no_token_cached(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'id_token'      => 'new_id_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in'    => 3600,
            ]),
        ]);

        $token = app(Payment::class)->getToken();

        $this->assertSame('new_id_token', $token);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/checkout/token/grant'));
    }

    public function test_calls_refresh_token_when_refresh_token_cached_but_id_token_expired(): void
    {
        Cache::put('bkash_tokenized_refresh_token', 'existing_refresh_token', 86400);

        Http::fake([
            "{$this->sandboxUrl}/checkout/token/refresh" => Http::response([
                'id_token'      => 'refreshed_id_token',
                'refresh_token' => 'rotated_refresh_token',
                'expires_in'    => 3600,
            ]),
        ]);

        $token = app(Payment::class)->getToken();

        $this->assertSame('refreshed_id_token', $token);
        Http::assertSent(fn ($req) => str_contains($req->url(), '/checkout/token/refresh'));
        Http::assertNotSent(fn ($req) => str_contains($req->url(), '/checkout/token/grant'));
    }

    public function test_caches_id_token_with_expires_in_minus_10_seconds(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'id_token'      => 'new_id_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in'    => 3600,
            ]),
        ]);

        app(Payment::class)->getToken();

        $this->assertTrue(Cache::has('bkash_tokenized_token'));
        $this->assertSame('new_id_token', Cache::get('bkash_tokenized_token'));
    }

    public function test_caches_refresh_token_for_seven_days(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'id_token'      => 'new_id_token',
                'refresh_token' => 'new_refresh_token',
                'expires_in'    => 3600,
            ]),
        ]);

        app(Payment::class)->getToken();

        $this->assertTrue(Cache::has('bkash_tokenized_refresh_token'));
        $this->assertSame('new_refresh_token', Cache::get('bkash_tokenized_refresh_token'));
    }

    public function test_falls_back_to_1_hour_lifetime_when_expires_in_missing(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'id_token'      => 'new_id_token',
                'refresh_token' => 'new_refresh_token',
            ]),
        ]);

        $token = app(Payment::class)->getToken();

        $this->assertSame('new_id_token', $token);
        $this->assertTrue(Cache::has('bkash_tokenized_token'));
    }

    public function test_throws_exception_when_id_token_is_missing(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'statusCode' => '2001',
                'statusMessage' => 'Invalid credentials',
            ]),
        ]);

        $this->expectException(TokenGrantException::class);

        app(Payment::class)->getToken();
    }

    public function test_throws_exception_when_id_token_is_empty_string(): void
    {
        Http::fake([
            "{$this->sandboxUrl}/checkout/token/grant" => Http::response([
                'id_token'      => '',
                'refresh_token' => 'some_refresh_token',
            ]),
        ]);

        $this->expectException(TokenGrantException::class);

        app(Payment::class)->getToken();
    }

    public function test_refresh_token_sent_in_request_body(): void
    {
        Cache::put('bkash_tokenized_refresh_token', 'my_refresh_token', 86400);

        Http::fake([
            "{$this->sandboxUrl}/checkout/token/refresh" => Http::response([
                'id_token'      => 'refreshed_id_token',
                'refresh_token' => 'rotated_refresh_token',
                'expires_in'    => 3600,
            ]),
        ]);

        app(Payment::class)->getToken();

        Http::assertSent(function ($req) {
            return str_contains($req->url(), '/checkout/token/refresh')
                && $req->data()['refresh_token'] === 'my_refresh_token';
        });
    }
}
