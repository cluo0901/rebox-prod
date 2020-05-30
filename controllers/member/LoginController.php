<?php

class LoginController extends Member
{
    public $linkage;
    public $member;

    public function __construct()
    {
        parent::__construct();
        $this->linkage = $this->model('linkage');
        $this->member = $this->model('member');
    }

    /**
     * 会员登录
     */
    public function indexAction()
    {
        $member_id = (int)get_cookie('member_id');//判断是否已经登录
        if ($member_id) {
            $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/');
            $this->redirect(url('member/', array('back' => urlencode($back))));
        }
        if ($this->isPostForm()) {
            $data = $this->post('data');
            $data['username'] = trim($data['username']);
            $data['password'] = trim($data['password']);
            if (empty($data['username']) || empty($data['password'])) {
                $this->msg(lang('m-log-2'), $_SERVER["HTTP_REFERER"], 'no');
                die;
            }
            $member = $this->db->where('username', $data['username'])->get('member')->row_array();
            if (empty($member)) {//判断用户名是否存在
                $member = $this->db->where('email', $data['username'])->get('member')->row_array();
                if (empty($member)) {
                    $this->msg(lang('m-log-8'), $_SERVER["HTTP_REFERER"], 'no');
                    die;
                }
            }
            $time = empty($data['cookie']) ? 24 * 3600 : 30 * 24 * 3600; //会话保存时间。
            if ($member['password'] != md5(md5($data['password']))) {
                $this->msg('密码错误!', $_SERVER["HTTP_REFERER"], 'no');
                die;
            }
            $this->member->update_login_info($member);
            set_cookie('member_id', $member['id'], $time);
//            set_cookie('member_code', substr(md5(SITE_MEMBER_COOKIE . $member['id']), 5, 20), $time);
            //$this->adminMsg(lang('m-log-4'), $backurl, 2,1,1);
            $this->msg('Log on successfully!', url('member/'), 'ok');
            die;
        }
//        $backurl = $this->get('back') ? htmlspecialchars_decode($this->get('back')) : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/'));
        $this->view->assign(array(
            'meta_title' => lang('会员登录'),
//            'backurl' => urlencode($backurl),
        ));
        $this->view->display('member_login');

    }

    /**
     * 一键登录
     */
    public function oauthAction()
    {
        $oauthname = $this->get('name');
        if ($this->memberconfig['isoauth'] && $oauthname) {
            $config = $this->loadOauth($oauthname);
            oauth_login($config);
        } else {
            $this->memberMsg(lang('m-log-10'), url('member/login'));
        }
    }

    //聚合数据发送短信
    public function sendcodeAction()
    {
        $username = trim($this->post('username'));
        $mid = $this->member->check_username($username);
        if ($mid) {//号码已存在
            echo 2;exit;
        }
        $randomkey = randomkeys(6);
        $res = juhe_sendcode($username, 35320, $randomkey);
        if ($res == 0) {
            echo 1;
            set_cookie("randomkey", $randomkey, 16800);
            set_cookie("rand_username", $username, 16800);
            exit;
        } else {
            echo 3;exit;
        }
    }

    //聚合数据发送短信
    public function sendcode2Action()
    {
        $username = trim($this->post('username'));
        $mid = $this->member->check_username($username);
        if ($mid) {//号码已存在
            echo 2;exit;
        }
        $randomkey = randomkeys(6);
        $res = juhe_sendcode($username, 37252, $randomkey);
        if ($res == 0) {
            echo 1;
            set_cookie("randomkey", $randomkey, 16800);
            set_cookie("rand_username", $username, 16800);
            exit;
        } else {
            echo 3;exit;
        }
    }

        //ajax判断验证码是否正确
    public function ajaxcodeAction()
    {
        $checkcode = trim($this->post('checkcode'));
        $username = trim($this->post('username'));
        if ($checkcode != get_cookie('randomkey') || $username != get_cookie('rand_username')) {
            echo 0;exit;
        } else {
            echo 1;exit;
        }
    }

    /**
     * 文件上传
     * @param  $fields        上传字段 'file'
     * @param  $type        文件类型  array(jpg,gif)
     * @param  $size        文件大小  MB
     * @param  $img            图片配置参数
     * @param  $mark        图片水印
     * @param  $admin        是否来自后台
     * @param  $stype        上传方式  swf或者ke
     * @param  $ofile        原文件
     * @param  $document    后台栏目归档目录
     * @return Array        返回数组
     */
    private function upload($fields, $type, $size = 10, $img = null, $mark = true, $admin = 0, $stype = null, $ofile = null, $document = null)
    {
        $path = 'uploadfiles/';
        $upload = $this->instance('file_upload');
        if (empty($admin) && $this->memberinfo) {
            $uid = $this->memberinfo['id']; //会员附件归类
            if ($uid) {
                $path .= 'member/' . $uid . '/';
                if (isset($this->membergroup[$this->memberinfo['groupid']]['filesize']) && $this->membergroup[$this->memberinfo['groupid']]['filesize']) {
                    $c = count_member_size($this->memberinfo['id']);
                    if ($c > $this->membergroup[$this->memberinfo['groupid']]['filesize'] * 1024 * 1024) {
                        $this->attMsg(lang('att-7', array('1' => $this->membergroup[$this->memberinfo['groupid']]['filesize'], '2' => formatFileSize($c))), $stype);
                    }
                }
            }
            $document = null;
        } elseif ($admin) {
            $uid = (int)get_cookie('yuangong_id');
        } else {
            //$this->attMsg(lang('att-0'), $stype);
            $uid = 0;
            $patp = 'uploadfiles/guest/';
        }
        $upload->set($_FILES[$fields])->set_limit_size(1024 * 1024 * $size)->set_limit_type($type);
        //设置路径和名称
        $ext = $upload->fileext();
        if (stripos($ext, 'php') !== FALSE
            || stripos($ext, 'asp') !== FALSE
            || stripos($ext, 'aspx') !== FALSE
        ) {
            return array('result' => '文件格式被系统禁止');
        }
        if (in_array($ext, array('jpg', 'jpeg', 'bmp', 'png', 'gif'))) {
            $dir = 'image';
            $upload->set_image($img['w'], $img['h'], $img['t']);
        } else {
            $dir = 'file';
        }
        $path .= $dir . '/' . (empty($document) || $document == 'undefined' || !preg_match('/^[a-zA-Z_0-9]+$/', $document) ? '' : $document . '/');
        if ($ofile && is_file($ofile) && strpos($path, dirname(dirname($ofile))) === 0) { //判断原文件
            $path = dirname($ofile) . '/';
            $file = $fname = basename($ofile);
        } else {
            $path .= date('Ym') . '/';
            $data = file_list::get_file_list($path);
            $name = count($data) + 1;
            $name = is_file($path . $name . '.' . $ext) ? $name . str_replace('0.', '_', (double)microtime()) : $name;
            $file = $upload->filename();
            $fname = $name . '.' . $ext;
        }
        $result = $upload->upload($path, $fname);
        //上传成功处理图片
        if (!$result && $dir == 'image') {
            $this->watermark($path . $fname);
        }
        return array('result' => $result, 'path' => $path . $fname, 'file' => $file, 'ext' => $dir == 'image' ? 1 : $ext);
    }

}