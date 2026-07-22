<?php
namespace GiveMonnify;

use GiveMonnify\Addon\Activation;
use GiveMonnify\Addon\Environment;
use GiveMonnify\Addon\ServiceProvider as AddonServiceProvider;
use GiveMonnify\Settings\ServiceProvider as SettingsServiceProvider;
use GiveMonnify\Monnify\ServiceProvider as MonnifyServiceProvider;

/**
 * Plugin Name:         Give - Monnify Gateway
 * Plugin URI:          https://monnify.com/
 * Description:         Fundraise with Monnify and GiveWP
 * Version:             1.0.0
 * Requires at least:   6.6
 * Requires PHP:        7.4
 * Author:              Monnify
 * Author URI:          https://monnify.com/
 * Text Domain:         give-monnify
 * Domain Path:         /languages
 */
defined('ABSPATH') or exit;

// Add-on name
define('GIVE_MONNIFY_NAME', 'Give - Monnify Gateway');

// Versions
define('GIVE_MONNIFY_VERSION', '1.0.0');
define('GIVE_MONNIFY_MIN_GIVE_VERSION', '4.10.0');

// Add-on paths
define('GIVE_MONNIFY_FILE', __FILE__);
define('GIVE_MONNIFY_DIR', plugin_dir_path(GIVE_MONNIFY_FILE));
define('GIVE_MONNIFY_URL', plugin_dir_url(GIVE_MONNIFY_FILE));
define('GIVE_MONNIFY_BASENAME', plugin_basename(GIVE_MONNIFY_FILE));

// Monnify API base URLs
define('GIVE_MONNIFY_LIVE_BASE_URL', 'https://api.monnify.com');
define('GIVE_MONNIFY_SANDBOX_BASE_URL', 'https://sandbox.monnify.com');

require_once __DIR__ . '/autoload.php';

// Activate add-on hook.
register_activation_hook(GIVE_MONNIFY_FILE, [Activation::class, 'activateAddon']);

// Deactivate add-on hook.
register_deactivation_hook(GIVE_MONNIFY_FILE, [Activation::class, 'deactivateAddon']);

// Uninstall add-on hook.
register_uninstall_hook(GIVE_MONNIFY_FILE, [Activation::class, 'uninstallAddon']);

// Register the add-on service provider with the GiveWP core.
add_action(
    'before_give_init',
    function () {
        // Check Give min required version.
        if (Environment::giveMinRequiredVersionCheck()) {
            give()->registerServiceProvider(AddonServiceProvider::class);
            give()->registerServiceProvider(SettingsServiceProvider::class);
            give()->registerServiceProvider(MonnifyServiceProvider::class);
        }
    }
);

// Check to make sure GiveWP core is installed and compatible with this add-on.
add_action('admin_init', [Environment::class, 'checkEnvironment']);
