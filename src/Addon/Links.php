<?php

namespace GiveMonnify\Addon;

use GiveMonnify\Settings\MonnifySettings;

class Links
{
    /**
     * Add settings link
     *
     * @return array
     */
    public function __invoke($actions)
    {
        $newActions = array(
            'settings' => sprintf(
                '<a href="%1$s">%2$s</a>',
                MonnifySettings::getSettingsUrl(),
                __('Settings', 'give-monnify')
            ),
        );

        return array_merge($newActions, $actions);
    }
}
