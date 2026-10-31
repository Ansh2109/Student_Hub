<?php
declare(strict_types=1);

function db(): mysqli
{
    static $connection = null;
    if ($connection instanceof mysqli) {
        return $connection;
    }

    $host = getenv('STUDENTHUB_DB_HOST') ?: '127.0.0.1';
    $user = getenv('STUDENTHUB_DB_USER') ?: 'root';
    $password = getenv('STUDENTHUB_DB_PASSWORD');
    $database = getenv('STUDENTHUB_DB_NAME') ?: 'studenthub';
    $port = (int) (getenv('STUDENTHUB_DB_PORT') ?: 3306);

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
