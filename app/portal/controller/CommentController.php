<?php
namespace app\portal\controller;
use cmf\controller\HomeBaseController;
use think\Db;

/**
 * Class CommentController
 * @package app\portal\controller
 * 用户留言
 */
class CommentController extends HomeBaseController
{
    public $noArticle=1;//是否显示文章选项卡 1：不显示  0：显示
    public function index()
    {
        $this->assign('nav','用户留言');
        $data = Db::name('comment')->where('is_show',1)->order(['time'=>'desc'])->paginate(20);
        $this->assign('page',$data->render());
        $this->assign('data',$data);

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
     * 留言提交
     */
    public function index_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (cmf_captcha_check($data['code'])) {
                if (empty($data['user_name'])) {
                    $this->error('请输入用户名');exit;
                }
                if (isset($data['user_email']) && $data['user_email']) {
                    $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
                    $match=preg_match($pattern, $data['user_email'], $matches);
                    if (!$match) {
                        $this->error('邮箱格式错误');exit;
                    }
                }
                unset($data['code']);
                $data['time']=time();
                $add = Db::name('comment')->insert($data);
                if ($add) {
                    $this->success('留言成功，等待站长审核');
                }else{
                    $this->error('留言失败');
                }
            } else {
                $this->error('验证码不正确');
            }
        }else{
            $this->error('请求有误');
        }
    }
}