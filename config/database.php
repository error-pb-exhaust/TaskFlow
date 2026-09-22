<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'taskflow';
const DB_USER = 'root';
const DB_PASS = '';

function database(bool $withoutDatabase = false): PDO
{
    static $connections = [];
    $key = $withoutDatabase ? 'server' : 'database';

    if (isset($connections[$key])) {
        return $connections[$key];
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    if (!$withoutDatabase) {
        $dsn .= ';dbname=' . DB_NAME;
    }

    $connections[$key] = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $connections[$key];
}
