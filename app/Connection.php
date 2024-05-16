<?php

namespace Hexlet\Code;

use Exception;
use PDO;

/**
 * Создание класса Connection
 */
final class Connection
{
    /**
     * Подключение к базе данных и возврат экземпляра объекта \PDO
     * @return PDO
     * @throws Exception
     */
    public static function connect()
    {
        if (getenv('DATABASE_URL')) {
            $databaseUrl = parse_url($_ENV['DATABASE_URL']);
        }

        if (isset($databaseUrl['host'])) {
            $params['host'] = $databaseUrl['host'];
            $params['port'] = isset($databaseUrl['port']) ? $databaseUrl['port'] : null;
            $params['database'] = isset($databaseUrl['path']) ? ltrim($databaseUrl['path'], '/') : null;
            $params['user'] = isset($databaseUrl['user']) ? $databaseUrl['user'] : null;
            $params['password'] = isset($databaseUrl['pass']) ? $databaseUrl['pass'] : null;
        } else {
        // чтение параметров в файле конфигурации
            $params = parse_ini_file(__DIR__ . '/../database.env');
        }
        if ($params === false) {
            throw new Exception("Error reading database configuration file");
        }

        // подключение к базе данных postgresql
        $conStr = sprintf(
            "pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
            $params['host'],
            $params['port'],
            $params['database'],
            $params['user'],
            $params['password']
        );

        $pdo = new PDO($conStr);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }
}
