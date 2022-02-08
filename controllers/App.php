<?php
namespace Controllers;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface;

class Websites implements ControllerProviderInterface
{
    protected $app;

    public function connect(Application $application)
    {
        $this->app   = $application;
        $controllers = $this->app['controllers_factory'];

        $controllers->get(
            '/',
            array($this, 'getAllBlogPosts')
        );
        return $controllers;
    }

    public function render($request, Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.'.$e->getStatusCode(), [], $e->getStatusCode());
        }
        return parent::render($request, $e);
    }
}