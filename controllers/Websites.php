<?php

namespace Controllers;

use Model\Website;

class Websites
{
    static public function list()
    {
        $user_id = $_SESSION['Auth']['id'];
        if (!!$_SESSION['Auth']['admin']) {
//            $sql = "SELECT * FROM  websites where user_id = '$user_id}';";
            $sql = "
                select  *
                from websites
            ";
        } else {

            $sql = "
                select  *
                from user_websites
                join websites w on w.id = user_websites.website_id
                where user_id = {$user_id};
            ";
        }
        $rows = (new Website())->select($sql);
        $data = [];
        $count = 0;
        if ($rows)
            foreach ($rows as $key => $row) {
                if (!!$row['id']) {
                    $data[$count]['id'] = $row['id'];
                }
                if (!!$row['tracking_id']) {
                    $data[$count]['tracking_id'] = $row['tracking_id'];
                }
                if (!!$row['name']) {
                    $data[$count]['name'] = $row['name'];
                }
                if (!!$row['user_id']) {
                    $data[$count]['user_id'] = $row['user_id'];
                }
                $count++;
            }

        return $rows;
    }

    public function store()
    {
        $website = new Website();
        $sql = 'SELECT count(*)
                FROM  websites 
                where tracking_id = "' . $_POST['tracking_id'] . '"';
        $query = $website->query($sql);
        $row = $query->fetchArray();
        if (!$row[0]) {
            $sql = "
            INSERT INTO websites (tracking_id, name) 
            VALUES (
                '" . trim($_POST['tracking_id']) . "', 
                '" . trim($_POST['name']) . "'
            )";

            if ($website->exec($sql)) {
                if (!$_SESSION['Auth']['admin']) {
                    $sql = "
                    INSERT INTO user_websites (website_id, user_id) 
                    VALUES (
                        '" . $website->lastInsertRowID() . "', 
                        '" . trim($_SESSION['Auth']['id']) . "'
                    )";
                    $website->exec($sql);
                }
            }
        }
    }

    public function permission($id): array
    {
        $website = new Website();
        $User = new \Model\User();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '"';
        $result['website'] = $website->select($sql)[0];

        $sql = "
        select  id,name, username,email
        from user_websites
        join users u on u.id = user_websites.user_id
        where website_id = {$id}";

        $result['user_websites'] = $website->select($sql);
        $result['list_users'] = $User::list();

        return $result;
    }


    public function edit($id): array
    {
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '"';
        $result = $website->select($sql);
        return $result[0] ?? [];
    }

    public function update($id)
    {
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '";';
        $item = $website->select($sql);

        if ($item) {
            $sql = 'UPDATE websites 
            SET  name = "' . trim($_POST['name']) . '" 
            where id = "' . trim($id) . '"';
            $website->exec($sql);
            return true;
        }

        return false;
    }

    public function permissionAdd()
    {
        $website = new Website();
        $sql = '
            SELECT * 
            FROM  user_websites 
            WHERE user_id = "' . trim($_POST['user_id']) . '"
            AND website_id = "' . trim($_POST['website_id']) . '"
        ';
        $item = $website->select($sql);
        if (!isset($item[0]['user_id'])) {

            $sql = "
            INSERT INTO user_websites (user_id, website_id) 
            VALUES (
                '" . trim($_POST['user_id']) . "', 
                '" . trim($_POST['website_id']) . "'
            )";
            return $website->query($sql);
        }
        return false;
    }

    public function permissionDelete()
    {
        $website = new Website();
        $sql = '
            SELECT * 
            FROM  user_websites 
            WHERE user_id = "' . trim($_POST['user_id']) . '"
            AND website_id = "' . trim($_POST['website_id']) . '"
        ';
        $item = $website->select($sql);
        if (isset($item[0]['user_id'])) {
            $sql = '
                DELETE FROM user_websites
                WHERE user_id = "' . trim($_POST['user_id']) . '"
                AND website_id = "' . trim($_POST['website_id']) . '"
            ';
            return $website->query($sql);
        }
        return false;
    }

    public function delete()
    {
            $website = new Website();
            $sql = 'SELECT * FROM  websites where id = "' . $_POST['id'] . '";';
            $item = $website->select($sql);
            if (isset($item[0])) {
                $sql = 'DELETE FROM websites WHERE id = "' . $_POST['id'] . '"';
               if($website->query($sql)){
                   $sql = '
                        DELETE FROM user_websites
                        WHERE website_id = "' . $_POST['id'] . '"
                    ';
                   $website->query($sql);
               }
               return  true;
            }
        return false;
    }
}