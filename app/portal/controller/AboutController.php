<?php
namespace app\portal\controller;
use cmf\controller\HomeBaseController;
use app\portal\service\PostService;
use think\Db;

class AboutController extends HomeBaseController
{
    /**
     * 个人简介
     */
    public function index()
    {
        $postService = new PostService();
        $pageId      = $this->request->param('id', 12, 'intval');
        $page        = $postService->publishedPage($pageId);

        if (empty($page)) {
            abort(404, ' 页面不存在!');
        }
        $this->assign('page', $page);
        return $this->fetch();
    }

    /**
     * 相册
     */
    public function photo()
    {
        return $this->fetch();
    }
}