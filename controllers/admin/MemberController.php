<?php

class MemberController extends Admin {

//    private $member;
    public $linkage;
    private $mgroup;
    public $order;

    public function __construct() {
        parent::__construct();
        if ($this->config['SYS_MEMBER']) {
            $this->adminMsg('系统禁止了会员功能');
        }
        $this->mgroup = $this->model('member_group');
        $this->member = $this->model('member');
        $this->order = $this->model('order');
        $this->linkage = $this->model('linkage');
    }

    //lou
    public function indexAction() {
        $page = (int)$this->get('page') ? (int)$this->get('page') : 1;
        $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 10;
        $urlparam = array('page' => '{page}');
        $limit = ($page-1)*$pagesize.",$pagesize";
        $order = 'id desc';
        $where = '1=1 and openid!=""';

        $us = unserialize(get_cookie('ci_session'));//后台管理员信息
        if($us['user_id'] != 1){
            $user = $this->db->where('userid',$us['user_id'])->get('user')->row_array();
            if(!empty($user['city_id'])){//当管理员地区id不是0时，则该管理员为区域管理员
                $where .= " and city_id='".$user['city_id']."'";
                $this -> view -> assign('user_cityid',$user['city_id']);
            }
        }

        //搜索条件
        $province_id = trim($this->post('province_id')) ?  trim($this->post('province_id')) : trim($this->get('province_id'));
        $city_id = trim($this->post('city_id')) ?  trim($this->post('city_id')) : trim($this->get('city_id'));
        if($province_id){
            $citylist = $this->linkage->get_all_list($province_id);
            $this -> view -> assign('citylist', $citylist);
            $this -> view -> assign('province_id', $province_id);
        }
        if($city_id){
            $where .= " and city_id='".$city_id."'";
            $this -> view -> assign('city_id', $city_id);
        }

        $data = $this->member->get_all_list($where,$order,$limit);
        $total = $this->member->get_all_list($where,$order,$limit,true);
        $pagelist = $this->instance('pagelist');    //加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/member', $urlparam))->num($pagesize)->page($page)->output();

        $province = $this->linkage->get_all_list();

        $this->view->assign(array(
            'province'=> $province,
//	        'city' => $city,
            'list' => $data,
            'page' => $page,
            'total' => $total,
            'pagelist' => $pagelist,
        ));
        $this->view->display('admin/member_list');
    }

    public function detailAction(){
        $id = (int)$this->get('id');
        $result = $this->member->get_one($id);

        $this->view->assign(array(
            'list' => $result,
        ));
        $this->view->display('admin/member_detail');
    }

    public function editAction(){
        $id = (int)$this->get('id');
        $province = $this->linkage->get_all_list();
        if($id){
            $member = $this->db->where('id',$id)->get('member')->row_array();
            $citylist = $this->linkage->get_all_list($member['province_id']);
            $arealist = $this->linkage->get_all_list($member['city_id']);
        }
        if($this->post('submit')){
            $data = $this->post('data');
            if(!$data['province_id']){
                $data['province'] = '';
            }else{
                $data['province'] = $this->linkage->get_name($data['province_id']);
            }
            if(!$data['city_id']){
                $data['city'] = '';
            }else{
                $data['city'] = $this->linkage->get_name($data['city_id']);
            }
            if(!$data['area_id']){
                $data['area'] = '';
            }else{
                $data['area'] = $this->linkage->get_name($data['area_id']);
            }

            $data['pca'] = $data['province'].",".$data['city'].",".$data['area'];
            $data['pca_id'] = $data['province_id'].",".$data['city_id'].",".$data['area_id'];

            $this->member->set($id,$data);
            $this->adminMsg(lang('success'), url('admin/member/edit',array('id'=>$id)), 3, 1, 1);
        }

        $this->view->assign(array(
            'province'=> $province,
            'citylist'=> $citylist,
            'arealist'=> $arealist,
            'province_id'=> $member['province_id'],
            'city_id'=> $member['city_id'],
            'area_id'=> $member['area_id'],
            'list' => $member,
        ));
        $this->view->display('admin/member_edit');
    }

    public function delAction(){
        $id = (int)$this->get('id');
        if (empty($id)){
            $this->adminMsg(lang('all-not-del-msg'));
        }
        $this->member->del($id);
        $this->adminMsg(lang('success'), url('admin/member/index'), 3, 1, 1);
    }

    public function get_cityAction(){
        $id = $this->post('id');
        $sql = "select * from ".$this->db->prefix ."linkage where parentid=$id";
        $query = $this->db->query($sql)->result_array();

        $str = '<option value="">请选择</option>';
        foreach($query as $item){
            $str .= '<option value ="'.$item['id'].'">'.$item['name'].'</option>';
        }
        echo $str;
    }

    /*
	 * 用户组
	 */
    public function groupAction() {
        $type = $this->get('type');
        switch ($type) {
            case 'add':
                if ($this->isPostForm()) {
                    $data = $this->post('data');
                    $data['listorder'] = intval($data['listorder']);
                    $data['disabled'] = intval($data['disabled']);
                    $data['credits'] = intval($data['credits']);
                    $data['allowpost'] = intval($data['allowpost']);
                    $data['allowpms'] = intval($data['allowpms']);
                    $data['filesize'] = intval($data['filesize']);
                    if (empty($data['name'])) $this->adminMsg(lang('a-mem-1'));
                    $this->mgroup->insert($data);
                    $this->adminMsg($this->getCacheCode('member') . lang('success'), url('admin/member/group/'), 3, 1, 1);
                }
                $this->view->display('admin/member_group_add');
                break;
            case 'edit':
                $id = (int)$this->get('id');
                if ($this->isPostForm()) {
                    $data = $this->post('data');
                    if (empty($data['name'])) $this->adminMsg(lang('a-mem-1'));
                    $this->mgroup->update($data, 'id=' . $id);
                    $this->adminMsg($this->getCacheCode('member') . lang('success'), url('admin/member/group/'), 3, 1, 1);
                }
                $this->view->assign('data', $this->mgroup->find($id));
                $this->view->display('admin/member_group_add');
                break;
            case 'cache':
                $this->adminMsg($this->getCacheCode('member') . lang('success'), url('admin/member/group/'), 3, 1, 1);
//                $this->cacheAction();
                break;
            case 'delete':
                $id = (int)$this->get('id');
                $this->mgroup->delete('id=' . $id);
                $this->adminMsg($this->getCacheCode('member') . lang('success'), url('admin/member/group/'), 3, 1, 1);
                break;
            default:
                if ($this->post('submit_order') && $this->post('form')=='order') {
                    foreach ($_POST as $var=>$value) {
                        if (strpos($var, 'order_')!==false) {
                            $id = (int)str_replace('order_', '', $var);
                            $this->mgroup->update(array('listorder'=>$value), 'id=' . $id);
                        }
                    }
                    $this->cacheAction(1);
                } elseif ($this->post('submit_del') && $this->post('form')=='del') {
                    foreach ($_POST as $var=>$value) {
                        if (strpos($var, 'del_')!==false) {
                            $id = (int)str_replace('del_', '', $var);
                            $this->mgroup->delete('id=' . $id);
                        }
                    }
                    $this->cacheAction(1);
                }
                $page     = (int)$this->get('page');
                $page     = (!$page) ? 1 : $page;
                $pagelist = $this->instance('pagelist');
                $pagelist->loadconfig();
                $total    = $this->mgroup->count('member_group');
                $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
                $url      = url('admin/member/group', array('page'=>'{page}'));
                $select   = $this->mgroup->page_limit($page, $pagesize)->order(array('listorder ASC', 'id DESC'));
                $data     = $select->select();
                $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
                $this->view->assign(array(
                    'list'     => $data,
                    'pagelist' => $pagelist,
                ));
                $this->view->display('admin/member_group_list');
        }
    }

    /*
	 * 短消息
	 */
    public function pmsAction() {
        $type      = $this->get('type');
        $memberpms = $this->model('member_pms');
        switch ($type) {
            case 'show';
                $id   = (int)$this->get('id');
                if ($this->isPostForm()) {
                    if ($id) $memberpms->delete('id=' . $id);
                    $this->adminMsg(lang('success'), url('admin/member/pms'));
                }
                if (empty($id)) $this->adminMsg(lang('a-mem-14'));
                $data = $memberpms->find($id);
                if (empty($data)) $this->adminMsg(lang('a-mem-15'));
                $this->view->assign(array(
                    'data'  => $data,
                    'model' => $this->membermodel,
                    'group' => $this->membergroup
                ));
                $this->view->display('admin/member_pms_show');
                break;
            case 'send':
                if ($this->isPostForm()) {
                    $type = $this->post('type');
                    $data = $this->post('data');
                    if (empty($type)) $this->adminMsg(lang('a-mem-16'));
                    if (empty($data['title']) || empty($data['content'])) $this->adminMsg(lang('a-mem-17'));
                    $data['sendid']   = $this->userinfo['userid'];
                    $data['isadmin']  = 1;
                    $data['sendname'] = $this->userinfo['username'];
                    $data['sendtime'] = time();
                    $sendtotal	= 0;
                    if ($type == 1) {
                        //群发
                        if (empty($data['modelid'])) $this->adminMsg(lang('a-mem-18'));
                        $where = 'modelid=' . $data['modelid'];
                        if ($data['groupid']) $where .= ' AND groupid=' . $data['groupid'];
                        $list  = $this->member->from(null, 'id,username')->where($where)->select();
                        foreach ($list as $row) {
                            $data['toid']   = $row['id'];
                            $data['toname'] = $row['username'];
                            if ($memberpms->insert($data)) $sendtotal ++;
                        }
                    } elseif ($type == 2) {
                        //个人
                        unset($data['togroupid'], $data['tomodelid']);
                        if (empty($data['tonames'])) $this->adminMsg(lang('a-mem-19'));
                        $users = explode(',', $data['tonames']);
                        foreach ($users as $user) {
                            $row = $this->member->from(null, 'id')->where('username=?', $user)->select(false);
                            if ($row) {
                                $data['toid'] = $row['id'];
                                $data['toname'] = $user;
                                if ($memberpms->insert($data)) $sendtotal ++;
                            }
                        }
                    }
                    $this->adminMsg(lang('a-mem-20') . '(' . $sendtotal . ')', url('admin/member/pms'), 3, 1, 1);
                }
                $this->view->assign(array(
                    'model' => $this->membermodel,
                    'group' => $this->membergroup
                ));
                $this->view->display('admin/member_pms_send');
                break;
            default:
                if ($this->isPostForm()) {
                    $ids  = $this->post('ids');
                    $ids  = implode(',', $ids);
                    if ($ids) $memberpms->delete('id IN(' . $ids . ')');
                }
                $page     = $this->get('page') ? (int)$this->get('page') : 1;
                $pagelist = $this->instance('pagelist');
                $pagelist->loadconfig();
                $where    = null;
                if ($this->get('toid')) $where = 'toid=' . $this->get('toid');
                $total    = $memberpms->count('member_pms', 'id', $where);
                $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
                $url      = url('admin/member/pms', array('page' => '{page}'));
                if ($this->get('toid')) $url = url('admin/member/pms', array('toid' => $this->get('toid'), 'page' => '{page}'));
                $select   = $memberpms->page_limit($page, $pagesize)->order('sendtime DESC');
                if ($this->get('toid')) $select->where('toid=' . $this->get('toid'));
                $data     = $select->select();
                $pagelist = $pagelist->total($total)->url($url)->num($pagesize)->page($page)->output();
                $this->view->assign(array(
                    'list'     => $data,
                    'model'    => $this->membermodel,
                    'group'    => $this->membergroup,
                    'pagelist' => $pagelist
                ));
                $this->view->display('admin/member_pms_list');
                break;
        }
    }

    /*
	 * 配置信息
	 */
    public function configAction() {
        $type   = $this->get('type') ? $this->get('type') : 'reg';
        $member = $this->cache->get('member');
        if ($this->post('submit')) {
            $data  = $this->post('data');
            $data['reg_tpl'] = stripslashes($data['reg_tpl']);
            $data['pass_tpl'] = stripslashes($data['pass_tpl']);
            $data['group_tpl'] = stripslashes($data['group_tpl']);
            $data['username_pattern'] = stripslashes($data['username_pattern']);
            $oauth = $this->post('oauth');
            if ($data['uc_use']) {
                $m = self::load_config('database');
                $s = '<?php ' . PHP_EOL . '/* UCenter配置 */' . PHP_EOL
                    . stripslashes($data['uc_config'])
                    . PHP_EOL . '/* FineCMS配置 */' . PHP_EOL
                    . '$dbhost    = \'' . $m['host'] . ':' . $m['port'] . '\';' . PHP_EOL
                    . '$dbuser    = \'' . $m['username'] . '\';' . PHP_EOL
                    . '$dbpw      = \'' . $m['password'] . '\';' . PHP_EOL
                    . '$dbname    = \'' . $m['dbname'] . '\';' . PHP_EOL
                    . '$pconnect  = 0;' . PHP_EOL
                    . '$tablepre  = \'' . $m['prefix'] . '\';' . PHP_EOL
                    . '$dbcharset = \'' . $m['charset'] . '\';' . PHP_EOL
                    . '/* 同步登录Cookie */' . PHP_EOL
                    . '$cookiedomain = \'' . SYS_COOKIE_DOMAIN . '\';' . PHP_EOL
                    . '$cookiepath   = \'/\';' . PHP_EOL
                    . '$cookiepre    = \'' . SYS_VAR_PREX . '\';' . PHP_EOL
                    . '$cookiecode   = \'' . SITE_MEMBER_COOKIE . '\';' . PHP_EOL
                    . '?>';
                $file = EXTENSION_DIR . 'ucenter' . DIRECTORY_SEPARATOR . 'config.inc.php';
                if (!file_put_contents($file, $s)) $this->adminMsg(lang('a-mem-2', array('1' => $file)));
            }
            $this->cache->set('member', array_merge($data, array('oauth' => $oauth)));
            $this->adminMsg(lang('success'), url('admin/member/config', array('type' => $type)), 3, 1, 1);
        }
        $this->view->assign(array(
            'type'        => $type,
            'data'        => $member,
            'string'      => $string,
            'membermodel' => $this->membermodel,
            'membergroup' => $this->membergroup
        ));
        $this->view->display('admin/member_config');
    }


}