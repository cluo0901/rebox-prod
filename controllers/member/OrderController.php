<?php

class OrderController extends Member {
    public $order;
	
    public function __construct() {
		parent::__construct();
		$this->order = $this->model('order');
		
		if(!get_cookie('openid')){
		  $this->oauthlogin();//微信授权跟获取
		}
		
	}
	
	/**
	 * 订单列表
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
		$where = '1=1 and o.show=1 and o.member_id='.$member_id;
		$status = (int)$this->get('status');
		$pay_status = (int)$this->get('pay_status');//支付状态
		if($status){
			$where .= ' and o.status='.$status.' and o.pay_status=2';
		}
		if($pay_status){
			$where .= ' and o.pay_status='.$pay_status;
		}

		$page = (int)$this->get('page') ? (int)$this->get('page') : 1;
	    //$pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
		$pagesize = 10;
		
	    $urlparam = array('page' => '{page}');
		$order = 'o.id desc';
		$limit = ($page-1)*$pagesize.",$pagesize";
		
		$data =  $this->order->get_all_list($where,$order ,$limit); //列表
		//print_r($data);
		$total = $this->order->get_all_list($where,$order ,$limit,true); //统计数量	

		$count = array();	//统计各个状态的数据量
	    $pagelist = $this->instance('pagelist');	//加载分页类
		$pagelist->loadconfig();
		$pagelist = $pagelist->total($total)->url(url('member/order/', $urlparam))->num($pagesize)->page($page)->output();
		$backurl = $this->get('back') ? $this->get('back') : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/'));
		$pagenum = ceil($total/$pagesize);

		$this->view->assign(array(
			'status' => $status,
			'pay_status' => $pay_status,
			'pagenum' => $pagenum,
			'meta_title' => lang('我的订单'),
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
		$this->view->display('member/order_list');
	}
	
	
	
	public function showAction(){
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
		$data = $this->order->get_show_list($id);
		//print_r($data);
		
		$this->view->assign(array(
			'meta_title' => lang('订单详情'),
			'backurl'    => urlencode($backurl),
	        'list' => $data,
	    ));
		$this->view->display('member/order_show');
		
	}

	
	//已收货
	public function upshouhuoAction(){
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
		$data['status'] = 3;
		$data['oktime'] = time();
		$this->order->set($id,$data);
		$this->redirect(url('member/order'));
	}
	
	//隐藏订单
	public function delAction(){
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
		$data['show'] = 0;
		$this->order->set($id,$data);
		$this->redirect(url('member/order'));
	}
	
	
	//删除订单
	public function deleteAction(){
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
		$this->order->del($id);
		$this->redirect(url('member/order'));
	}
	
	
	
	
	
	
	
	

}