<?php

namespace Controllers;
use Model\User;

class Users
{

    public function login($username,$password){
        $user = new User();
        return $user->login($username,$password);
    }

}