<?php

namespace app\portal\model;

use think\Model;
use think\Db;

class TouristMsg extends Model
{
    public function writeTouristMsg()
    {
        $ip = get_client_ip(0, true);
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $condition = [
            'ip' => $ip,
            'create_at' => ['between', [$beginToday, $endToday]]
        ];
        $result = Db::table('lg_tourist_msg')
            ->where($condition)
            ->find();
        if ($result) {
            Db::table('lg_tourist_msg')->where('ip', $ip)->setInc('login_sum');
        } else {
            $data = [
                'ip' => $ip,
                'create_at' => time(),
            ];
            $re = Db::table('lg_tourist_msg')->insert($data);
        }

    }
}
