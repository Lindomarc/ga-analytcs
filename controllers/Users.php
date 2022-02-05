<?php

namespace Controllers;
use Model\User;

class Users extends App
{
    public function login($username,$password){
        $user = new User();
        return $user->login($username,$password);
    }

}