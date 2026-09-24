<?php

require_once __DIR__ . '/../config/Database.php';

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    abstract public function validate(): bool;
}
