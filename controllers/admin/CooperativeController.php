<?php
  date_default_timezone_set('PRC');
class CooperativeController extends Admin{

        public $fei;
        public function __construct()
        {
            parent::__construct();
            $this->fei = $this->model('Fei');
        }
        public function listAction(){
			$where = "1=1";
			$page = (int)$this->get('page')?(int)$this->get('page'):1;
			$limit = ($page-1)*$this->admin_pagesize .",$this->admin_pagesize";
			$order = 'sort desc';
			$urlparam = array('page' => '{page}');
			$total = $this->fei->get_all_list('whizmo_partner',$order,$limit,$where,$id='id',true);
	        $pagelist = $this->instance('pagelist');//加载分页类
	        $pagelist->loadconfig();
	        $pagelist = $pagelist->total($total)->url(url('admin/cooperative/list', $urlparam))->num($this->admin_pagesize)->page($page)->output();
			$list = $this->fei->get_all_list('whizmo_partner',$order,$limit,$where);
			if ($this->post('submit_del')==1) {
                    foreach ($_POST as $var=>$value) {
                        if (strpos($var, 'data_')!==false) {
                            $id = (int)str_replace('data_', '', $var);
                            $info['sort'] = $value ? $value : 0;
                            $this->fei->db_update('whizmo_partner',$info,"id = $id");
                        }
                    }
                    $this->adminMsg('Sort success', url('admin/cooperative/list',$urlparam), 3, 1, 1);
            }
	        $this->view->assign(array('list'=>$list,'pagelist'=>$pagelist));
	        $this->view->display('admin/partner_list');
        }
        public function showAction(){
        	$id = $this->post('id');
        	$str = $this->post('str');
        	if($str==1){
        		$res = $this->fei->db_update('whizmo_partner',array('status'=>1),"id = $id");
        		if($res){
        			die(json_encode(array('code'=>1,'info'=>'Display success')));
        		}
        	}
        	if($str==2){
        		$res1 = $this->fei->db_update('whizmo_partner',array('status'=>2),"id = $id");
        		if($res1){
        			die(json_encode(array('code'=>1,'info'=>'Do not show success')));
        		}
        	}

        }

        public function delpartnerAction(){
        	$id = $this->post('id');
        	$res = $this->fei->db_del('whizmo_partner',"id = $id");
        	if($res){
        		die(json_encode(array('code'=>1,'info'=>'successfully deleted')));
        	}
        }
        public function editAction(){
        	$id = $this->get('id');
        	if($this->post('submit')){
        		$data = $this->post('data');
        		$data['addtime'] = time();
        		if($id){
        			$this->fei->db_update('whizmo_partner',$data,"id = $id");
        		} else {
        			$this->fei->db_add('whizmo_partner',$data);
        		}
        		$this->adminMsg(lang('success'),url('admin/cooperative/list'),2,1,1);
        	} else {
	        	if($id){
	        		$data = $this->fei->get_one('whizmo_partner',"id = $id",'');
	        	}
	        	$this->view->assign(array('data'=>$data));
	        	$this->view->display('admin/partner_edit');
        	}
        }

       public function footerAction(){
       	$where = "1=1";
			$page = (int)$this->get('page')?(int)$this->get('page'):1;
			$limit = ($page-1)*$this->admin_pagesize .",$this->admin_pagesize";
			$order = 'sort desc';
			$urlparam = array('page' => '{page}');
			$total = $this->fei->get_all_list('whizmo_footer',$order,$limit,$where,true);
	        $pagelist = $this->instance('pagelist');//加载分页类
	        $pagelist->loadconfig();
	        $pagelist = $pagelist->total($total)->url(url('admin/cooperative/footer', $urlparam))->num($this->admin_pagesize)->page($page)->output();
			$list = $this->fei->get_all_list('whizmo_footer',$order,$limit,$where);
			if ($this->post('submit_del')==1) {
                    foreach ($_POST as $var=>$value) {
                        if (strpos($var, 'data_')!==false) {
                            $id = (int)str_replace('data_', '', $var);
                            $info['sort'] = $value ? $value : 0;
                            $this->fei->db_update('whizmo_footer',$info,"id = $id");
                        }
                    }
                    $this->adminMsg('Sort success', url('admin/cooperative/footer',$urlparam), 3, 1, 1);
            }
	        $this->view->assign(array('list'=>$list,'pagelist'=>$pagelist));
       	$this->view->display('admin/partner_footer');
       }

       public function footeraddAction(){
       		$id = $this->get('id');
        	if($this->post('submit')){
        		$data = $this->post('data');
        		$data['addtime'] = time();
        		if($id){
        			$this->fei->db_update('whizmo_footer',$data,"id = $id");
        		} else {
        			$this->fei->db_add('whizmo_footer',$data);
        		}
        		$this->adminMsg(lang('success'),url('admin/cooperative/footer'),2,1,1);
        	} else {
	        	if($id){
	        		$data = $this->fei->get_one('whizmo_footer',"id = $id",'');
	        	}
	        	$this->view->assign(array('data'=>$data));
	        	$this->view->display('admin/partner_footeradd');
        	}
       } 

        public function footershowAction(){
        	$id = $this->post('id');
        	$str = $this->post('str');
        	if($str==1){
        		$res = $this->fei->db_update('whizmo_footer',array('status'=>1),"id = $id");
        		if($res){
        			die(json_encode(array('code'=>1,'info'=>'Display success')));
        		}
        	}
        	if($str==2){
        		$res1 = $this->fei->db_update('whizmo_footer',array('status'=>2),"id = $id");
        		if($res1){
        			die(json_encode(array('code'=>1,'info'=>'Do not show success')));
        		}
        	}

        }

        public function delfooterAction(){
        	$id = $this->post('id');
        	$res = $this->fei->db_del('whizmo_footer',"id = $id");
        	if($res){
        		die(json_encode(array('code'=>1,'info'=>'successfully deleted')));
        	}
        }

        public function popupAction(){
        	$data = $this->fei->get_one('whizmo_popup',"id = 1",'');
        	if($this->post('submit')){
        		$arr = $this->post('data');
        		if($data){
        			$this->fei->db_update('whizmo_popup',$arr,"id = 1");
        		} else {
        			$this->fei->db_add('whizmo_popup',$arr);
        		}
        		$this->adminMsg(lang('success'),url('admin/cooperative/popup'),2,1,1);
        	}else{
        		$this->view->assign(array('data'=>$data));
        		$this->view->display('admin/whizmo_popup');
        	}
        	
        }
}        
