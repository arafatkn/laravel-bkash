<?php

namespace Arafatkn\LaravelBkash\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array  grantToken()
 * @method static string getToken()
 * @method static array  refreshToken(string $refreshToken)
 * @method static array  createPayment(array $data)
 * @method static array  executePayment(string $paymentID)
 * @method static array  queryPayment(string $paymentID)
 * @method static array  searchTransaction(string $trxID)
 * @method static array  refundPayment(string $paymentID, string $trxID, array $data = [])
 *
 * @see \Arafatkn\LaravelBkash\Tokenized\Payment
 */
class TokenizedPayment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bkash.tokenized.payment';
    }
}
