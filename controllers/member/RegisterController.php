<?php

class RegisterController extends Member {
    public $linkage;
    public $member;
    public function __construct() {
        parent::__construct();
        $this->linkage = $this->model('linkage');
        $this->member = $this->model('member');
//        if(!get_cookie('openid')){
//            $this->oauthlogin();//微信授权跟获取
//        }
    }

    /**
     * 会员注册
     */
    public function indexAction() {
        $member_id = (int)get_cookie('member_id');//判断是否已经登录
        if($member_id>0){
            $this->msg('检测到您已登录会员帐号,正在为您跳转!',url('member/'),2);die;
        }
        if ($this->isPostForm()) {
            $data = $this->post('data');
//            $this->check($data);
//            $uid  = $this->reg($data);
            $data['password'] = md5(md5($data['password']));
            $data['regtime'] = time();
            $return = $this->member1->set('', $data);
            if ($return > 0) {
                if (empty($return)){
                    echo "<script>alert('".lang('m-reg-2')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
                    exit;
                }
                set_cookie('member_id',$return['id'],3600*48);//登录cookie
                set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20), $time);
                $this->msg("注册成功！",url('member/'),'ok');die;
            }
        }
        $this->view->assign(array(
            'meta_title'	=> '会员注册',
        ));
        $this->view->display('member_register');
    }

    /**
     * 内部验证
     */
    private function check($data) {
        if ($this->member->check_val($data['openid'])){
            echo "<script>alert('".lang('该微信号已经注册,请登录')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        if (empty($data['username'])){
            echo "<script>alert('".lang('m-reg-7')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        if ($this->member->check_username($data['username'])){
            echo "<script>alert('".lang('账号已经被注册了')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        if ($this->member->check_email($data['email'])){
            echo "<script>alert('".lang('邮箱账号已被使用')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        if (empty($data['password'])){
            echo "<script>alert('".lang('m-reg-11')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
        if ($data['password'] != $data['repassword']){
            echo "<script>alert('".lang('m-reg-12')."');location.href='".$_SERVER["HTTP_REFERER"]."';</script>";
            exit;
        }
    }

    /**
     * 注册处理
     */
    private function reg($data) {
        if (empty($data)) return false;
        $data['openid'] = $data['openid'];
        $data['access_token'] = $data['access_token'];
        $data['regip']		= client::get_user_ip();
        $data['status']		= 1;
        $data['regdate']	= time();
//	    $data['password'] = md5(md5($data['password']) . $data['salt'] . md5($data['password']));

//		$diquarr = explode(',',$data['diqu']);
//		$data['pca'] = $this->linkage->get_one_name($data['diqu']);//拼接省份，市，区,以英文逗号分割
//		$data['pca_id'] = $data['diqu'];
//		$data['province'] = $this->linkage->get_one_name($data['diqu'],1);//省份
//		$data['province_id'] = $diquarr[0];
//		$data['city'] = $this->linkage->get_one_name($data['diqu'],2);//市
//		$data['city_id'] = $diquarr[1];
//		$data['area'] = $this->linkage->get_one_name($data['diqu'],3);//区
//		$data['area_id'] = $diquarr[2];
        if (empty($data['openid']) || empty($data['access_token'])){
            return false;
        }else{
            $flg = $this->member->set(0,$data);
            if(flg){
                return $this->member->get_one_id($data['regdate']);
            }
        }

    }

    /**
     * 一键登录返回
     */
    public function callbackAction() {
        $oauthconfig  = $this->loadOauth();
        if (!$oauthconfig) $this->memberMsg(lang('m-log-15'));
        oauth_callback($oauthconfig);
        $oauth_data   = $this->session->get('oauth_data');
        $oauth_name   = $this->session->get('oauth_name');
        $memberinfo   = get_user_info($oauthconfig, $oauth_data);
        if (empty($oauth_data['oauth_openid']) || empty($oauth_name)) $this->memberMsg(lang('m-log-12'));
        if (empty($memberinfo['name']) && empty($memberinfo['avatar'])) $this->memberMsg(lang('a-mod-200'));
        //查询是否已经绑定
        $omember = $this->member->from('oauth')->where("oauth_openid = '" . $oauth_data['oauth_openid'] . "' AND oauth_name = '" . $oauth_name . "'")->select(false);
        if (empty($omember)) {  //绑定用户,v1.7.7修改为直接注册
            //注册会员
            $pwd = rand(0, 9999);
            $data = array(
                'username'	=> $oauth_name.time().rand(0, 999),
                'password'	=> $pwd,
                'password2'	=> $pwd,
                'email'		=> $oauth_name.$oauth_data['oauth_openid'].'@dayrui.com',
                'nickname'	=> (string)$memberinfo['name'],
                'avatar'	=> $memberinfo['avatar']
            );
            $uid = $this->reg($data);
            if (empty($uid)) $this->memberMsg(lang('m-reg-2'));
            $data['id'] = $uid;
            $this->bang($data);
            $this->regEmail($data); //注册邮件提示
            //登录cookie
            set_cookie('member_id', $uid, 24*3600);
            set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20), $time);
            $this->memberMsg(lang('m-log-4'), url('member/'), 1);
        } else { //验证成功,判断用户表
            $member = $this->member->where('username=?', $omember['username'])->select(false);;
            if (empty($member)) $this->memberMsg(lang('m-log-14'), url('member/login'));
            $oauth = $this->model('oauth');
            //更新登录时间
            $oauth->update(array('logintime'=>time(), 'logintimes'=>$omember['logintime'], 'oauth_data'=>array2string($oauth_data)), 'id=' . $omember['id']);
            $this->update_login_info($member);
            set_cookie('member_id', $member['id'], 24*3600); //保存会话24小时。
            set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), 24*3600);
            if ($this->memberconfig['uc_use'] == 1) {
                list($uid) = uc_get_user($member['username']);
                if ($uid > 0) {
                    $ucsynlogin = uc_user_synlogin($uid);
                    $this->memberMsg(lang('m-log-4') . $ucsynlogin, url('member/'), 1);
                }
            }
            $this->memberMsg(lang('m-log-4'), url('member/'), 1);
        }
    }

    /**
     * 一键登录绑定会员
     */
    public function bangAction() {
        $type = $this->post('type');
        $data = $this->post('data');
        if ($type == 'bang') {
            //绑定会员
            $member = $this->member->where('username=?', $data['username'])->select(false);
            if ($member) {
                if ($this->memberconfig['uc_use'] == 1) {
                    list($uid, $username, $password, $email) = uc_user_login($data['username'], $data['password']);
                    if (!$uid > 0) $this->memberMsg(lang('m-reg-6'));
                } elseif (md5(md5($data['password']) . $member['salt'] . md5($data['password'])) != $member['password']) {
                    $this->memberMsg(lang('m-reg-6'));
                }
                $config = $this->loadOauth();
                $row    = get_user_info($config, $this->session->get('oauth_data'));
                $row['username'] = $member['username'];
                $row['nickname'] = (string)$row['name'];
                $this->bang($row);
                //登录cookie
                set_cookie('member_id', $member['id'], 24*3600);
                set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
                $this->memberMsg(lang('m-reg-5'), url('member'), 1);
            } else {
                $this->memberMsg(lang('m-reg-6'));
            }
        } elseif ($type == 'reg') {
            //注册会员
            $this->check($data);
            $uid = $this->reg($data);
            if (empty($uid)) $this->memberMsg(lang('m-reg-2'));
            $data['id'] = $uid;
            $this->bang($data);
            $this->regEmail($data); //注册邮件提示
            //登录cookie
            set_cookiet('member_id', $uid, 24*3600);
            set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $uid), 5, 20), $time);
            $this->memberMsg(lang('m-reg-3'), url('member'), 1);
        } else {
            $this->memberMsg(lang('m-pms-8'));
        }
    }

    /**
     * 绑定
     */
    private function bang($data) {
        $oauth_data	= $this->session->get('oauth_data');
        $oauth_name	= $this->session->get('oauth_name');
        if (empty($oauth_data['oauth_openid']) || empty($oauth_name)) $this->memberMsg(lang('m-reg-15'), url('member/login'));
        $oauth  = $this->model('oauth');
        $member	= $oauth->where('oauth_openid=?', $oauth_data['oauth_openid'])->where('oauth_name=?', $oauth_name)->select(false);
        if ($member) $this->memberMsg(lang('m-reg-16'));
        $data['logintime']		= $data['addtime'] = time();
        $data['oauth_data']		= array2string($oauth_data);
        $data['oauth_name']		= $oauth_name;
        $data['oauth_openid']	= $oauth_data['oauth_openid'];
        unset($data['id']);
        $oauth->insert($data);
    }


    function checkusernameAction(){
        $username = $_POST["param"]; //传过来的值
        $arr = array( "info"=>"", "status"=>"y" );
        // 失败
        if($this->member->check_username($username))
        {
            $arr = array( "info"=>lang('会员已被注册'), "status"=>"n" );
        }
        echo json_encode($arr);
    }

    function fenxiangAction(){
        $member_id = get_cookie('yuangong_id');
        if(!$member_id){
            $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/login/member');
            $this->redirect(url('member/login/member', array('back' => urlencode($back))));
        }
        $member = $this->db->where('id',$member_id)->get('member')->row_array();
        //.$_SERVER ['HTTP_HOST']

        $url = "http://".$_SERVER ['HTTP_HOST']."/".url('member/register',array('parentid' => $member_id));
        $erwei = create_erweima($url,$member_id);
        $renshu = $this->member->get_fenxiang_renshu($member_id);
        $this->view->assign(array(
            'member' => $member,
            'renshu' => $renshu,
            'indexc' => 'fenxiang',
            'erwei' => $erwei,
            'meta_title' => lang('分享'),
        ));
        $this->view->display('member/member_fenxiang');
    }

}