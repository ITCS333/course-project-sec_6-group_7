<?php
//Database Connection

if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO
    {
        static $pdo = null;

        if ($pdo === null) {
            $host = 'localhost';
            $db   = 'course';
            $user = 'root';
            $pass = '';

            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,           PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return $pdo;
    }
}
