<?php
declare(strict_types=1);

namespace App\Core;

use App\Config\Database;
use PDO;

/**
 * Base Model with PDO Helpers
 */
abstract class Model
{
    protected PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findWhere(array $conditions, string $orderBy = 'id DESC', ?int $limit = null): array
    {
        $whereClauses = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            $paramKey = "col_" . str_replace('.', '_', $column);
            $whereClauses[] = "`{$column}` = :{$paramKey}";
            $params[$paramKey] = $value;
        }

        $whereSql = !empty($whereClauses) ? "WHERE " . implode(' AND ', $whereClauses) : "";
        $limitSql = $limit !== null ? "LIMIT {$limit}" : "";

        $sql = "SELECT * FROM `{$this->table}` {$whereSql} ORDER BY {$orderBy} {$limitSql}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findOneWhere(array $conditions): ?array
    {
        $rows = $this->findWhere($conditions, 'id DESC', 1);
        return $rows[0] ?? null;
    }

    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":{$col}", $columns);

        $colsSql = implode('`, `', $columns);
        $valsSql = implode(', ', $placeholders);

        $sql = "INSERT INTO `{$this->table}` (`{$colsSql}`) VALUES ({$valsSql})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $setClauses = [];
        $params = ['id' => $id];

        foreach ($data as $column => $value) {
            $setClauses[] = "`{$column}` = :{$column}";
            $params[$column] = $value;
        }

        $setSql = implode(', ', $setClauses);
        $sql = "UPDATE `{$this->table}` SET {$setSql} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
