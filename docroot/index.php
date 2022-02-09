<?php
date_default_timezone_set("America/Belem");

const ROOT = __DIR__;

require_once ROOT . '/../vendor/autoload.php';

use Controllers\Analytics;
use Controllers\Users;
//use Illuminate\Database\Capsule\Manager as Capsule;
use Middleware\Authentication as MAuth;


//$capsule = new Capsule();
//$capsule->addConnection([
//    "driver" => "mysql",
//    "host" => "localhost",
//    "database" => "database",
//    "username" => "root",
//    "password" => "password",
//    "charset" => "utf8",
//    "collation" => "utf8_general_ci"
//]);
//$capsule->bootEloquent();


$application = new Silex\Application();

/* START CONFIGURATION */
$application['debug'] = true;

$application['config_path'] = __DIR__ . '/../config/config.json';
/* END CONFIGURATION */

$application['session.storage.options'] = [
    'cookie_lifetime' => 3600
];

//$application->before(function ($request, $application) {
//    MAuth::authenticate($request, $application);
//});

$application->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__ . '/../log/development.log',
));

$application->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/../views',
));

$application->register(new Silex\Provider\SessionServiceProvider());

$application['session']->all();

$application->get('/login', function (Silex\Application $application) {
    return $application['twig']->render('users/login.html.twig');
});

$application->post('/login', function (Silex\Application $application) {

    if ((isset($_POST['username']) && $_POST['username']) && (isset($_POST['password']) && $_POST['password'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $user = new \Model\User();
        if ($user->login($username, $password)) {
            return $application->redirect('/');
        }
    }
    return $application['twig']->render('users/login.html.twig');
});

$application->get('/logout', function (Silex\Application $application) {
    $application['session']->all();
    unset($_SESSION['Auth']);
    return $application->redirect('/login');
});


//function websites()
//{
//    return Controllers\Websites::list();
//}

$application->get('/api/websites', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    $data = Controllers\Websites::list();
    return $application->json($data);
});


/*
$application->get('/api/get-active-users-hrs.json/{ga}', function (Silex\Application $application, $ga) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }

    $service = Analytics::initialize();
    $websites = Controllers\Websites::list();

    $data = array();
    $params = array(
        'dimensions' => 'ga:dateHour',
        'sort' => '-ga:dateHour',
        'max-results' => '24',
    );

    try {

        $visitors = $service->data_ga->get(
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
            $current_datetime->add(new DateInterval('PT1H'));
        }

        $hrs[] = '...';
        $data = [
            'visitors' => (int)Analytics::visitors($service, $ga),
            'ga' => $ga,
            'name' => Controllers\Websites::websiteName($ga),
            'series' => $series,
            'hrs' => $hrs
        ];

    } catch (Google_Exception $e) {
        $application['monolog']->addError($e->getMessage());
    }
    return $application->json($data);
});

$application->get('/api/get-active-users-hrs.json', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    $service = Analytics::initialize();
    $websites = Controllers\Websites::list();
    $data = array();
    $params = array(
        'dimensions' => 'ga:dateHour',
        'sort' => '-ga:dateHour',
        'max-results' => '24',
    );
    if ($websites) {
        foreach ($websites as $website) {
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
                    $current_datetime->add(new DateInterval('PT1H'));
                }

                $hrs[] = '...';
                $data[] = [
                    'visitors' => (int)Analytics::visitors($service, $website['tracking_id']),
                    'ga' => $website['tracking_id'],
                    'name' => $website['name'],
                    'series' => $series,
                    'hrs' => $hrs
                ];
            } catch (Google_Exception $e) {
                $application['monolog']->addError($e->getMessage());
            }
        }
    }

    return $application->json($data);
});
*/

/*
$application->get('/api/get-active-users-online.json', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    $service = Analytics::initialize();
    $websites = Controllers\Websites::list();
    $data = $results = array();
    foreach ($websites as $website) {
        try {
            $visitors = Analytics::visitors($service, $website['tracking_id']); // $service->data_realtime->get($website['tracking_id'], 'rt:activeUsers');
            $results[$website['tracking_id']][$visitors] = $website['name'];
        } catch (Google_Exception $e) {
            $application['monolog']->addError($e->getMessage());
        }
    }
    asort($results);
    foreach ($results as $ga => $result) {
        $data[$ga]['visitors'] = key($result);
        $data[$ga]['name'] = $result[key($result)];

    }
    return $application->json($data);
});
*/
//$application->get('/', function (Silex\Application $application) {
//    return $application->redirect('/dashboard');
//});
//


$application->get('/visitors-online', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    return $application['twig']->render('default/visitors-online.html.twig',[
        'current' => 'visitors-online'
    ]);
});

//$application->get('/api/visitors-online.json', function (Silex\Application $application) {
//    if (!isset($_SESSION['Auth'])) {
//        return $application->redirect('/login');
//    }
//    $service = Analytics::initialize();
//    $websites = Controllers\Websites::list();
//    $items = $categories = $series = [];
//    if ($websites) {
//
//        foreach ($websites as $name => $website) {
//            $items[$website['name']] = (int)Analytics::visitors($service, $website['tracking_id']);
//            //var_dump($items[$website['name']]);exit();
////            try {
////                $items[$website['name']] = (int)Analytics::visitors($service, $website['tracking_id']);
////            } catch (Google_Exception $e) {
////                $application['monolog']->addError($e->getMessage());
////            }
//        }
//
//        arsort($items);
//        foreach ($items as $name => $visitors) {
//            $series[] = (int)$visitors;
//            $categories[] = $name;
//        }
//    }
//
//    $content = [
//        'series' => $series,
//        'categories' => $categories,
//    ];
//    return $application->json($content);
//});
/*
$application->get('/api/visitors-online.json/{ga}', function (Silex\Application $application, $ga) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    $service = Analytics::initialize();
    $websites = Controllers\Websites::list();
    $items = $categories = $series = [];

    try {
        $items[$websites[$ga]] = (int)Analytics::visitors($service, $ga);
    } catch (Google_Exception $e) {
        $application['monolog']->addError($e->getMessage());
    }

    arsort($items);
    foreach ($items as $name => $visitors) {
        $series[] = (int)$visitors;
        $categories[] = $name;
    }

    $data = [
        'ga' => $ga,
        'series' => $series,
        'categories' => $categories,
    ];
    return $application->json($data);
});
*/

$application->get('/websites', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    return $application['twig']->render('websites/index.html.twig', array(
        'data' => \Controllers\Websites::list(),
        'isAdmin' => $_SESSION['Auth']['admin'],
        'current' => 'websites'
    ));
});

$application->get('/websites/add', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    return $application['twig']->render('websites/add.html.twig', array(
        'data' => ['user_id' => $_SESSION['Auth']['id']],
        'current' => 'websites'
    ));
});

$application->post('/websites/add', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    if (isset($_POST['tracking_id']) && !!$_POST['tracking_id']) {
        $website = new \Controllers\Websites();
        $website->store();
        return $application->redirect('/websites');
    }
    return false;
});

$application->get('/websites/edit/{id}', function (Silex\Application $application, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    if ($id) {
        $data = (new \Controllers\Websites())->edit($id);
        if (!!$data) {
            return $application['twig']->render('websites/edit.html.twig', array(
                'data' => $data,
                'current' => 'websites'
            ));
        }
    }
    return $application->redirect('/websites');
});

$application->post('/websites/edit/{id}', function (Silex\Application $application, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    if ($id) {
        if ((new \Controllers\Websites())->update($id)) {
            return $application->redirect('/websites');
        }
    }
    return false;
});

$application->post('/websites/permission/{id}', function (Silex\Application $application, $id) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    if (!$_SESSION['Auth']['admin']) {
        return $application->redirect('/websites');
    }
    if (isset($_POST['user_id']) && isset($_POST['website_id'])) {
        (new \Controllers\Websites())->permissionAdd();
    }
    return $application->redirect('/websites/permission/' . $id);

});

$application->post('/websites/permission_delete', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    if (isset($_POST['user_id']) && isset($_POST['website_id'])) {
        (new \Controllers\Websites())->permissionDelete();
    }
    return $application->redirect('/websites');

});

$application->post('/websites/delete', function (Silex\Application $application) {


    if (isset($_POST['id']) && !!$_POST['id']) {
        (new \Controllers\Websites())->delete();
    }
    return $application->redirect('/websites');
});


//$api = new Silex\Application();

$application->get('/', function (Silex\Application $application) {
    if (!isset($_SESSION['Auth'])) {
        return $application->redirect('/login');
    }
    return $application->redirect('/dashboard');
});

$application->mount('/api/analytics', new Controllers\Api\ApiAnalytics());
$application->mount('/dashboard', new Controllers\Dashboard());
$application->mount('/users', new Controllers\Users());
$application->mount('/websites', new Controllers\Websites());
//$api->run();
$application->run();