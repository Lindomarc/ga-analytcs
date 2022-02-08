<?php

namespace Controllers;

use Model\User;
use Silex\Application;
use Silex\ControllerProviderInterface;

class Users implements ControllerProviderInterface
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

        return $controllers;
    }

    public function index()
    {
        $options = [
            'fields' => 'id,name,email',
            'conditions' => [
                'where' => 'admin = 0'
            ]
        ];
        $results = (new User())->list($options);

        return $this->app['twig']->render('users/index.twig', [
            'data' => ['users' => $results],
            'current' => '/dashboard'
        ]);

    }
}