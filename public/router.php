<?php

/**
 * Router script for PHP built-in server
 * This file redirects all requests to index.php for Symfony routing
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the request is for a file that exists in the public directory, serve it directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Otherwise, route everything through index.php for Symfony to handle
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
require __DIR__ . '/index.php';

