<?php

namespace Model;

use SQLite3;

class DB extends SQLite3
{
    private string $sqlite = __DIR__ . '/sqlite/database.db';

    private string $table;

    public function __construct()
    {
        parent::__construct($this->sqlite,SQLITE3_OPEN_READWRITE);
    }

    public function getTable($value)
    {
        $this->table = $value;
    }

    public function setTable()
    {
        return $this->table;
    }

    public function install()
    {
        $query = "
        CREATE TABLE IF NOT EXISTS websites (
            tracking_id STRING, 
            name STRING)";
        $this->exec($query);

        $query = "
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username STRING,
            password STRING
         )";
        $this->exec($query);
    }

    public function select($sql){
        $results = $this->query($sql);
        return $results->fetchArray();
    }
}

