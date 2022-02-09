<?php
namespace Controllers;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpKernel\HttpKernelInterface;

class App   implements ControllerProviderInterface
{
    protected $app;

    public function connect(Application $application)
    {
        $this->app   = $application;
        return $application['controllers_factory'];
    }

    public function render($request, Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.'.$e->getStatusCode(), [], $e->getStatusCode());
        }
        return parent::render($request, $e);
    }
}