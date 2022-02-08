<?php
namespace Controllers\Api;

use Controllers\Analytics;
use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiAnalytics implements ControllerProviderInterface
{
    protected $app;
    protected $service;

    public function connect(Application $application)
    {
        $this->app = $application;
        $this->service = Analytics::initialize();
        $controllers = $this->app['controllers_factory'];


        $controllers->get(
            '/visitors-online.json',
            array($this, 'visitorsOnline')
        );

        $controllers->get(
            '/active-users-hrs.json',
            array($this, 'activeUsersHrs')
        );

        return $controllers;
    }

    public function visitorsOnline()
    {
        if (!isset($_SESSION['Auth'])) {
            return $this->app->redirect('/login');
        }

        $websites = \Controllers\Websites::list();

        $items = $categories = $series = [];
        if ($websites) {

            foreach ($websites as $name => $website) {
                try {
                    $items[$website['name']] = (int)Analytics::visitors($this->service, $website['tracking_id']);
                } catch (\Google_Exception $e) {
                    $this->app['monolog']->addError($e->getMessage());
                }
            }

            arsort($items);
            foreach ($items as $name => $visitors) {
                $series[] = (int)$visitors;
                $categories[] = $name;
            }
        }

        $content = [
            'series' => $series,
            'categories' => $categories,
        ];
        return $this->app->json($content);
    }

    public function activeUsersHrs()
    {
        $websites = \Controllers\Websites::list();
        $results = [];
        foreach ($websites as $ga => $website){
            $results[] = $this->activeUsersHrsGa($ga);
        }
        return $this->app->json($results);
    }

    private function activeUsersHrsGa($ga)
    {
        $data = array();
        $params = array(
            'dimensions' => 'ga:dateHour',
            'sort' => '-ga:dateHour',
            'max-results' => '24',
        );

        try {

            $visitors = $this->service->data_ga->get(
                'ga:' . $ga,
                'yesterday',
                'today',
                'ga:users',
                $params
            );

            $results = [];
            if ($visitors->getRows()) {
                foreach ($visitors->getRows() as $row) {
                    if (count($row) !== 2) {
                        continue;
                    }
                    $results[$row[0]] = $row[1];
                }
            }

            $current_datetime = new \DateTime('23 hours ago');
            $end_date = new \DateTime('now');
            $hrs = $series = [];

            while ($current_datetime < $end_date) {
                $formated_date = $current_datetime->format('YmdH');
                $hrs[] = $current_datetime->format('H');
                $users = (array_key_exists($formated_date, $results)) ? $results[$formated_date] : 0;
                $series[] = (int)$users;
                $current_datetime->add(new \DateInterval('PT1H'));
            }

            $hrs[] = '...';
            $data = [
                'visitors' => (int)Analytics::visitors($this->service, $ga),
                'ga' => $ga,
                'name' => \Controllers\Websites::websiteName($ga),
                'series' => $series,
                'hrs' => $hrs
            ];

        } catch (\Google_Exception $e) {
            $this->app['monolog']->addError($e->getMessage());
        }
        return $data;
    }
}