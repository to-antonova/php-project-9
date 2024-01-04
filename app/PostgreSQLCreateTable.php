<?php

namespace Hexlet\Code;

/**
 * Создание в PostgreSQL таблицы из демонстрации PHP
 */
class PostgreSQLCreateTable
{
    /**
     * объект PDO
     * @var \PDO
     */
    private $pdo;

    /**
     * инициализация объекта с объектом \PDO
     * @тип параметра $pdo
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * создание таблиц
     */
    public function createTables()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS urls (
                   id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
                   name varchar(255),
                   created_at timestamp
        );';

        $this->pdo->exec($sql);

        return $this;
    }

    public function createTablesWithChecks()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS urls_checks (
            id bigint PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
            url_id bigint REFERENCES urls (id),
            status_code int,
            h1 varchar(255),
            title varchar(255),
            description varchar(255),
            created_at timestamp
        );';

        $this->pdo->exec($sql);

        return $this;
    }
}
