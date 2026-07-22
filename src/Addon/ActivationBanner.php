<?php

namespace GiveMonnify\Addon;

use Give_Addon_Activation_Banner;
use GiveMonnify\Settings\MonnifySettings;

/**
 * Helper class responsible for showing add-on Activation Banner.
 */
class ActivationBanner
{

    /**
     * Show activation banner
     *
     * @return void
     */
    public function show()
    {
        // Check for Activation banner class.
        if ( ! class_exists('Give_Addon_Activation_Banner')) {
            include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
        }

        // Only runs on admin.
        $args = [
            'file' => GIVE_MONNIFY_FILE,
            'name' => GIVE_MONNIFY_NAME,
            'version' => GIVE_MONNIFY_VERSION,
            'settings_url' => MonnifySettings::getSettingsUrl(),
            'documentation_url' => 'https://monnify.com/',
            'support_url' => 'https://monnify.com/',
            'testing' => false, // Never leave true.
        ];

        new Give_Addon_Activation_Banner($args);
    }
}
