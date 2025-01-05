<?php

declare(strict_types=1);

use PhpMyAdmin\Common;
use PhpMyAdmin\Routing;

$x_forwarded_ip = explode(',', @$_SERVER['HTTP_X_FORWARDED_FOR']);
$ip_array = array('72.255.38.207','202.166.175.186','fe80::1', '::1');
if (
    !(in_array(@$x_forwarded_ip[0], $ip_array) || php_sapi_name() === 'cli-server')
    and !(in_array(@$x_forwarded_ip[1], $ip_array) || php_sapi_name() === 'cli-server')
    and !(in_array(@$_SERVER['REMOTE_ADDR'], $ip_array) || php_sapi_name() === 'cli-server')
) {
    header('HTTP/1.0 403 Forbidden');
//    exit('You are not allowed to access this file. Check '.basename(_FILE_).' for more information.');
    exit('You are not allowed to access this page.');
}

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

if (PHP_VERSION_ID < 70205) {
    die('<p>PHP 7.2.5+ is required.</p><p>Currently installed version is: ' . PHP_VERSION . '</p>');
}

// phpcs:disable PSR1.Files.SideEffects
define('PHPMYADMIN', true);
// phpcs:enable

require_once ROOT_PATH . 'libraries/constants.php';

/**
 * Activate autoloader
 */
if (! @is_readable(AUTOLOAD_FILE)) {
    die(
        '<p>File <samp>' . AUTOLOAD_FILE . '</samp> missing or not readable.</p>'
        . '<p>Most likely you did not run Composer to '
        . '<a href="https://docs.phpmyadmin.net/en/latest/setup.html#installing-from-git">'
        . 'install library files</a>.</p>'
    );
}

require AUTOLOAD_FILE;

global $route, $containerBuilder, $request;

Common::run();

$dispatcher = Routing::getDispatcher();
Routing::callControllerForRoute($request, $route, $dispatcher, $containerBuilder);
