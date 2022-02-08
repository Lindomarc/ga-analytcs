<?php

namespace Model;

//use Illuminate\Database\Eloquent\Model;

class User extends DB
{
    public $table = 'users';

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


//    static public function list($options)
//    {
//
//        foreach ($options as $key => $option){
//            if($key === 'conditions'){
//                $conditions = ' where ' . $option['where'];
//                if(!!$option['end']){
//                    if (is_array($option['end'])){
//                        $conditions .= ' end '. implode(' end ', $option['end']);
//                    } else {
//                        $conditions .= ' end '.$option['end'];
//                    }
//                }
//
//            }
//            var_dump($conditions);
//            exit();
//        }
//        $User = new \Model\User();
//        $sql = 'SELECT id, name FROM  '.self::table .$conditions;
//        return $User->select($sql);
//    }
}