<?php

namespace GiveMonnify\Addon;

/**
 * Helper class responsible for loading add-on translations.
 */
class Language
{
    /**
     * Load language.
     *
     * @return void
     */
    public static function load()
    {
        // Set filter for plugin's languages directory.
        $langDir = apply_filters(
            sprintf('%s_languages_directory', 'give-monnify'),
            // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.ValidHookName.NotLowercase
            dirname(GIVE_MONNIFY_BASENAME) . '/languages/'
        );

        // Traditional WordPress plugin locale filter.
        $locale = apply_filters('plugin_locale', get_locale(), 'give-monnify');
        $moFile = sprintf('%1$s-%2$s.mo', 'give-monnify', $locale);

        // Setup paths to current locale file.
        $moFileLocal = $langDir . $moFile;
        $moFileGlobal = WP_LANG_DIR . 'give-monnify' . $moFile;

        if (file_exists($moFileGlobal)) {
            // Look in global /wp-content/languages/TEXTDOMAIN folder.
            load_textdomain('give-monnify', $moFileGlobal);
        } elseif (file_exists($moFileLocal)) {
            // Look in local /wp-content/plugins/TEXTDOMAIN/languages/ folder.
            load_textdomain('give-monnify', $moFileLocal);
        } else {
            // Load the default language files.
            load_plugin_textdomain('give-monnify', false, $langDir);
        }
    }
}
