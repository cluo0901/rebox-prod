<?php
class WhizmoshelveController extends Admin
{
    public $fei;
    public $flowerhua;
    public function __construct()
    {
        parent::__construct();
        $this->fei = $this->model('fei');
        $this->flowerhua = $this->model('flowerhua');
    }
    // 待上架的商品
    public function admin_shelve_listAction(){
    	$page = (int)$this->get('page') ? (int)$this->get('page') : 1;
       	$order = "order_id desc";
       	$where = "order_status in (5,8)";
        $order_sn_number = $this->safe('order_sn_number');
        if($order_sn_number){
            $where .= " and order_sn_number like '%$order_sn_number%'";
        }
        $urlparam = array('page' => '{page}','order_sn_number'=>$order_sn_number);
        $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";
        $list = $this->fei->get_all_list('whizmo_order', $order, $limit, $where); //列表
        $total = $this->fei->get_all_list('whizmo_order', $order, $limit, $where,'order_id', true); //统计数量
        $pagelist = $this->instance('pagelist');    //加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/whizmoshelve/admin_shelve_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();
    	foreach ($list as $key => $value) {
    		$list[$key]['member_phone'] = $this->fei->get_field('member',"id = $value[order_member_id]",'phone');
    		$recovery= $this->fei->get_one('whizmo_recovery',"id = $value[order_recovery_id]",'');
    		$list[$key]['title'] = $recovery['recovery_title'].$recovery['recovery_title2'];
    		$list[$key]['recovery_img'] = $recovery['recovery_img'];
    	}
    	$this->view->assign(array(
    		'list'=>$list,
    		'pagelist'=>$pagelist,
            'order_sn_number'=>$order_sn_number,
    	));
    	$this->view->display('admin/admin_shelve_list');
    }
    // 添加上架商品
    public function admin_shelve_addAction(){
        session_start();
    	if($this->post('submit')){
    		$data = $this->post('data');
    		$array = $data['img'];
             $array1 = $data['order_parameter_id'];
            $array2 = $data['recovery_parameter_title'];
    		$data['shelf_time']	= time();
            $data['number'] = rand(10000000,99999999);
            unset($data['img']);
            unset($data['order_parameter_id']);
    		unset($data['recovery_parameter_title']);
    		$res = $this->fei->db_add('whizmo_shelve',$data);

            foreach ($array as $key => $value) {
                $info = [];
                $info['img_url'] = $value;
                $info['shelve_id'] = $res;
                $this->fei->db_add('whizmo_shelve_img',$info);
    		}
            $new_array = array_combine($array1,$array2);
            foreach ($new_array as $key => $value) {
                $this->fei->db_update('whizmo_order_paramemter',array('recovery_parameter_title'=>$value),"order_parameter_id = ".$key);
            }
    		if($res){
                $this->fei->db_update('whizmo_order',array('order_status'=>6),"order_id = $data[order_id]");
                $_SESSION["order_status_view"] = 5;
                $this->adminMsg(lang('success'),url('admin/flowerhua/admin_order_list'),2,1,1);
    		}
    	}

    	$order_id = $this->get('id');
    	$data = $this->fei->get_one('whizmo_order',"order_id = $order_id",'');
    	$data['member_phone'] = $this->fei->get_field('member',"id = $data[order_member_id]",'phone');
		$recovery= $this->fei->get_one('whizmo_recovery',"id = $data[order_recovery_id]",'');
		$data['title'] = $recovery['recovery_title'].$recovery['recovery_title2'];
		$data['recovery_img'] = $recovery['recovery_img'];
        $list = $this->fei->get_all_where('whizmo_order_paramemter',"order_id = $order_id",'');
    	$this->view->assign(array('data'=>$data,'list'=>$list));
    	$this->view->display('admin/admin_shelve_add');
    }
    // 下架改变状态
    public function ajax_change_statusAction(){
        $id = $this->post('id');
        $status = $this->post('status');
        $res = $this->fei->db_update('whizmo_shelve',array('status'=>$status),"id = $id");
        if($res){
            $order_id = $this->fei->get_field('whizmo_shelve',"id = $id",'order_id');
            $this->fei->db_update('whizmo_order',array('order_status'=>8),"order_id = $order_id");
            die(json_encode(array('code'=>1)));
        }
    }
    // 重新上架再次改变状态
    public function ajax_rechange_statusAction(){
        $order_id = $this->post('order_id');
        $res = $this->fei->db_update('whizmo_shelve',array('status'=>1),"order_id = $order_id");
        if($res){
            $this->fei->db_update('whizmo_order',array('order_status'=>6),"order_id = $order_id");
            die(json_encode(array('code'=>1)));
        }
    }
    // 正在拍卖的
    public function admin_onshelve_listAction(){
        if ($this->post('sort')) {
            foreach ($_POST as $var => $value) {
                if (strpos($var, 'data_') !== false) {
                    $id = (int)str_replace('data_', '', $var);
                    if ($value > 0) {
                        $data = array();
                        $data['sort'] = $value;
                        $this->fei->db_update('whizmo_shelve', $data, "id = $id");
                    }
                }
            }
            $this->adminMsg('排序成功', $_SERVER['HTTP_REFERER'], 3, 1, 1);
        }

    	$page = (int)$this->get('page') ? (int)$this->get('page') : 1;
       	$order = "sort desc";
       	$where = "status=1";
        $number = $this->safe('number');
        if($number){
            $where .= " and number like '%$number%'";
        }
        $urlparam = array('page' => '{page}','number'=>$number);
        $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";
        $list = $this->fei->get_all_list('whizmo_shelve', $order, $limit, $where); //列表
        $total = $this->fei->get_all_list('whizmo_shelve', $order, $limit, $where,'id', true); //统计数量
        $pagelist = $this->instance('pagelist');    //加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/whizmoshelve/admin_onshelve_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();
        foreach ($list as $key => $value) {
            if($value['recycler_id']){
                $list[$key]['recycler_phone'] = $this->fei->get_field('whizmo_recycler',"id = ".$value['recycler_id'],'phone');
            }
            $order_recovery = $this->fei->get_one('whizmo_order',"order_id = $value[order_id]",
                            'order_recovery_id,order_member_id,order_sn_number');
            $info = $this->fei->get_one('whizmo_recovery',"id = $order_recovery[order_recovery_id]",'');
            $list[$key]['title'] = $info['recovery_title'].$info['recovery_title2'];
            $list[$key]['recovery_img'] = $info['recovery_img'];
            $list[$key]['member_phone'] = $this->fei->get_field('member',"id = $order_recovery[order_member_id]",'phone');
            $list[$key]['order_sn_number'] = $order_recovery['order_sn_number'];

            $list[$key]['shelve_id'] = $this->fei->get_field('whizmo_shelve',"order_id = $value[order_id]",'id');; //find shelve_id

        }
    	$this->view->assign(array(
    		'list'=>$list,
    		'pagelist'=>$pagelist,
            'number'=>$number,
    	));
    	$this->view->display('admin/admin_onshelve_list');
    }
    //修改正在拍卖的商品信息
    public function admin_shelve_editAction(){
        $id = $this->get('id');
        if($this->post('submit')){
            $data = $this->post('data');
            $res = $this->flowerhua->db_update('whizmo_shelve',$data,"id = $id");
            if($res){
                $this->adminMsg(lang('success'),url('admin/whizmoshelve/admin_onshelve_list'),2,1,1);
            }
        }
        $data = $this->flowerhua->get_one('whizmo_shelve',"id = $id",'');
        if($data['recycler_id']){
            $data['recycler_phone'] = $this->fei->get_field('whizmo_recycler',"id = ".$data['recycler_id'],'phone');
        }
        $order_recovery = $this->fei->get_one('whizmo_order',"order_id = $data[order_id]",
                        'order_recovery_id,order_member_id,order_sn_number');
        $info = $this->fei->get_one('whizmo_recovery',"id = $order_recovery[order_recovery_id]",'');
        $data['title'] = $info['recovery_title'].$info['recovery_title2'];
        $data['recovery_img'] = $info['recovery_img'];
        $data['member_phone'] = $this->fei->get_field('member',"id = $order_recovery[order_member_id]",'phone');
        $data['order_sn_number'] = $order_recovery['order_sn_number'];
        $this->view->assign(array('data'=>$data));
        $this->view->display('admin/admin_shelve_edit');
    }
   
    // 删除拍卖的商品
    public function admin_shelve_delAction(){
    	$id = $this->get('id');
    	$res = $this->fei->db_del('whizmo_shelve',"id = $id");
    	if($res){
    		$this->adminMsg(lang('success'),url('admin/whizmoshelve/admin_onshelve_list'),2,1,1);
    	}
    }
  
    // 竞价记录
    public function admin_bidding_listAction(){
        $shelve_id = $this->get('id');
        $page = (int)$this->get('page') ? (int)$this->get('page') : 1;
        $order = "bidding_price desc";
        $where = "shelve_id=$shelve_id and status=1";
        $recycler_phone = $this->safe('recycler_phone');
        if($recycler_phone){
            $recycler_id = $this->fei->get_field('whizmo_recycler',"phone = $recycler_phone",'id');
            if($recycler_id){
                $where .= " and recycler_id = $recycler_id";
            }
        }
        $urlparam = array('page' => '{page}');
        $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";
        $list = $this->fei->get_all_list('whizmo_bidding', $order, $limit, $where); //列表
        $total = $this->fei->get_all_list('whizmo_bidding', $order, $limit, $where,'id', true); //统计数量
        $pagelist = $this->instance('pagelist');    //加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/whizmoshelve/admin_bidding_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();
        foreach ($list as $key => $value) {
           $order_recovery_id =  $this->fei->get_field('whizmo_order',"order_id = $value[order_id]",'order_recovery_id');
           $info = $this->fei->get_one('whizmo_recovery',"id = $order_recovery_id",'');
           $list[$key]['recovery_img'] = $info['recovery_img'];
           $list[$key]['title'] = $info['recovery_title'].$info['recovery_title2'];
           $recycler_info= $this->fei->get_one('whizmo_recycler',"id = $value[recycler_id]",'phone,company_name');
           $list[$key]['recycler_phone'] = $recycler_info['phone'];
           $list[$key]['company_name'] = $recycler_info['company_name'];
        }
        $this->view->assign(array(
            'list'=>$list,
            'pagelist'=>$pagelist,
            'shelve_id'=>$shelve_id,
            'recycler_phone'=>$recycler_phone,
            ));
    	$this->view->display('admin/admin_bidding_list');
    }
    // 拍定改变状态
    public function ajax_admin_biddingOKAction(){
        $id = $this->post('id');
        $res = $this->fei->db_update('whizmo_bidding',array('status'=>6,'bided_time'=>time()),"id = $id");
        $info = $this->fei->get_one('whizmo_bidding',"id = $id",'order_id,shelve_id,recycler_id');
        $res1 = $this->fei->db_update('whizmo_order',array('order_status'=>7,'order_finishtime'=>time()),"order_id = $info[order_id]");
        $res2 = $this->fei->db_update('whizmo_shelve',array('status'=>3),"id = $info[shelve_id]");
        $arr_id = $this->fei->get_all_where('whizmo_bidding',"shelve_id = $info[shelve_id] and recycler_id != $info[recycler_id]",'id');
        if($arr_id){
            foreach ($arr_id as $key => $value) {
                $this->fei->db_update('whizmo_bidding',array('status'=>-6),"id = $value[id]");
            }
        }
        if($res && $res1 && $res2){
            die(json_encode(array('code'=>1)));
        }
    }
    // 拍卖成功的
    public function admin_shelveok_listAction(){
        $page = (int)$this->get('page') ? (int)$this->get('page') : 1;
        $order = "recycler_id desc";
        $status = $this->post('status');
        if($status){
            $where = "status = $status";
        }else{
            $where = "status>=3";
        }
        $number = $this->post('number');
        if($number){
            $where .= " and number like '%$number%'";
        }
        $urlparam = array('page' => '{page}','number'=>$number,'status'=>$status);
        $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";
        $list = $this->fei->get_all_list('whizmo_shelve', $order, $limit, $where); //列表
        $total = $this->fei->get_all_list('whizmo_shelve', $order, $limit, $where,'order_id', true); //统计数量
        $pagelist = $this->instance('pagelist');    //加载分页类
        $pagelist->loadconfig();
        $pagelist = $pagelist->total($total)->url(url('admin/whizmoshelve/admin_shelveok_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();


        foreach ($list as $key => $value) {
            $order_info = $this->fei->get_one('whizmo_order',"order_id = $value[order_id]",'order_recovery_id,order_sn_number');
            $data = $this->fei->get_one('whizmo_recovery',"id = $order_info[order_recovery_id]",'');
            $list[$key]['title'] = $data['recovery_title'].$data['recovery_title2'];
            $list[$key]['recovery_img'] = $data['recovery_img'];
            $list[$key]['bided_time'] = $this->fei->get_field('whizmo_bidding',"status>=2 and shelve_id = $value[id]",'bided_time');
            $list[$key]['bided_id'] = $this->fei->get_field('whizmo_bidding',"shelve_id = $value[id] and status > 0",'id');
            $recycler_info= $this->fei->get_one('whizmo_recycler',"id = $value[recycler_id]",'phone,company_name');
            $list[$key]['recycler_phone'] = $recycler_info['phone'];
            $list[$key]['company_name'] = $recycler_info['company_name'];
            $list[$key]['order_sn_number'] = $order_info['order_sn_number'];

            $list[$key]['shelve_id'] = $value[id]; //find shelve_id

        }
        $this->view->assign(array(
            'list'=>$list,
            'pagelist'=>$pagelist,
            'number'=>$number,
            'status'=>$status,
        ));
        $this->view->display('admin/admin_shelveok_list');
    }
 //添加同一个回收商成功拍卖的其他商品订单号
    public function ajax_addordernumberAction(){
        if(IS_AJAX){
            $ordernumber = $this->post('value');
            $id = $this->post('id');
            $res = $this->fei->db_update('whizmo_shelve',array('ordernumber'=>$ordernumber,'status'=>5),"id = $id");
            if($res){
                die(json_encode(array('code'=>1)));
            }

        }
    }

    //竞价状态更改
    public function shellChangeAction(){
        $id = $this->post('id');
        $type = $this->post('type');
        if($type == 'delivery'){
            $res = $this->fei->db_update('whizmo_shelve',['status'=>5],"id = $id");
            if($res){
                die(json_encode(array('code'=>1)));
            }
        }else if($type == 'confirme'){
            $shelve_id = $this->fei->get_field('whizmo_bidding',"id = $id",'shelve_id');
            $res = $this->fei->db_update('whizmo_bidding',array('status'=>3),"id = $id and bided_time > 0");
            $this->fei->db_update('whizmo_shelve',['status'=>6],"id = $shelve_id");
            if($res){
                die(json_encode(array('code'=>1)));
            }
        }
    }






















    public function ajax_set_imgAction(){
        $img = $_FILES['addfile'];
        $up = $this->upload($img, array('jpeg', 'jpg', 'gif', 'png','bmp'));//上传原图图片
        $path_thumb = "uploadfiles/admin/" . md5($up['path']) . ".png";
        img2thumb($up['path'],$path_thumb);//生成缩略图
        unset($data);
        $arr = array(
            'result'=>$up['result'],
            'path'=>$up['path'],
            'path_thumb'=>$path_thumb,
            'size' =>$img['size']
        );
        echo json_encode($arr);
    }


    /**
     * 文件上传
     * @param  $fields      上传字段 'file'
     * @param  $type        文件类型  array(jpg,gif)
     * @param  $size        文件大小  MB
     * @param  $img         图片配置参数
     * @param  $mark        图片水印
     * @param  $admin       是否来自后台
     * @param  $stype       上传方式  swf或者ke
     * @param  $ofile       原文件
     * @param  $document    后台栏目归档目录
     * @return Array        返回数组
     */
    private function upload($fields, $type, $size=20, $img=null, $mark=true, $admin=0, $stype=null, $ofile=null, $document=null) {
        $path = 'uploadfiles/';
        $upload = $this->instance('file_upload');
        if (empty($admin) && $this->memberinfo) {
            $uid = $this->memberinfo['id']; //会员附件归类
            if ($uid) {
                $path.= 'member/' . $uid . '/';
                if (isset($this->membergroup[$this->memberinfo['groupid']]['filesize']) && $this->membergroup[$this->memberinfo['groupid']]['filesize']) {
                    $c = count_member_size($this->memberinfo['id']);
                    if ($c > $this->membergroup[$this->memberinfo['groupid']]['filesize'] * 1024 * 1024) {
                        $this->attMsg(lang('att-7', array('1' => $this->membergroup[$this->memberinfo['groupid']]['filesize'], '2' => formatFileSize($c))), $stype);
                    }
                }
            }
            $document = null;
        } elseif ($admin) {
            $uid = (int)get_cookie('member_id');
        } else {
            //$this->attMsg(lang('att-0'), $stype);
            $uid = 0;
            $patp = 'uploadfiles/guest/';
        }
        $upload->set($fields)->set_limit_size(1024*1024*$size)->set_limit_type($type);
        //设置路径和名称
        $ext = $upload->fileext();
        if (stripos($ext, 'php') !== FALSE
            || stripos($ext, 'asp') !== FALSE
            || stripos($ext, 'aspx') !== FALSE
        ) {
            return array('result' => '文件格式被系统禁止');
        }
        if (in_array($ext, array('jpg','jpeg','bmp','png','gif'))) {
            $dir = 'image';
            $upload->set_image($img['w'], $img['h'], $img['t']);
        } else {
            $dir = 'file';
        }
        $path.= $dir . '/' . (empty($document) || $document == 'undefined' || !preg_match('/^[a-zA-Z_0-9]+$/', $document) ? '' : $document . '/');
        if ($ofile && is_file($ofile) && strpos($path, dirname(dirname($ofile))) === 0) { //判断原文件
            $path = dirname($ofile) . '/';
            $file = $fname = basename($ofile);
        } else {
            $path.= date('Ym') . '/';
            $data = file_list::get_file_list($path);
            $name = count($data) + 1;
            $name = is_file($path.$name.'.'.$ext) ? $name.str_replace('0.', '_',(double)microtime()) : $name;
            $file = $upload->filename();
            $fname = $name . '.' . $ext;
        }
        $result = $upload->upload($path, $fname);
        //上传成功处理图片
        if (!$result && $dir == 'image') {
            $this->watermark($path . $fname);
        }
        return array('result' => $result, 'path' => $path . $fname, 'file' => $file , 'ext' => $dir == 'image' ? 1 : $ext);
    }






}    