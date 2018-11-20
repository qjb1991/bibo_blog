<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

/**
 * Class CommentController
 * @package app\admin\controller
 * 留言管理
 */
class CommentController extends AdminBaseController
{
    /**
     * 留言列表
     */
    public function index()
    {
        $keyword=$this->request->param('keyword');
        $this->assign('keyword',$keyword);
        $config['query'] = array('keyword'=>$keyword);
        $where['user_name|user_email|content'] = array('like','%'.$keyword.'%');
        $data = Db::name('comment')->where($where)->order(['time'=>'desc'])->paginate(15,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 留言编辑/回复
     */
    public function edit()
    {
        $id=$this->request->param('id');
        $data = Db::name('comment')->where('id', $id)->find();
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 留言编辑/回复提交
     */
    public function edit_post()
    {
        if ($this->request->isPost()) {
            $data=$this->request->param();
            $data['time'] = strtotime($data['time']);
            $save = Db::name('comment')->update($data);
            if ($save) {
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 留言编辑/回复审核
     */
    public function audit()
    {
        $ids=$this->request->param()['ids'];
        $save=Db::name('comment')->where('id','in',$ids)->update(['is_show'=>1]);
        if ($save) {
            $this->success('审核成功');
        }else{
            $this->error('审核失败');
        }
    }
    /**
     * 留言编辑/回复 取消审核
     */
    public function audit_no()
    {
        $ids=$this->request->param()['ids'];
        $save=Db::name('comment')->where('id','in',$ids)->update(['is_show'=>0]);
        if ($save) {
            $this->success('取消成功');
        }else{
            $this->error('取消失败');
        }
    }
    /**
     * 留言编辑/回复 删除
     */
    public function delete()
    {
        $ids=$this->request->param()['ids'];
        $save=Db::name('comment')->delete($ids);
        if ($save) {
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }
}