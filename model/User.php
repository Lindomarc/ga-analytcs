<?php

namespace Model;

class User extends DB
{
    const SESSION = "Auth";

    public string $table = 'users';

    public function login($username, $password)
    {
        $data = $this->select("SELECT *  FROM `users` WHERE `username`='$username'");
  var_dump($data);exit;
        if (password_verify($password, $data['password'])) {
            $_SESSION[User::SESSION] = $data;
            return $data;
        }
        return $data;
    }
}