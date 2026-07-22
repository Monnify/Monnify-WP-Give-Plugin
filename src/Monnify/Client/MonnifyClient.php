<?php

namespace GiveMonnify\Monnify\Client;

/**
 * A thin client for the Monnify REST API: OAuth2 token exchange (cached via
 * transients), transaction initialization, transaction verification, and
 * refunds.
 *
 * @see https://developers.monnify.com/api
 */
class MonnifyClient
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $secretKey;

    /**
     * @var string
     */
    protected $contractCode;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string 'test' or 'live', used only to namespace the cached access token.
     */
    protected $mode;

    public function __construct(string $apiKey, string $secretKey, string $contractCode, string $baseUrl, string $mode)
    {
        $this->apiKey = $apiKey;
        $this->secretKey = $secretKey;
        $this->contractCode = $contractCode;
        $this->baseUrl = $baseUrl;
        $this->mode = $mode;
    }

    /**
     * Determines if the credentials needed to talk to Monnify have been configured.
     */
    public function isReady(): bool
    {
        return '' !== $this->apiKey && '' !== $this->secretKey && '' !== $this->contractCode;
    }

    /**
     * Initializes a hosted-checkout transaction.
     *
     * @param array $params { amount, customerEmail, customerName, paymentReference, paymentDescription, currencyCode, redirectUrl, metaData }
     * @return object|false responseBody with checkoutUrl/transactionReference/paymentReference, or false on failure.
     */
    public function initializeTransaction(array $params)
    {
        $body = array_merge(
            [
                'contractCode' => $this->contractCode,
            ],
            $params
        );

        $response = $this->request('POST', '/api/v1/merchant/transactions/init-transaction', $body);

        return $this->responseBody($response);
    }

    /**
     * Queries Monnify for the status of a transaction, keyed by OUR OWN
     * previously generated payment reference (never a client-supplied value).
     *
     * @return object|false responseBody with paymentStatus/amountPaid/paidOn/transactionReference, or false on failure.
     */
    public function verifyTransaction(string $paymentReference)
    {
        $response = $this->request(
            'GET',
            '/api/v2/merchant/transactions/query?paymentReference=' . rawurlencode($paymentReference)
        );

        return $this->responseBody($response);
    }

    /**
     * Initiates a refund for a previously completed transaction.
     *
     * @param array $params { transactionReference, refundReference, refundAmount, refundReason, customerNote }
     * @return object|false responseBody with refundReference/refundStatus, or false on failure.
     */
    public function initiateRefund(array $params)
    {
        $response = $this->request('POST', '/api/v1/refunds/initiate-refund', $params);

        return $this->responseBody($response);
    }

    /**
     * Unwraps a successful Monnify envelope {requestSuccessful, responseBody},
     * or returns false if the request failed or Monnify reported an error.
     *
     * @param object|false $response
     * @return object|false
     */
    protected function responseBody($response)
    {
        if (false === $response || empty($response->requestSuccessful) || empty($response->responseBody)) {
            return false;
        }

        return $response->responseBody;
    }

    /**
     * Gets a valid access token, requesting/caching a new one if needed.
     *
     * @return string|false
     */
    protected function getAccessToken(bool $forceRefresh = false)
    {
        if ( ! $this->isReady()) {
            return false;
        }

        $transientKey = 'give_monnify_token_' . $this->mode;

        if ( ! $forceRefresh) {
            $cached = get_transient($transientKey);
            if (false !== $cached) {
                return $cached;
            }
        }

        $auth = base64_encode($this->apiKey . ':' . $this->secretKey); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

        $response = wp_remote_post(
            $this->baseUrl . '/api/v1/auth/login',
            [
                'headers' => [
                    'Authorization' => 'Basic ' . $auth,
                    'Content-Type' => 'application/json',
                ],
                'timeout' => 60,
            ]
        );

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        if (empty($body->requestSuccessful) || empty($body->responseBody->accessToken)) {
            return false;
        }

        $expiresIn = isset($body->responseBody->expiresIn) ? (int) $body->responseBody->expiresIn : 3600;
        set_transient($transientKey, $body->responseBody->accessToken, max(60, $expiresIn - 60));

        return $body->responseBody->accessToken;
    }

    /**
     * Sends an authenticated request to the Monnify API, retrying once on a
     * 401 with a freshly refreshed access token.
     *
     * @param string     $method   GET|POST|PUT.
     * @param string     $path     Path beginning with /api/..., may include a query string.
     * @param array|null $body     Request body for POST/PUT requests.
     * @param bool       $retrying Internal flag used for the single retry.
     * @return object|false Decoded JSON response, or false on failure.
     */
    protected function request(string $method, string $path, ?array $body = null, bool $retrying = false)
    {
        if ( ! $this->isReady()) {
            return false;
        }

        $token = $this->getAccessToken($retrying);
        if (false === $token) {
            return false;
        }

        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 60,
        ];

        if (null !== $body) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($this->baseUrl . $path, $args);

        if (is_wp_error($response)) {
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);

        if (401 === $code && ! $retrying) {
            return $this->request($method, $path, $body, true);
        }

        if ($code < 200 || $code >= 300) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }
}
