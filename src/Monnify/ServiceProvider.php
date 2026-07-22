<?php

namespace GiveMonnify\Monnify;

use Give\Framework\PaymentGateways\PaymentGatewayRegister;
use GiveMonnify\Monnify\Gateway\MonnifyGateway;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;
use Give\Helpers\Hooks;

/**
 * Main service provider for the Monnify gateway.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        // No container bindings needed for Monnify.
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Register the Monnify gateway
        add_action('givewp_register_payment_gateway', function (PaymentGatewayRegister $paymentGatewayRegister) {
            $paymentGatewayRegister->registerGateway(MonnifyGateway::class);
        });

        // Link transaction ID in donation details to Monnify dashboard
        $this->linkDonationDetailsToMonnifyDashboard();
    }

    /**
     * Link donation details to Monnify dashboard
     */
    private function linkDonationDetailsToMonnifyDashboard()
    {
        Hooks::addFilter(
            'give_payment_details_transaction_id-' . MonnifyGateway::id(),
            MonnifyGateway::class,
            'linkTransactionId',
            10,
            2
        );
    }
}
