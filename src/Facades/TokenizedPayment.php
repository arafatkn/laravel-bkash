<?php

namespace Arafatkn\LaravelBkash\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array  grantToken()
 * @method static string getToken()
 * @method static array  refreshToken(string $refreshToken)
 * @method static array  createPayment(string $amount, string $merchantInvoiceNumber, string $callbackURL, string $currency = null, string $intent = null)
 * @method static array  executePayment(string $paymentID)
 * @method static array  queryPayment(string $paymentID)
 * @method static array  searchTransaction(string $trxID)
 * @method static array  refundTransaction(string $paymentID, string $trxID, string $amount, string $reason, string $sku = 'NA')
 * @method static array  queryRefund(string $paymentID, string $trxID, string $amount, string $reason, string $sku = 'NA')
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
