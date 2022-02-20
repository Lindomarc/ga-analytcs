<?php

namespace Model;

use SQLite3;

class DB extends SQLite3
{

    protected string $sqlite = __DIR__ . '/sqlite/database.db';

    public $table = '';

    public $collumnsNane;

    public function __construct()
    {
        parent::__construct($this->sqlite, SQLITE3_OPEN_READWRITE);
        $this->setCollumnsNane([]);
    }


    public function select($sql)
    {
        $results = $this->query($sql);
        $rows = [];
        if ($results) {
            while ($row = $results->fetchArray(SQLITE3_INTEGER)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * @return mixed
     */
    public function getCollumnsNane()
    {
        return $this->collumnsNane;
    }

    /**
     * @param mixed $collumnsNane
     */
    public function setCollumnsNane($collumnsNane)
    {
        $table = $this->query("PRAGMA table_info(users);");
        while ($columns = $table->fetchArray(SQLITE3_ASSOC)) {
            $collumnsNane[$columns['name']] = '';
        }
        $this->collumnsNane = $collumnsNane;
    }

    public function list($options = null)
    {
        $results = [];
        try {
            $conditions = '';
            $selects = '*';
            if ($options) {
                foreach ($options as $key => $option) {
                    if ($key === 'fields') {
                        $selects = $option;
                    }
                    if ($key === 'conditions') {
                        $conditions = ' WHERE ' . $option['where'];
                        if (!!$option['and']) {
                            if (is_array($option['and'])) {
                                $conditions .= ' AND ' . implode(' AND ', $option['and']);
                            } else {
                                $conditions .= ' AND ' . $option['and'];
                            }
                        }

                    }
                }
            }
            $sql = 'SELECT ' . $selects . ' FROM  ' . $this->table . $conditions;
            $results = $this->select($sql);
        } catch (\Exception $exception) {
            var_dump($exception);
        }

        return $results;
    }

    public function unique($fields, $data)
    {
        foreach ($fields as $field) {
            if ($data[$field]) {
                $sql = '
                SELECT  ' . $field . ' 
                FROM ' . $this->table . ' 
                WHERE ' . $field . ' ="' . trim($data[$field]) . '" ';
                $sql .= !!$data['id'] ? 'AND id!="' . $data['id'] . '"' : '';
                $result = $this->select($sql);
//                if (!!$result) {
                //TODO: (new FlashBag())->add('danger',$field . ': ' . $data[$field] . ': já está registrado');
//                }
            }
        }
        return !$result;
    }

    public function save($data)
    {
        $fields = implode(',', array_keys($data));
        $values = '"' . implode('", "', $data) . '"';
        $sql = '
        INSERT INTO ' . $this->table . ' (' . $fields . ') VALUES (' . $values . ')';
        try {
            $result = $this->exec($sql);
        } catch (\SQLiteException $exception) {
            var_dump($exception);
        }
        return $result;
    }


    public function update($data)
    {

        $id = $data['id'];
        unset($data['id']);

        $setFields = '';

        foreach ($data as $field => $value) {
            $setFields .= $field . '="' . $value . '", ';

        }
        $setFields = trim($setFields, ', ');

        $sql = 'UPDATE ' . $this->table . ' 
        SET  ' . $setFields . ' 
        WHERE id="' . $id . '"';
        return $this->exec($sql);
    }

    public function delete($id)
    {
        $sql = 'DELETE FROM ' . $this->table . ' WHERE id = "' . $id . '"';
        return $this->exec($sql);
    }

    public function getId($id)
    {
        $options = [
            'conditions' => [
                'where' => 'id=' . $id
            ]
        ];
        $item = $this->list($options);
        return $item[0] ?? [];
    }


}

