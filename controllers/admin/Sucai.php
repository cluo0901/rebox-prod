<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dayrui Website Management System
 *
 * @since		version 2.5.0
 * @author		Dayrui <dayrui@gmail.com>
 * @license     http://www.dayrui.com/license
 * @copyright   Copyright (c) 2011 - 9999, Dayrui.Com, Inc.
 * 微信回复
 */
	
class Sucai extends Admin {

    public $file;
    protected $wx_config;
    protected $expires_in;
    protected $cache_file;

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->expires_in = 7200;
        $this->file = FCPATH.'config/weixin.php';
        $this->cache_file = 'wx_user';
        if(file_exists($this->file))
            $this->wx_config = unserialize(file_get_contents($this->file));
        $this->createTable();
    }
	

    /**
     * 素材管理主页
     */
    public function index()
    {
        if ($this->post('delete')) {
            $ids = $this->post('ids');
            if ($ids) {
                foreach ($ids as $id) {
                    $this->delResource($id, 1);
                }
            }
            $this->adminMsg(lang('success'), url('admin/wx/index'), 3, 1, 1);
        }

        $data = $this->_pagelist(10,'wx_content',(int)$this->get('page'),'admin/wx/index');
        $this->template->assign(
            array(
                'data' => $data['data'],
                'pages'=> $data['pages'],
            )
        );
        $this->template->display('admin/wx_resources_list.html');
    }

    /**
     * 添加素材
     */
    public function addResource()
    {

        if(IS_POST)
        {
            $data = $this->input->post('data');
            $data['tid'] = 0;


            if($data['title'] == NUll or $data['content'] == NULL)
            {
                $this->adminMsg('素材标题或正文不能为空');
            }

            $arr = $data;

            $arr['inputtime'] = time();
            $this->db->insert('wx_content',$arr);

            $this->adminMsg('素材添加成功！',url('admin/wx/index'),2,1,1);

        }

        $this->assign(
            array(
                'data' => $data
            )
        );
        $this->template->display('admin/wx_add_resource.html');
    }

    /**
     * 编辑素材
     */
    public function editResource()
    {
        $id = $this->input->get('id');

        if(IS_POST)
        {
            $data = $this->input->post('data');


            if($data['title'] == NUll or $data['content'] == NULL)
            {
                $this->adminMsg('素材标题或正文不能为空');
            }

            $arr = $data;

            $arr['inputtime'] = time();
            $this->db
                ->where('id',$id)
                ->update('wx_content',$arr);

            $this->adminMsg('素材修改成功！',url('admin/wx/index'),2,1,1);
        }
        $list = $this->db
            ->where('id',$id)
            ->get('wx_content')->row_array();

        $list['images'] = unserialize($list['thumb']);
        $this->template->assign(
            array(
                'data' => $list,
            )
        );
        $this->template->display('admin/wx_add_resource.html');
    }

    /**
     *  删除素材
     * @param int $id
     * @param int $all
     */
    public function delResource($id = 0, $all = 0)
    {
        $id = $id ? $id : (int)$this->get('id');
        $all = $all ? $all : $this->get('all');

        $result = $this->db
            ->where('id',$id)
            ->delete('wx_content');

        if ($result) {
            $all or $this->adminMsg(lang('success'), url('admin/wx/index'), 3, 1, 1);
        } else {
            $all or $this->adminMsg(lang('a-cat-8'));
        }
    }
/////////////////////////////////REPLY////////////////////////////////////////

    /**
     * 回复关键字管理主页
     */
    public function keyword()
    {

        if ($this->post('delete')) {
            $ids = $this->post('ids');
            if ($ids) {
                foreach ($ids as $id) {
                    $this->delKeyword($id, 1);
                }
            }
            $this->adminMsg(lang('success'), url('admin/wx/keyword'), 3, 1, 1);
        }
        $data = $this->_pagelist(15,'wx_reply',(int)$this->get('page'),'admin/wx/keyword');
        $this->template->assign(
            array(
                'list' => $data['data'],
                'pages' => $data['pages'],
            )
        );
        $this->template->display('admin/wx_keyword.html');
    }


    /**
     * 添加回复关键字
     */
    public function addKeyword()
    {

        if(IS_POST)
        {
            $data = $this->input->post('data');
            if($data['type'] != 1){
			if($data['content'] == null || $data['keyword'] == null)
              $this->adminMsg('添加关键字或内容不能为空！');
			}else{
			  if( $data['keyword'] == null)
              $this->adminMsg('添加关键字为空！');
			}

            $keywords = explode(' ',$data['keyword']);
            foreach ($keywords as $keyword){
                if($keyword) {
                    $data['keyword'] = $keyword;
                    if(!$this->db->where('keyword =',$keyword)->get('wx_reply')->row_array())
                        $this->db->insert('wx_reply', $data);
                    else
                        $this->adminMsg("关键字“{$keyword}”已经存在！");
                }
            }


            $this->adminMsg('添加成功！',url('admin/wx/keyword'),3,1,1);
        }
        $resources = $this->db->get('wx_content')->result_array();

        $this->template->assign(
            array(
                'resources' => $resources,

            )
        );
        $this->template->display('admin/wx_add_keyword');
    }

    /**
     * 编辑回复关键字
     */
    public function editKeyword()
    {
        $id = $this->input->get('id');

        if(IS_POST)
        {
            $data = $this->input->post('data');

            $this->db
                ->where('id',$id)
                ->update('wx_reply',$data);
            $this->adminMsg('修改成功！',url('admin/wx/keyword'),3,1,1);

        }
        $data1 = $this->db
            ->where('id',$id)
            ->get('wx_reply')
            ->row_array();
        $resources = $this->db->get('wx_content')->result_array();

        $this->template->assign(
            array(
                'resources' => $resources,
                'data'      => $data1

            )
        );
        $this->template->display('admin/wx_add_keyword');
    }

    /**
     *  删除回复关键字
     * @param int $id
     * @param int $all
     */
    public function delKeyword($id = 0, $all =0)
    {
        $id = $id ? $id : (int)$this->get('id');
        $all = $all ? $all : $this->get('all');

        $result = $this->db
            ->where('id',$id)
            ->delete('wx_reply');

        if ($result) {
            $all or $this->adminMsg(lang('success'), url('admin/wx/keyword'), 3, 1, 1);
        } else {
            $all or $this->adminMsg(lang('a-cat-8'));
        }
    }
    //////////////////////////RESOLVE METHOD////////////////////////////
    /**
     * 获取access_token
     * @return mixed
     */
    protected function _get_access_token() {

        $name = APP_ROOT.'cache/'.SITE_ID.'_access_token.txt';
        $data = @json_decode(@file_get_contents($name), true);
        if (isset($data['time']) && $data['time'] > time() && isset($data['access_token']) && $data['access_token']) {
            return $data['access_token'];
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$this->wx_config['appid'].'&secret='.$this->wx_config['appsecret'];
        $data = json_decode(@dr_catcher_data($url), true);
        if (!$data) {
            @unlink($name);
            $this->adminMsg('获取access_token失败，请检查服务器是否支持远程链接');
        }
        if (isset($data['errmsg']) && $data['errmsg']) {
            @unlink($name);
            $this->adminMsg('错误代码（'.$data['errcode'].'）：'.$data['errmsg']);
        }
        $data['time'] = time() + $data['expires_in'];
        @file_put_contents($name, json_encode($data));

        return $data['access_token'];
    }
    /**
     * POST请求
     */
    protected function _post($url, $params) {
        if (function_exists('curl_init')) { // curl方式
            $oCurl = curl_init();
            if (stripos($url, 'https://') !== FALSE) {
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            }
            $string = $params;
            if (is_array($params)) {
                $aPOST = array();
                foreach ($params as $key => $val){
                    $aPOST[] = $key.'='.urlencode($val);
                }
                $string = join('&', $aPOST);
            }
            curl_setopt($oCurl, CURLOPT_URL, $url);
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($oCurl, CURLOPT_POST, TRUE);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $string);
            $response = curl_exec($oCurl);
            curl_close($oCurl);
            return $response;
        } elseif (function_exists('stream_context_create')) { // php5.3以上
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($params),
                )
            );
            $_opts = stream_context_get_params(stream_context_get_default());
            $context = stream_context_create(array_merge_recursive($_opts['options'], $opts));
            return file_get_contents($url, false, $context);
        } else {
            return FALSE;
        }
    }
    // 转化为json
    protected function _en_json($data) {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            return urldecode(json_encode(_url_encode($data)));
        } else {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 创建表
     */
    protected function createTable()
    {
        $this->db->query(" CREATE TABLE IF NOT EXISTS `".$this->db->dbprefix."wx_content` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `tid` tinyint(1) unsigned NOT NULL COMMENT '素材类型',
          `cid` varchar(255) DEFAULT NULL COMMENT '关联内容id',
          `title` varchar(255) NOT NULL COMMENT '标题',
          `thumb` varchar(255) NOT NULL COMMENT '图片',
          `description` varchar(255) DEFAULT NULL COMMENT '描述',
          `url` varchar(255) DEFAULT NULL COMMENT '更多阅读地址',
          `content` text NOT NULL COMMENT '详细内容',
          `orther` mediumtext COMMENT '其他数据信息',
          `inputtime` int(10) unsigned NOT NULL COMMENT '输入时间',
          PRIMARY KEY (`id`),
          KEY `tid` (`tid`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT '素材内容表';
                ");
        $this->db->query("CREATE TABLE IF NOT EXISTS `".$this->db->dbprefix."wx_app` (
          `id` int(10) NOT NULL AUTO_INCREMENT,
          `type` tinyint(1) NOT NULL COMMENT '应用类型',
          `name` varchar(50) NOT NULL COMMENT '应用名称',
          `images` text NOT NULL,
          `filename` varchar(50) NOT NULL COMMENT '文件名称',
          `config` text COMMENT '应用本身信息',
          `setting` text COMMENT '应用配置信息',
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT '微信应用表';
                        ");
        $this->db->query("CREATE TABLE IF NOT EXISTS `".$this->db->dbprefix."wx_menu` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `pid` int(10) unsigned NOT NULL,
          `type` char(10) NOT NULL,
          `name` varchar(30) NOT NULL,
          `key` varchar(30) DEFAULT NULL,
          `url` varchar(255) DEFAULT NULL,
          `displayorder` tinyint(3) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `pid` (`pid`),
          KEY `displayorder` (`displayorder`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT '微信菜单表'; ");
        $this->db->query(" CREATE TABLE IF NOT EXISTS `".$this->db->dbprefix."wx_reply` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '回复类型',
          `keyword` varchar(255) NOT NULL COMMENT '关键字',
          `app` varchar(30) DEFAULT NULL COMMENT '应用目录',
          `cid` int(10) NOT NULL DEFAULT '0' COMMENT '素材id',
          `content` text COMMENT '文本信息',
          `count` int(10) NOT NULL DEFAULT '0' COMMENT '总计回复次数',
          PRIMARY KEY (`id`),
          KEY `count` (`count`),
          KEY `keyword` (`keyword`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT '微信回复规则表';");
        $this->db->query(" CREATE TABLE IF NOT EXISTS `".$this->db->dbprefix."wx_user` (
          `id` int(10) NOT NULL AUTO_INCREMENT,
          `uid` int(10) unsigned DEFAULT NULL COMMENT '会员id',
          `openid` varchar(50) NOT NULL COMMENT '唯一id',
          `nickname` varchar(50) NOT NULL COMMENT '微信昵称',
          `sex` tinyint(1) unsigned DEFAULT NULL COMMENT '性别',
          `city` varchar(30) DEFAULT NULL COMMENT '城市',
          `country` varchar(30) DEFAULT NULL COMMENT '国家',
          `province` varchar(30) DEFAULT NULL COMMENT '省',
          `language` varchar(30) DEFAULT NULL COMMENT '语言',
          `headimgurl` varchar(255) DEFAULT NULL COMMENT '头像地址',
          `subscribe_time` int(10) unsigned NOT NULL COMMENT '关注时间',
          `location_x` varchar(20) DEFAULT NULL COMMENT '坐标',
          `location_y` varchar(20) DEFAULT NULL COMMENT '坐标',
          `location_info` varchar(255) DEFAULT NULL COMMENT '坐标详情',
          `msg_today` INT( 10 ) NOT NULL DEFAULT '0' COMMENT '每日消息的发送时间',
          PRIMARY KEY (`id`),
          KEY `uid` (`uid`),
          KEY `msg_today` (`msg_today`),
          KEY `openid` (`openid`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT '微信会员表';");

    }

    /**
     * 分页
     * @param int $pagesize
     * @param $table
     * @param int $page
     * @param $url
     * @param string $order
     * @return array
     */
    protected function _pagelist($pagesize = 8,$table,$page = 1,$url,$order = 'id DESC')
    {
        $page     = (!$page) ? 1 : $page;
        $urlparam['page']   = '{page}';
        $pagelist = $this->instance('pagelist');
        $pagelist->loadconfig();
        $total = $this->db->count_all_results($table);
        $data     = $this->db->limit($pagesize,($page-1)*$pagesize)->order_by($order)->get($table)->result_array();
        $pagelist = $pagelist->total($total)->url(url($url, $urlparam))->num($pagesize)->page($page)->output();

        return array('data'=>$data,'pages'=>$pagelist);
    }

}
/**
 * url 编码
 */
function _url_encode($str) {
    if (is_array($str)) {
        foreach($str as $key=>$value) {
            $str[urlencode($key)] = _url_encode($value);
        }
    } else {
        $str = urlencode($str);
    }

    return $str;
}

