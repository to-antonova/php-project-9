<?php

namespace Hexlet\Code;

class Database
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

    public function query(string $sql, array $params = [])
    {
        // Подготовка запроса
        $stmt = $this->pdo->prepare($sql);

        // Обход массива с параметрами
        // и подставляем значения
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
        }

        // Выполняя запрос
        $stmt->execute();
        // Возвращаем ответ
        return $stmt->fetchAll($this->pdo::FETCH_ASSOC);
    }
}
