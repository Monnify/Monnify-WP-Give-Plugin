<?php

namespace GiveMonnify\Addon;

/**
 * Helper class responsible for checking the add-on environment.
 */
class Environment
{

    /**
     * Check environment.
     *
     * @return void
     */
    public static function checkEnvironment()
    {
        // Check is GiveWP active
        if ( ! static::isGiveActive()) {
            add_action('admin_notices', [Notices::class, 'giveInactive']);

            return;
        }
        // Check min required version
        if ( ! static::giveMinRequiredVersionCheck()) {
            add_action('admin_notices', [Notices::class, 'giveVersionError']);
        }
    }

    /**
     * Check min required version of GiveWP.
     *
     * @return bool
     */
    public static function giveMinRequiredVersionCheck()
    {
        return defined('GIVE_VERSION') && version_compare(GIVE_VERSION, GIVE_MONNIFY_MIN_GIVE_VERSION, '>=');
    }

    /**
     * Check if GiveWP is active.
     *
     * @return bool
     */
    public static function isGiveActive()
    {
        return defined('GIVE_VERSION');
    }
}
