<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\db;
use tree\Tree;

/**
 * Class WechatController
 * @package app\admin\controller
 * 微信管理
 */
class WechatController extends AdminBaseController
{
    /**
     * 公众号配置
     */
    public function index()
    {
        $wechat = Db::name('wx_user')->find();
        $this->assign('wechat',$wechat);
        return $this->fetch();
    }

    /**
     * 公众号配置保存
     */
    public function index_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (isset($data['id']) && $data['id']) {
                //修改
                $save = Db::name('wx_user')->where('id', $data['id'])->update($data);
            }else{
                //添加
                $data['token'] = get_rand_str(6,1,0);
                $save = Db::name('wx_user')->insert($data);
            }
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
     * 微信菜单
     */
    public function menu()
    {

        $wechat = Db::name('wx_user')->find();
        $result = Db::name('wx_menu')->where('token',$wechat['token'])->order(["sort" => "ASC", "id" => "ASC"])->select()->toArray();
        $tree = new Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        $newMenus = [];
        foreach ($result as $m) {
            $newMenus[$m['id']] = $m;
        }
        foreach ($result as $k=> $v) {
            $result[$k]['parent_id_node'] = ($v['parent_id']) ? ' class="child-of-node-' . $v['parent_id'] . '"' : '';
            $result[$k]['style'] = empty($v['parent_id']) ? '' : 'display:none;';
            $result[$k]['str_manage']='';
            $result[$k]['type']='';
            switch ($v['type'])
            {
                case "view":
                    $result[$k]['type']= "链接";
                    break;
                case "click":
                    $result[$k]['type']= "触发关键字";
                    break;
                case "scancode_push":
                    $result[$k]['type']= "扫码";
                    break;
                case "scancode_waitmsg":
                    $result[$k]['type']= " 扫码（等待信息）";
                    break;
                case "pic_sysphoto":
                    $result[$k]['type']= "系统拍照发图";
                    break;
                case "pic_photo_or_album":
                    $result[$k]['type']= "拍照或者相册发图";
                    break;
                case "pic_weixin":
                    $result[$k]['type']= "微信相册发图";
                    break;
                case "location_select":
                    $result[$k]['type']= "地理位置";
                    break;
            }
            if ($v['parent_id']==0) {
                $result[$k]['str_manage'] = '<a href="' . url("menu_add", ["parent_id" => $v['id']]) . '">' . lang('添加子菜单') . '</a>';
            }
            $result[$k]['str_manage'].='
                 <a href="' . url("menu_edit", ["id" => $v['id']]) . '">' . lang('EDIT') . '</a>
                 <a class="js-ajax-delete" href="' . url("menu_del", ["id" => $v['id']]) . '">' . lang('DELETE') . '</a> ';
        }
        $tree->init($result);
        $str = "
                    <tr id='node-\$id' \$parent_id_node style='\$style'>
                        <td style='padding-left:20px;'>
                           <input name='sort[\$id]' type='text' size='3' value='\$sort' class='input input-order' style='width: 50px;'>
                        </td>
                        <td>\$spacer\$name</td>
                        <td>\$type</td>
                        <td>\$value</td>
                        <td>\$str_manage</td>
                    </tr>";
        $category = $tree->getTree(0, $str);
        $this->assign("category", $category);
        return $this->fetch();
    }

    /**
     * 添加菜单
     */
    public function menu_add()
    {
        $tree = new Tree();
        $parentId = $this->request->param("parent_id", 0, 'intval');
        $result = Db::name('wx_menu')->where('parent_id',0)->order(["sort" => "ASC", "id" => "ASC"])->select();
        $array = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $parentId ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);
        $this->assign("select_category", $selectCategory);
        return $this->fetch();
    }

    /**
     * 添加菜单提交
     */
    public function menu_add_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if ($data['parent_id']==0) {
                $menu_num=Db::name('wx_menu')->where('parent_id',0)->count();
                if ($menu_num>=3) {
                    $this->error('一级菜单最多三个');
                }
            }else{
                $menu_num=Db::name('wx_menu')->where('parent_id',$data['parent_id'])->count();
                if ($menu_num>=5) {
                    $this->error('二级菜单最多五个');
                }
            }
            $where['name'] = array('eq',$data['name']);
            $where['value'] = array('eq',$data['value']);
            $where['type'] = array('eq',$data['type']);
            $menu=Db::name('wx_menu')->where($where)->find();
            if ($menu) {
                $this->error('微信菜单已存在');
            }
            $wechat = Db::name('wx_user')->find();
            $data['token'] = $wechat['token'];
            $add=Db::name('wx_menu')->insertGetId($data);
            if ($add) {
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 微信菜单编辑
     */
    public function menu_edit()
    {
        $tree = new Tree();
        $id = $this->request->param("id", 0, 'intval');
        $data = Db::name('wx_menu')->where('id', $id)->find();
        $result = Db::name('wx_menu')->where('parent_id',0)->order(["sort" => "ASC", "id" => "ASC"])->select();
        $array = [];
        foreach ($result as $r) {
            $r['selected'] = $r['id'] == $data['parent_id'] ? 'selected' : '';
            $array[] = $r;
        }
        $str = "<option value='\$id' \$selected>\$spacer \$name</option>";
        $tree->init($array);
        $selectCategory = $tree->getTree(0, $str);
        $this->assign("select_category", $selectCategory);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 微信菜单编辑提交
     */
    public function menu_edit_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $where['id'] = array('neq',$data['id']);
            $where['name'] = array('eq',$data['name']);
            $where['value'] = array('eq',$data['value']);
            $where['type'] = array('eq',$data['type']);
            $menu=Db::name('wx_menu')->where($where)->find();
            if ($menu) {
                $this->error('微信菜单已存在');
            }
            $add=Db::name('wx_menu')->update($data);
            if ($add) {
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 微信菜单删除
     */
    public function menu_del()
    {
        $id = $this->request->param("id", 0, 'intval');
        if ($id) {
            $del = Db::name('wx_menu')->where('id', $id)->delete();
            if ($del) {
                $this->success('删除成功');
            }else{
                $this->success('删除失败');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 微信菜单排序
     */
    public function menu_sort()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $order_arr = [];
            $count = 0;
            foreach ($data['sort'] as $k => $v) {
                $count++;
                $order_arr[$count]['id'] = $k;
                $order_arr[$count]['sort'] = (float)$v;
            }
            $addr = Db::name('wx_menu')->field('id,sort')->select()->toArray();
            $arr = [];
            /*比较两个二位数组的不同,并返回差集*/
            foreach ($order_arr as $k1 => $v1) {
                foreach ($addr as $k2 => $v2) {
                    if ($v1['id'] == $v2['id']) {
                        $diff = array_diff($v1, $v2);
                        if ($diff) {
                            $arr[$k1]['id'] = $v1['id'];
                            $arr[$k1]['sort'] = $diff['sort'];
                        }
                    }
                }
            }
            if ($arr) {
                Db::startTrans();
                foreach ($arr as $v) {
                    $upd = Db::name('wx_menu')->update($v);
                    if (!$upd) {
                        Db::rollback();
                        $this->error('更新失败');
                        exit; /*返回更新并退出循环*/
                    }
                }
                Db::commit();
                $this->success('更新成功');
            } else {
                $this->error('没有更改的数据');
            }
        }
    }
    /*
     * 生成微信菜单
     */
    public function pub_menu(){
        //获取菜单
        $wechat = Db::name('wx_user')->find();
        //获取父级菜单
        $p_menus = Db::name('wx_menu')->where(array('parent_id'=>0))->order(["sort" => "ASC", "id" => "ASC"])->select();
        $p_menus = convert_arr_key($p_menus,'id');

        $post_str = $this->convert_menu($p_menus,$wechat['token']);
        // http post请求
        if(!count($p_menus) > 0){
            $this->error('没有菜单可发布');
            exit;
        }
        $access_token = $this->get_access_token();
        if(!$access_token){
            $this->error('获取access_token失败');
            exit;
        }
        $url ="https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
//        exit($post_str);
        $return = httpRequest($url,'POST',$post_str);
        $return = json_decode($return,1);
        if($return['errcode'] == 0){
            $this->success('菜单已成功生成');
        }else{
            $this->error("错误代码：".$return['errcode']);
        }
    }

    //菜单转换
    private function convert_menu($p_menus,$token){
        $key_map = array(
            'scancode_waitmsg'=>'rselfmenu_0_0',
            'scancode_push'=>'rselfmenu_0_1',
            'pic_sysphoto'=>'rselfmenu_1_0',
            'pic_photo_or_album'=>'rselfmenu_1_1',
            'pic_weixin'=>'rselfmenu_1_2',
            'location_select'=>'rselfmenu_2_0',
        );
        $new_arr = array();
        $count = 0;
        foreach($p_menus as $k => $v){
            $new_arr[$count]['name'] = $v['name'];
            //获取子菜单
            $c_menus = Db::name('wx_menu')->where(array('token'=>$token,'parent_id'=>$k))->order(["sort" => "ASC", "id" => "ASC"])->select()->toArray();
            if($c_menus){
                foreach($c_menus as $kk=>$vv){
                    $add = array();
                    $add['name'] = $vv['name'];
                    $add['type'] = $vv['type'];
                    // click类型
                    if($add['type'] == 'click'){
                        $add['key'] = $vv['value'];
                    }elseif($add['type'] == 'view'){
                        $add['url'] = $vv['value'];
                    }else{
                        $add['key'] = $key_map[$add['type']];
                        //$add['key'] = $vv['value'];
                    }
                    $add['sub_button'] = array();
                    if($add['name']){
                        $new_arr[$count]['sub_button'][] = $add;
                    }
                }
            }else{
                $new_arr[$count]['type'] = $v['type'];
                // click类型
                if($new_arr[$count]['type'] == 'click'){
                    $new_arr[$count]['key'] = $v['value'];
                }elseif($new_arr[$count]['type'] == 'view'){
                    //跳转URL类型
                    $new_arr[$count]['url'] = $v['value'];
                }else{
                    //其他事件类型
                    $new_arr[$count]['key'] = $key_map[$v['type']];
                    //$new_arr[$count]['key'] = $v['value'];
                }
            }
            $count++;
        }
        // return json_encode(array('button'=>$new_arr));
        return json_encode(array('button'=>$new_arr),JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取access_token值
     */
    public function get_access_token(){
        //判断是否过了缓存期
        $wechat = Db::name('wx_user')->find();
        $expire_time = $wechat['web_expires'];
        if($expire_time > time()){
            return $wechat['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$wechat['appid']}&secret={$wechat['appsecret']}";
        $return = httpRequest($url,'GET');
        $return = json_decode($return,1);
        $web_expires = time() + 7100; // 提前100秒过期
        Db::name('wx_user')->where(array('id'=>$wechat['id']))->update(array('web_access_token'=>$return['access_token'],'web_expires'=>$web_expires));
        return $return['access_token'];
    }

    /**
     * 文本回复列表
     */
    public function text()
    {
        $post= $this->request->param();
        $this->assign('post',$post);
        $where = array();
        $query = array();
        if (isset($post['keyword']) && $post['keyword']) {
            $where['t1.keyword'] = array('like','%'.$post['keyword'].'%');
            $query['keyword'] = $post['keyword'];
        }
        $wechat = Db::name('wx_user')->find();
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'TEXT');
        $config['query'] = $query;
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.text,t2.createtime')->where($where)
            ->join('lg_wx_text t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->paginate(20,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加文本回复
     */
    public function text_add()
    {
        return $this->fetch();
    }

    /**
     * 添加文本回复提交
     */
    public function text_add_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $key = Db::name('wx_keyword')->where(array('keyword' => $data['keyword'], 'token' => $wechat['token']))->find();
            if ($key) {
                $this->error('关键字已存在');exit;
            }
            $arr['keyword'] = trim($data['keyword']);
            $arr['text'] = $data['text'];
            $arr['createtime'] = time();
            $arr['token'] = $wechat['token'];
            Db::startTrans();
            $add_text_id = Db::name('wx_text')->insertGetId($arr);
            unset($arr['text'],$arr['createtime']);
            $arr['pid'] = $add_text_id;
            $arr['type'] = 'TEXT';
            $add_key = Db::name('wx_keyword')->insert($arr);
            if ($add_text_id && $add_key) {
                Db::commit();
                $this->success('添加成功');
            }else{
                Db::rollback();
                $this->error('添加失败');
            }

        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 编辑文本回复
     */
    public function text_edit()
    {
        $id = $this->request->param()['id'];
        $wechat = Db::name('wx_user')->find();
        $where['t1.id'] = array('eq',$id);
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'TEXT');
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.text')->where($where)
            ->join('lg_wx_text t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 编辑文本回复提交
     */
    public function text_edit_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $data['keyword'] = trim($data['keyword']);
            $arr['keyword'] = $data['keyword'];
            $arr['text'] = $data['text'];
            $arr['updatetime'] = time();
            $arr['token'] = $wechat['token'];
            /*判断关键字是否存在*/
            $where['id'] = array('neq',$data['id']);
            $where['keyword'] = array('eq',$data['keyword']);
            $where['token'] = array('eq',$wechat['token']);
            $is_kye = Db::name('wx_keyword')->where($where)->find();
            if ($is_kye) {
                $this->error('关键字已存在');
            }
            $keyword = Db::name('wx_keyword')->where('id', $data['id'])->find();
            if ($keyword) {
                unset($data['text']);
                $data['token'] = $wechat['token'];
                $data['type'] = 'TEXT';
                Db::name('wx_keyword')->update($data);
                $arr['id'] = $keyword['pid'];
                Db::name('wx_text')->update($arr);
                $this->success('保存成功');
            }else{
                $this->error('请求有误');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 删除文本回复
     */
    public function text_del()
    {
        $ids=$this->request->param()['ids'];
        $wechat = Db::name('wx_user')->find();
        $where['id'] = array('in',$ids);
        $where['token'] = array('eq',$wechat['token']);
        $keyword = Db::name('wx_keyword')->where($where)->select()->toArray();
        $arr_id = array();
        foreach ($keyword as $v) {
            $arr_id[] = $v['pid'];
        }
        Db::startTrans();
        $key_del = Db::name('wx_keyword')->delete($ids);
        $text_del = Db::name('wx_text')->delete($arr_id);
        if ($key_del && $text_del) {
            Db::commit();
            $this->success('删除成功');
        }else{
            Db::rollback();
            $this->error('删除失败');
        }
    }

    /**
     * 单图文回复列表
     */
    public function img()
    {
        $post= $this->request->param();
        $this->assign('post',$post);
        $where = array();
        $query = array();
        if (isset($post['keyword']) && $post['keyword']) {
            $where['t1.keyword'] = array('like','%'.$post['keyword'].'%');
            $query['keyword'] = $post['keyword'];
        }
        $wechat = Db::name('wx_user')->find();
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'IMG');
        $config['query'] = $query;
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.title,t2.desc,t2.createtime,t2.pic')->where($where)
            ->join('lg_wx_img t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->paginate(20,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加单图文
     */
    public function img_add()
    {
        return $this->fetch();
    }

    /**
     * 添加单图文提交
     */
    public function img_add_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $data['keyword'] = trim($data['keyword']);
            $key = Db::name('wx_keyword')->where(array('keyword' => $data['keyword'], 'token' => $wechat['token']))->find();
            if ($key) {
                $this->error('关键字已存在');exit;
            }
            $data['pic'] = cmf_get_image_url($data['pic']);
            $data['token'] = $wechat['token'];
            $data['createtime'] = time();
            Db::startTrans();
            $add_img_id = Db::name('wx_img')->insertGetId($data);
            $arr['keyword'] = $data['keyword'];
            $arr['token'] = $wechat['token'];
            $arr['pid'] = $add_img_id;
            $arr['type'] = 'IMG';
            $add_key = Db::name('wx_keyword')->insert($arr);
            if ($add_img_id && $add_key) {
                Db::commit();
                $this->success('添加成功');
            }else{
                Db::rollback();
                $this->error('添加失败');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 编辑单图文回复
     */
    public function img_edit()
    {
        $id = $this->request->param()['id'];
        $wechat = Db::name('wx_user')->find();
        $where['t1.id'] = array('eq',$id);
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'IMG');
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.title,t2.desc,t2.url,t2.pic')->where($where)
            ->join('lg_wx_img t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->find();
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 编辑单图文回复提交
     */
    public function img_edit_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $data['keyword'] = trim($data['keyword']);
            $data['updatetime'] = time();
            $data['token'] = $wechat['token'];
            $data['pic'] = cmf_get_image_url($data['pic']);
            /*判断关键字是否存在*/
            $where['id'] = array('neq',$data['id']);
            $where['keyword'] = array('eq',$data['keyword']);
            $where['token'] = array('eq',$wechat['token']);
            $is_kye = Db::name('wx_keyword')->where($where)->find();
            if ($is_kye) {
                $this->error('关键字已存在');
            }
            $keyword = Db::name('wx_keyword')->where('id', $data['id'])->find();
            if ($keyword) {
                $arr['id'] = $data['id'];
                $arr['keyword'] = $data['keyword'];
                $arr['pid'] = $keyword['pid'];
                $arr['type'] = 'IMG';
                $arr['token'] = $wechat['token'];
                Db::name('wx_keyword')->update($arr);
                $data['id'] = $keyword['pid'];
                Db::name('wx_img')->update($data);
                $this->success('保存成功');
            }else{
                $this->error('请求有误');
            }
        }else{
            $this->error('请求有误');
        }
    }

    /**
     * 删除单图文回复
     */
    public function img_del()
    {
        $ids=$this->request->param()['ids'];
        $wechat = Db::name('wx_user')->find();
        $where['id'] = array('in',$ids);
        $where['token'] = array('eq',$wechat['token']);
        $keyword = Db::name('wx_keyword')->where($where)->select()->toArray();
        $arr_id = array();
        foreach ($keyword as $v) {
            $arr_id[] = $v['pid'];
        }
        Db::startTrans();
        $key_del = Db::name('wx_keyword')->delete($ids);
        $text_del = Db::name('wx_img')->delete($arr_id);
        if ($key_del && $text_del) {
            Db::commit();
            $this->success('删除成功');
        }else{
            Db::rollback();
            $this->error('删除失败');
        }
    }

    /**
     * 单图文选择列表
     */
    public function img_list()
    {
        $post= $this->request->param();
        $this->assign('post',$post);
        $where = array();
        $query = array();
        if (isset($post['keyword']) && $post['keyword']) {
            $where['t1.keyword'] = array('like','%'.$post['keyword'].'%');
            $query['keyword'] = $post['keyword'];
        }
        if (isset($post['id']) && $post['id']) {
            $where['t1.id'] = array('not in',trim($post['id'],','));
            $query['id'] = $post['id'];
        }
        $wechat = Db::name('wx_user')->find();
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'IMG');
        $config['query'] = $query;
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.id mid,t2.title,t2.desc,t2.createtime,t2.pic')->where($where)
            ->join('lg_wx_img t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->paginate(10,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        $this->assign('json_data',json_encode($data->items()));
        return $this->fetch();
    }

    /**
     * 多图文列表
     */
    public function news()
    {
        $post= $this->request->param();
        $this->assign('post',$post);
        $where = array();
        $query = array();
        if (isset($post['keyword']) && $post['keyword']) {
            $where['t1.keyword'] = array('like','%'.$post['keyword'].'%');
            $query['keyword'] = $post['keyword'];
        }
        $wechat = Db::name('wx_user')->find();
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'NEWS');
        $config['query'] = $query;
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.createtime')->where($where)
            ->join('lg_wx_news t2', 't1.pid=t2.id', 'left')
            ->order('t2.createtime', 'DESC')->paginate(20,false,$config);
        $this->assign('page',$data->render());
        $this->assign('data',$data);
        return $this->fetch();
    }

    /**
     * 添加多图文
     */
    public function news_add()
    {
        return $this->fetch();
    }

    /**
     * 添加多图文提交
     */
    public function news_add_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $arr = explode(',',$data['img_id']);
            if (count($arr) < 2) {
                $this->error("单图文请到单图文回复设置");exit;
            }
            $data['keyword'] = trim($data['keyword']);
            $key = Db::name('wx_keyword')->where(array('keyword' => $data['keyword'], 'token' => $wechat['token']))->find();
            if ($key) {
                $this->error('关键字已存在');exit;
            }
            $data['token']=$wechat['token'];
            $data['createtime']=time();
            Db::startTrans();
            $add_news_id = Db::name('wx_news')->insertGetId($data);
            $add['keyword'] =   $data['keyword'];
            $add['token'] =  $wechat['token'];
            $add['type'] =  'NEWS';
            $add['pid'] = $add_news_id;
            $add_key=Db::name('wx_keyword')->insert($add);
            if ($add_news_id && $add_key) {
                Db::commit();
                $this->success('添加成功');
            }else{
                Db::rollback();
                $this->error('添加失败');
            }

        }
    }

    /**
     * 多图文编辑
     */
    public function news_edit()
    {
        $id = $this->request->param()['id'];
        $wechat = Db::name('wx_user')->find();
        $where['t1.id'] = array('eq',$id);
        $where['t1.token'] = array('eq', $wechat['token']);
        $where['t1.type'] = array('eq', 'NEWS');
        $data = Db::name('wx_keyword')->alias('t1')->field('t1.id,t1.keyword,t2.img_id')->where($where)
            ->join('lg_wx_news t2', 't1.pid=t2.id', 'left')->find();
        $where2['id'] = array('in',$data['img_id']);
        $where2['token'] = array('eq', $wechat['token']);
        $data['img'] = Db::name('wx_img')->where($where2)->select()->toArray();
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function news_edit_post()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $wechat = Db::name('wx_user')->find();
            $data['keyword'] = trim($data['keyword']);
            $data['updatetime'] = time();
            $data['token'] = $wechat['token'];
            /*判断关键字是否存在*/
            $where['id'] = array('neq',$data['id']);
            $where['keyword'] = array('eq',$data['keyword']);
            $where['token'] = array('eq',$wechat['token']);
            $is_kye = Db::name('wx_keyword')->where($where)->find();
            if ($is_kye) {
                $this->error('关键字已存在');
            }
            $keyword = Db::name('wx_keyword')->where('id', $data['id'])->find();
            if ($keyword) {
                $arr['id'] = $data['id'];
                $arr['keyword'] = $data['keyword'];
                $arr['pid'] = $keyword['pid'];
                $arr['type'] = 'NEWS';
                $arr['token'] = $wechat['token'];
                Db::name('wx_keyword')->update($arr);
                $data['id'] = $keyword['pid'];
                Db::name('wx_news')->update($data);
                $this->success('保存成功');
            }else{
                $this->error('请求有误');
            }
        }else{
            $this->error('请求有误');
        }
    }
    /**
     * 多图文删除
     */
    public function news_del()
    {
        $ids=$this->request->param()['ids'];
        $wechat = Db::name('wx_user')->find();
        $where['id'] = array('in',$ids);
        $where['token'] = array('eq',$wechat['token']);
        $keyword = Db::name('wx_keyword')->where($where)->select()->toArray();
        $arr_id = array();
        foreach ($keyword as $v) {
            $arr_id[] = $v['pid'];
        }
        Db::startTrans();
        $key_del = Db::name('wx_keyword')->delete($ids);
        $text_del = Db::name('wx_news')->delete($arr_id);
        if ($key_del && $text_del) {
            Db::commit();
            $this->success('删除成功');
        }else{
            Db::rollback();
            $this->error('删除失败');
        }
    }
}
