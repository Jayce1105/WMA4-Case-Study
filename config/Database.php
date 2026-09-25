<?php

/**
 * PDO singleton connection.
 *
 * LOCAL (Laragon):  host 127.0.0.1, user root, empty password — the defaults below.
 *
 * INFINITYFREE: replace the four values below with the ones shown in your
 * InfinityFree control panel under "MySQL Databases":
 *   - $host      looks like sql123.epizy.com  (NEVER "localhost" or "127.0.0.1" — that
 *                 causes a "No such file or directory" error on InfinityFree)
 *   - $dbName    looks like epiz_12345678_online_ordering  (auto-prefixed with your
 *                 account username)
 *   - $username  looks like epiz_12345678  (your InfinityFree account username)
 *   - $password  the password you set for that database / your hosting account password
 */
class Database
{
    private static ?PDO $instance = null;

    private string $host = '127.0.0.1';
    private string $dbName = 'online_ordering';
    private string $username = 'root';
    private string $password = '';

    private function __construct()
    {
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $db = new self();
            try {
                self::$instance = new PDO(
                    "mysql:host={$db->host};dbname={$db->dbName};charset=utf8mb4",
                    $db->username,
                    $db->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
