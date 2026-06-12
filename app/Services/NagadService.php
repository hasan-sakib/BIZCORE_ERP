<?php

declare(strict_types=1);

namespace App\Services;

use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * NagadService
 *
 * Integrates with the Nagad MFS (Mobile Financial Service) payment gateway.
 *
 * Authentication & encryption:
 *   Every request to the Nagad API includes an RSA-encrypted sensitive payload.
 *   The merchant's private key signs the data; Nagad's public key encrypts it.
 *   This follows Nagad's official API specification.
 *
 * Flow:
 *   1. generateChallenge()  – Retrieve the one-time challenge from Nagad.
 *   2. createOrder()        – Submit the payment order with the challenge solution.
 *   3. Redirect user to Nagad payment page (URL returned by createOrder).
 *   4. verifyPayment()      – Verify and retrieve final status after callback.
 *   5. refund()             – Optional: initiate a refund.
 *
 * All transactions are logged to the `nagad_transactions` table.
 *
 * Configuration keys:
 *   merchant_id           – Nagad merchant ID
 *   merchant_phone        – Merchant's registered phone number
 *   merchant_private_key  – PEM-formatted RSA private key (PKCS#8)
 *   nagad_public_key      – PEM-formatted Nagad RSA public key
 *   base_url              – API base URL (sandbox or production)
 *   callback_url          – Default callback URL
 *
 * @see https://nagad.com.bd/developer
 */
final class NagadService
{
    private const HTTP_TIMEOUT  = 30;
    private const DATE_FORMAT   = 'YmdHis';
    private const KEY_ALGORITHM = OPENSSL_KEYTYPE_RSA;

    /**
     * @param array{
     *     merchant_id: string,
     *     merchant_phone: string,
     *     merchant_private_key: string,
     *     nagad_public_key: string,
     *     base_url: string,
     *     callback_url?: string
     * } $config
     */
    public function __construct(
        private readonly array $config,
        private readonly LoggerInterface $logger,
        private readonly \PDO $pdo,
    ) {}

    // =========================================================================
    // Public API
    // =========================================================================

    /**
     * Step 1 — Retrieve the one-time challenge from Nagad.
     *
     * The challenge contains a random nonce that must be encrypted and returned
     * in the createOrder call.
     *
     * @return array{
     *     devCode: string,
     *     challenge: string
     * }
     *
     * @throws RuntimeException
     */
    public function generateChallenge(string $orderId): array
    {
        $datetime  = date(self::DATE_FORMAT);
        $merchantId = $this->config['merchant_id'];

        $sensitiveData = json_encode([
            'merchantId' => $merchantId,
            'datetime'   => $datetime,
        ], JSON_THROW_ON_ERROR);

        $encryptedSensitive = $this->encryptWithNagadPublicKey($sensitiveData);
        $signature          = $this->signWithMerchantPrivateKey($sensitiveData);

        $url     = $this->buildUrl("/api/dfs/check-out/initialize/{$merchantId}/{$orderId}");
        $payload = json_encode([
            'accountNumber' => $this->config['merchant_phone'],
            'dateTime'      => $datetime,
            'sensitiveData' => $encryptedSensitive,
            'signature'     => $signature,
        ], JSON_THROW_ON_ERROR);

        $response = $this->post($url, $payload);

        $this->logTransaction([
            'type'     => 'generate_challenge',
            'order_id' => $orderId,
            'status'   => $response['status'] ?? 'unknown',
            'response_body' => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['status'] ?? '') !== 'Success') {
            throw new RuntimeException(
                'Nagad generateChallenge failed: ' . ($response['reason'] ?? 'Unknown error'),
            );
        }

        return [
            'devCode'   => $response['devCode']   ?? '',
            'challenge' => $response['challenge'] ?? '',
        ];
    }

    /**
     * Step 2 — Create a Nagad payment order.
     *
     * Accepts the challenge received in step 1. Returns the payment URL that the
     * user must be redirected to.
     *
     * @return array{
     *     status: string,
     *     paymentReferenceId: string,
     *     callBackUrl: string
     * }
     *
     * @throws RuntimeException
     */
    public function createOrder(float $amount, string $orderId, string $callbackUrl): array
    {
        $challengeData = $this->generateChallenge($orderId);
        $datetime      = date(self::DATE_FORMAT);
        $merchantId    = $this->config['merchant_id'];

        $sensitiveData = json_encode([
            'merchantId'   => $merchantId,
            'orderId'      => $orderId,
            'challenge'    => $challengeData['challenge'],
            'amount'       => number_format($amount, 2, '.', ''),
            'currencyCode' => '050',    // BDT ISO 4217 numeric code
            'callBackUrl'  => $callbackUrl,
        ], JSON_THROW_ON_ERROR);

        $encryptedSensitive = $this->encryptWithNagadPublicKey($sensitiveData);
        $signature          = $this->signWithMerchantPrivateKey($sensitiveData);

        $url     = $this->buildUrl("/api/dfs/check-out/complete/{$merchantId}/{$orderId}");
        $payload = json_encode([
            'sensitiveData' => $encryptedSensitive,
            'signature'     => $signature,
            'merchantCallbackURL' => $callbackUrl,
        ], JSON_THROW_ON_ERROR);

        $response = $this->post($url, $payload);

        $this->logTransaction([
            'type'               => 'create_order',
            'order_id'           => $orderId,
            'amount'             => $amount,
            'payment_ref_id'     => $response['paymentReferenceId'] ?? null,
            'status'             => $response['status'] ?? 'unknown',
            'response_body'      => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['status'] ?? '') !== 'Success') {
            throw new RuntimeException(
                'Nagad createOrder failed: ' . ($response['reason'] ?? 'Unknown error'),
            );
        }

        return $response;
    }

    /**
     * Step 4 — Verify the outcome of a payment after the Nagad callback.
     *
     * Decrypts the sensitive payload returned by Nagad, verifies signature, and
     * returns the final transaction status.
     *
     * @return array{
     *     merchantId: string,
     *     orderId: string,
     *     paymentRefId: string,
     *     amount: string,
     *     clientMobileNo: string,
     *     merchantMobileNo: string,
     *     orderDateTime: string,
     *     issuerPaymentDateTime: string,
     *     issuerPaymentRefNo: string,
     *     additionalMerchantInfo: string,
     *     status: string,
     *     statusCode: string
     * }
     *
     * @throws RuntimeException
     */
    public function verifyPayment(string $paymentRefId): array
    {
        $merchantId = $this->config['merchant_id'];
        $url        = $this->buildUrl("/api/dfs/verify/payment/{$paymentRefId}");

        $response = $this->get($url);

        $this->logTransaction([
            'type'           => 'verify_payment',
            'payment_ref_id' => $paymentRefId,
            'status'         => $response['status'] ?? 'unknown',
            'response_body'  => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['status'] ?? '') !== 'Success') {
            throw new RuntimeException(
                'Nagad verifyPayment failed: ' . ($response['reason'] ?? 'Unknown error'),
            );
        }

        // Decrypt the sensitive data portion of the response
        if (isset($response['sensitiveData'])) {
            $decrypted = $this->decryptWithMerchantPrivateKey($response['sensitiveData']);
            $inner     = json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($inner)) {
                return array_merge($response, $inner);
            }
        }

        return $response;
    }

    /**
     * Initiate a refund for a completed Nagad transaction.
     *
     * @return array{
     *     status: string,
     *     refundId: string,
     *     refundAmount: string,
     *     reason: string
     * }
     *
     * @throws RuntimeException
     */
    public function refund(string $paymentRefId, float $amount): array
    {
        $merchantId = $this->config['merchant_id'];
        $datetime   = date(self::DATE_FORMAT);

        $sensitiveData = json_encode([
            'merchantId'     => $merchantId,
            'paymentRefId'   => $paymentRefId,
            'refundAmount'   => number_format($amount, 2, '.', ''),
            'currencyCode'   => '050',
            'dateTime'       => $datetime,
        ], JSON_THROW_ON_ERROR);

        $encryptedSensitive = $this->encryptWithNagadPublicKey($sensitiveData);
        $signature          = $this->signWithMerchantPrivateKey($sensitiveData);

        $url     = $this->buildUrl("/api/dfs/check-out/refund/{$merchantId}");
        $payload = json_encode([
            'sensitiveData' => $encryptedSensitive,
            'signature'     => $signature,
        ], JSON_THROW_ON_ERROR);

        $response = $this->post($url, $payload);

        $this->logTransaction([
            'type'           => 'refund',
            'payment_ref_id' => $paymentRefId,
            'amount'         => $amount,
            'refund_id'      => $response['refundId'] ?? null,
            'status'         => $response['status']   ?? 'unknown',
            'response_body'  => json_encode($response, JSON_THROW_ON_ERROR),
        ]);

        if (($response['status'] ?? '') !== 'Success') {
            throw new RuntimeException(
                'Nagad refund failed: ' . ($response['reason'] ?? 'Unknown error'),
            );
        }

        return $response;
    }

    // =========================================================================
    // Cryptographic helpers
    // =========================================================================

    /**
     * Encrypt data with Nagad's RSA public key (PKCS#1 v1.5 padding).
     *
     * @throws RuntimeException  On encryption failure.
     */
    private function encryptWithNagadPublicKey(string $data): string
    {
        $pubKey = openssl_pkey_get_public($this->config['nagad_public_key']);

        if ($pubKey === false) {
            throw new RuntimeException('Nagad: failed to load Nagad public key.');
        }

        $encrypted = '';
        $result    = openssl_public_encrypt($data, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING);

        if (!$result) {
            throw new RuntimeException('Nagad: RSA public-key encryption failed: ' . openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Sign data with the merchant's RSA private key (SHA-256 digest).
     *
     * @throws RuntimeException  On signing failure.
     */
    private function signWithMerchantPrivateKey(string $data): string
    {
        $privKey = openssl_pkey_get_private($this->config['merchant_private_key']);

        if ($privKey === false) {
            throw new RuntimeException('Nagad: failed to load merchant private key.');
        }

        $signature = '';
        $result    = openssl_sign($data, $signature, $privKey, OPENSSL_ALGO_SHA256);

        if (!$result) {
            throw new RuntimeException('Nagad: RSA signing failed: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    /**
     * Decrypt data received from Nagad using the merchant's RSA private key.
     *
     * @throws RuntimeException  On decryption failure.
     */
    private function decryptWithMerchantPrivateKey(string $encryptedBase64): string
    {
        $privKey = openssl_pkey_get_private($this->config['merchant_private_key']);

        if ($privKey === false) {
            throw new RuntimeException('Nagad: failed to load merchant private key for decryption.');
        }

        $decoded   = base64_decode($encryptedBase64, true);
        $decrypted = '';
        $result    = openssl_private_decrypt($decoded, $decrypted, $privKey, OPENSSL_PKCS1_PADDING);

        if (!$result) {
            throw new RuntimeException('Nagad: RSA decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    // =========================================================================
    // HTTP helpers
    // =========================================================================

    /**
     * Build a full Nagad API endpoint URL.
     */
    private function buildUrl(string $path): string
    {
        return rtrim($this->config['base_url'], '/') . $path;
    }

    /**
     * Standard request headers for all Nagad API calls.
     *
     * @return list<string>
     */
    private function defaultHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'X-KM-Api-Version: v-0.2.0',
            'X-KM-IP-V4: ' . ($_SERVER['SERVER_ADDR'] ?? '127.0.0.1'),
            'X-KM-Client-Type: MOBILE_APP',
        ];
    }

    /**
     * Execute an HTTP POST request via cURL.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function post(string $url, string $payload): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->defaultHeaders(),
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        return $this->executeCurl($ch, $url);
    }

    /**
     * Execute an HTTP GET request via cURL.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function get(string $url): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->defaultHeaders(),
            CURLOPT_TIMEOUT        => self::HTTP_TIMEOUT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        return $this->executeCurl($ch, $url);
    }

    /**
     * Execute a cURL handle and return parsed JSON response.
     *
     * @param  \CurlHandle $ch
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    private function executeCurl(\CurlHandle $ch, string $url): array
    {
        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno !== 0) {
            $this->logger->error('Nagad HTTP request failed', [
                'url'   => $url,
                'errno' => $errno,
                'error' => $error,
            ]);
            throw new RuntimeException("Nagad API cURL error ({$errno}): {$error}");
        }

        if (!is_string($body)) {
            throw new RuntimeException("Nagad API returned empty response body for {$url}");
        }

        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            throw new RuntimeException("Nagad API returned non-array JSON for {$url}");
        }

        $this->logger->debug('Nagad API response', [
            'url'         => $url,
            'http_status' => $code,
            'status'      => $decoded['status'] ?? null,
        ]);

        return $decoded;
    }

    // =========================================================================
    // Audit logging
    // =========================================================================

    /**
     * Insert a row into `nagad_transactions` for audit/reconciliation.
     *
     * @param array<string, mixed> $data
     */
    private function logTransaction(array $data): void
    {
        try {
            $sql = <<<'SQL'
                INSERT INTO nagad_transactions
                    (type, order_id, payment_ref_id, refund_id, amount,
                     status, response_body, created_at)
                VALUES
                    (:type, :order_id, :payment_ref_id, :refund_id, :amount,
                     :status, :response_body, :created_at)
            SQL;

            $this->pdo->prepare($sql)->execute([
                'type'           => $data['type']           ?? null,
                'order_id'       => $data['order_id']       ?? null,
                'payment_ref_id' => $data['payment_ref_id'] ?? null,
                'refund_id'      => $data['refund_id']      ?? null,
                'amount'         => $data['amount']         ?? null,
                'status'         => $data['status']         ?? null,
                'response_body'  => $data['response_body']  ?? null,
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Logging must not break the payment flow.
            $this->logger->error('Nagad: failed to log transaction to DB', [
                'error' => $e->getMessage(),
                'data'  => $data,
            ]);
        }
    }
}
