<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Creates the shared PDO database connection used throughout the ConcertHelper app.
*/
declare(strict_types=1);

/**
 * Creates the PDO connection used by the app.
 *
 * @return PDO A configured PDO connection for the ConcertHelper MySQL database.
 */
function getDb(): PDO
{
    $host = getenv("DB_HOST") ?: "localhost";
    $db = getenv("DB_NAME") ?: "graydj1_db";
    $user = getenv("DB_USER") ?: "graydj1_local";
    $pass = getenv("DB_PASS") !== false ? (string) getenv("DB_PASS") : ")wt_xR:e";
    $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
