<?php

namespace Model;




class User extends DB
{
    public $table = 'users';




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