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
use app\portal\model\TouristMsg;

class IndexController extends HomeBaseController
{
    public $noArticle=0;//是否显示文章选项卡 1：不显示  0：显示

    /**
     * 首页 最新图文列表
     */
    public function index()
    {
        $touristMsgModel = new TouristMsg;
        $touristMsgModel->writeTouristMsg();
        $where['t1.post_type']=array('eq',1);
        $where['t1.post_status']=array('eq',1);
        $data = Db::name('portal_post')->alias('t1')->where($where)
            ->field("t1.id,t1.more,t1.post_title,t1.post_excerpt,t1.published_time,t1.post_hits,t1.post_like,t3.id cid,t3.name")
            ->join('lg_portal_category_post t2','t1.id=t2.post_id','left')
            ->join('lg_portal_category t3','t2.category_id=t3.id','left')
            ->order(['t1.is_top'=>'desc','t1.published_time'=>'desc'])
            ->group('t1.id')
            ->paginate(12,false);
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
    /**
     * 搜索页 列表
     */
    public function search()
    {
        $where['post_type']=array('eq',1);
        $where['post_status']=array('eq',1);
        $count = Db::name('portal_post')->field('id')->count();
        $p = input('p')?input('p'):0; /*跳转页数*/
        $page_length=6;/*每页显示条数*/
        $page=page($count,$page_length,$p);
        $data = Db::name('portal_post')->alias('t1')->where($where)
            ->field("t1.id,t1.more,t1.post_title,t1.post_excerpt,t1.published_time,t1.post_hits,GROUP_CONCAT(t3.name separator ' | ') name")
            ->join('lg_portal_category_post t2','t1.id=t2.post_id','left')
            ->join('lg_portal_category t3','t2.category_id=t3.id','left')
            ->order('t1.published_time desc')
            ->group('t1.id')
            ->limit($page['page_offset'],$page_length)->select();
        $this->assign('tid',input('tid'));
        $this->assign('p',$page['p']);/*当前页码*/
        $this->assign('data', $data);
        return $this->fetch();
    }
}
