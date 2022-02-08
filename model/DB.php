<?php

namespace Model;

use MongoDB\Driver\Exception\Exception;
use SQLite3;

class DB extends SQLite3
{

    private string $sqlite = __DIR__ . '/sqlite/database.db';

    public $table = '';

    public function __construct()
    {
        parent::__construct($this->sqlite, SQLITE3_OPEN_READWRITE);
    }


    public function select($sql)
    {
        $results = $this->query($sql);
        $rows = [];
        if ($results)
        while ($row = $results->fetchArray(SQLITE3_INTEGER)) {
            $rows[] = $row;
        }
        return $rows;
    }


    public function list($options)
    {

        $results = [];
        try {
            $conditions = '';
            $selects = '*';
            foreach ($options as $key => $option) {
                if ($key === 'fields') {
                    $selects = $option;
                }
                if ($key === 'conditions') {
                    $conditions = ' WHERE ' . $option['where'];
                    if (!!$option['end']) {
                        if (is_array($option['end'])) {
                            $conditions .= ' END ' . implode(' END ', $option['end']);
                        } else {
                            $conditions .= ' END ' . $option['end'];
                        }
                    }

                }
            };
            $sql = 'SELECT ' . $selects . ' FROM  ' . $this->table . $conditions;
            $results = $this->select($sql);
        } catch (\Exception $exception) {
            var_dump($exception);
        }
        return $results;
    }

}

