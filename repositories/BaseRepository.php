<?php

namespace repositories;
use Database;

class BaseRepository
{
    protected $conn;
    protected string $table;

    public function __construct(string $table)
    {
        $this->conn = Database::getConnection();
        $this->table = $table;
    }

    public function findAll(): array
    {
        $stmt = $this->conn->query(/** @lang text */ "SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function findById($id)
    {
        $stmt = $this->conn->prepare(/** @lang text */ "SELECT * FROM {$this->table} WHERE {$this->table}_ID = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getColumnsAsById ($id, array $columns, string $byId): array {
        $stringColumns = count($columns) > 0 ? ('t.' . implode(', t.', $columns)) : '*';
        error_log("String columns: $stringColumns, byId: $byId, $this->table");
        $byId = $byId ?: $this->table . '_ID';
        $stmt = $this->conn->prepare( /** @lang text */ "SELECT {$stringColumns} FROM {$this->table} t WHERE {$byId} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findWhere(string $field, $value)
    {
        $stmt = $this->conn->prepare(/** @lang text */ "SELECT * FROM {$this->table} WHERE {$field} = :val");
        $stmt->execute(['val' => $value]);
        return $stmt->fetch();
    }

    public function create(array $data): bool
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_map(fn($k) => ":$k", array_keys($data)));

        $stmt = $this->conn->prepare(/** @lang text */ "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)");
        return $stmt->execute($data);
    }

    public function update($id, array $data): bool
    {
        $set = implode(", ", array_map(fn($k) => "$k = :$k", array_keys($data)));
        $data['id'] = $id;

        $stmt = $this->conn->prepare(/** @lang text */ "UPDATE {$this->table} SET $set WHERE {$this->table}_ID = :id");
        return $stmt->execute($data);
    }

    public function delete($id, string $byOtherID = null): bool
    {
        $byOtherID = $byOtherID ?: "{$this->table}_ID";
        $stmt = $this->conn->prepare(/** @lang text */ "DELETE FROM {$this->table} WHERE $byOtherID = :id");
        return $stmt->execute(['id' => $id]);
    }
}
