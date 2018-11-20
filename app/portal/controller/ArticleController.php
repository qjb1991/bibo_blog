<?php
namespace app\portal\controller;

use cmf\controller\HomeBaseController;
use app\portal\model\PortalCategoryModel;
use app\portal\service\PostService;
use app\portal\model\PortalPostModel;
use think\Db;

/**
 * Class ArticleController
 * @package app\portal\controller
 * 文章页
 */
class ArticleController extends HomeBaseController
{
    public $noArticle=0;//是否显示文章选项卡 1：不显示  0：显示
    public function index()
    {

        $portalCategoryModel = new PortalCategoryModel();
        $postService         = new PostService();

        $articleId  = $this->request->param('id', 0, 'intval');
        $categoryId = $this->request->param('cid', 0, 'intval');
        $article    = $postService->publishedArticle($articleId, $categoryId);

        if (empty($article)) {
            abort(404, '文章不存在!');
        }


        $prevArticle = $postService->publishedPrevArticle($articleId, $categoryId);
        $nextArticle = $postService->publishedNextArticle($articleId, $categoryId);

        if (empty($categoryId)) {
            $categories = $article['categories'];

            if (count($categories) > 0) {
                /*$this->assign('category', $categories[0]);*/
            } else {
                abort(404, '文章未指定分类!');
            }

        } else {
            $category = $portalCategoryModel->where('id', $categoryId)->where('status', 1)->find();

            if (empty($category)) {
                abort(404, '文章不存在!');
            }

            $this->assign('category', $category);
        }

        Db::name('portal_post')->where(['id' => $articleId])->setInc('post_hits');


        hook('portal_before_assign_article', $article);
        $this->assign('data', $article);
        $this->assign('prev_article', $prevArticle);
        $this->assign('next_article', $nextArticle);

        /*重设CEO优化内容*/
        $siteInfo = cmf_get_site_info();
        $siteInfo['site_seo_title'] = $article['post_title']?$article['post_title']:$siteInfo['site_seo_title'];
        $siteInfo['site_seo_keywords'] = $article['post_keywords']?$article['post_keywords']: $siteInfo['site_seo_keywords'];
        $this->assign('site_info',$siteInfo);

        /*相关文章 根据分类查找*/
        $tids = array();
        foreach ($article['categories'] as $v) {
            $tids[] = $v['id'];
        }
        $where['t1.post_type']=array('eq',1);
        $where['t1.post_status']=array('eq',1);
        $where['t2.category_id'] = array('in',$tids);
        $xg_article = Db::name('portal_post')->alias('t1')->where($where)
            ->field('t1.id,t1.post_title')
            ->join('lg_portal_category_post t2', 't1.id=t2.post_id', 'left')
            ->group('t1.id')
            ->order('t1.published_time desc')
            ->paginate(10);
        $this->assign('xg_data',$xg_article);

        /*是否展示右侧文章*/
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
    // 文章点赞
    public function doLike()
    {
        $articleId = $this->request->param('id', 0, 'intval');
        $canLike = cmf_check_user_action("post_like$articleId", 1,true,43200);
        if ($canLike) {
            Db::name('portal_post')->where(['id' => $articleId])->setInc('post_like');
            $like_num = Db::name('portal_post')->where(['id' => $articleId])->value('post_like');
            $this->success("赞好啦！",'',$like_num);
        } else {
            $this->error("您已赞过啦！");
        }
    }
}
