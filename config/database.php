<?php
declare(strict_types=1);

/**
 * Returns the DB sub-config only. The application loader in app/bootstrap.php
 * passes this into App\Services\Db::init().
 */

$config = require __DIR__ . '/config.php';
return $config['db'];