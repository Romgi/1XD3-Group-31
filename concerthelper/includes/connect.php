<?php
declare(strict_types=1);

/*
    Name: Jonathan Graydon
    Date Created: April 1, 2026
    File Description: Connects ConcertHelper to the course database with PDO.
*/

/**
 * Connects to the database.
 *
 * Override with env vars: DB_HOST, DB_NAME, DB_USER, DB_PASS.
 *
 * Defaults match a typical local XAMPP install (root, no password). If you use
 * the course MySQL account instead, set DB_USER / DB_PASS (or edit the fallbacks below).
 *
 * @return PDO The database connection.
 */
function getDb(): PDO
{
    $host = getenv("DB_HOST") ?: "localhost";
    $db = getenv("DB_NAME") ?: "graydj1_db";
    $user = getenv("DB_USER") ?: "root";
    // Empty password is common for local XAMPP; use getenv so course servers can set a real password.
    $pass = getenv("DB_PASS") !== false ? (string) getenv("DB_PASS") : "";
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
