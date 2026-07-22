<?php

namespace GiveMonnify\Settings;

use GiveMonnify\Monnify\Gateway\MonnifyGateway;

/**
 * Monnify settings.
 */
class MonnifySettings
{
    /**
     * Register the Monnify settings.
     *
     * @return void
     */
    public static function register()
    {
        add_filter('give_get_sections_gateways', [self::class, 'registerSections']);
        add_filter('give_get_settings_gateways', [self::class, 'addSettings']);
        add_action('give_admin_field_monnify_webhooks', [self::class, 'addMonnifyWebhookField'], 10, 2);
    }

    /**
     * Register the Monnify section.
     *
     * @param array $sections
     */
    public static function registerSections($sections): array
    {
        $sections['monnify'] = __('Monnify', 'give-monnify');
        return $sections;
    }

    /**
     * Add the Monnify settings.
     *
     * @param array $settings
     */
    public static function addSettings($settings): array
    {
        // Only show settings when in the Monnify section
        if (give_get_current_setting_section() !== 'monnify') {
            return $settings;
        }

        $settings[] = [
            'id' => 'give_title_monnify_settings',
            'type' => 'title',
        ];

        $settings[] = [
            'name' => __('Monnify Webhooks', 'give-monnify'),
            'desc' => __('In order for Monnify to function properly, you must configure your webhooks.', 'give-monnify'),
            'id' => 'monnify_webhooks',
            'wrapper_class' => 'give-monnify-webhooks-tr',
            'type' => 'monnify_webhooks',
            'default' => MonnifyGateway::webhook()->getNotificationUrl(),
        ];

        $settings[] = [
            'name' => __('Monnify Live API Key', 'give-monnify'),
            'desc' => __('Enter your Monnify live API key.', 'give-monnify'),
            'id' => 'monnify_live_api_key',
            'type' => 'text',
        ];

        $settings[] = [
            'name' => __('Monnify Live Secret Key', 'give-monnify'),
            'desc' => __('Enter your Monnify live secret key.', 'give-monnify'),
            'id' => 'monnify_live_secret_key',
            'type' => 'password',
        ];

        $settings[] = [
            'name' => __('Monnify Live Contract Code', 'give-monnify'),
            'desc' => __('Enter your Monnify live contract code.', 'give-monnify'),
            'id' => 'monnify_live_contract_code',
            'type' => 'text',
        ];

        $settings[] = [
            'name' => __('Monnify Test API Key', 'give-monnify'),
            'desc' => __('Enter your Monnify test API key.', 'give-monnify'),
            'id' => 'monnify_test_api_key',
            'type' => 'text',
        ];

        $settings[] = [
            'name' => __('Monnify Test Secret Key', 'give-monnify'),
            'desc' => __('Enter your Monnify test secret key.', 'give-monnify'),
            'id' => 'monnify_test_secret_key',
            'type' => 'password',
        ];

        $settings[] = [
            'name' => __('Monnify Test Contract Code', 'give-monnify'),
            'desc' => __('Enter your Monnify test contract code.', 'give-monnify'),
            'id' => 'monnify_test_contract_code',
            'type' => 'text',
        ];

        $settings[] = [
            'id' => 'give_title_monnify_settings',
            'type' => 'sectionend',
        ];

        return $settings;
    }

    /**
     * Get the settings URL.
     */
    public static function getSettingsUrl(): string
    {
        return admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=monnify');
    }

    /**
     * Add the Monnify webhook field.
     *
     * This was forked from Stripe's/Paystack's webhook field.
     * Render the Monnify webhook field in the settings.
     *
     * @param array $value
     * @param array $option_value
     * @return void
     */
    public static function addMonnifyWebhookField($value, $option_value)
    {
        $wrapperClass = ! empty($value['wrapper_class']) ? 'class="' . esc_attr($value['wrapper_class']) . '"' : '';
        $webhookUrl = isset($value['default']) ? esc_url($value['default']) : '';
        ?>
        <tr valign="top" <?php echo $wrapperClass; ?>>
            <th scope="row" class="titledesc">
                <label><?php esc_html_e('Monnify Webhooks', 'give-monnify'); ?></label>
            </th>
            <td class="give-forminp give-forminp-api_key">
                <div class="give-monnify-webhook-sync-wrap">
                    <p class="give-monnify-webhook-explanation" style="margin-bottom: 15px;">
                        <?php
                        esc_html_e('In order for Monnify to function properly, you must configure your Monnify webhooks.', 'give-monnify');
                        printf(
                            /* translators: 1. Webhook settings page. */
                            ' ' . __('You can visit your <a href="%1$s" target="_blank">Monnify Account Dashboard</a> to add a new webhook.', 'give-monnify'),
                            esc_url('https://app.monnify.com/')
                        );
                        echo ' ';
                        esc_html_e('Please add a new webhook endpoint for the following URL:', 'give-monnify');
                        ?>
                    </p>
                    <p style="margin-bottom: 15px;">
                        <strong><?php esc_html_e('Webhook URL:', 'give-monnify'); ?></strong>
                        <input style="width: 400px;" type="text" readonly
                            value="<?php echo esc_attr($webhookUrl); ?>" />
                    </p>
                </div>
                <p class="give-field-description">
                    <?php esc_html_e('Monnify webhooks are critical to configure so that GiveWP can receive communication properly from the payment gateway, particularly for donations completed via bank transfer or USSD. Note: webhooks do not function on localhost or websites in maintenance mode.', 'give-monnify'); ?>
                </p>
            </td>
        </tr>
        <?php
    }
}
