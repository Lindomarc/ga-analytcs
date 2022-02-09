<?php
namespace  Model;
class  Website extends DB
{
    public $table = 'websites';

    public function getUserWebsites($userId)
    {
        $sql = '
        SELECT  websites.id, websites.name ,tracking_id 
        FROM user_websites
        JOIN websites 
            ON websites.id = user_websites.website_id
        WHERE user_id = ' . $userId;
        $website = new Website();
        $result['websites'] = $website->select($sql);

        $userOptions = [];
        if (!!$result['websites']) {
            foreach ($result['websites'] as $website) {
                $websitesIds[] = $website['id'];
            }
            $ids = implode(',', $websitesIds);
            $userOptions = [
                'fields' => 'id,name',
                'conditions' => [
                    'where' => 'id NOT IN(' . $ids . ')'
                ]
            ];
        }
        $website = new Website();
        $result['list_websites'] = $website->list($userOptions);
        return $result;
    }
}
