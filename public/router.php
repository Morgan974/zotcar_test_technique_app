<?php

/**
 * Router script for PHP built-in server
 * This file redirects all requests to index.php for Symfony routing
 */

// If the request is for a file that exists, serve it directly
if (file_exists(__DIR__ . $_SERVER['REQUEST_URI'])) {
    return false;
}

// Otherwise, route everything through index.php
require __DIR__ . '/index.php';

