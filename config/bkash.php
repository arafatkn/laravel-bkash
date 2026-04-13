<?php

return [
	/*
	|--------------------------------------------------------------------------
	| bKash Credentials
	|--------------------------------------------------------------------------
	*/
	'sandbox' => env('BKASH_SANDBOX', true),
	'debug' => env('BKASH_DEBUG', env('APP_DEBUG', false)),
	'app_key' => env('BKASH_APP_KEY', ''),
	'app_secret' => env('BKASH_APP_SECRET', ''),
	'username' => env('BKASH_USERNAME', ''),
	'password' => env('BKASH_PASSWORD', ''),

	'sandbox_base_url' => 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized',
	'live_base_url' => 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized',

	'cache' => [
		'refresh_token_lifetime' => 60 * 60 * 24 * 7, // 7 days
	],

	'default_currency' => 'BDT',
	'default_intent' => 'sale',
];
