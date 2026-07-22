<?php

namespace GiveMonnify\Settings;

use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;

/**
 * Service provider for Monnify settings.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        // Register Monnify settings here if needed.
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Register Monnify settings.
        MonnifySettings::register();
    }
}
