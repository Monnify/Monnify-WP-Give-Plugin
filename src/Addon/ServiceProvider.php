<?php

namespace GiveMonnify\Addon;

use Give\Helpers\Hooks;
use GiveMonnify\Addon\Activation;
use GiveMonnify\Addon\ActivationBanner;
use GiveMonnify\Addon\Language;
use Give\ServiceProviders\ServiceProvider as ServiceProviderInterface;

/**
 * Service provider responsible for add-on initialization.
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register()
    {
        give()->singleton(Activation::class);
    }

    /**
     * @inheritDoc
     */
    public function boot()
    {
        // Load add-on translations.
        Hooks::addAction('init', Language::class, 'load');
        // Load add-on links.
        Hooks::addFilter('plugin_action_links_' . GIVE_MONNIFY_BASENAME, Links::class);

        Hooks::addAction('admin_init', ActivationBanner::class, 'show', 20);
    }
}
