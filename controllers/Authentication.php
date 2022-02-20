<?php

namespace Controllers;

use Model\User;
use Silex\Application;
use Silex\ControllerProviderInterface;

class Authentication implements ControllerProviderInterface
{
    protected $app;

    public function connect(Application $application)
    {
        $this->app = $application;
        $controllers = $this->app['controllers_factory'];

        $controllers->get(
            '/',
            array($this, 'index')
        );
        $controllers->post(
            '/',
            array($this, 'autentication')
        );

        return $controllers;
    }

    public function index()
    {
        return $this->app['twig']->render('users/login.html.twig');
    }

    static public function isAuth()
    {
        $Auth = (new \Symfony\Component\HttpFoundation\Session\Session())->get('Auth');
        if (!$Auth){
            return  (new \Silex\Application())->redirect('/login');
        }
    }

    public function autentication()
    {
        $username = trim($_POST['username']);

        $user = new User();
        $data = $user->select('
            SELECT * 
            FROM users 
            WHERE username="' . $username . '" 
            OR email="' . $username . '"'
        )[0];

        if (!!$data) {
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            if (password_verify($data['password'], $hash)) {

                $this->app['session']->set('Auth',$data);
                return $this->app->redirect('/dashboard');
            }
        }
    }
}