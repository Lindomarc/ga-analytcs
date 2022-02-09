<?php

namespace Controllers;

use Model\User;
use Model\UserWebsite;
use Model\Website;
use Silex\Application;
use Silex\ControllerProviderInterface;

class Users implements ControllerProviderInterface
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
        $options = [
            'fields' => 'id,name,email'
        ];
        $results = (new User())->list($options);

        return $this->app['twig']->render('users/index.twig', [
            'data' => ['users' => $results],
            'current' => '/dashboard'
        ]);

    }

    public function add()
    {
        return $this->app['twig']->render('users/add.twig', [
            'current' => 'users'
        ]);
    }

    public function edit($id)
    {
        $user = new User();
        $result = $user->getId($id);
        return $this->app['twig']->render('users/edit.twig', [
            'data' => [
                'id' => $result['id'] ?? '',
                'name' => $result['name'] ?? '',
                'username' => $result['username'] ?? '',
                'email' => $result['email'] ?? '',
            ],
            'current' => 'users'
        ]);
    }

    public function permission($id)
    {
        if (!$_SESSION['Auth']['admin']) {
            return $this->app->redirect('/users');
        }
        $website = new Website();
        $result = $website->getUserWebsites($id);

        $user = new \Model\User();
        $result['user'] = $user->getId($id);


//        $sql = '
//        SELECT  websites.id, websites.name ,tracking_id
//        FROM user_websites
//        JOIN websites
//            ON websites.id = user_websites.website_id
//        WHERE user_id = ' . $id;
//        $website = new Website();
//        $result['websites'] = $website->select($sql);
//
//        $userOptions = [];
//        if (!!$result['websites']) {
//            foreach ($result['websites'] as $website) {
//                $websitesIds[] = $website['id'];
//            }
//            $ids = implode(',', $websitesIds);
//            $userOptions = [
//                'fields' => 'id,name',
//                'conditions' => [
//                    'where' => 'id NOT IN(' . $ids . ')'
//                ]
//            ];
//        }
//        $website = new Website();
//        $result['list_websites'] = $website->list($userOptions);


        return $this->app['twig']->render('users/permission.twig', [
            'data' => $result,
            'current' => 'users'
        ]);
    }

    public function permissionAdd($id)
    {
        $data = $_POST;
        $userOptions = [
            'conditions' => [
                'where' => 'user_id =' . $id,
                'and' => 'website_id =' . $data['website_id']
            ]
        ];
        $userWebsite = new UserWebsite();
        $userWebsites = $userWebsite->list($userOptions);

        if(!$userWebsites){
            $userWebsite = new UserWebsite();
            $userWebsite->save($data);
        }
        return $this->app->redirect('/users/permission/'.$id);

    }


    public function store()
    {
        $user = new User();
        $data = $_POST;
        if ($user->unique(['email', 'username'], $data) && !!$data['password']) {
            $data['password'] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            if ($user->save($data)) {
                return $this->app->redirect('/users');
            }
        }
        return $this->app->redirect('/users/add');
    }

    public function update($id)
    {
        $user = new User();
        $data = $_POST;
        $data['id'] = $id;
        if ($user->unique(['email', 'username'], $data)) {
            if (!!$data['password']) {
                $data['password'] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }
            $user->update($data);
        }
        return $this->app->redirect('/users/edit/' . $id);
    }

    public function delete($id)
    {
        $user = new User();
        $response['status'] = $user->delete($id);
        return $this->app->json($response);
    }

}