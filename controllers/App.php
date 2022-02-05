<?php
namespace Controllers;
use Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class App
{
    public function render($request, Exception $e)
    {
        if ($e instanceof NotFoundHttpException) {
            return response()->view('errors.'.$e->getStatusCode(), [], $e->getStatusCode());
        }
        return parent::render($request, $e);
    }
}