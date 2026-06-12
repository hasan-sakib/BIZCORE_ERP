<?php

declare(strict_types=1);

namespace App\Services;

use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * BkashService
 *
 * Integrates with the bKash Tokenized Checkout API v1.2.0-beta.
 *
 * Authentication:
 *   POST /checkout/token/grant  →  returns id_token (Bearer) and refresh_token.
 *   Tokens are stored in Redis with a TTL 60 seconds shorter than expiry to
 *   prevent edge-case rejections on stale tokens.
 *
 * All transactions are logged to the `bkash_transactions` table.
 *
 * Configuration (via constructor $config array):
 *   app_key       – bKash app key
 *   app_secret    – bKash app secret
 *   username      – bKash merchant username
 *   password      – bKash merchant password
 *   base_url      – API base URL (sandbox or production)
 *   token_ttl_buffer – seconds to subtract from token TTL before caching (default 60)
 *
 * @see https://developer.bka.sh/docs/tokenized-checkout-process-overview
 */
final class BkashService
{
    private const API_VERSION    = 'v1.2.0-beta';
    private const TOKEN_REDIS_KEY = 'bizcore:bkash:token';
    private const HTTP_TIMEOUT   = 30;

    /**
     * @param array{
     *     app_key: string,
     *     app_secret: string,
     *     username: string,
     *     password: string,
     *     base_url: string,
     *     token_ttl_buffer?: int
     * } $config
     */
    public function __construct(
        private readonly array $config,
        private readonly RedisClient $redis,
        private readonly LoggerInterface $logger,
        private readonly \PDO $pdo,
    ) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Obtain a valid OAuth2 Bearer token, using the cached value when possible.
     *
     * @throws RuntimeException  When token grant fails.
     */
    public function getToken(): string
    {
        $cached = $this->redis->get(self::TOKEN_REDIS_KEY);
        if ($cached !== null) {
            return $cached;
        }

        $url = $this->buildUrl('/checkout/token/grant');

        $payload = json_encode([
            'app_key'    => $this->config['app_key'],
            'app_secret' => $this->config['app_secret'],
        ], JSON_THROW_ON_ERROR);

        $headers = [
            'Content-Type: application/json',
            'username: ' . $this->config['username'],
            'password: ' . $this->config['password'],
        ];

        $response = $this->post($url, $payload, $headers);

        if (($response['statusCode'] ?? '') !== '0000') {
            $this->logger->error('bKash token grant failed', $response);
            throw new RuntimeException(
                'bKash token grant failed: ' . ($response['statusMessage'] ?? 'Unknown error'),
            );
        }

        $token  = $response['id_token']        ?? throw new RuntimeException('bKash: id_token missing in grant response.');
        $expiry = (int) ($response['expires_in'] ?? 3600);
        $buffer = (int) ($this->config['token_ttl_buffer'] ?? 60);
        $ttl    = max(1, $expiry - $buffer);

        $this->redis->setex(self::TOKEN_REDIS_KEY, $ttl, $token);

        return $token;
    }

    /**
     * Create a payment intent and return the bKash redirect URLs.
     *
     * @return array{
     *     paymentID: string,
     *     bkashURL: string,
     *     callbackURL: string,
     *     successCallbackURL: string,
     *     failureCallbackURL: string,
     *     cancelledCallbackURL: string,
     *     amount: string,
     *     intent: string,
     *     currency: string,
     *     paymentCreateTime: string,
     *     transactionStatus: string,
     *     merchantInvoiceNumber: string
     * }
     *
     * @throws RuntimeException
     */
    public function createPayment(float $amount, string $merchantInvoiceNumber, string $callbackUrl): array
    {
        $token = $this->getToken();
        $url   = $this->buildUrl('/checkout/create');

        $payload = json_encode([
            'mode'                  => '0011',       // Tokenized checkout
            'payerReference'        => $merchantInvoiceNumber,
            'callbackURL'           => $callbackUrl,
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $merchantInvoiceNumber,
        ], JSON_THROW_ON_ERROR);

        $response = $this->post($url, $payload, $this->authHeaders($token));

        $this->logTransaction([
            'type'                   => 'create_payment',
            'merchant_invoice_number'=> $merchantInvoiceNumber,
            'amount'                 => $amount,
            'payment_id'             => $response['paymentID'] ?? null,
            'status'                 => $response['statusCode'] ?? 'unknown',
            'response_body'          => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (!isset($response['paymentID'])) {
            throw new RuntimeException(
                'bKash createPayment failed: ' . ($response['statusMessage'] ?? 'No paymentID in response'),
            );
        }

        return $response;
    }

    /**
     * Execute (capture) a previously created payment.
     *
     * @return array{
     *     paymentID: string,
     *     trxID: string,
     *     transactionStatus: string,
     *     amount: string,
     *     currency: string,
     *     intent: string,
     *     paymentExecuteTime: string,
     *     merchantInvoiceNumber: string,
     *     statusCode: string,
     *     statusMessage: string
     * }
     *
     * @throws RuntimeException
     */
    public function executePayment(string $paymentId): array
    {
        $token = $this->getToken();
        $url   = $this->buildUrl('/checkout/execute');

        $payload  = json_encode(['paymentID' => $paymentId], JSON_THROW_ON_ERROR);
        $response = $this->post($url, $payload, $this->authHeaders($token));

        $this->logTransaction([
            'type'       => 'execute_payment',
            'payment_id' => $paymentId,
            'trx_id'     => $response['trxID'] ?? null,
            'status'     => $response['statusCode'] ?? 'unknown',
            'response_body' => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['statusCode'] ?? '') !== '0000') {
            throw new RuntimeException(
                'bKash executePayment failed: ' . ($response['statusMessage'] ?? 'Unknown error'),
            );
        }

        return $response;
    }

    /**
     * Query the current status of a payment by paymentID.
     *
     * @return array{
     *     paymentID: string,
     *     trxID: string,
     *     transactionStatus: string,
     *     amount: string,
     *     currency: string,
     *     statusCode: string,
     *     statusMessage: string
     * }
     *
     * @throws RuntimeException
     */
    public function queryPayment(string $paymentId): array
    {
        $token = $this->getToken();
        $url   = $this->buildUrl('/checkout/payment/status');

        $payload  = json_encode(['paymentID' => $paymentId], JSON_THROW_ON_ERROR);
        $response = $this->post($url, $payload, $this->authHeaders($token));

        $this->logTransaction([
            'type'       => 'query_payment',
            'payment_id' => $paymentId,
            'status'     => $response['statusCode'] ?? 'unknown',
            'response_body' => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        return $response;
    }

    /**
     * Refund a completed bKash transaction (partial or full).
     *
     * @return array{
     *     originalTrxID: string,
     *     refundTrxID: string,
     *     transactionStatus: string,
     *     amount: string,
     *     currency: string,
     *     statusCode: string,
     *     statusMessage: string
     * }
     *
     * @throws RuntimeException
     */
    public function refundPayment(string $paymentId, float $amount, string $reason): array
    {
        $token = $this->getToken();

        // Retrieve the original trxID from the query endpoint before refund.
        $queryResponse = $this->queryPayment($paymentId);
        $trxId         = $queryResponse['trxID'] ?? throw new RuntimeException('bKash: cannot refund, original trxID not found.');

        $url = $this->buildUrl('/checkout/payment/refund');

        $payload = json_encode([
            'paymentID' => $paymentId,
            'trxID'     => $trxId,
            'amount'    => number_format($amount, 2, '.', ''),
            'currency'  => 'BDT',
            'reason'    => mb_substr($reason, 0, 255),
        ], JSON_THROW_ON_ERROR);

        $response = $this->post($url, $payload, $this->authHeaders($token));

        $this->logTransaction([
            'type'          => 'refund_payment',
            'payment_id'    => $paymentId,
            'trx_id'        => $trxId,
            'refund_trx_id' => $response['refundTrxID'] ?? null,
            'amount'        => $amount,
            'reason'        => $reason,
            'status'        => $response['statusCode'] ?? 'unknown',
            'response_body' => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['statusCode'] ?? '') !== '0000') {
            throw new RuntimeException(
                'bKash refundPayment failed: ' . ($response['statusMessage'] ?? 'Unknown error'),
            );
        }

        return $response;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Construct a full API endpoint URL.
     */
    private function buildUrl(string $path): string
    {
        $base = rtrim($this->config['base_url'], '/');
        return "{$base}/" . self::API_VERSION . $path;
    }

    /**
     * Standard authenticated request headers.
     *
     * @return list<string>
     */
    private function authHeaders(string $token): array
    {
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
            'X-APP-Key: ' . $this->config['app_key'],
        ];
    }

    /**
     * Execute an HTTP POST request via cURL.
     *
     * @param  list<string>  $headers
     * @return array<string, mixed>
     *
     * @throws RuntimeException  On cURL or JSON error.
     */
    private function post(string $url, string $payload, array $headers): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno !== 0) {
            $this->logger->error('bKash HTTP request failed', [
                'url'   => $url,
                'errno' => $errno,
                'error' => $error,
            ]);
            throw new RuntimeException("bKash API cURL error ({$errno}): {$error}");
        }

        if (!is_string($body)) {
            throw new RuntimeException("bKash API returned empty body for {$url}");
        }

        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException("bKash API returned non-array JSON for {$url}");
        }

        $this->logger->debug('bKash API response', [
            'url'         => $url,
            'http_status' => $code,
            'status_code' => $decoded['statusCode'] ?? null,
        ]);

        return $decoded;
    }

    /**
     * Insert a row into `bkash_transactions` for audit/reconciliation.
     *
     * @param array<string, mixed> $data
     */
    private function logTransaction(array $data): void
    {
        try {
            $sql = <<<'SQL'
                INSERT INTO bkash_transactions
                    (type, merchant_invoice_number, payment_id, trx_id, refund_trx_id,
                     amount, reason, status, response_body, created_at)
                VALUES
                    (:type, :merchant_invoice_number, :payment_id, :trx_id, :refund_trx_id,
                     :amount, :reason, :status, :response_body, :created_at)
            SQL;

            $this->pdo->prepare($sql)->execute([
                'type'                    => $data['type']                    ?? null,
                'merchant_invoice_number' => $data['merchant_invoice_number'] ?? null,
                'payment_id'              => $data['payment_id']              ?? null,
                'trx_id'                  => $data['trx_id']                  ?? null,
                'refund_trx_id'           => $data['refund_trx_id']           ?? null,
                'amount'                  => $data['amount']                  ?? null,
                'reason'                  => $data['reason']                  ?? null,
                'status'                  => $data['status']                  ?? null,
                'response_body'           => $data['response_body']           ?? null,
                'created_at'              => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Log failure but do not throw — transaction logging must not break the payment flow.
            $this->logger->error('bKash: failed to log transaction to DB', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }
    }
}
