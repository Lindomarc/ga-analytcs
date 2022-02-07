<?php

namespace Controllers;

use Google_Auth_AssertionCredentials;
use Google_Client;
use Google_Service_Analytics;

class Analytics
{

    static public function initialize()
    {

        $config = self::config();
        $certification = file_get_contents(ROOT .$config->google_api->key_path);

        $client = new Google_Client();
        $client->setApplicationName($config->app_name);
        $client->setClientId($config->client_id);
        $client->setAssertionCredentials(
            new Google_Auth_AssertionCredentials(
                'api-jornais@ga-jornais.iam.gserviceaccount.com',
                array('https://www.googleapis.com/auth/analytics.readonly'),
                $certification
            )
        );
        return new Google_Service_Analytics($client);
    }

    static public function config()
    {
        $config_path = ROOT . '/../config/config.json';
        $json = file_get_contents($config_path);
        return json_decode($json);
    }

    static public function visitors( $service, $GA_VIEW_ID = null)
    {
        $result = $service->data_realtime->get(
            'ga:' . $GA_VIEW_ID,
            'rt:activeVisitors'
        );
        return $result->totalsForAllResults['rt:activeVisitors'];
    }
}