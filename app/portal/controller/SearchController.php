<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use think\Db;

class SearchController extends HomeBaseController
{
    public $noArticle=0;//是否显示文章选项卡 1：不显示  0：显示
    public function index()
    {
        $keyword = $this->request->param('keyword');
        if (!$keyword) {
            $this -> error("关键词不能为空！请重新输入！");
        }
        $this -> assign("keyword", $keyword);
        $this->assign('nav','内容搜索');
        $config['query'] = array('keyword'=>$keyword);
        $where['t1.post_type']=array('eq',1);
        $where['t1.post_status']=array('eq',1);
        $where['t1.post_title|t1.post_keywords|t3.name|t3.seo_keywords|t3.seo_title']=array('like','%'.$keyword.'%');
        $data = Db::name('portal_post')->alias('t1')->where($where)
            ->field("t1.id,t1.more,t1.post_title,t1.post_excerpt,t1.published_time,t1.post_hits,t1.post_like,t3.id cid,t3.name")
            ->join('lg_portal_category_post t2','t1.id=t2.post_id','left')
            ->join('lg_portal_category t3','t2.category_id=t3.id','left')
            ->order(['t1.published_time'=>'desc'])
            ->group('t1.id')
            ->paginate(12,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data', $data);


        if ($this->noArticle!=1) {
            /*文章展示*/
            $this->assign('data_newest',$this->newest()); //最新文章
            $this->assign('data_recommended',$this->recommend());  //推荐文章
            $this->assign('data_ranking', $this->ranking());   //点击量排行文章
            $this->assign('data_headline', $this->headline());  //置顶，头条文章
        }
        $this->assign('noArticle',$this->noArticle);
        return $this->fetch();
    }
}
