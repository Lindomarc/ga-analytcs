<?php

namespace Model;

use SQLite3;

class DB extends SQLite3
{
    private string $sqlite = __DIR__ . '/sqlite/database.db';


    public function __construct()
    {
        parent::__construct($this->sqlite,SQLITE3_OPEN_READWRITE);
    }


    public function select($sql)
    {
        $results = $this->query($sql);
        while ($row = $results->fetchArray(SQLITE3_INTEGER)){
            $rows[] = $row;
        }
        $results->finalize();
        return $rows;
    }

}

