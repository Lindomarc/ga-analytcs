<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../core/JWTWrapper.php';

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
    $service = null;
    try {
        $config = $app['config']->google_api;
        $client = new Google_Client();
        $client->setApplicationName($config->app_name);
        $client->setClientId($config->client_id);
        $service = new Google_Service_Analytics($client);
        $key = file_get_contents(__DIR__ . $config->key_path);
        $cred = new Google_Auth_AssertionCredentials(
            $config->username,
            array(Google_Service_Analytics::ANALYTICS_READONLY),
            $key
        );
        $client->setAssertionCredentials($cred);
    } catch (Google_Exception $e) {
        $app['monolog']->addError($e->getMessage());
        exit(1);
    }
    return $service;
});

$app['websites'] = $app->share(function ($app) {
    /* @var Google_Service_Analytics $service */
    $config = $app['config'];
    $service = $app['google_api_service'];
    $websites = array();
    $tracking_codes = websites();
    try {
        $web_properties = $service->management_webproperties->listManagementWebproperties('~all');
        foreach ($web_properties->getItems() as $property) {
            foreach ($tracking_codes as $code) {
                if ($property->id === $code['tracking_id']) {
                    $websites[$code['name']] = 'ga:' . $property->defaultProfileId;
                }
            }
        }
    } catch (Google_Exception $e) {
        $app['monolog']->addError($e->getMessage());
    }
    return $websites;
});

function websites()
{
    require_once __DIR__ . '/../config/dbconfig.php';
    $sql = 'SELECT tracking_id, name FROM  websites';
    $results = $db->query($sql);
    $data = [];
    $count = 0;
    while ($row = $results->fetchArray()) {
        if (!!$row['tracking_id']) {
            $data[$count]['tracking_id'] = $row['tracking_id'];
        }
        if (!!$row['name']) {
            $data[$count]['name'] = $row['name'];
        }
        $count++;
    }

    return $data;
}

$app->get('/api/websites', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $content = json_encode(websites());
    $headers = array('Content-Type' => 'application/json');
    return new Response($content, 200, $headers);
});

$app->get('/api/getuserslastday.csv', function (Silex\Application $app) {
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
        'max-results' => '25',
    );

    foreach ($websites as $name => $code) {
        $visitors = null;

        try {
            $visitors = $service->data_ga->get($code, 'yesterday', 'today', 'ga:users', $params);
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
                    'website' => $name,
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

$app->get('/api/getactiveusers.json', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    /* @var Google_Service_Analytics $service */
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $data = array();

    foreach ($websites as $name => $code) {
        try {
            $visitors = $service->data_realtime->get($code, 'rt:activeUsers');
            $data[$name] = ($visitors->getTotalResults() > 0) ? $visitors->getRows()[0][0] : 0;
        } catch (Google_Exception $e) {
            $app['monolog']->addError($e->getMessage());
        }
    }

    $content = $app['twig']->render('api/getactiveusers.json.twig', array(
        'data' => $data,
    ));

    $headers = array('Content-Type' => 'application/json');
    $response = new Response($content, 200, $headers);
    return $response;
});

$app->get('/', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $config = $app['config']->display;
    return $app['twig']->render('default/index.html.twig', array(
        'horizontal_tiles' => $config->horizontal_tiles,
        'vertical_tiles' => $config->vertical_tiles,
    ));
});

$app->get('/visitantes-online', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    /* @var Google_Service_Analytics $service */
    $service = $app['google_api_service'];
    $websites = $app['websites'];
    $items = array();

    foreach ($websites as $name => $code) {
        try {
            $visitors = $service->data_realtime->get($code, 'rt:activeUsers');
            $items[$name] = ($visitors->getTotalResults() > 0) ? $visitors->getRows()[0][0] : 0;
        } catch (Google_Exception $e) {
            $app['monolog']->addError($e->getMessage());
        }
    }

    $categories = [];
    foreach ($items as $caregory => $serie) {
        $categories[] = $caregory;
        $series[] = (int)$serie;
    }


    return $app['twig']->render('default/highcharts.html.twig', array(
        'categories' => json_encode($categories),
        'series' => json_encode($series)
    ));
});

$app->get('/websites', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    return $app['twig']->render('websites/index.html.twig', array(
        'data' => websites()
    ));
});

$app->get('/websites/add', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    $config = $app['config']->display;
    return $app['twig']->render('websites/add.html.twig', array(
        'horizontal_tiles' => $config->horizontal_tiles,
        'vertical_tiles' => $config->vertical_tiles,
    ));
});

$app->post('/websites/add', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (isset($_POST['tracking_id']) && !!$_POST['tracking_id']) {
        require_once __DIR__ . '/../config/dbconfig.php';
        $sql = 'SELECT count(*) 
                FROM  websites where tracking_id = "' . $_POST['tracking_id'] . '"';
        $query = $db->query($sql);
        $row = $query->fetchArray();
        if (!$row[0]) {
            $sql = "INSERT INTO websites (tracking_id, name) 
                    VALUES ('" . $_POST['tracking_id'] . "', '" . trim($_POST['name']) . "')";
            $db->exec($sql);
            header('Location: /websites');

        }
        return $app['twig']->render('websites/add.html.twig', array());
    }
});

$app->get('/websites/edit/{item}', function (Silex\Application $app, $item) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }

    if ($item) {
        require_once __DIR__ . '/../config/dbconfig.php';
        $sql = 'SELECT * FROM  websites where tracking_id = "' . trim($item) . '"';
        $query = $db->query($sql);
        $data = $query->fetchArray();

        if (!!$data) {
            return $app['twig']->render('websites/edit.html.twig', array(
                'data' => $data
            ));
        }
        header('Location: /websites');

    }
});

$app->post('/websites/edit/{item}', function (Silex\Application $app, $item) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if ($item) {
        require_once __DIR__ . '/../config/dbconfig.php';
        $sql = 'UPDATE websites SET  name = "' . $_POST['name'] . '" where tracking_id = "' . $item . '"';
        $db->exec($sql);

        $sql = 'SELECT * FROM  websites where tracking_id = "' . trim($item) . '"';
        $query = $db->query($sql);
        $data = $query->fetchArray();

        if (!!$data) {
            return $app['twig']->render('websites/edit.html.twig', array(
                'data' => $data
            ));
        }
        header('Location: /websites');

    }
});

$app->post('/websites/delete', function (Silex\Application $app) {
    if (!isset($_SESSION['Auth'])) {
        return $app->redirect('/login');
    }
    if (isset($_POST['tracking_id']) && !!$_POST['tracking_id']) {
        require_once __DIR__ . '/../config/dbconfig.php';

        $sql = 'DELETE FROM websites WHERE tracking_id = "' . $_POST['tracking_id'] . '"';
        if ($db->query($sql)) {
            $data = ['message' => 'Removido com sucesso', 'status' => true];
        } else {
            $data = ['message' => 'Não pode ser removido', 'status' => false];
        }

        $content = $app['twig']->render('api/getactiveusers.json.twig', array(
            'data' => $data,
        ));

        $headers = array('Content-Type' => 'application/json');
        $response = new Response($content, 200, $headers);
        return $response;
    }
});

$app->run();