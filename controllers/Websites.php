<?php

namespace Controllers;

use Model\UserWebsite;
use Model\Website;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class Websites implements ControllerProviderInterface
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
        $controllers->get(
            '/add',
            array($this, 'add')
        );
        $controllers->get(
            '/edit/{id}',
            array($this, 'edit')
        );
        $controllers->get(
            '/permission/{id}',
            array($this, 'permission')
        );

        $controllers->post(
            '/permission/{id}',
            array($this, 'permissionAdd')
        );

        $controllers->post(
            '/permission_delete',
            array($this, 'permissionDelete')
        );

        $controllers->post(
            '/add',
            array($this, 'store')
        );
        $controllers->post(
            '/edit/{id}',
            array($this, 'update')
        );

        $controllers->delete(
            '/delete/{id}',
            array($this, 'delete')
        );

        return $controllers;
    }

    public function index()
    {
        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        return $this->app['twig']->render('websites/index.html.twig', array(
            'data' => \Controllers\Websites::list(),
            'isAdmin' => $_SESSION['Auth']['admin'],
            'current' => 'websites'
        ));
    }

    static public function list($limit = false)
    {
        $session = (new Session())->get('Auth');
        $user_id = $session['id'];

        if ( $limit ) {
            $sql = "
                select  *
                from user_websites
                join websites w on w.id = user_websites.website_id
                where user_id = {$user_id};
            ";
        } else {
            $sql = "
                select  *
                from websites
            ";
        }

        $rows = (new Website())->select($sql);

        $data = [];
        if ($rows) {
            foreach ($rows as $row) {
                if (!!$row['id']) {
                    $data[$row['tracking_id']]['id'] = $row['id'];
                }
                if (!!$row['tracking_id']) {
                    $data[$row['tracking_id']]['tracking_id'] = $row['tracking_id'];
                }
                if (!!$row['name']) {
                    $data[$row['tracking_id']]['name'] = $row['name'];
                }
                if (!!$row['user_id']) {
                    $data[$row['tracking_id']]['user_id'] = $row['user_id'];
                }

            }
        }

        return $data;
    }

    static public function websiteName($ga)
    {
        $website = self::list();
        return $website[$ga]['name'];
    }

    public function store()
    {
        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        $website = new Website();
        $sql = 'SELECT count(*)
                FROM  websites 
                where tracking_id = "' . $_POST['tracking_id'] . '"';
        $query = $website->query($sql);
        $row = $query->fetchArray();
        if (!$row[0]) {
            $sql = "
            INSERT INTO websites (tracking_id, name) 
            VALUES (
                '" . trim($_POST['tracking_id']) . "', 
                '" . trim($_POST['name']) . "'
            )";

            if ($website->exec($sql)) {
                if (!$_SESSION['Auth']['admin']) {
                    $sql = "
                    INSERT INTO user_websites (website_id, user_id) 
                    VALUES (
                        '" . $website->lastInsertRowID() . "', 
                        '" . trim($_SESSION['Auth']['id']) . "'
                    )";
                    $website->exec($sql);
                }
            }
        }
    }

    public function permission($id)
    {
        if (!isAuth()){
            return $this->app->redirect('/login');
        }

        $user = new \Model\User();
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '"';
        $result['website'] = $website->select($sql)[0];

        $sql = "
        select user_websites.id, name, username,email, user_id
        from user_websites
        join users u on u.id = user_websites.user_id
        where website_id = {$id} ";

        $result['user_websites'] = $website->select($sql);

        $userOptions = [];

        if (!!$result['user_websites']) {
            $userIds = [];
            foreach ($result['user_websites'] as $value) {
                $userIds[] = $value['user_id'];
            }
            $userOptions = [
                'conditions' => [
                    'where' => 'id NOT IN(' . implode(',', $userIds) . ')',
                ]
            ];
        }
        $result['list_users'] = $user->list($userOptions);

        return $this->app['twig']->render('websites/permission.html.twig', [
            'data' => $result,
            'current' => 'websites'
        ]);
    }


    public function edit($id)
    {
        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '"';
        $result = $website->select($sql);
        return $result[0] ?? [];
    }

    public function update($id)
    {
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . trim($id) . '";';
        $item = $website->select($sql);

        if ($item) {
            $sql = 'UPDATE websites 
            SET  name = "' . trim($_POST['name']) . '" 
            where id = "' . trim($id) . '"';
            $website->exec($sql);
            return true;
        }

        return false;
    }

    public function permissionAdd($id)
    {

        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        $website = new Website();
        $sql = '
            SELECT * 
            FROM  user_websites 
            WHERE user_id = "' . trim($_POST['user_id']) . '"
            AND website_id = "' . trim($_POST['website_id']) . '"
        ';
        $item = $website->select($sql);
        if (!isset($item[0]['user_id'])) {

            $sql = "
            INSERT INTO user_websites (user_id, website_id) 
            VALUES (
                '" . trim($_POST['user_id']) . "', 
                '" . trim($_POST['website_id']) . "'
            )";
            $website->query($sql);
        }
        return $this->app->redirect('/websites/permission/'.$id);
    }



    public function permissionDelete($id)
    {
        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        $userWebsite = new UserWebsite();
        $response['status'] = $userWebsite->delete($id);
        return $this->app->json($response);
    }

    public function delete()
    {

        if (!isAuth()){
            return $this->app->redirect('/login');
        }
        $website = new Website();
        $sql = 'SELECT * FROM  websites where id = "' . $_POST['id'] . '";';
        $item = $website->select($sql);
        if (isset($item[0])) {
            $sql = 'DELETE FROM websites WHERE id = "' . $_POST['id'] . '"';
            if ($website->query($sql)) {
                $sql = '
                        DELETE FROM user_websites
                        WHERE website_id = "' . $_POST['id'] . '"
                    ';
                $website->query($sql);
            }
            return true;
        }
        return false;
    }
}