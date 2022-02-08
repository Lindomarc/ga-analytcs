<?php

namespace Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class Dashboard implements ControllerProviderInterface
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
        return $this->app['twig']->render('default/active-users-hrs.html.twig', [
            'data' => Websites::list(),
            'current' => '/'
        ]);
    }
}