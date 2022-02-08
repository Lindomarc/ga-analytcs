<?php

namespace Controllers;

use Silex\Application;
use Silex\ControllerProviderInterface;

class Blogs implements ControllerProviderInterface
{
    public function connect(Application $application)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', array($this, 'getAllComments'));
        $controllers->get('/{comment_id}/', array($this, 'getOneComment'));

        return $controllers;
    }

// ... other methods

}