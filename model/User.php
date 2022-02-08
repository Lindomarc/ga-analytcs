<?php

namespace Model;

//use Illuminate\Database\Eloquent\Model;

class User extends DB
{

    public string $table = 'users';

    public function authenticate($apikey)
    {
        $data = @$this->select("SELECT *  FROM `users` WHERE `apikey`='$apikey'")[0];
        if (password_verify($apikey, $data['apikey'])) {
            return $data;
        }
        return false;
    }

    public function login($username, $password)
    {
        $data = @$this->select("SELECT *  FROM `users` WHERE `username`='$username'")[0];
        if (password_verify($password, $data['password'])) {

            $_SESSION['Auth'] = $data;
            return $data;
        }
        return $data;
    }
    static public function list()
    {
        $User = new \Model\User();
        $sql = 'SELECT id, name FROM  users WHERE admin = 0';
        return $User->select($sql);
    }
}