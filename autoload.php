<?php
/**
 * A minimal PSR-4 autoloader for the GiveMonnify\ namespace, mapped to /src.
 * Avoids a Composer dependency since this add-on has no third-party packages.
 */

defined('ABSPATH') or exit;

spl_autoload_register(function ($class) {
    $prefix = 'GiveMonnify\\';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
