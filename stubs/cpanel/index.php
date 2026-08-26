<?php

/*
|--------------------------------------------------------------------------
| cPanel entry point
|--------------------------------------------------------------------------
|
| Copy this into public_html and adjust APP_BASE below to wherever the
| application folder sits (the directory holding artisan, app/, vendor/).
|
| Everything else in public/ — index.php aside — is copied into public_html
| as-is: build/, images/, uploads/, storage/, favicon files, robots.txt.
|
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Path to the Laravel application, OUTSIDE the web root.
$appBase = dirname(__DIR__).'/laravel_app';

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = $appBase.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appBase.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appBase.'/bootstrap/app.php';

/*
 * public_html is the web root, so point Laravel's public path at this very
 * directory. Doing it here rather than through .env matters: config:cache
 * stops .env being read at bootstrap, which would leave APP_PUBLIC_PATH null.
 */
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
