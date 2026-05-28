<?php

namespace Core;

use PDO;
use PDOStatement;

class Model
{
    protected PDO $pdo;
    protected string $table = '';

    public function __construct()
    {
        $this->pdo = Connect::getInstance();
    }

    protected function insert(array $data): int
    {
        $columns = array_keys($data);
        $fields = implode(', ', $columns);
        $params = ':' . implode(', :', $columns);

        $this->execute("INSERT INTO {$this->table} ({$fields}) VALUES ({$params})", $data);

        return (int) $this->pdo->lastInsertId();
    }

    protected function update(array $data, array $where): void
    {
        $sets = [];
        $params = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = :set_{$column}";
            $params["set_{$column}"] = $value;
        }

        [$whereSql, $whereParams] = $this->buildWhere($where);
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " {$whereSql}";

        $this->execute($sql, array_merge($params, $whereParams));
    }

    protected function findAll(
        array $where = [],
        array $columns = ['*'],
        string $orderBy = '',
        ?int $limit = null
    ): array {
        [$whereSql, $params] = $this->buildWhere($where);
        $fields = implode(', ', $columns);
        $sql = "SELECT {$fields} FROM {$this->table} {$whereSql}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $this->execute($sql, $params)->fetchAll();
    }

    protected function findOne(array $where = [], array $columns = ['*'], string $orderBy = ''): ?array
    {
        $row = $this->findAll($where, $columns, $orderBy, 1);
        return $row[0] ?? null;
    }

    private function buildWhere(array $where): array
    {
        if (!$where) {
            return ['', []];
        }

        $conditions = [];
        $params = [];

        foreach ($where as $column => $value) {
            $param = 'where_' . $column;
            $conditions[] = "{$column} = :{$param}";
            $params[$param] = $value;
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeParams($params));

        return $stmt;
    }

    private function normalizeParams(array $params): array
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            $param = str_starts_with((string) $key, ':') ? $key : ':' . $key;
            $normalized[$param] = $value;
        }

        return $normalized;
    }
}

