<?php

/*
|--------------------------------------------------------------------------
| cPanel entry point
|--------------------------------------------------------------------------
|
| Copy this file into public_html, replacing the index.php that ships in
| public/. Everything else from public/ is used as-is.
|
| The application itself lives OUTSIDE the web root. This file finds it
| automatically; set APP_BASE below only if the search fails.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Set this to skip the search, e.g. '/home/myaccount/laravel_app'.
$appBase = '';

if ($appBase === '') {
    // Look for the directory holding artisan: first the conventional name,
    // then any sibling of the web root that looks like a Laravel install.
    $candidates = [
        dirname(__DIR__).'/laravel_app',
        dirname(__DIR__).'/laravel',
        dirname(__DIR__).'/app',
        __DIR__.'/..',
    ];

    foreach (glob(dirname(__DIR__).'/*', GLOB_ONLYDIR) ?: [] as $sibling) {
        $candidates[] = $sibling;
    }

    foreach ($candidates as $candidate) {
        if (is_file($candidate.'/artisan') && is_file($candidate.'/vendor/autoload.php')) {
            $appBase = $candidate;
            break;
        }
    }
}

if ($appBase === '') {
    http_response_code(500);
    exit('Could not locate the application directory. Set $appBase in index.php to the folder containing artisan.');
}

if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appBase.'/bootstrap/app.php';

/*
 * public_html is the web root, so Laravel's public path points here.
 *
 * This is set in code rather than through .env because config:cache stops
 * .env being read during bootstrap, which would silently leave it unset —
 * breaking asset() URLs and the upload disk, which writes into public/storage.
 */
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
