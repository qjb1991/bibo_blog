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
use app\portal\model\PortalCategoryModel;
use think\Db;

class ListController extends HomeBaseController
{
    public $noArticle=0;//是否显示文章选项卡 1：不显示  0：不显示
    /**
     * 文章列表页
     */
    public function index()
    {
        $id                  = $this->request->param('id', 0, 'intval');
        $portalCategoryModel = new PortalCategoryModel();
        $category = $portalCategoryModel->where('id', $id)->where('status', 1)->find();
        $where['t1.post_type']=array('eq',1);
        $where['t1.post_status']=array('eq',1);
        $where['t3.id']=array('eq',$category['id']);
        $data = Db::name('portal_post')->alias('t1')->where($where)
            ->field("t1.id,t1.more,t1.post_title,t1.post_excerpt,t1.published_time,t1.post_hits,t1.post_like,t3.id cid,t3.name")
            ->join('lg_portal_category_post t2','t1.id=t2.post_id','left')
            ->join('lg_portal_category t3','t2.category_id=t3.id','left')
            ->order(['t1.is_top'=>'desc','t1.published_time'=>'desc'])
            ->group('t1.id')
            ->paginate(12);
        $this->assign('page',$data->render());
        $this->assign('data', $data);
        $this->assign('nav',$category['name']);

        /*重设CEO优化内容*/
        $siteInfo = cmf_get_site_info();
        $siteInfo['site_seo_title'] = $category['seo_title']?$category['seo_title']:$siteInfo['site_seo_title'];
        $siteInfo['site_seo_keywords'] = $category['seo_keywords']?$category['seo_keywords']:$siteInfo['site_seo_keywords'];
        $siteInfo['site_seo_description'] = $category['seo_description']?$category['seo_description']: $siteInfo['site_seo_description'];
        $this->assign('site_info',$siteInfo);

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
