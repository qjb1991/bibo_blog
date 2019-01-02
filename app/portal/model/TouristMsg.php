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
            $url = 'http://ip.taobao.com/service/getIpInfo.php?ip='.$ip;
            // $ipMsg = file_get_contents($url, false); 
            $ipMsg = $this->http_get($url);
            $data = [
                'ip' => $ip,
                'create_at' => time(),
                'login_sum' => 1
            ];
            if (!empty($ipMsg)) {
                $ipMsg = json_decode($ipMsg, true);
                if ($ipMsg['code'] === 0) {
                    $data['city'] = $ipMsg['data']['country'].$ipMsg['data']['region'].$ipMsg['data']['city'];
                }
            }
            $re = Db::table('lg_tourist_msg')->insert($data);
        }

    }

    public function http_get($url)
    {
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出 (关闭)
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        //显示获得的数据
        return $data;
    }
}
