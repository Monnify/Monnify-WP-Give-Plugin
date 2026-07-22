<?php

namespace GiveMonnify\Addon;

/**
 * Helper class responsible for showing add-on notices.
 */
class Notices
{

    /**
     * GiveWP min required version notice.
     *
     * @return void
     */
    public static function giveVersionError()
    {
        Give()->notices->register_notice(
            [
                'id' => 'give-monnify-gateway-activation-error',
                'type' => 'error',
                'description' => View::load('notices/give-version-error'),
                'show' => true,
            ]
        );
    }

    /**
     * GiveWP inactive notice.
     *
     * @return void
     */
    public static function giveInactive()
    {
        echo View::load('notices/give-inactive');
    }
}
