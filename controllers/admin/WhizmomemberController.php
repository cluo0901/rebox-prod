<?php



class WhizmomemberController extends Admin

{

    public $fei;

    public $flowerhua;

    public function __construct()

    {

        parent::__construct();

        $this->fei = $this->model('fei');

        $this->flowerhua = $this->model('flowerhua');

    }

    public function whizmomember_listAction(){

        $role_id = get_cookie('role_id'); // get admin role

    	$page = (int)$this->get('page')?(int)$this->get('page'):1;

		$limit = ($page-1)*$this->admin_pagesize .",$this->admin_pagesize";

		$order = 'addtime desc';

		$urlparam = array('page' => '{page}');

        //
        $member_phone = $this->safe('member_phone');

        $where = "1=1";

        if($member_phone){

            $where .= " and phone = $member_phone";

        }

        $list = $this->fei->get_all_list('member',$order,$limit,$where);

        $total = $this->fei->get_all_list('member',$order,$limit,$where,'id',true);



  //       $list = $this->fei->get_all_list('member',$order,$limit,'1=1');

		// $total = $this->fei->get_all_list('member',$order,$limit,'1=1','id',true);




        $pagelist = $this->instance('pagelist');//加载分页类

        $pagelist->loadconfig();

        $pagelist = $pagelist->total($total)->url(url('admin/whizmomember/whizmomember_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();



		foreach ($list as $key => $value) {

            if($value['country']){

                $list[$key]['country'] = $this->fei->get_field('whizmo_type',"id = ".$value['country'],'country');

            }

		}

    	$this->view->assign(array('list'=>$list,'pagelist'=>$pagelist, 'role_id' => $role_id)); // add in role_id

    	$this->view->display('admin/whizmomember_list');

    }

    public function whizmomember_editAction(){

        $id = $this->get('id');

        if($this->post('submit')){

            $arr = $this->post('data');
            $arr['date_of_birth'] = date("Y-m-d", strtotime($arr['date_of_birth']));
            $res = $this->fei->db_update('member',$arr,"id = $id");

            if($res){

                // $this->adminMsg(lang('success!'),url('admin/whizmomember/whizmomember_list'),2,1,1);

                $this->adminMsg(lang('success!'),url('admin/whizmomember/whizmomember_edit',array('id'=>$id)),3,1,1);

            }

        }

        $data = $this->fei->get_one('member',"id = $id",'');
        if($data['date_of_birth'] == "0000-00-00"){
            $data['date_of_birth'] = "0000-00-00";
        }else{
            $date=date_create($data['date_of_birth']);
            $data['date_of_birth'] = date_format($date,"F d, Y");
        }
        
    	$address = $this->fei->get_one('whizmo_address',"member_id = $id",'address,unit');

        $sftype_list = $this->fei->get_all_where('whizmo_type',"status=1",'');

        $country_list = $this->fei->get_all_where('whizmo_type',"status=2",'');


    	$this->view->assign(array(

            'data'=>$data,

            'address'=>$address,

            'sftype_list'=>$sftype_list,

            'country_list'=>$country_list,

            ));

    	$this->view->display('admin/whizmomember_edit');

    }

    public function delmemberAction(){

    	$id = $this->post('id');

    	$res = $this->fei->db_del('member',"id = $id");

    	if($res){

    		die(json_encode(array('code'=>1,'info'=>'successfully deleted')));

    	}

    }

    public function shenheAction(){

    	$id = $this->get('id');

        $sex = $this->get('sex'); // update gender as well

    	$res = $this->fei->db_update('member',array('status'=>2, 'sex' => $sex),"id = $id");

    	if($res){

    		$this->adminMsg(lang('success'),url('admin/whizmomember/whizmomember_list'),2,1,1);

    	}

    	

    }





    public function typeAction(){

    	$status = $this->get('status');

        $page = (int)$this->get('page')?(int)$this->get('page'):1;

        $limit = ($page-1)*$this->admin_pagesize .",$this->admin_pagesize";

        $order = 'addtime desc';

        $urlparam = array('page' => '{page}','status'=>$status);

        $total = $this->fei->get_all_list('whizmo_type',$order,$limit,"status = $status",$id='id',true);

        $pagelist = $this->instance('pagelist');//加载分页类

        $pagelist->loadconfig();

        $pagelist = $pagelist->total($total)->url(url('admin/whizmomember/type', $urlparam))->num($this->admin_pagesize)->page($page)->output();

        $list = $this->fei->get_all_list('whizmo_type',$order,$limit,"status = $status");



    	$this->view->assign(array('list'=>$list,'status'=>$status,'pagelist'=>$pagelist));

    	$this->view->display('admin/whizmo_type');

    }

    public function typeaddAction(){

    	$status = $this->get('status');

    	if($this->post('submit')){

    		$data['idtype'] = $this->post('idtype');

    		$country = array_filter($this->post('country'));

    		$data['addtime'] = time();

    		if($data['idtype']){

    			$data['status']=1;

    			$res = $this->fei->db_add('whizmo_type',$data);

    		}

    		if($country){

    			$data['status']=2;

                foreach ($country as  $value) {

                    $data['country']=$value;

                    $result = $this->fei->get_field('whizmo_type',"country="."'".$value."'",'country');

                    if(empty($result)){

                        $res = $this->fei->db_add('whizmo_type',$data);

                    }

                }

    		}

    		if($res){

    			$this->adminMsg(lang('success'),url('admin/whizmomember/type',array('status'=>$status)),2,1,1);

    		}

    	}

    	$this->view->assign(array('status'=>$status));

    	$this->view->display('admin/whizmo_typeadd');

    }

    public function deltypeAction(){

    	$id = $this->post('id');

    	$res = $this->fei->db_del('whizmo_type',"id = $id");

    	if($res){

    		die(json_encode(array('code'=>1,'info'=>'successfully deleted')));

    	}

    }

    

}    