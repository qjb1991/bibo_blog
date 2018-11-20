<?php
header("Content-Type: text/html;charset=utf-8");
$res= new Ranking;
$res->index($_GET['keyword'],$_GET['url'],$_GET['page']);
class Ranking
{
    public function index($keyword,$url,$page = 1)
    {
        $arr=explode(',',$keyword);
        foreach ($arr as $v) {
            echo "<hr/>地址：".$url."；关键字：".$v.'<hr/>';
            echo $this->baidu($v, $url,$page);
            echo $this->mbaidu($v, $url,$page);
            echo $this->R360($v, $url,$page);
            echo $this->MR360($v, $url,$page);
            echo $this->sogou($v, $url,$page);
            echo $this->mSogou($v, $url,$page);
            echo $this->bing($v, $url,$page);
            echo $this->sm($v, $url,$page);
        }
    }
    /**
     * curl get 请求
     * @param $url
     * @return mixed
     */
    public function cmf_curl_get($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $SSL = substr($url, 0, 8) == "https://" ? true : false;
        if ($SSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
        }
        $content = curl_exec($ch);
        curl_close($ch);
        return $content;
    }

    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * PC百度
     */
    public function baidu($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;
        if($page > 3){
            return '百度10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("http://www.baidu.com/s?wd=$enKeyword&pn=$firstRow&ie=utf-8");
        preg_match_all("/<div class=\"f13\">.*?<\/a>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '百度搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='http://www.baidu.com/s?wd=$enKeyword&pn=$firstRow&ie=utf-8'>进入百度搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->baidu($keyword, $url,++$page);
        }
    }

    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * 手机百度
     */
    public function mBaidu($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 3){
            return '手机百度10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://m.baidu.com/s?word=$enKeyword&pn=$firstRow");
        preg_match_all("/<div class=\"c-showurl c-line-clamp1\">.*?<\/a>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '手机百度搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://m.baidu.com/s?word=$enKeyword&pn=$firstRow'>进入手机百度</a><br/>";
        }else{
            /*没结果翻页*/
            $this->mBaidu($keyword, $url,++$page);
        }
    }

    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * PC360
     */
    public function R360($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 3){
            return '360搜索10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://www.so.com/s?q=$enKeyword&pn=$page");
        preg_match_all("/<p class=\"res-linkinfo\">.*?<\/cite>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '360搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://www.so.com/s?q=$enKeyword&pn=$page'>进入360搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->R360($keyword, $url,++$page);
        }
    }
    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * 移动端360
     */
    public function MR360($keyword,$url,$page = 1){
        $url=str_replace("www.","",$url);
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;
        if($page > 3){
            return '手机360搜索10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://m.so.com/s?q=$enKeyword&pn=$page&ajax=1");
        preg_match_all("/<div class=\"res-supplement\">.*?<\/cite>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '手机360搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://m.so.com/s?q=$enKeyword&pn=$page'>进入手机360搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->MR360($keyword, $url,++$page);
        }
    }
    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * PC搜狗
     */
    public function sogou($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 3){
            return '搜狗搜索10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://www.sogou.com/web?query=$enKeyword&page=$page&ie=utf8");
        preg_match_all("/<div class=\"fb\">.*?<\/cite>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '搜狗搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://www.sogou.com/web?query=$keyword&page=$page&ie=utf8'>进入搜狗搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->sogou($keyword, $url,++$page);
        }
    }

    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * 移动搜狗
     */
    public function mSogou($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 3){
            return '手机搜狗搜索10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://m.sogou.com/web/searchList.jsp?keyword=$enKeyword&p=$page");
        preg_match_all("/<span class=\"site\">.*?<\/span>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '手机搜狗搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://m.sogou.com/web/searchList.jsp?keyword=$enKeyword&p=$page'>进入手机搜狗搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->mSogou($keyword, $url,++$page);
        }
    }

    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * PC必应/移动端  必应是响应式  代码排名等都一样
     */
    public function bing($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        if ($page==1) {
            $firstRow=1;
        } else{
            $firstRow = ($page - 2) * 10+6;
        }

        if($page > 3){
            return '必应10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://cn.bing.com/search?q=$enKeyword&first=$firstRow");
        preg_match_all("/<li class=\"b_algo\">.*?<\/a>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=($page-1)*10+$px;
            return '必应搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://cn.bing.com/search?q=$enKeyword&first=$firstRow'>进入必应搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->bing($keyword, $url,++$page);
        }
    }
    /**
     * @param $keyword string 关键字
     * @param $url string 网址
     * @param int $page 页数
     * 神马  神马是专门的移动搜索  没有PC端
     */
    public function sm($keyword,$url,$page = 1){
        static $px = 0;  //排名
        $rsState = false;
        $enKeyword = urlencode($keyword);
        $firstRow = ($page - 1) * 10;

        if($page > 3){
            return '神马10页之内没有该网站排名..end';
        }
        $contents = $this->cmf_curl_get("https://m.sm.cn/s?q=$enKeyword&page=$page");
        preg_match_all("/<div class=\"other\">.*?<\/div>/ism",$contents,$rs);
        $pk=0;/*所在页排名*/
        foreach($rs[0] as $k=>$v){
            $px++;
            $pk=$k;
            if(strstr($v,$url)){  /*有结果终止循环*/
                $rsState = true;
                break;
            }
        }
        unset($contents);
        if ($rsState === true) {
            $px=$firstRow+$px;
            return '神马搜索排名：'.$px.'；第' . $page . '页，第' . ++$pk . "个.....<a target='_blank' href='https://m.sm.cn/s?q=$enKeyword&page=$page'>进入神马搜索</a><br/>";
        }else{
            /*没结果翻页*/
            $this->sm($keyword, $url,++$page);
        }
    }


}