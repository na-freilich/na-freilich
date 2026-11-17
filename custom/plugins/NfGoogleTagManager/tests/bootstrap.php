<?php declare(strict_types=1);

$loader = require dirname(__DIR__, 4) . '/vendor/autoload.php';
$loader->addPsr4('NfGoogleTagManager\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('NfGoogleTagManager\\Tests\\', __DIR__ . '/');
