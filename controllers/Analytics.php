<?php
namespace Controllers;

class Analytics
{

    static public function initialize()
    {
        $config = self::config();
        $certification = file_get_contents(ROOT .$config->google_api->key_path);

        $client = new \Google_Client();
      //  $client->setApplicationName($config->app_name);
        $client->setClientId($config->google_api->client_id);

        $client->setAssertionCredentials(
            new \Google_Auth_AssertionCredentials(
                $config->google_api->username,
                array('https://www.googleapis.com/auth/analytics.readonly'),
                $certification
            )
        );
        return new \Google_Service_Analytics($client);
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

    static public function pageviews()
    {
        $config = self::config();
        $analytics = Analytics::initialize();
        $GA_VIEW_ID = $config->ga;
        $result = array();
        try {
            for ($j = 5; $j >= 0; $j--) {
                $start_date = date('Y-m', strtotime("-$j month")) . '-01';
                $d = new \DateTime($start_date);
                $end_date = $d->format('Y-m-t');
                $optParams = array(
                    'dimensions' => 'ga:source',
                    'filters' => 'ga:medium==organic',
                    'metrics' => 'ga:sessions'
                );
                $result['organic'][$j] = $analytics->data_ga->get(
                    'ga:' . $GA_VIEW_ID,
                    $start_date,
                    $end_date,
                    'ga:sessions',
                    $optParams
                );
                $optParams = array(
                    'dimensions' => 'ga:source',
                    'filters' => 'ga:medium!=organic',
                    'metrics' => 'ga:sessions'
                );
                $result['nonorganic'][$j] = $analytics->data_ga->get(
                    'ga:' . $GA_VIEW_ID,
                    $start_date,
                    $end_date,
                    'ga:sessions',
                    $optParams
                );
            }
        } catch (\Google_Exception $e) {
            return $e;
        } catch (\Exception $e) {
        }
        return $result;
    }
}
//Analytics::pageviews();