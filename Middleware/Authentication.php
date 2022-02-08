<?php

namespace Middleware;

use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

use Model\User;

class Authentication {

    static public function authenticate(Request $request, Application $app)
    {

        $auth = $request->headers->get("Authorization");
        $apikey = substr($auth, strpos($auth, ' '));
        $apikey = trim($apikey);
        $user = new User();
        $check = $user->authenticate($apikey);
        if(!$check){
            $app->abort(401);
        }
        else $request->attributes->set('userid',$check);
    }
}
