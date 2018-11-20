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
use app\portal\service\PostService;
use FontLib\Table\Type\name;
use think\Db;

class PageController extends HomeBaseController
{
    public $noArticle=1;//是否显示文章选项卡 1：不显示  0：不显示

    public function _initialize()
    {
        if ($this->noArticle!=1) {
            /*文章展示*/
            $this->assign('data_newest',$this->newest()); //最新文章
            $this->assign('data_recommended',$this->recommend());  //推荐文章
            $this->assign('data_ranking', $this->ranking());   //点击量排行文章
            $this->assign('data_headline', $this->headline());  //置顶，头条文章
        }
        $this->assign('noArticle',$this->noArticle);
    }

    /**
     * 单页页面
     */
    public function index()
    {
        $postService = new PostService();
        $pageId      = $this->request->param('id', 0, 'intval');
        $page        = $postService->publishedPage($pageId);
        if (empty($page)) {
            abort(404, ' 页面不存在!');
        }
        $this->assign('page', $page);

        /*重设CEO优化内容*/
        $siteInfo = cmf_get_site_info();
        $siteInfo['site_seo_title'] = $page['post_title']?$page['post_title']: $siteInfo['site_seo_title'];
        $siteInfo['site_seo_keywords'] = $page['post_keywords']?$page['post_keywords']:$siteInfo['site_seo_keywords'];
        $this->assign('site_info',$siteInfo);

        $more = $page['more'];
        $tplName = empty($more['template']) ? 'index' : $more['template'];
        return $this->fetch("$tplName");
    }

    /**
     * 时间轴
     */
    public function time_line()
    {
        /*重设CEO优化内容*/
        $siteInfo = cmf_get_site_info();
        $this->assign('site_info',$siteInfo);

        $where['post_type']=array('eq',1);
        $where['post_status']=array('eq',1);
        $data = Db::name('portal_post')->where($where)
            ->field("id,post_title,published_time")
            ->order(['published_time'=>'desc','id'=>'desc'])
            ->paginate(18,false);
        $this->assign('page',$data->render());
        $this->assign('data', $data);
        $this->assign('nav','时间轴');
        return $this->fetch();
    }

}
