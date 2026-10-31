<?php
declare(strict_types=1);

function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    $localConfig = [];
    $localConfigPath = __DIR__ . '/config.local.php';
    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (is_array($loadedConfig)) {
            $localConfig = $loadedConfig;
        }
    }

    $host = $localConfig['host'] ?? getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
    $user = $localConfig['user'] ?? getenv('STUDENTHUB_DB_USER') ?: 'root';
    $password = $localConfig['password'] ?? getenv('STUDENTHUB_DB_PASSWORD');
    $database = $localConfig['database'] ?? getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
    $port = (int) ($localConfig['port'] ?? getenv('STUDENTHUB_DB_PORT') ?: 3306);

    try {
        $connection = new mysqli(
            $host,
            $user,
            $password === false ? '' : $password,
            $database,
            $port
        );
        $connection->set_charset('utf8mb4');
        return $connection;
    } catch (mysqli_sql_exception $exception) {
        error_log('StudentHub database connection failed: ' . $exception->getMessage());
        throw new RuntimeException(
            'The database is unavailable. Check the XAMPP MySQL service and database setup.',
            0,
            $exception
        );
    }
}
