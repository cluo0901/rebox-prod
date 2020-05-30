<?php

class PinglunController extends Admin {

    public $pinglun;
	public $linkage;
	
    public function __construct() {
		parent::__construct();
		$this->pinglun    = $this->model('pinglun');
		$this->linkage    = $this->model('linkage');
	}
	
	
	public function indexAction() {
	   
	   	if ($this->post('submit')) {//删除
	        foreach ($_POST as $var => $value) {
	            if (strpos($var, 'del_') !== false) {
	                $id = (int)str_replace('del_', '', $var);
	                $this->pinglun->del($id);
	            }
	        }
			$this->adminMsg(lang('success'), url('admin/pinglun/index'), 3, 1, 1);
	    }
	   
	    $page = (int)$this->get('page') ? (int)$this->get('page') : 1;
	    $pagesize = isset($this->site['SITE_ADMIN_PAGESIZE']) && $this->site['SITE_ADMIN_PAGESIZE'] ? $this->site['SITE_ADMIN_PAGESIZE'] : 8;
			 
		$where = "1=1";
		
		$us = unserialize(get_cookie('ci_session'));//后台管理员id
		if($us['user_id'] != 1){
			$user = $this->db->where('userid',$us['user_id'])->get('user')->row_array();
			if(!empty($user['city_id'])){//当管理员地区id不是0时，则该管理员为区域管理员
				$where .= " and y.city_id='".$user['city_id']."'";
				$this -> view -> assign('user_cityid',$user['city_id']);
			}
		}
		
		
		//搜索
		$province_id = trim($this->post('province_id')) ?  trim($this->post('province_id')) : trim($this->get('province_id'));
		$city_id = trim($this->post('city_id')) ?  trim($this->post('city_id')) : trim($this->get('city_id'));
		$status = trim($this->post('status')) ?  trim($this->post('status')) : trim($this->get('status'));
		$starttime =  trim($this->post('starttime')) ?  trim($this->post('starttime')) : trim($this->get('starttime'));
		$endtime =  trim($this->post('endtime')) ?  trim($this->post('endtime')) : trim($this->get('endtime'));
		if($province_id){
			$citylist = $this->linkage->get_all_list($province_id);
			$this -> view -> assign('citylist', $citylist);	
			$this -> view -> assign('province_id', $province_id);	
		}
		if($city_id){
			$where .= " and y.city_id='".$city_id."'";
			$this -> view -> assign('city_id', $city_id);	
		}
		
		if($status){
			$where .= " and yt.status=".$status;
			$this -> view -> assign('status', $status);	
		}
		if($starttime){
			$where .= " and yt.addtime > ".strtotime($starttime);
			$this -> view -> assign('starttime', $starttime);	
		}	
		if($endtime){
			$endt = strtotime($endtime)+(24*60*60);
			$where .= " and yt.addtime < ".$endt;
			$this -> view -> assign('endtime', $endtime);	
		}
		$urlparam = array('page' => '{page}','status' => $status,'province_id' => $province_id,'city_id' => $city_id, 'starttime' => $starttime,'endtime' => $endtime);
		
		$order = "id desc";
		$limit = ($page-1)*$pagesize.",$pagesize";
		$data =  $this->pinglun->get_admin_list($where,$order ,$limit); //列表
		$total = $this->pinglun->get_admin_list($where,$order ,$limit,true); //统计数量
		
	    $count = array();	//统计各个状态的数据量
	    $pagelist = $this->instance('pagelist');	//加载分页类
		$pagelist->loadconfig();
		$pagelist = $pagelist->total($total)->url(url('admin/pinglun/', $urlparam))->num($pagesize)->page($page)->output();
		
		$province = $this->linkage->get_all_list();
		

	    $this->view->assign(array(
			'province'=> $province,
	        'page' => $page,
	        'list' => $data,
			'total' => $total,
			'count' => $count,
			'status' => $status,
	        'pagelist' => $pagelist,
	    ));	
		$this->view->display('admin/pinglun_list');
	}
	

	
	public function delAction(){
        $id = (int)$this->get('id'); 
        if (empty($id)){
            $this->adminMsg(lang('all-not-del-msg'));
        }
        $this->pinglun->del($id);
        $this->adminMsg(lang('success'), url('admin/pinglun/index'), 3, 1, 1);
    }

    public function ajax_huifuAction(){
        $id = (int)$this->post('id');
        $text = $this->post('text');
        $data['huifu'] = $text;
        $return = $this->pinglun->set2($id,$data);
        if($return>0){
            echo 1;die;
        }else{
            echo 2;die;
        }
    }
	
	
	


}