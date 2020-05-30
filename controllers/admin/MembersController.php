<?php



class MembersController extends Common

{

    public $fei;

    public $flowerhua;

    public function __construct()

    {

        parent::__construct();

        $this->fei = $this->model('fei');

        $this->flowerhua = $this->model('flowerhua');



    }

    public function centerAction(){

        //check if logged in as agent
        if(get_cookie('member_id')){
            $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
        }

    	$id = $this->get('id') ? $this->get('id') : get_cookie('member_id');

    	empty($id) && $this->redirect(url('/index'));

    	$data = $this->fei->get_one('member',"id = $id",'');

    	$address = $this->fei->get_one('whizmo_address',"member_id = $id",'');

    	$type = $this->fei->get_all_where('whizmo_type',"status=1",'id,idtype');

    	$country = $this->fei->get_all_where('whizmo_type',"status=2",'id,country');

    	$recovery_tips = $this->fei->get_one('system_selected','id = 1','recovery_tips_title,recovery_tips_content');

    	$this->view->assign(array('data'=>$data,'address'=>$address,'type'=>$type,'country'=>$country,'recovery_tips'=>$recovery_tips, 'agent_check'=>$agent_check));

    	$this->view->display('center');

    }





    public function memberinfoAction(){

    	$id = get_cookie('member_id');

    	$zhaopian1 = $this->post('zhaopian1');

    	$zhaopian2 = $this->post('zhaopian2');

    	$fullname = $this->post('fullname');

    	$sftype = $this->post('sftype');

    	$nric = $this->post('nric');

    	$country = $this->post('country');

    	$sex = $this->post('sex');

    	$postal = $this->post('postal');

    	$address = $this->post('address');

    	$unit = $this->post('unit');

        if (isset($zhaopian1) && isset($zhaopian2)) {

            $status = 1;
        } else {

            $status = 1;
        }

        

    	$res = $this->fei->db_update('member',array('zhaopian1'=>$zhaopian1,'zhaopian2'=>$zhaopian2,'fullname'=>$fullname,'sftype'=>$sftype,'nric'=>$nric,'country'=>$country,'sex'=>$sex,'postal'=>$postal,'address'=>$address,'unit'=>$unit, 'status'=>$status),"id = $id");

    	if($res){

    		die(json_encode(array('code'=>1,'info'=>'Successfully submitted!')));

    	}

    	

    }

    public function member_passwordAction(){

    	$id = get_cookie('member_id');

    	$password = $this->fei->get_field('member',"id = $id",'password');

    	$password1 = $this->post('password');

    	$password1 = md5(md5($password1).md5($password1));

    	if($password1 != $password){

    		die(json_encode(array('code'=>2,'info'=>'Original password error')));

    	}else{

    		$newpassword = $this->post('newpassword1');

	    	$newpassword = md5(md5($newpassword).md5($newpassword));

	    	$res = $this->fei->db_update('member',array('password'=>$newpassword),"id = $id");

	    	if($res){

	    		die(json_encode(array('code'=>1,'info'=>'Password changed!')));

	    	}

    	}

    	

    }

    public function member_addressAction(){

    	$id = get_cookie('member_id');

    	$data['postal'] = $this->post('postal');

    	$data['address'] = $this->post('address');

    	$data['unit'] = $this->post('unit');

    	$data['member_id'] =$id;

    	$address_id = $this->fei->get_field('whizmo_address',"member_id = $id",'id');

    	if($address_id){

    		$res = $this->fei->db_update('whizmo_address',array('postal'=>$data['postal'],'address'=>$data['address'],'unit'=>$data['unit']),"id = $address_id");

    	} else {

    		$res = $this->fei->db_add('whizmo_address',$data);

    	}

    	if($res){

    		die(json_encode(array('code'=>1,'info'=>'Address Changed!')));

    	}

    }



    public function member_headerimgAction(){

        $id = get_cookie('member_id');

        $headerimg = $this->post('headerimg');

        $res = $this->fei->db_update('member',array('headerimg'=>$headerimg),"id = $id");

        if($res){

            die(json_encode(array('code'=>1,'info'=>'Submission of success')));

        }

    }





}