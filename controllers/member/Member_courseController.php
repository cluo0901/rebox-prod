<?php

class Member_courseController extends Member {
    public $linkage;
	public $yuangong;
	public $yuangong_course;
	public $yuangong_type;
	public $yuangong_yuding;
	
	
    public function __construct() {
		parent::__construct();
		$this->linkage = $this->model('linkage');
		$this->yuangong = $this->model('yuangong');
		$this->yuangong_course = $this->model('yuangong_course');
		$this->yuangong_type = $this->model('yuangong_type');
		$this->yuangong_yuding = $this->model('yuangong_yuding');
		
		if(!get_cookie('openid')){
		  $this->oauthlogin();//微信授权跟获取
		}
		
	}
	
	/**
	 * 课程列表
	 */
	public function indexAction() {
		$member_id = get_cookie('yuangong_id');//判断是否已经登录
		if(empty($member_id)){
			$back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/');
			$this->redirect(url('member/', array('back' => urlencode($back))));
		}
		$type = get_cookie('type');
		if($type){
			$this->redirect(url('member/'));
		}
		$member = $this->db->where('id',$member_id)->get('member')->row_array();
		$status = (int)$this->get('status');
		$where = '1=1 and d.member_id='.$member_id;
		if($status == 1){//1,未上课程
			$where .= ' and c.endtime>'.time().' and d.status=1';
		}else if($status == 2){//已上课程
			$where .= ' and d.status=2';
		}else if($status == 3){//1,已过期
			$where .= ' and c.endtime<'.time().' and d.status=1';
		}
		
		
		$page = (int)$this->get('page') ? (int)$this->get('page') : 1;
	    //$pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
		$pagesize = 8;
		
	    $urlparam = array('page' => '{page}');
		$order = 'd.id desc';
		$limit = ($page-1)*$pagesize.",$pagesize";
		
		$data =  $this->yuangong_yuding->get_member_course($where,$order ,$limit); //列表
		//print_r($data);
		$total = $this->yuangong_yuding->get_member_course($where,$order ,$limit,true); //统计数量

		$count = array();	//统计各个状态的数据量
	    $pagelist = $this->instance('pagelist');	//加载分页类
		$pagelist->loadconfig();
		$pagelist = $pagelist->total($total)->url(url('member/yuangong_course/', $urlparam))->num($pagesize)->page($page)->output();
		$backurl = $this->get('back') ? $this->get('back') : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/'));
		$pagenum = ceil($total/$pagesize);
		
		$wh = 'd.status=2 and d.member_id='.$member_id;
		$yishang = $this->yuangong_yuding->get_member_coursenum($wh);//已上课
		
		$ws = 'd.status=1 and d.member_id='.$member_id.' and c.endtime>'.time();
		$weishang = $this->yuangong_yuding->get_member_coursenum($ws);//未上课
		

		$this->view->assign(array(
			'indexc' => 'course',
			'yishang' => $yishang,
			'weishang' => $weishang,
			'member' => $member,
			'status' => $status,
			'pagenum' => $pagenum,
			'meta_title' => lang('我的课程'),
			'backurl'    => urlencode($backurl),
	        'page' => $page,
	        'list' => $data,
			'total' => $total,
			'model' => $model[$modelid],
	        'catid' => $catid,
			'count' => $count,
			'modelid' => $modelid,
	        'pagelist' => $pagelist
	    ));
		$this->view->display('member/member_course');
	}
	
	
	
	
	/**
	 * 课程列表
	 */
	public function showAction() {
		$member_id = get_cookie('yuangong_id');//判断是否已经登录
		if(empty($member_id)){
			$back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/');
			$this->redirect(url('member/', array('back' => urlencode($back))));
		}
		$type = get_cookie('type');
		if($type){
			$this->redirect(url('member/'));
		}
		$id = (int)$this->get('id');
		$data = $this->yuangong_course->get_one_list($id);
		//print_r($data);
		
		$this->view->assign(array(
			'indexc' => 'course',
			'member' => $member,
			'meta_title' => lang('课程详情'),
	        'list' => $data,
	    ));
		$this->view->display('member/member_course_show');
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}