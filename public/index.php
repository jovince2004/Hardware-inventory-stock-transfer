<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$projectRoot = __DIR__;
$autoloadPath = $projectRoot.'/../vendor/autoload.php';

if (!is_file($autoloadPath)) {
    $searchRoot = $projectRoot;

    while (dirname($searchRoot) !== $searchRoot) {
        $searchRoot = dirname($searchRoot);
        $candidate = $searchRoot.'/vendor/autoload.php';

        if (is_file($candidate)) {
            $autoloadPath = $candidate;
            break;
        }
    }
}

if (!is_file($autoloadPath)) {
    throw new Error('Unable to locate Composer autoload. Please run "composer install" in the application root.');
}

$projectRoot = dirname($autoloadPath, 2);

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $projectRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $autoloadPath;

// Bootstrap Laravel and handle the request...
(require_once $projectRoot.'/bootstrap/app.php')
    ->handleRequest(Request::capture());
