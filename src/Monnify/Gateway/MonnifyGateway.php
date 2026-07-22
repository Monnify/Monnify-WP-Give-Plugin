<?php

namespace GiveMonnify\Monnify\Gateway;

use Give\Donations\Models\Donation;
use Give\Donations\Models\DonationNote;
use Give\Donations\ValueObjects\DonationStatus;
use Give\Framework\Http\Response\Types\RedirectResponse;
use Give\Framework\PaymentGateways\Commands\GatewayCommand;
use Give\Framework\PaymentGateways\Commands\RedirectOffsite;
use Give\Framework\PaymentGateways\Commands\PaymentRefunded;
use Give\Framework\PaymentGateways\Contracts\WebhookNotificationsListener;
use Give\Framework\PaymentGateways\Exceptions\PaymentGatewayException;
use Give\Framework\PaymentGateways\Log\PaymentGatewayLog;
use Give\Framework\PaymentGateways\PaymentGateway;
use Give\Log\Log;
use GiveMonnify\Monnify\Client\MonnifyClient;
use GiveMonnify\Monnify\Gateway\Actions\ProcessWebhookNotifications;
use GiveMonnify\Monnify\Gateway\DataTransferObjects\InitializeTransactionResponse;

/**
 * @see https://developers.monnify.com/api
 */
class MonnifyGateway extends PaymentGateway implements WebhookNotificationsListener
{
    /**
     * @var array
     */
    public $secureRouteMethods = [
        'handleMonnifyReturn',
    ];

    /**
     * @var array
     */
    public $routeMethods = [
        'webhookNotificationsListener',
        'handleMonnifyRedirectBridge',
    ];

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'monnify';
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return self::id();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return __('Monnify', 'give-monnify');
    }

    /**
     * @inheritDoc
     */
    public function getPaymentMethodLabel(): string
    {
        return __('Monnify', 'give-monnify');
    }

    /**
     * @inheritDoc
     */
    public function getLegacyFormFieldMarkup(int $formId, array $args): string
    {
        return sprintf(
            '<div style="text-align: center;">
                <img src="%s" alt="Monnify" style="max-width: 200px;" />
                <br />
                <br />
                <p style="font-size: 0.9rem;">
                    <strong>%s</strong>
                </p>
                <p style="font-size: 0.8rem;">
                    <strong>%s</strong> %s
                </p>
            </div>',
            esc_url(GIVE_MONNIFY_URL . 'src/Monnify/Gateway/resources/images/logo.png'),
            esc_html__('Make your donation quickly and securely with Monnify', 'give-monnify'),
            esc_html__('How it works:', 'give-monnify'),
            esc_html__('You will be redirected to Monnify to securely complete your donation, then brought back to this site to view your receipt.', 'give-monnify')
        );
    }

    /**
     * @inheritDoc
     */
    public function createPayment(Donation $donation, $gatewayData): GatewayCommand
    {
        try {
            $response = $this->initializeMonnifyTransaction($donation, $gatewayData);

            if (empty($response->checkoutUrl)) {
                throw new PaymentGatewayException(
                    __('Unable to initialize Monnify transaction.', 'give-monnify')
                );
            }

            // Store the reference for later use
            give_update_payment_meta($donation->id, '_give_monnify_reference', $response->paymentReference);

            return new RedirectOffsite($response->checkoutUrl);
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                sprintf(
                    __('Monnify Error: %s', 'give-monnify'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function refundDonation(Donation $donation): GatewayCommand
    {
        $transactionReference = $donation->gatewayTransactionId;

        if (empty($transactionReference)) {
            throw new PaymentGatewayException(
                __('No Monnify transaction reference found for this payment.', 'give-monnify')
            );
        }

        try {
            $response = $this->getClient()->initiateRefund([
                'transactionReference' => $transactionReference,
                'refundReference' => 'refund-' . $donation->id . '-' . time(),
                'refundAmount' => (float) $donation->amount->formatToDecimal(),
                'refundReason' => sprintf(
                    __('Refund for donation #%d', 'give-monnify'),
                    $donation->id
                ),
            ]);

            $refundInProgressStatuses = [
                'PENDING',
                'IN_PROGRESS',
                'COMPLETED',
            ];

            if (false === $response || empty($response->refundStatus) || ! in_array($response->refundStatus, $refundInProgressStatuses, true)) {
                Log::error('Unable to refund Monnify transaction details.', [
                    'transactionReference' => $transactionReference,
                    'data' => $response,
                ]);

                throw new PaymentGatewayException(
                    __('Unable to refund Monnify transaction. Please check the Monnify dashboard.', 'give-monnify')
                );
            }

            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf(
                    __('Donation refunded in Monnify for transaction reference: %s. Monnify refund status: %s', 'give-monnify'),
                    $transactionReference,
                    $response->refundStatus
                ),
            ]);

            return new PaymentRefunded();
        } catch (PaymentGatewayException $exception) {
            DonationNote::create([
                'donationId' => $donation->id,
                'content' => sprintf(
                    __('Error! Donation %s was NOT refunded. Find more details on the error in the logs at Donations > Tools > Logs. To refund the donation, use the Monnify dashboard tools.', 'give-monnify'),
                    $donation->id
                ),
            ]);

            throw $exception;
        }
    }

    /**
     * @inheritDoc
     */
    public function formSettings(int $formId): array
    {
        return [
            'testMode' => give_is_test_mode(),
            'formId' => $formId,
        ];
    }

    /**
     * Enqueue the visual-form-builder (v3) gateway script. Hand-written plain
     * JS depending on WordPress core's bundled 'react'/'wp-i18n' script
     * handles, so no build step (webpack/npm) is required.
     *
     * @inheritDoc
     */
    public function enqueueScript(int $formId)
    {
        wp_enqueue_script(
            'give-monnify-gateway',
            GIVE_MONNIFY_URL . 'src/Monnify/Gateway/resources/monnifyGateway.js',
            ['react', 'wp-i18n'],
            GIVE_MONNIFY_VERSION,
            true
        );

        wp_localize_script('give-monnify-gateway', 'GiveMonnifyGatewayData', [
            'logoUrl' => GIVE_MONNIFY_URL . 'src/Monnify/Gateway/resources/images/logo.png',
        ]);
    }

    /**
     * Builds a Monnify API client for the current mode (test/live).
     */
    protected function getClient(): MonnifyClient
    {
        $mode = give_is_test_mode() ? 'test' : 'live';
        $baseUrl = 'live' === $mode ? GIVE_MONNIFY_LIVE_BASE_URL : GIVE_MONNIFY_SANDBOX_BASE_URL;

        $apiKey = give_get_option('monnify_' . $mode . '_api_key');
        $secretKey = give_get_option('monnify_' . $mode . '_secret_key');
        $contractCode = give_get_option('monnify_' . $mode . '_contract_code');

        return new MonnifyClient((string) $apiKey, (string) $secretKey, (string) $contractCode, $baseUrl, $mode);
    }

    /**
     * Initialize a Monnify hosted-checkout transaction
     */
    private function initializeMonnifyTransaction(Donation $donation, array $gatewayData): InitializeTransactionResponse
    {
        $client = $this->getClient();

        if ( ! $client->isReady()) {
            throw new PaymentGatewayException(__('Monnify API keys are not configured', 'give-monnify'));
        }

        $realRedirectUrl = $this->generateSecureGatewayRouteUrl(
            'handleMonnifyReturn',
            $donation->id,
            [
                'givewp-donation-id' => $donation->id,
                'givewp-success-url' => $gatewayData['successUrl'],
            ]
        );

        // GiveWP's secure route URL embeds the full success URL, receipt ID,
        // and route signature, making it far too long for Monnify's
        // redirectUrl field (verified: a ~520 char URL is rejected with a
        // generic 500, a short one works fine). Bridge through a short,
        // single-use token instead of sending the long URL directly.
        $redirectUrl = $this->createShortRedirectBridgeUrl($realRedirectUrl);

        $customerName = trim($donation->firstName . ' ' . $donation->lastName);

        $response = $client->initializeTransaction([
            'amount' => (float) $donation->amount->formatToDecimal(),
            'customerEmail' => $donation->email,
            'customerName' => '' !== $customerName ? $customerName : $donation->email,
            'paymentReference' => $donation->purchaseKey,
            'paymentDescription' => $donation->formTitle,
            'currencyCode' => $donation->amount->getCurrency()->getCode(),
            'redirectUrl' => $redirectUrl,
            'metaData' => apply_filters('givewp_monnify_transaction_initialization_metadata', [
                'plugin' => 'GiveWP',
                'form_title' => $donation->formTitle,
                'donation_id' => $donation->id,
            ]),
        ]);

        if (false === $response) {
            throw new PaymentGatewayException(__('Unable to reach Monnify.', 'give-monnify'));
        }

        return InitializeTransactionResponse::fromArray((array) $response);
    }

    /**
     * Stores the real (long) secure return URL behind a short, single-use
     * token, and returns a short URL pointing at handleMonnifyRedirectBridge
     * for Monnify's redirectUrl field.
     */
    private function createShortRedirectBridgeUrl(string $realRedirectUrl): string
    {
        $token = wp_generate_password(20, false, false);
        set_transient('give_monnify_redirect_' . $token, $realRedirectUrl, HOUR_IN_SECONDS);

        return $this->generateGatewayRouteUrl('handleMonnifyRedirectBridge', ['t' => $token]);
    }

    /**
     * Resolves the short redirect token back to the real secure GiveWP
     * return URL and forwards the browser there.
     *
     * Intentionally NOT single-use / deleted on access: redirect chains
     * commonly hit this URL more than once (e.g. a preliminary/prefetch
     * request before the final browser navigation), and the token itself
     * isn't the security boundary - GiveWP's own route signature on the
     * target URL is. The transient's natural expiry is enough cleanup.
     */
    public function handleMonnifyRedirectBridge(array $queryParams)
    {
        $token = isset($queryParams['t']) ? sanitize_text_field($queryParams['t']) : '';

        // Monnify appends its own "?paymentReference=..." to the redirectUrl
        // using a literal "?" instead of "&", even when the URL already has
        // a query string (e.g. "...&t=XYZ?paymentReference=..."). That
        // corrupts standard query-string parsing and leaves the remainder
        // stuck onto our token's value. Strip it defensively.
        if (false !== strpos($token, '?')) {
            $token = substr($token, 0, strpos($token, '?'));
        }

        $realRedirectUrl = '' !== $token ? get_transient('give_monnify_redirect_' . $token) : false;

        if (false === $realRedirectUrl) {
            throw new PaymentGatewayException(__('This Monnify redirect link has expired or is invalid.', 'give-monnify'));
        }

        return new RedirectResponse(esc_url_raw($realRedirectUrl));
    }

    /**
     * Handle the return from Monnify
     */
    public function handleMonnifyReturn(array $queryParams)
    {
        $successUrl = $queryParams['givewp-success-url'];
        $donationId = (int) $queryParams['givewp-donation-id'];
        $donation = Donation::find($donationId);

        if ( ! $donation) {
            throw new PaymentGatewayException(__('Donation not found.', 'give-monnify'));
        }

        $paymentReference = give_get_payment_meta($donation->id, '_give_monnify_reference', true);

        if (empty($paymentReference)) {
            throw new PaymentGatewayException(__('No Monnify reference found for this payment.', 'give-monnify'));
        }

        try {
            $response = $this->getClient()->verifyTransaction($paymentReference);

            if (false === $response) {
                throw new PaymentGatewayException(__('Unable to verify Monnify transaction.', 'give-monnify'));
            }

            $donation->gatewayTransactionId = (string) give_clean($response->transactionReference ?? '');
            $donation->status = $this->getDonationStatusFromMonnifyPaymentStatus($response->paymentStatus ?? '');
            $donation->save();

            return new RedirectResponse(esc_url_raw($successUrl));
        } catch (\Exception $e) {
            throw new PaymentGatewayException(
                sprintf(
                    __('Monnify Error: %s', 'give-monnify'),
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Links the transaction ID in the donation details to the Monnify transaction details page.
     *
     * @return string A link to the Monnify transaction details.
     */
    public function linkTransactionId(?string $gatewayTransactionId, ?int $donationId)
    {
        if (empty($gatewayTransactionId)) {
            return '';
        }

        $url = 'https://app.monnify.com/transactions';
        $transactionLink = '<a href="' . esc_url($url) . '" target="_blank">' . $gatewayTransactionId . '</a>';

        return apply_filters('give_monnify_link_payment_details_transaction_id', $transactionLink);
    }

    /**
     * Webhook notifications listener.
     *
     * @see https://monnify-docs.playground.monnify.com/docs/webhooks/event-types
     *
     * @return void
     */
    public function webhookNotificationsListener()
    {
        // only a post with a Monnify signature header gets our attention
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || ! array_key_exists('HTTP_MONNIFY_SIGNATURE', $_SERVER)) {
            wp_die('Unauthorized request');
        }

        // Retrieve the request's body
        $input = @file_get_contents('php://input');
        $secretKey = give_get_option('monnify_' . (give_is_test_mode() ? 'test' : 'live') . '_secret_key');

        // constant-time comparison to avoid a timing attack
        if ( ! hash_equals(hash_hmac('sha512', $input, (string) $secretKey), $_SERVER['HTTP_MONNIFY_SIGNATURE'])) {
            wp_die('Unauthorized request');
        }

        http_response_code(200);

        $request = json_decode($input);

        do_action('givewp_monnify_webhook_notification', $request, $this);

        if (isset($request->eventType)) {
            do_action("givewp_monnify_webhook_notification_$request->eventType", $request, $this);
        }

        Log::http('Monnify webhook received', ['request' => $request]);

        try {
            (new ProcessWebhookNotifications())($request, $this);
        } catch (\Exception $e) {
            PaymentGatewayLog::error('Monnify webhook error', ['error' => $e->getMessage()]);
        }

        exit();
    }

    /**
     * @see https://developers.monnify.com/docs/collections/one-time-payment
     */
    protected function getDonationStatusFromMonnifyPaymentStatus(string $status): DonationStatus
    {
        switch ($status) {
            case 'FAILED':
                return DonationStatus::FAILED();
            case 'EXPIRED':
                return DonationStatus::ABANDONED();
            case 'PAID':
                return DonationStatus::COMPLETE();
            default:
                // PENDING, PARTIALLY_PAID, OVERPAID are left for manual admin
                // review rather than auto-completing the donation.
                return DonationStatus::PROCESSING();
        }
    }
}
