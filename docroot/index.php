<?php
date_default_timezone_set("America/Belem");
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/JWTWrapper.php';

const ROOT = __DIR__;

use Controllers\Analytics;
use Controllers\Users;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\Response;


$app = new Silex\Application();

/* START CONFIGURATION */
$app['debug'] = true;
$app['config_path'] = __DIR__ . '/../config/config.json';
/* END CONFIGURATION */
$app['session.storage.options'] = [
    'cookie_lifetime' => 3600
];

/*
 * Register Providers
 */
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__ . '/../log/development.log',
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));

$app->register(new Silex\Provider\SessionServiceProvider());

$app['session']->all();

$app->get('/login', function (Silex\Application $app) {
    return $app['twig']->render('users/login.html.twig');
});

$app->post('/login', function (Silex\Application $app) {

    if ((isset($_POST['username']) && $_POST['username']) && (isset($_POST['password']) && $_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $user = new Users();
        $login = $user->login($username, $password);

        if ($login) {
            return $app->redirect('/');
        }
    }

    return $app['twig']->render('users/login.html.twig');
});

$app->get('/logout', function (Silex\Application $app) {
    $app['session']->all();
    unset($_SESSION['Auth']);
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
});


$app['config'] = $app->share(function ($app) {
    $config = array();
    try {
        if (!file_exists($app['config_path'])) {
            throw new FileNotFoundException('Could not find file' . $app['config_path']);
        }
        $json = file_get_contents($app['config_path']);

        $config = json_decode($json);

    } catch (Exception $e) {
        $app['monolog']->addError($e->getMessage());
        exit(1);
    }

    return $config;
});

$app['google_api_service'] = $app->share(function ($app) {
    try {
        $service = Analytics::initialize();
    } catch (Google_Exception $e) {
        $app['monolog']->addError($e->getMessage());
        exit(1);
    }
    return $service;
});

$app['websites'] = $app->share(function ($app) {
    return  \Controllers\Websites::list();
//    $config = $app['config'];
//    $service = $app['google_api_service'];
//    $websites = array();
//    $tracking_codes = websites();
//    try {
//        $web_properties = $service->management_webproperties->listManagementWebproperties('~all');
//        foreach ($web_properties->getItems() as $property) {
//            foreach ($tracking_codes as $code) {
//                if ($property->id === $code['tracking_id']) {
//                    $websites[$property->id]['ga'] = $code['ga'];
//                    $websites[$property->id]['name'] = $code['name'];
//                }
//            }
//        }
//    } catch (Google_Exception $e) {
//        $app['monolog']->addError($e->getMessage());
//    }
//    return $websites;
});

function websites()
{
    return \Controllers\Websites::list();
}

$app->get('/api/websites', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $content = json_encode(websites());
    $headers = array('Content-Type' => 'application/json');
    return new Response($content, 200, $headers);
});
/*
$app->get('/api/getuserslastday.csv', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $data = array();
    $params = array(
        'dimensions' => 'ga:dateHour',
        'sort' => '-ga:dateHour',
        'max-results' => '25',
    );
    foreach ($websites as $code => $website) {
        $visitors = null;
        try {
            $visitors = $service->data_ga->get($website['tracking_id'], 'yesterday', 'today', 'ga:users', $params);
            $results = array();
            if ($visitors->getRows())
                foreach ($visitors->getRows() as $row) {
                    if (count($row) !== 2) {
                        continue;
                    }
                    $results[$row[0]] = $row[1];
                }
            // Backfill the data
            $current_datetime = new \DateTime('24 hours ago');
            $end_date = new \DateTime('now');
            while ($current_datetime < $end_date) {
                $formated_date = $current_datetime->format('YmdH');
                $users = (array_key_exists($formated_date, $results)) ? $results[$formated_date] : 0;
                $data[] = array(
                    'website' => $website['name'],
                    'dateHour' => $formated_date,
                    'users' => $users,
                );
                $current_datetime->add(new DateInterval('PT1H'));
            }
        } catch (Google_Exception $e) {
            $app['monolog']->addError($e->getMessage());
        }
    }

    $content = $app['twig']->render('api/getuserslastday.csv.twig', array(
        'data' => $data,
    ));

    $headers = array('Content-Type' => 'text/csv');
    $response = new Response($content, 200, $headers);

    return $response;
});
*/
$app->get('/api/get-active-users-hrs.json', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    /* @var Google_Service_Analytics $service */
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $data = array();
    $params = array(
        'dimensions' => 'ga:dateHour',
        'sort' => '-ga:dateHour',
        'max-results' => '24',
    );
    if ($websites) {

        foreach ($websites as $code => $website) {
            $visitors = null;
            try {
                $visitors = $service->data_ga->get(
                    'ga:' . $website['tracking_id'],
                    'yesterday',
                    'today',
                    'ga:users',
                    $params
                );

                $results = [];

                if ($visitors->getRows())
                    foreach ($visitors->getRows() as $row) {
                        if (count($row) !== 2) {
                            continue;
                        }
                        $results[$row[0]] = $row[1];
                    }

                $current_datetime = new \DateTime('23 hours ago');
                $end_date = new \DateTime('now');
                $hrs = $series = [];

                while ($current_datetime < $end_date) {
                    $formated_date = $current_datetime->format('YmdH');
                    $hrs[] = $current_datetime->format('H');
                    $users = (array_key_exists($formated_date, $results)) ? $results[$formated_date] : 0;
                    $series[] = (int)$users;
                    $current_datetime->add(new DateInterval('PT1H'));
                }

                $hrs[] = '...';
                $data[] = [
                    'visitors' => (int)Analytics::visitors($service, $website['tracking_id']),
                    'UA' => $code,
                    'name' => $website['name'],
                    'series' => $series,
                    'hrs' => $hrs
                ];

            } catch (Google_Exception $e) {
                $app['monolog']->addError($e->getMessage());
            }
        }
    }

    return $app->json($data);
});
/*
$app->get('/api/getactiveusers.json', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $data = array();

    foreach ($websites as $code => $website) {
        try {
            $visitors = $service->data_realtime->get($website['tracking_id'], 'rt:activeUsers');
            $data[$website['name']] = ($visitors->getTotalResults() > 0) ? $visitors->getRows()[0][0] : 0;
        } catch (Google_Exception $e) {
            $app['monolog']->addError($e->getMessage());
        }
    }

    return $app->json($data);
});
*/

$app->get('/api/get-active-users-online.json', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    /* @var Google_Service_Analytics $service */
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $data = $results = array();
    foreach ($websites as $website) {
        try {
            $visitors = Analytics::visitors($service, $website['tracking_id']); // $service->data_realtime->get($website['tracking_id'], 'rt:activeUsers');
            $results[$website['tracking_id']][$visitors] = $website['name'];
        } catch (Google_Exception $e) {
            $app['monolog']->addError($e->getMessage());
        }
    }
    asort($results);
    foreach ($results as $ga => $result){
        $data[$ga]['visitors'] = key($result);
        $data[$ga]['name'] = $result[key($result)];

    }
    return $app->json($data);
});

$app->get('/', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('default/active-users-hrs.html.twig');
});

$app->get('/visitantes-online', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('default/visitors-online.html.twig');
});
$app->get('/api/visitors-online.json', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $items = $categories = $series = [];
    if ($websites) {
        foreach ($websites as $name => $website) {
            try {
                $items[$website['name']] = (int)Analytics::visitors($service, $website['tracking_id']);
            } catch (Google_Exception $e) {
                $app['monolog']->addError($e->getMessage());
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
    return $app->json($content);
});

$app->get('/websites', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('websites/index.html.twig', array(
        'data' => websites(),
        'isAdmin' => $_SESSION['Auth']['admin']
    ));
});

$app->get('/websites/add', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('websites/add.html.twig', array(
        'data' => ['user_id' => $_SESSION['Auth']['id']]
    ));
});

$app->post('/websites/add', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (isset($_POST['tracking_id']) && !!$_POST['tracking_id']) {
        $website = new \Controllers\Websites();
        $website->store();
        return $app->redirect('/websites');
    }
    return false;
});


$app->get('/websites/edit/{id}', function (Silex\Application $app, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if ($id) {
        $data =  (new \Controllers\Websites())->edit($id);
        if (!!$data) {
            return $app['twig']->render('websites/edit.html.twig', array(
                'data' => $data
            ));
        }
    }
    return $app->redirect('/websites');
});

$app->post('/websites/edit/{id}', function (Silex\Application $app, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if ($id) {
        if((new \Controllers\Websites())->update($id)){
            return $app->redirect('/websites');
        }
    }
    return false;
});

$app->get('/websites/permission/{id}', function (Silex\Application $app, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (!$_SESSION['Auth']['admin']) {
        return $app->redirect('/websites');
    }
    if ($id) {
        $data =  (new \Controllers\Websites())->permission($id);
        if (!!$data) {
            return $app['twig']->render('websites/permission.html.twig', array(
                'data' => $data
            ));
        }
    }
    return $app->redirect('/websites');
});
$app->post('/websites/permission/{id}', function (Silex\Application $app,$id) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (!$_SESSION['Auth']['admin']) {
        return $app->redirect('/websites');
    }
    if (isset($_POST['user_id']) && isset($_POST['website_id'])) {
         (new \Controllers\Websites())->permissionAdd();
    }
    return $app->redirect('/websites/permission/'.$id);

});
$app->post('/websites/permission_delete', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (isset($_POST['user_id']) && isset($_POST['website_id'])) {
         (new \Controllers\Websites())->permissionDelete();
    }
    return $app->redirect('/websites');

});

$app->post('/websites/delete', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (isset($_POST['id']) && !!$_POST['id']) {
         (new \Controllers\Websites())->delete();
    }
    return $app->redirect('/websites');

});

$app->run();