<?php

class RecyclerController extends Admin
{
    public $fei;
    public function __construct()
    {
        parent::__construct();
        $this->fei = $this->model('fei');
    }

    public function recycler_listAction(){
    	$where='1=1';
    	$phone = $this->safe('phone');
    	if($phone){$where .= " and phone like '%$phone%'";}
    	$page = (int)$this->get('page')?(int)$this->get('page'):1;
		$limit = ($page-1)*$this->admin_pagesize .",$this->admin_pagesize";
		$order = 'status asc,id desc';
		$urlparam = array('page' => '{page}','phone'=>$phone);


		$list = $this->fei->get_all_list('whizmo_recycler',$order,$limit,$where);
        $total = $this->fei->get_all_list('whizmo_recycler',$order,$limit,$where,'id',true);
        $pagelist = $this->instance('pagelist');//加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/recycler/recycler_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();
    	$this->view->assign(array('list'=>$list,'pagelist'=>$pagelist,'phone'=>$phone));
    	$this->view->display('admin/whizmo_recycler');
    }
    public function recycler_shenheAction(){
    	$id = $this->post('id');
    	$username = $this->post('username');
        $pwd = $this->post('pwd');
    	$password= md5(md5($pwd).md5($pwd));
        $result = $this->fei->get_one('whizmo_recycler',"username = "."'".$username."'",'');
        if($result){
            die(json_encode(array('code'=>2,'info'=>'The username has already existed')));
        }else{
            $res = $this->fei->db_update('whizmo_recycler',array('status'=>2,'password'=>$password,'username'=>$username),"id = $id");
            if($res){
                die(json_encode(array('code'=>1,'info'=>'Review and pass through')));
            }
        }
    	
    }
    public function recycler_editAction(){
    	$id = $this->get('id');
    	$data = $this->fei->get_one('whizmo_recycler',"id = $id",'');
        $lessphone_list = $this->fei->get_all_where('whizmo_recycler_lessphone',"recycler_id = $id",'');
    	$this->view->assign(array('data'=>$data,'lessphone_list'=>$lessphone_list));
    	$this->view->display('admin/whizmo_recycleredit');
    }

    public function recycler_delAction(){
        $id = (int)$this->get('id');
        if (empty($id)){
            $this->adminMsg(lang('all-not-del-msg'));
        }
        $this->fei->db_del('whizmo_recycler',"id = $id");
        $this->adminMsg(lang('success'), url('admin/recycler/recycler_list'), 3, 1, 1);
    }

    public function recycler_biddingAction(){
        $recycler_id = $this->get('id');
        $list = $this->fei->get_all_where('whizmo_bidding',"recycler_id = $recycler_id and status != -2",'');
        foreach ($list as $key => $value) {
            $order_recovery_id = $this->fei->get_field('whizmo_order',"order_id = $value[order_id]",'order_recovery_id');
            $info = $this->fei->get_one('whizmo_recovery',"id = $order_recovery_id",'');
            $list[$key]['title'] = $info['recovery_title'].$info['recovery_title2'];
            $list[$key]['recovery_img'] = $info['recovery_img'];
        }
        $this->view->assign(array('list'=>$list,'recycler_id'=>$recycler_id));
        $this->view->display('admin/recycler_bidding');
    }

    public function downloadAction(){
        $file_name = $this->get('file');     //下载文件名    
        $file_dir = "D:\phpStudy\PHPTutorial\WWW\whizmo/";        //下载文件存放目录    
        //检查文件是否存在    
        if (! file_exists ( $file_dir . $file_name )) {    
            echo "文件找不到";    
            exit ();    
        } else {    
            //打开文件    
            $file = fopen ( $file_dir . $file_name, "r" );    
            //输入文件标签     
            Header ( "Content-type: application/octet-stream" );    
            Header ( "Accept-Ranges: bytes" );    
            Header ( "Accept-Length: " . filesize ( $file_dir . $file_name ) );    
            Header ( "Content-Disposition: attachment; filename=" . $file_name );    
            //输出文件内容     
            //读取文件内容并直接输出到浏览器    
            echo fread ( $file, filesize ( $file_dir . $file_name ) );    
            fclose ( $file );    
            exit ();    
        }    
    }
       
       

}    