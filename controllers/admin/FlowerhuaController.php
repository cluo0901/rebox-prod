<?php

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use \PhpOffice\PhpSpreadsheet\Reader\IReader;
    use \PhpOffice\PhpSpreadsheet\Writer\IWriter;
    require('utilities.php');

    /**

     * Created by PhpStorm.

     * User: flowerhua

     * Date: 2017/6/22 0022

     * Time: 下午 5:36

     */

header("Content-type:text/html;charset=utf-8");

    class flowerhuaController extends Admin

    {



        public $flowerhua;

        public $linkage;



        public function __construct()

        {

            parent::__construct();

            $this->linkage = $this->model('linkage');

            $this->flowerhua = $this->model('flowerhua');

        }

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



        //回收产品列表

        public function admin_recovery_listAction()
        

        {

            $type_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = 28");

            $parent_list = $this->flowerhua->get_all_where('whizmo_category_type',"type = 11");

            $parent_arr = tran_one($parent_list,'id','name');

            $type_arr = tran_one($type_list,'id','name');

            $where = "1=1";

            $order = "listorder desc,id desc";

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $limit = ($page-1)*$this->admin_pagesize.",$this->admin_pagesize";

            $recovery_type_id = $this->safe('recovery_type_id');

            $recovery_parent_id = $this->safe('recovery_parent_id');

            $username = $this->safe('username');

            if($recovery_type_id){

                $where .= " and recovery_type_id = $recovery_type_id";

                $parent_list2 = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = $recovery_type_id");

            }

            if($recovery_parent_id){

                $where .= " and recovery_parent_id = $recovery_parent_id";

            }

            if($username){

                $where .= " and concat(recovery_title,recovery_title2) like '%$username%'";

            }

            $urlparam = array('page' => '{page}','username'=>$username,'recovery_parent_id'=>$recovery_parent_id,'recovery_type_id'=>$recovery_type_id);

            $list = $this->flowerhua->get_all_list('whizmo_recovery',$order,$limit,$where);

            $total = $this->flowerhua->get_all_list('whizmo_recovery',$order,$limit,$where,true);

            $pagelist = $this->instance('pagelist');//加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/admin_recovery_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();

            if ($this->post('submit_del')==1) {

                if (count($_POST)==1) {

                    $this->adminMsg('Please select the record you want to delete!', url('admin/flowerhua/admin_recovery_list',$urlparam), 2, 1, 2);

                }else{

                    foreach ($_POST as $var=>$value) {

                        if (strpos($var, 'del_')!==false) {

                            $id = (int)str_replace('del_', '', $var);

                            $this->del($id,'whizmo_recovery');

                        }

                    }

                    $this->adminMsg('Multiple record deleting success', url('admin/flowerhua/admin_recovery_list',$urlparam), 3, 1, 1);

                }

            }

            if ($this->post('submit_del')==2) {

                if (count($_POST)==1) {

                    $this->adminMsg('Please select the record you want to sort!', url('admin/flowerhua/admin_recovery_list',$urlparam), 2, 1, 2);

                }else{

                    foreach ($_POST as $var=>$value) {

                        if (strpos($var, 'order_')!==false) {

                            $id = (int)str_replace('order_', '', $var);

                            $info['listorder'] = $value ? $value : 0;

                            $this->flowerhua->db_update('whizmo_recovery',$info,"id = $id");

                        }

                    }

                    $this->adminMsg('Success of multiple records sorting', url('admin/flowerhua/admin_recovery_list',$urlparam), 3, 1, 1);

                }

            }

            $this->view->assign(array(

                'recovery_type_id'        => $recovery_type_id,

                'recovery_parent_id'        => $recovery_parent_id,

                'list'        => $list,

                'username'        => $username,

                'pagelist'        => $pagelist,

                'type_arr'        => $type_arr,

                'parent_arr'        => $parent_arr,

                'parent_list'        => $parent_list,

                'parent_list2'        => $parent_list2,

                'type_list'        => $type_list,

            ));

            $this->view->display('admin/admin_recovery_list');

        }

        //添加/修改回收产品

        public function admin_recovery_addAction()

        {

            $id = $this->safe('id');

            if($id){

                $data = $this->flowerhua->get_one('whizmo_recovery',"id = $id");

                if($data['recovery_parent_id']){

                    $parent_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = ".$data['recovery_type_id']);

                }

                $list = $this->flowerhua->get_one('whizmo_recovery',"id = $id");

                $list['parent'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid = 0");

                foreach ($list['parent'] as $key => $value) {

                    $list['parent'][$key]['child'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid !=0 and parentid = $value[id]");

                    foreach ($value['child'] as $key2 => $value2) {

                        $list['parent'][$key]['child'][$key2]['child'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid !=0 and parentid = $value2[id]");

                    }

                }

                foreach ($list['parent'] as $key => $value) {

                    foreach ($value['child'] as $key2 => $value2) {

                        $list['parent'][$key]['child'][$key2]['child'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid !=0 and parentid = $value2[id] order by parameter_listorder desc");

                    }

                }

                $list['ch_parent'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid =2");

                $list['ch_parent2'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id and parentid =22");

            }

            $type_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = 28");

            $this->view->assign(array(

                'list'        => $list,

                'data'        => $data,

                'type_list'        => $type_list,

                'parent_list'        => $parent_list,

            ));

            $this->view->display('admin/admin_recovery_add');

        }

        //回收产品小贴士

        public function admin_recovery_tipsAction()

        {

            $data = $this->flowerhua->get_one('system_selected',"id = 1");

            if($this->post('submit')){

                $info = $this->post('data');

                $this->flowerhua->db_update('system_selected',$info,"id = 1");

                $this->adminMsg(lang('success'), url('admin/flowerhua/admin_recovery_tips'), 3, 1, 1);

            }

            $this->view->assign(array(

                'data'        => $data,

            ));

            $this->view->display('admin/admin_recovery_tips');

        }

        //回收产品步骤

        public function admin_recovery_stepAction()

        {

            $data = $this->flowerhua->get_one('system_selected',"id = 1");

            if($this->post('submit')){

                $info = $this->post('data');

                $this->flowerhua->db_update('system_selected',$info,"id = 1");

                $this->adminMsg(lang('success'), url('admin/flowerhua/admin_recovery_step'), 3, 1, 1);

            }

            $this->view->assign(array(

                'data'        => $data,

            ));

            $this->view->display('admin/admin_recovery_step');

        }

        //回收产品优惠券

        public function admin_recovery_coupon_listAction()

        {

            $coupon_status = $this->post('coupon_status');

            $search = $this->post('search');

            $where = "1=1";

            if($coupon_status){

                $where .= " and coupon_status = $coupon_status";

            }

            if($search){

                $where .= " and concat(coupon_title,coupon_sn) like '%$search%'";

            }

            $order = "id desc";

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $limit = ($page-1)*$this->admin_pagesize.",$this->admin_pagesize";

            $urlparam = array('page' => '{page}','coupon_status'=>$coupon_status,'search'=>$search);

            $list = $this->flowerhua->get_all_list('whizmo_recovery_coupon',$order,$limit,$where);

            $total = $this->flowerhua->get_all_list('whizmo_recovery_coupon',$order,$limit,$where,true);

            $pagelist = $this->instance('pagelist');//加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/admin_recovery_coupon_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();

            if ($this->post('submit_del')==1) {

                if (count($_POST)==1) {

                    $this->adminMsg('Please select the record you want to delete!', url('admin/flowerhua/admin_recovery_coupon_list',$urlparam), 2, 1, 2);

                }else{

                    foreach ($_POST as $var=>$value) {

                        if (strpos($var, 'del_')!==false) {

                            $id = (int)str_replace('del_', '', $var);

                            $this->del($id,'whizmo_recovery_coupon');

                        }

                    }

                    $this->adminMsg('Multiple record deleting success', url('admin/flowerhua/admin_recovery_coupon_list',$urlparam), 3, 1, 1);

                }

            }

            $this->view->assign(array(

                'list'        => $list,

                'search'        => $search,

                'coupon_status'        => $coupon_status,

                'pagelist'        => $pagelist,

            ));

            $this->view->display('admin/admin_recovery_coupon_list');

        }

        //拷贝复制一个同样的商品

        public function admin_recovery_copyAction()

        {

            $this->flowerhua->begin();

            $recovery_id = $this->get('id');

            $recovery = $this->flowerhua->get_one('whizmo_recovery',"id = $recovery_id");

            unset($recovery['id']);

            $recovery['listorder'] = 0;

            $recovery['recovery_hits'] = 0;

            $new_recovery_id = $this->flowerhua->db_add('whizmo_recovery',$recovery);

            //查询所有的参数

            $recovery_parameter = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $recovery_id and (parentid = 0 or parentid = 2 or parentid = 22)");

            foreach ($recovery_parameter as $key => $value) {

                if($value['parentid']!=2){

                    $recovery_parameter[$key]['parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $recovery_id and parentid !=0 and parentid = $value[id]");

                    foreach ($recovery_parameter[$key]['parameter'] as $key2 => $value2) {

                        $recovery_parameter[$key]['parameter'][$key2]['parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $recovery_id and parentid !=0 and parentid = $value2[id]");

                    }

                }else{

                    unset($value['id']);

                    $value['recovery_id'] = $new_recovery_id;

                    $value['parameter_listorder'] = 0;

                    unset($recovery_parameter[$key]);

                    $this->flowerhua->db_add('whizmo_recovery_parameter',$value);

                }

            }

            //查询出来的参数全部重新插入数据库

            foreach ($recovery_parameter as $key => $value) {

                unset($value['id']);

                $value['recovery_id'] = $new_recovery_id;

                $array = $value;

                unset($array['parameter']);

                $recovery_parameter_id = $this->flowerhua->db_add('whizmo_recovery_parameter',$array);

                foreach ($value['parameter'] as $key2 => $value2) {

                    unset($value2['id']);

                    $value2['recovery_id'] = $new_recovery_id;

                    $value2['parentid'] = $recovery_parameter_id;

                    $array2 = $value2;

                    unset($array2['parameter']);

                    $recovery_parameter_id2 = $this->flowerhua->db_add('whizmo_recovery_parameter',$array2);

                    foreach ($value['parameter'][$key2]['parameter'] as $key3 => $value3) {

                        unset($value3['id']);

                        $value3['recovery_id'] = $new_recovery_id;

                        $value3['parentid'] = $recovery_parameter_id2;

                        $array3 = $value3;

                        $this->flowerhua->db_add('whizmo_recovery_parameter',$array3);

                    }

                }

            }

            if($new_recovery_id){

                $this->flowerhua->commit();

            }else{

                $this->flowerhua->rollback();

            }

            //成功后直接进入此copy内容的编辑页面

            $this->adminMsg('success', url('admin/flowerhua/admin_recovery_add',array('id'=>$new_recovery_id)), 3, 1, 1);

        }

        //ajax批量生成优惠券

        public function ajax_add_batch_couponsAction(){

            if (IS_AJAX) {

                $batch_coupons_title = $this->post('batch_coupons_title');

                $batch_coupons_number = $this->post('batch_coupons_number');

                $batch_coupons_money = $this->post('batch_coupons_money');

                if($batch_coupons_number>100){

                    die(json_encode(array('code' => -1)));

                }

                $data = [];

                $conn1 = mysqli_connect('localhost', 'whizmotech', 'H@ppy2018', 'coupon_auto'); //connect to coupon_auto database

                for($i = 0 ; $i < $batch_coupons_number ; $i ++){

                    $data['coupon_title'] = $batch_coupons_title;

                    $data['coupon_money'] = $batch_coupons_money;

                    $data['coupon_addtime'] = time();

                    // $data['coupon_sn'] = 'Sn'.date('ymd',time()).randomkeys(10).'s';
                    $data['coupon_sn'] = 'RBX'.date('ym',time()).randomkeys(5);

                    $coupon_number = $data['coupon_sn'];

                    $this->flowerhua->db_add('whizmo_recovery_coupon',$data);

                    //add into auto send coupon database starts

                    $query = "INSERT INTO coupon (no, status) VALUES ('$coupon_number', 0)";

                    if (mysqli_query($conn1,$query)) {
                        echo "Coupon added!";
                    } else {
                        echo "Error adding coupon: " . mysqli_error($conn1);                   
                    }
                    //add into auto send coupon database ends


                }

                mysqli_close($conn1); // close coupon_auto database

                die(json_encode(array('code' => 1)));

            }

        }

        //添加优惠券

        public function admin_recovery_coupon_addAction()

        {

            $id = $this->get('id');

            if($id){

                $data = $this->flowerhua->get_one('whizmo_recovery_coupon',"id = $id");

            }

            if($this->post('submit')){

                $info = $this->post('data');

                if($id){

                    $this->flowerhua->db_update('whizmo_recovery_coupon',$info,"id = $id");

                }else{

                    $info['coupon_addtime'] = time();

                    // $info['coupon_sn'] = 'Sn'.date('ymd',time()).randomkeys(10);

                    $info['coupon_sn'] = 'RBX'.date('ym',time()).randomkeys(5);

                    $this->flowerhua->db_add('whizmo_recovery_coupon',$info);

                }

                $this->adminMsg(lang('success'), url('admin/flowerhua/admin_recovery_coupon_list'), 3, 1, 1);

            }

            $this->view->assign(array('data'=>$data));

            $this->view->display('admin/admin_recovery_coupon_add');

        }

        //删除优惠券

        public function admin_recovery_coupon_delAction()

        {

            $id = $this->get('id');

            if($id){

                $this->flowerhua->db_del('whizmo_recovery_coupon',"id = $id");

                $this->adminMsg('successfully deleted', url('admin/flowerhua/admin_recovery_coupon_list'), 3, 1, 1);

            }

        }

        //回收产品条款和协议

        public function admin_recovery_clauseAction()

        {

            $data = $this->flowerhua->get_one('whizmo_recovery_clause',"id = 1");

            if($this->post('submit')){

                $info = $this->post('data');

                $this->flowerhua->db_update('whizmo_recovery_clause',$info,"id = 1");

                $this->adminMsg('Operation is successful', url('admin/flowerhua/admin_recovery_clause'), 3, 1, 1);

            }

            $this->view->assign(array(

                'data'        => $data,

            ));

            $this->view->display('admin/admin_recovery_clause');

        }

        //订单列表管理

        public function admin_order_listAction()

        {
            
            session_start();
            $role_id = get_cookie('role_id'); // get admin role

            $order_sn = $this->safe('order_sn');

            $recovery_type_id = $this->safe('recovery_type_id');

            $recovery_parent_id = $this->safe('recovery_parent_id');

            if($_SESSION["order_status_view"]){
                $order_status = $_SESSION["order_status_view"];
                unset($_SESSION["order_status_view"]);
            } else {
                $order_status = $this->safe('order_status');
            }

            $where = "1=1";

            $order = "a.order_id desc";

            if($order_sn){

                // $where .= " and a.order_sn_number like '%$order_sn%'";

                $where .= " and (a.order_sn_number like '%$order_sn%' OR b.phone like '%$order_sn%' OR a.order_delivery_number like '%$order_sn%' OR a.imei like '%$order_sn%' OR a.order_id like '%$order_sn%')";

            }

            if($recovery_type_id){

                $where .= " and c.recovery_type_id = $recovery_type_id";

                $parent_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = $recovery_type_id");

            }

            if($recovery_parent_id){

                $where .= " and c.recovery_parent_id = $recovery_parent_id";

            }

            if($order_status){

                $where .= " and a.order_status = $order_status";

            }

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $limit = ($page-1)*$this->admin_pagesize.",$this->admin_pagesize";

            $urlparam = array('page' => '{page}','order_sn'=>$order_sn,'recovery_type_id'=>$recovery_type_id,'recovery_parent_id'=>$recovery_parent_id,'order_status'=>$order_status);

            //Hide wip orders start
            $where .= " and (order_status <> 1 or order_delivery_method_type <> 1) and (order_status <> -1 or order_delivery_method_type = 2) ";
            //Hide wip orders end

            $list = $this->flowerhua->get_order_list('whizmo_order as a left join fn_member as b on a.order_member_id = b.id left join fn_whizmo_recovery as c on a.order_recovery_id = c.id',$order,$limit,$where);

            $total = $this->flowerhua->get_order_list('whizmo_order as a left join fn_member as b on a.order_member_id = b.id left join fn_whizmo_recovery as c on a.order_recovery_id = c.id',$order,$limit,$where,true);

            $pagelist = $this->instance('pagelist');//加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/admin_order_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();

//            foreach ($list as $key => $value) {

//                $list[$key]['recovery'] = $this->flowerhua->get_one('whizmo_recovery',"id = $value[order_recovery_id]",'recovery_title,recovery_img');

//                $list[$key]['member_phone'] = $this->flowerhua->get_field('member',"id = $value[order_member_id]",'phone');

//            }

            $type_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = 28");

            foreach ($list as $key => $item) {
                $list[$key]["whatsapp"] = "https://wa.me/65". $item[phone];
                $list[$key]["member_id"] = $item[order_member_id];
                $google_review_url = urlencode("If you enjoyed our service, please give us a 5 star review on Google! Get a cup of coffee for review with more than 10 words! : ) https://g.page/rebox-jurong-point/review");
                $list[$key]["google_review"] = "https://wa.me/65". $item[phone]. "?text=". $google_review_url;
                $one = $this->flowerhua->getOrderParameters($item[order_id]);
                $list[$key]["storage"] = $one["recovery_parameter_title"];
                if($list[$key]["dateAccepted"]){
                    $list[$key]["dateAccepted"] = date("d/m/Y H:i:s",strtotime($list[$key]["dateAccepted"]));
                }

                switch ($list[$key]['storeId']) {
                    case 1:
                        $list[$key]['store'] = 'CSM';
                        break;
                    case 2:
                        $list[$key]['store'] = 'JPM';
                        break;
                    case 3:
                        $list[$key]['store'] = 'AMK';
                        break;
                    default:
                        $list[$key]['store'] = null;
                        break;
                }
                
            }

            $this->view->assign(array(

                'list'        => $list,

                'order_status'        => $order_status,

                'order_sn'        => $order_sn,

                'pagelist'        => $pagelist,

                'parent_list'        => $parent_list,

                'type_list'        => $type_list,

                'recovery_type_id'        => $recovery_type_id,

                'recovery_parent_id'        => $recovery_parent_id,

                'role_id'   =>  $role_id, //check admin role

            ));

            $this->view->display('admin/admin_order_list');

        

        }

        //删除订单

        public function admin_order_delAction()

        {

            $id = $this->get('id');

            if($id){

                $this->flowerhua->db_del('whizmo_order',"order_id = $id");

                $this->adminMsg('successfully deleted', url('admin/flowerhua/admin_order_list'), 3, 1, 1);

            }

        }

        //修改订单

        public function admin_order_addAction()

        {

            $admin_username = $this->userinfo['username']; // get admin username

            $order_id = $this->get('id');

            if($order_id){

                $order = $this->flowerhua->get_one('whizmo_order',"order_id = $order_id",'');

                $order['member_phone'] = $this->flowerhua->get_field('member',"id = $order[order_member_id]",'phone');

                $order['member_status'] = $this->flowerhua->get_field('member',"id = $order[order_member_id]",'status'); // get verification status

                $order['name'] = $this->flowerhua->get_field('whizmo_category_type',"id = $order[order_recovery_typeid]",'name');

                $order['recovery_title'] = $this->flowerhua->get_field('whizmo_recovery',"id =$order[order_recovery_id]", 'recovery_title'); // get model name

                $order['coupon_title'] = $this->flowerhua->get_field('whizmo_recovery_coupon',"id = $order[order_coupon_code_id]",'coupon_title');

                $order['order_payment_type'] = $this->flowerhua->get_field('whizmo_category_type',"id = $order[order_payment_type]",'name');

                $order['recovery_parameter'] = $this->flowerhua->get_join_all('whizmo_order_paramemter as a','whizmo_recovery_parameter as b',"a.recovery_parameter_id = b.id","a.order_id = $order_id",'a.*,b.parameter_name,b.parentid');

                $order['recovery_type_id'] = $this->flowerhua->get_field('whizmo_recovery',"id =$order[order_recovery_id]", 'recovery_type_id');

                //find the selections for check results input

                foreach ($order['recovery_parameter'] as $key => $item) {
                    if ($item[recovery_parameter_type] == 1 && $item['recovery_parameter_parent_title'] != 'iCloud Account & Passcode') {
                        $parentid = $this->flowerhua->get_field('whizmo_recovery_parameter', "recovery_id = $order[order_recovery_id] and parameter_name = '$item[recovery_parameter_parent_title]'", 'id');

                        $order['recovery_parameter'][$key]['child'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $parentid");

                        if (is_null($item[evaluation_parameter])) {
                            $data2[$key][evaluation_parameter] =  $item[recovery_parameter_title];                            
                        } else {
                            $data2[$key][evaluation_parameter] =  str_replace("&amp;","&", $item[evaluation_parameter]);
                        }
                    }
                }

                //find functional and accessories options
                $optional = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and (parentid = 2 or parentid = 22)");         



            }


//            dd($order);

            if($this->post(submit)){

                $order['recovery_parameter'] = $this->flowerhua->get_join_all('whizmo_order_paramemter as a','whizmo_recovery_parameter as b',"a.recovery_parameter_id = b.id","a.order_id = $order_id",'a.*,b.parameter_name,b.parentid');

                $data = $this->post('data');

                $data2 = $this->post('data2'); // get test results

                $data[indicative_final_price] = $this->flowerhua->get_field('whizmo_recovery',"id =$order[order_recovery_id]", 'recovery_high_price');;

                $lcd_damage_tag = 0;
                $lcd_minor_tag = 0;
                $glass_damage_tag = 0;
                $glass_minor_tag = 0;
                $lcd_minor_addback = 0;
                $glass_addback = 0;
                    
                foreach ($data2 as $key => $item) {

                    $this->flowerhua->db_update('whizmo_order_paramemter',$item,'order_parameter_id = ' . $order['recovery_parameter'][$key][order_parameter_id]);

                    $parentid = $this->flowerhua->get_field('whizmo_recovery_parameter','recovery_id = '. $order[order_recovery_id] . " and parameter_name = \"". $order['recovery_parameter'][$key][recovery_parameter_parent_title]. "\"",'id');

                    $component = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name = '$item[evaluation_parameter]' and parentid = $parentid",'parameter_sale_price');

                    $data[indicative_final_price] -=  $component;

                    //take note of glass and screen tag
                    $this_tag = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name = '$item[evaluation_parameter]' and parentid = $parentid",'parameter_tag');  

                    if ($this_tag == 'LD') {
                        $lcd_damage_tag = 1;
                    } else if ($this_tag == 'LM') {
                        $lcd_minor_tag = 1;
                        $lcd_minor_addback = $component;
                    } else if ($this_tag == 'GD') {
                        $glass_damage_tag = 1;
                        $glass_addback = $component;
                    } else if ($this_tag == 'GM') {
                        $glass_minor_tag = 1;
                        $glass_addback = $component;
                    }

                }

                // add back LCD or glass double deduction
                $lcd_damage_addback = $this->flowerhua->get_field('whizmo_recovery_parameter',"parameter_tag = 'LD' AND recovery_id = $order[order_recovery_id]",'parameter_sale_price');

                if ($lcd_damage_tag == 1) {
                    $data[indicative_final_price] += (int)$glass_addback;
                }

                if ($lcd_minor_tag == 1 && $glass_damage_tag == 1) {
                    $data[indicative_final_price] -= (int)$lcd_damage_addback;
                    $data[indicative_final_price] += (int)$glass_addback;
                    $data[indicative_final_price] += (int)$lcd_minor_addback;
                }

                if ($lcd_minor_tag == 1 && $glass_minor_tag == 1) {
                    $subtotal = $lcd_minor_addback + $glass_addback;
                    if ($subtotal > $lcd_damage_addback) {
                        $data[indicative_final_price] -= (int)$lcd_damage_addback;
                        $data[indicative_final_price] += (int)$glass_addback;
                        $data[indicative_final_price] += (int)$lcd_minor_addback;
                    }
                }


                $data3 = $this->post('data3'); // get functional test results

                $data[assessment] = "";                

                foreach ($data3 as $key => $item) {

                    if ($item != "Nil") {

                        $data[assessment] = $data[assessment] . $item . $key;

                        //add component cost

                        $component = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name = '$item'",'parameter_sale_price');

                        $data[indicative_final_price] -=  $component;

                    }                    

                }

                if ($data[indicative_final_price] <= 0) {
                    $data[indicative_final_price] = 0;
                }           

                // $test_results = "";

                // foreach ($data2 as $item) {

                //     $test_results .= strval($item) . ' ';

                // }

                // $data[order_evaluation] = trim($test_results);



                if($this->post('storeName')){
                    $data['storeId'] =  $this->post('storeName');
                }

                $this->flowerhua->db_update('whizmo_order',$data,"order_id = $order_id");

                $this->adminMsg('success', url('admin/flowerhua/admin_order_add',array('id'=>$order_id)), 3, 1, 1);

            }

            // dd($order);die;

            //download payment file starts
            if($this->post('payment_file')){

                if($order['payment_file_status'] == 1){
                    echo "<script>
                    alert('Payment File has already been downloaded!');
                    </script>";
                }else{
                    if ($order['member_status'] == 1) {

                        echo "<script>
                            alert('NRIC is not verified!');
                            </script>";
    
    
                    } else if ($order['order_final_price'] == null) {
    
                        echo "<script>
                            alert('Please input final price!');
                            </script>";
    
                    }else {
    
    
                        // $template_file=fopen( __DIR__ . "/payment_template.txt","r") or die("Cannot open");
                        $str=file_get_contents(__DIR__ . "/payment/payment_template.txt");
    
                        $str=str_replace("DATE", str_replace("/", "", date('d/m/Y', time())), $str);
                        $str=str_replace("BENEFICIARYNAME", str_replace(',', '', $order['order_payment_name']), $str);
                        if ($order['order_payment_type'] == "PayNow Account" && ($order['order_payment_number'][0] == "8" || $order['order_payment_number'][0] == "9")) {
                            $str=str_replace("ACCOUNTNUMBER", '+65'.$order['order_payment_number'], $str);                    
                        } else {
                            $str=str_replace("ACCOUNTNUMBER", str_replace("-", "", $order['order_payment_number']), $str);
                        }
                        $str=str_replace("AMOUNT", $order['order_final_price'], $str);
                        $str=str_replace("IMEI", $order['imei'], $str);
                        $bene_cate = "";
                        if ($order['order_payment_type'] == "PayNow Account") {
                            $str=str_replace("GPP", "PPP", $str);
                            if ($order['order_payment_number'][0] == "8" || $order['order_payment_number'][0] == "9") {
                                $bene_cate = "M";
                            } else {
                                $bene_cate = "N";
                            }
                        }
                        $str=str_replace("BENECATEGORY", $bene_cate, $str);
                        $str=str_replace("INVOICEDETAILS", $order['order_sn_number'], $str);
    
                        $swiftcode = "";
                        switch ($order['order_payment_type']) {
                            case "DBS/POSB":
                                $swiftcode = "DBSSSGSGXXX";
                                break;
                            case "UOB":
                                $swiftcode = "UOVBSGSGXXX";
                                break;
                            case "OCBC":
                                $swiftcode = "OCBCSGSGXXX";
                                break;
                            case "HSBC":
                                $swiftcode = "HSBCSGS2XXX";
                                break;
                            case "CITIBANK":
                                $swiftcode = "CITISGSLXXX";
                                break;
                            default:
                                break;
                        }
    
                        
                        $str=str_replace("SWIFTCODE", $swiftcode, $str);
    
    
    
                        file_put_contents(__DIR__ . "/payment/payment_file.txt", $str);
    
                        #setting headers
                        header('Content-Description: File Transfer');
                        header('Cache-Control: public');
                        header('Content-Type: '.'.txt');
                        header("Content-Transfer-Encoding: binary");
                        header('Content-Disposition: attachment; filename='. basename(__DIR__ . "/payment/".$order['order_id']."_".$order['order_final_price'].".txt"));
                        header('Content-Length: '.filesize(__DIR__ . "/payment/payment_file.txt"));
                        ob_clean(); #THIS!
                        flush();
                        readfile(__DIR__ . "/payment/payment_file.txt");
                        $oid = $order['order_id'];
                        $this->flowerhua->db_update('whizmo_order',array('payment_file_status'=>1),"order_id = $oid");
    
                    }
                }

                
            }
            //download payment file ends

            //print order info starts
            if($this->post('print')){

                $data = $this->post('data');

                //get deduction for each item
                //capacity
                $capacity_deduction = '';
                $capacity_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and (parameter_name like '%Capacity%' or parameter_name like '%Storage%')",'id');
                if ($capacity_id) {
                    $capacity_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $capacity_id");  
                    foreach ($capacity_rows as $key => $capacity_row) {
                        $capacity_deduction .= $capacity_row[parameter_name].' - '.$capacity_row[parameter_sale_price].' / ';
                    }                  
                }

                //icloud or google account
                $account_deduction = '';
                $account_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%account%'",'id');
                if ($account_id) {
                    $account_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $account_id");   

                    foreach ($account_rows as $key => $account_row) {
                        $account_prices[$key] = $account_row[parameter_sale_price];
                    }
                    asort($account_prices); //sort small to large
                    foreach ($account_prices as $key => $account_price) {
                            $account_deduction .= strval($account_price).' / ';                    
                    }                      
                }

                //glass
                $screen_deduction = '';
                $screen_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Screen%'",'id');
                if ($screen_id) {
                    $screen_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $screen_id");   

                    foreach ($screen_rows as $key => $screen_row) {
                        $screen_prices[$key] = $screen_row[parameter_sale_price];
                    }
                    asort($screen_prices); //sort small to large
                    foreach ($screen_prices as $key => $screen_price) {
                            $screen_deduction .= strval($screen_price).' / ';                    
                    }                      
                }

                //LCD
                $lcd_deduction = '';
                $lcd_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Display%'",'id');
                if ($lcd_id) {
                    $lcd_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $lcd_id");   

                    foreach ($lcd_rows as $key => $lcd_row) {
                        $lcd_prices[$key] = $lcd_row[parameter_sale_price];
                    }
                    asort($lcd_prices); //sort small to large
                    foreach ($lcd_prices as $key => $lcd_price) {
                            $lcd_deduction .= strval($lcd_price).' / ';                    
                    }                      
                }

                //Housing
                $housing_deduction = '';
                $housing_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Housing%'",'id');
                if ($housing_id) {
                    $housing_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $housing_id");   

                    foreach ($housing_rows as $key => $housing_row) {
                        $housing_prices[$key] = $housing_row[parameter_sale_price];
                    }
                    asort($housing_prices); //sort small to large
                    foreach ($housing_prices as $key => $housing_price) {
                            $housing_deduction .= strval($housing_price).' / ';                    
                    }                      
                }

                //back glass
                $back_deduction = '';
                $back_id = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Back Glass%'",'id');
                if ($back_id) {
                    $back_rows = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $back_id");   

                    foreach ($back_rows as $key => $back_row) {
                        $back_prices[$key] = $back_row[parameter_sale_price];
                    }
                    asort($back_prices); //sort small to large
                    foreach ($back_prices as $key => $back_price) {
                            $back_deduction .= strval($back_price).' / ';                    
                    }                      
                }       

                //water
                $water_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Water%'",'parameter_sale_price');
                //switch
                $switch_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%switch%'",'parameter_sale_price');
                //Battery
                $battery_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Battery%'",'parameter_sale_price');
                //Charging
                $charging_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Charging%'",'parameter_sale_price');
                //Front camera
                $frontcam_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Front camera%'",'parameter_sale_price');
                //Back camera
                // $backcam_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Back camera%'",'parameter_sale_price');

                $backcam_minor_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Faint%'",'parameter_sale_price');

                $backcam_major_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%severe black%'",'parameter_sale_price');

                $backcam_deduction = strval($backcam_minor_deduction).' / '.strval($backcam_major_deduction);

                //Power
                $power_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Power%'",'parameter_sale_price');
                //Volume
                $volume_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Volume%'",'parameter_sale_price');
                //Fingerorint
                $fingerprint_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Fingerprint%'",'parameter_sale_price');
                //IRIS
                $iris_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%IRIS%'",'parameter_sale_price');
                //Face ID
                $faceid_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Face ID%'",'parameter_sale_price');
                //Speaker
                $speaker_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Speaker%'",'parameter_sale_price');
                //Wifi
                $wifi_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Wifi%'",'parameter_sale_price');
                //home
                $home_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Home%'",'parameter_sale_price');

                $faceid_iris_deduction = $faceid_deduction + $iris_deduction;

                //charger
                $charger_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Charger%'",'parameter_sale_price');
                //cable
                $cable_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Cable%'",'parameter_sale_price');
                //earphone
                $earphone_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Earphone%'",'parameter_sale_price');
                //adapter
                $adapter_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%adapter%'",'parameter_sale_price');
                //box
                $box_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and parameter_name like '%Box%'",'parameter_sale_price');
                //stylus
                $stylus_deduction = $this->flowerhua->get_field('whizmo_recovery_parameter',"recovery_id = $order[order_recovery_id] and (parameter_name like '%stylus%' or parameter_name like '%pen%')",'parameter_sale_price');

                $roadbul_order = $this->flowerhua->get_one('whizmo_order2roadbul',"order_id = $order_id", ''); // retrieve pickup order details

                require_once("vendor/autoload.php"); 

                /* Start to develop here. Best regards https://php-download.com/ */

                // $spreadsheet = new Spreadsheet();
                $inputFileName = __DIR__ . '/checklist/checklist.xlsx';
                $spreadsheet = IOFactory::load($inputFileName);  

                if ($order['member_status'] == 2) {
                    $member_status = 'Verified';
                } else {
                    $member_status = 'NOT verified';
                }            

                if ($roadbul_order['roadbul_PickupTimeSlotId'] == 26) {
                    $collection_slot = "Afternoon";
                } else if ($roadbul_order['roadbul_PickupTimeSlotId'] == 29) {
                    $collection_slot = "Morning";                    
                } else {
                    $collection_slot = "Evening";
                }

                
                $spreadsheet->setActiveSheetIndex(0)
                    ->setCellValue('B1', $order['order_sn_number'])
                    ->setCellValue('E1', $roadbul_order['roadbul_PickupDate'].' '.$collection_slot)
                    ->setCellValue('B2', $roadbul_order['roadbul_FromAddress'].' (S) '.$roadbul_order['roadbul_FromZipCode'])
                    ->setCellValue('B5', $order['order_payment_name'])                    
                    ->setCellValue('B6', $order['member_phone'])
                    // ->setCellValue('B7', date('d/m/Y H:i',$order['order_addtime']))
                    ->setCellValue('E4', $order_id)
                    // ->setCellValue('B9', $member_status)
                    // ->setCellValue('B10', $order['order_payment_type'].' - '.$order['order_payment_number'])

                    // ->setCellValue('E5', $admin_username) 
                    ->setCellValue('E5', $order['recovery_title'])
                    ->setCellValue('E7', $order['order_totalprice'])  
                    ->setCellValue('E8', $order['order_coupon_code_money'])
                    

                    //auto populate item price deductions
                    ->setCellValue('C15', $capacity_deduction)
                    ->setCellValue('C17', $account_deduction)
                    ->setCellValue('C19', $screen_deduction) 
                    ->setCellValue('C20', $lcd_deduction) 
                    ->setCellValue('C23', $housing_deduction) 
                    ->setCellValue('C24', $back_deduction)  

                    ->setCellValue('E26', strval($water_deduction))
                    ->setCellValue('E27', strval($switch_deduction))
                    ->setCellValue('E28', strval($battery_deduction))
                    ->setCellValue('E29', strval($charging_deduction))
                    ->setCellValue('E30', strval($frontcam_deduction))
                    ->setCellValue('E31', strval($backcam_deduction))
                    ->setCellValue('E32', strval($home_deduction))
                    ->setCellValue('E33', strval($fingerprint_deduction))
                    ->setCellValue('E34', strval($volume_deduction))
                    ->setCellValue('E35', strval($power_deduction))
                    ->setCellValue('E36', strval($speaker_deduction))
                    ->setCellValue('E37', strval($faceid_iris_deduction))
                    ->setCellValue('E38', strval($wifi_deduction))

                    ->setCellValue('E41', strval($charger_deduction))
                    ->setCellValue('E42', strval($cable_deduction))
                    ->setCellValue('E43', strval($earphone_deduction))
                    ->setCellValue('E44', strval($adapter_deduction))
                    ->setCellValue('E46', strval($stylus_deduction))//stylus
                    ->setCellValue('E45', strval($box_deduction));



                //self appraisal in different lines
                // foreach ($order['recovery_parameter'] as $key => $item) {
                //     if ($item['recovery_parameter_type']==1) {
                //         $spreadsheet->getActiveSheet()->setCellValue('I'.strval($key+15), $item['recovery_parameter_parent_title'].' : '.$item['recovery_parameter_title']);
                //     } else {
                //         $spreadsheet->getActiveSheet()->setCellValue('I'.strval($key+15), $item['recovery_parameter_title']);
                //     }              
                // }

                //self appraisal in one cell
                $self_appraisal = '';
                foreach ($order['recovery_parameter'] as $key => $item) {
                    if ($item['recovery_parameter_type']==1) {
                        $self_appraisal .= $item['recovery_parameter_parent_title'].' : '.$item['recovery_parameter_title'].' ';            
                        

                    } else {
                        $self_appraisal .= $item['recovery_parameter_title'].' ';
                    }              
                }
                $spreadsheet->getActiveSheet()->setCellValue('L2', $self_appraisal);



                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="newchecklist.xlsx"');
                header('Cache-Control: max-age=0');

                $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                $writer->save('php://output');
                exit();

            }
            //print order info ends

            //get self selection functional and accessories

            $assessment = $order[assessment];
            if (is_null($assessment)) {
                $assessment = "";
                $i = 0;
                foreach ($order['recovery_parameter'] as $key => $item) {
                    if ($item[recovery_parameter_type] == 2) {
                        $assessment = $assessment . $item[recovery_parameter_title] . $i;
                        $i++;
                    }
                }
            }

            $apiResponse = callAPI('GET', '/store');
            $stores = json_decode($apiResponse->getBody(),true);

            $surveyResponse = callAPI('GET', '/customer-survey/'.$order['customerSurvey']);
            $surveyOption = json_decode($surveyResponse->getBody(),true);

            if($order['storeId']){
                $selectedStore = $order['storeId'];
            } 

            // print_r($order);
            // die();
            $this->view->assign(array(

                'order'         => $order,
                'selectedStore' => $selectedStore,
                'surveyOption'  => $surveyOption,
                'stores'        => $stores,
                'data2'         => $data2,
                'optional'      => $optional, // send functional and accessory options
                'assessment'    => $assessment,

            ));

            $this->view->display('admin/admin_order_add');

        }

        //修改订单状态集合

        public function ajax_change_statusAction(){

            if (IS_AJAX) {

                $status = $this->post('status');

                $price = $this->post('value');

                $fang = $this->post('fang');

                $description = $this->post('description');

                $order_id = $this->post('order_id');

                if($order_id){

                    if($price){

                        $array['order_final_price'] = $price;

                    }

                    if($description){

                        $array['order_description'] = $description;

                    }

                    $array['order_status'] = $status;

                    if($status == 5){
                        $array['dateAccepted'] = date("Y-m-d H:i:s");
                    }
                   
                    $this->flowerhua->db_update('whizmo_order',$array,"order_id = $order_id");

                    // die(json_encode(array('code' => 1)));

                    $price = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_final_price');
                    //bank account details
                    $bank_id = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_payment_type');
                    $bank = $this->flowerhua->get_field('whizmo_category_type',"id = $bank_id",'name');
                    $accountname = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_payment_name');
                    $accountno = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_payment_number');

                    die(json_encode(array('code' => 1, 'status' => $status, 'fang' => $fang, 'order_sn' => $order_id, 'final_price' => $price, 'bank' => $bank, 'account_name' => $accountname, 'account_no' => $accountno)));

                }

            }

        }

        //物流发货 - 使用接口发快递

        public function admin_roadblu_fahuoAction()

        {

            if (fileatime(FCPATH.'cache/views/roadblu_type.txt')>=(time()-86400)) {

                $roadblu_type = file_get_contents(FCPATH.'cache/views/roadblu_type.txt');

                $roadbul_array = json_decode($roadblu_type,true);

            }else{

                $obj = _post($this->roadbul_url.'/orders/producttypes','',$this->header);

                $roadbul_array = json_decode($obj, true);

                file_put_contents(FCPATH.'cache/views/roadblu_type.txt',json_encode($roadbul_array));

            }

            if($this->post('submit')){

                $shelve_id = $this->post('shelve_id');

                $this->flowerhua->begin();

                $data = $this->post('data');

                if((int)(date('w',$data['PickupDate']))==6){

                    $data['DeliveryDate'] = date('d/m/Y',($data['PickupDate']+86400*2));

                }else {

                    $data['DeliveryDate'] = date('d/m/Y',($data['PickupDate']+86400*1));

                }

                $data['PickupDate'] = date('d/m/Y',$data['PickupDate']);

//                $data['PickupDate'] = date('d/m/Y',(time()+86400*5));

//                $data['DeliveryDate'] = date('d/m/Y',(time()+86400*6));

//                $data['PickupDate']         = date('d/m/Y',time());

//                $data['DeliveryDate']       = date('d/m/Y',time()+3600*24);

                $obj = _post($this->roadbul_url.'/orders/book',json_encode($data),$this->header);

                $roadbul_array = json_decode($obj, true);

                if($roadbul_array['Code']==0){

                    $data = $this->post('data');

                    //保存物流信息在数据库中

                    $roadbul_mysql = [];

                    if($shelve_id){

                        $roadbul_mysql['roadbul_whizmo_type'] = 300;

                        $roadbul_mysql['roadbul_type']=3;

                        $this->flowerhua->db_update('whizmo_shelve',array('status'=>4,'ordernumber'=>$roadbul_array['OrderNumber']),"id = $shelve_id");

                    }

                    $roadbul_mysql['order_id'] = '';

                    $roadbul_mysql['roadbul_OrderNumber'] = $roadbul_array['OrderNumber'];

                    $roadbul_mysql['roadbul_FromName'] = $this->site['DELIVERY_NAME'];

                    $roadbul_mysql['roadbul_FromZipCode'] = $this->site['DELIVERY_CODE'];

                    $roadbul_mysql['roadbul_FromAddress'] = $this->site['DELIVERY_ADDRESS'];

                    $roadbul_mysql['roadbul_FromMobilePhone'] = $this->site['DELIVERY_PHONE'];

                    $roadbul_mysql['roadbul_ToName'] = $data['ToName'];

                    $roadbul_mysql['roadbul_ToZipCode'] = $data['ToZipCode'];

                    $roadbul_mysql['roadbul_ToAddress'] = $data['ToAddress'];

                    $roadbul_mysql['roadbul_ToMobilePhone'] = $data['ToMobilePhone'];

                    $roadbul_mysql['roadbul_ProductTypeId'] = $data['ProductTypeId'];

                    $roadbul_mysql['roadbul_SizeId'] = $data['SizeId'];

                    $roadbul_mysql['roadbul_ServiceId'] = $data['ServiceId'];

                    $roadbul_mysql['roadbul_PickupTimeSlotId']   = $data['PickupTimeSlotId'];

                    if((int)(date('w',$data['PickupDate']))==6){

                        $roadbul_mysql['roadbul_DeliveryDate'] = date('d/m/Y',($data['PickupDate']+86400*2));

                    }else {

                        $roadbul_mysql['roadbul_DeliveryDate'] = date('d/m/Y',($data['PickupDate']+86400*1));

                    }

                    $roadbul_mysql['roadbul_PickupDate'] = date('d/m/Y',$data['PickupDate']);

                    $roadbul_mysql['roadbul_DeliveryTimeSlotId'] = $data['DeliveryTimeSlotId'];

                    $return = $this->flowerhua->db_add('whizmo_order2roadbul',$roadbul_mysql);

                    if($return && $roadbul_array['Code']==0){

                        $this->flowerhua->commit();

                        $this->adminMsg('Delivery success', url('admin/flowerhua/admin_roadblu_fahuo'), 3, 1, 1);

                    }

                }else{

                    $this->flowerhua->rollback();

                    $this->adminMsg($roadbul_array['Message'], '', 2, 1, 2);

                }

            }

            $id = $this->get('id');

            if($id){

                $recycler_id = $this->flowerhua->get_field('whizmo_shelve',"id = $id",'recycler_id');

                $data = $this->flowerhua->get_one('whizmo_recycler',"id = $recycler_id",'');

            }



            //取件时间(默认明天到三天后)

            $pickup_time_start = [];

            if(date('w',time())==6){

                $pickup_time_start[0] = time()+3600*24*2;

                $pickup_time_start[1] = time()+3600*24*3;

                $pickup_time_start[2] = time()+3600*24*4;

            }else if(date('w',time())==4){

                $pickup_time_start[0] = time()+3600*24;

                $pickup_time_start[1] = time()+3600*24*2;

                $pickup_time_start[2] = time()+3600*24*4;

            }

            else if(date('w',time())==5){

                $pickup_time_start[0] = time()+3600*24;

                $pickup_time_start[1] = time()+3600*24*3;

                $pickup_time_start[2] = time()+3600*24*4;

            }else{

                $pickup_time_start[0] = time()+3600*24;

                $pickup_time_start[1] = time()+3600*24*2;

                $pickup_time_start[2] = time()+3600*24*3;

            }

            $this->view->assign(array(

                'pickup_time_start'=>$pickup_time_start,

                'roadbul_array' => $roadbul_array['ProductTypes'],

                'data'=>$data,

                'id'=>$id,

            ));

            $this->view->display('admin/admin_roadblu_fahuo');

        }

        //查询物流

        public function get_roadbul_queryAction()

        {

            $sn = $this->get('sn');

            if($sn){

                $roadbul_order = $this->flowerhua->get_one('whizmo_order2roadbul',"roadbul_OrderNumber = '$sn'");

                $obj = _post($this->roadbul_url.'/orders/tracking/'.$sn,'',$this->header);

                $roadbul_array = json_decode($obj, true);

                $order_status = $roadbul_array['Orders'][0]['Status'];

                if($roadbul_array['Code']==0){

                    $this->view->assign(array(

                        'order_status'=>$this->order_status,

                        'sn'=>$sn,

                        'roadbul_order'        => $roadbul_order,

                        'order_status' => $this->order_status[$order_status],

                    ));

                    $this->view->display('admin/get_roadbul_query');

                }

            }

        }

        //物流发货列表

        public function admin_fahuo_listAction(){

            $roadbul_whizmo_type = $this->post('roadbul_whizmo_type');

            $order_sn = $this->post('order_sn');

            $where = "1=1";

            $order = "id desc";

            if($roadbul_whizmo_type){

                $where .= " and roadbul_whizmo_type = $roadbul_whizmo_type";

            }

            if($order_sn){

                $where .= " and concat(roadbul_OrderNumber,roadbul_FromName,roadbul_ToName,roadbul_FromMobilePhone,roadbul_ToMobilePhone) like  '%$order_sn%'";

            }

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $limit = ($page-1)*$this->admin_pagesize.",$this->admin_pagesize";

            $urlparam = array('page' => '{page}','roadbul_whizmo_type'=>$roadbul_whizmo_type,'order_sn'=>$order_sn);

            $list = $this->flowerhua->get_all_list('whizmo_order2roadbul',$order,$limit,$where);

            $total = $this->flowerhua->get_all_list('whizmo_order2roadbul',$order,$limit,$where,true);

            $pagelist = $this->instance('pagelist');//加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/admin_fahuo_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();

            $time = [28=>'Evening Collection (16:00PM - 20:00PM)',27=>'Evening Collection (16:00PM - 20:00PM)',26=>'Noon Collection (12:00PM - 16:00PM)', 29=>'Morning Collection (09:00AM - 13:00PM)']; //29 is qoo10 pickup

            $this->view->assign(array(

                'list' => $list,

                'time' => $time,

                'order_sn' => $order_sn,

                'roadbul_whizmo_type' => $roadbul_whizmo_type,

                'pagelist' => $pagelist,

            ));

            $this->view->display('admin/admin_fahuo_list');

        }

        //ajax查询所有父级品牌分类

        public function find_parent_listAction(){

            if (IS_AJAX) {

                $parentid = $this->post('parentid');

                $result = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = $parentid");

                if(count($result)){

                    $str = '<option value="">Please select the brand</option>';

                    foreach ($result as $key => $value) {

                        $str .="<option value='".$value['id']."'>$value[name]</option>";

                    }

                    die(json_encode(array('code' => 1,'str'=>$str)));

                }else{

                    die(json_encode(array('code' => -1)));

                }

            }

        }



        public function ajax_Time2picktimeAction(){

            if (IS_AJAX) {

                $time = $this->post('time');

                $resultPickup = '';

                if((int)(date('w',$time))==6){

                    $resultPickup .='<option value="26" data-value="Noon Collection (12:00 - 16:00)" >Noon Collection (12:00 - 16:00)</option>';

                }else{

                    $resultPickup .='<option value=""> Select Pickup Time</option>';

                    $resultPickup .='<option value="26" data-value="Noon Collection (12:00 - 16:00)" >Noon Collection (12:00 - 16:00)</option>';

                    $resultPickup .='<option value="27" data-value="Evening Collection (16:00 - 20:00)" >Evening Collection (16:00 - 20:00)</option>';

                }

                die(json_encode(array('code' => 1,'str'=>$resultPickup)));

            }

        }



        public function ajax_addArrayAction(){

            if (IS_AJAX) {

                $recovery_id = $_POST['recovery_id'];

                $data_parameter['ch_parameter_is_select'] = $_POST['ch_parameter_is_select'];

                $data_parameter['ch_recovery_listorder']  = $_POST['ch_recovery_listorder'];

                $data_parameter['ch_recovery_name']       = $_POST['ch_recovery_name'];

                $data_parameter['ch_recovery_sale']       = $_POST['ch_recovery_sale'];

                $data_parameter['ch_recovery_desc']       = $_POST['ch_recovery_desc'];

                $data_parameter['ch_recovery_thumb']      = $_POST['ch_recovery_thumb'];

                $data_parameter['ch_recovery_thumb1']      = $_POST['ch_recovery_thumb1'];

                $data_parameter['ch_parameter_is_select2'] = $_POST['ch_parameter_is_select2'];

                $data_parameter['ch_recovery_listorder2']  = $_POST['ch_recovery_listorder2'];

                $data_parameter['ch_recovery_name2']       = $_POST['ch_recovery_name2'];

                $data_parameter['ch_recovery_sale2']       = $_POST['ch_recovery_sale2'];

                $data_parameter['ch_recovery_desc2']       = $_POST['ch_recovery_desc2'];

                $data_parameter['ch_recovery_thumb2']      = $_POST['ch_recovery_thumb2'];

                $data_parameter['ch_recovery_thumb12']      = $_POST['ch_recovery_thumb12'];

                $one_Array                                = $_POST['one_Array'];



                $one_Array = htmlspecialchars_decode($one_Array);

                $result = json_decode($one_Array,true);

                if($recovery_id){

                    $delete_list = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $recovery_id",'id');

                    foreach($delete_list as $key => $value){

                        $res0 = $this->flowerhua->db_del('whizmo_recovery_parameter',"id = $value[id]");

                    }

                }

                

                if(is_array($result)){

                    foreach ($result as $_key => $_value) {

                        $parent_parent_info['recovery_id'] = $recovery_id;

                        $parent_parent_info['parentid'] = 0;

                        $parent_parent_info['parameter_name'] = $_value[0];

                        $parent_parent_info['parameter_is_select'] = 1;



                        $parent_parameter_id = $this->flowerhua->db_add('whizmo_recovery_parameter', $parent_parent_info);



                        unset($_value[0]);

                        foreach ($_value as $one_key => $one_value) {

                            $parent_info['recovery_id'] = $recovery_id;

                            $parent_info['parentid'] = $parent_parameter_id;

                            $parent_info['parameter_is_select'] = 1;

                            $parent_info['parameter_name'] = $one_value[0];



                            $parameter_id = $this->flowerhua->db_add('whizmo_recovery_parameter', $parent_info);

                            unset($one_value[0]);

                            foreach ($one_value as $two_key => $two_value) {

                                //获取到回收菜单的名称为$two_value

                                $child_info['recovery_id'] = $recovery_id;

                                $child_info['parentid'] = $parameter_id;

                                $child_info['parameter_is_select'] = $two_value['parameter_is_select'] == 'true' ? 1 : -1;

                                $child_info['parameter_name'] = $two_value['parameter_name'];

                                $child_info['parameter_listorder'] = $two_value['recovery_listorder'];

                                $child_info['parameter_sale_price'] = $two_value['recovery_sale'];

                                $child_info['parameter_boost'] = $two_value['recovery_boost']; //add boost

                                $child_info['parameter_tag'] = $two_value['tag']; //add tag

                                $child_info['parameter_description'] = $two_value['recovery_desc'];

                                $child_info['parameter_img'] = $two_value['recovery_thumb'];

                                $res2 = $this->flowerhua->db_add('whizmo_recovery_parameter', $child_info);

                            }

                        }

                    }

                }



                foreach($data_parameter['ch_recovery_name'] as $key => $value){

                    //获取到回收菜单的名称为$two_value

                    $ch_info['recovery_id'] = $recovery_id;

                    $ch_info['parentid'] = 2;

                    $ch_info['parameter_is_select'] = $data_parameter['ch_parameter_is_select'][$key] == 'true' ?1:-1;

                    $ch_info['parameter_name'] = $data_parameter['ch_recovery_name'][$key];

                    $ch_info['parameter_listorder'] = $data_parameter['ch_recovery_listorder'][$key];

                    $ch_info['parameter_sale_price'] = $data_parameter['ch_recovery_sale'][$key];

                    $ch_info['parameter_description'] = $data_parameter['ch_recovery_desc'][$key];

                    $ch_info['parameter_img'] = $data_parameter['ch_recovery_thumb'][$key];

                    $ch_info['parameter_img1'] = $data_parameter['ch_recovery_thumb1'][$key];

                    $res1 = $this->flowerhua->db_add('whizmo_recovery_parameter',$ch_info);

                }

                foreach($data_parameter['ch_recovery_name2'] as $key => $value){

                    //获取到回收菜单的名称为$two_value

                    $ch_info['recovery_id'] = $recovery_id;

                    $ch_info['parentid'] = 22;

                    $ch_info['parameter_is_select'] = $data_parameter['ch_parameter_is_select2'][$key] == 'true' ?1:-1;

                    $ch_info['parameter_name'] = $data_parameter['ch_recovery_name2'][$key];

                    $ch_info['parameter_listorder'] = $data_parameter['ch_recovery_listorder2'][$key];

                    $ch_info['parameter_sale_price'] = $data_parameter['ch_recovery_sale2'][$key];

                    $ch_info['parameter_description'] = $data_parameter['ch_recovery_desc2'][$key];

                    $ch_info['parameter_img'] = $data_parameter['ch_recovery_thumb2'][$key];

                    $ch_info['parameter_img1'] = $data_parameter['ch_recovery_thumb12'][$key];

                    $res1 = $this->flowerhua->db_add('whizmo_recovery_parameter',$ch_info);

                }



                die(json_encode(array('code' => 1)));

            }

        }

        //ajax添加回收信息

        public function ajax_add_activityAction(){

//            echo header("Access-Control-Allow-Origin:*");

//            dd($_POST);

//            die;

            if (IS_AJAX) {

                $recovery_id         = $_POST['recovery_id'];

                $data['recovery_title']         = $_POST['recovery_title'];

                $data['recovery_title2']        = $_POST['recovery_title2'];

                $data['recovery_type_id']       = $_POST['recovery_type_id'];

                $data['recovery_parent_id']     = $_POST['recovery_parent_id'];

                $data['recovery_high_price']    = $_POST['recovery_high_price'];

                $data['recovery_low_price']    = $_POST['recovery_low_price'];

                $data['recovery_cold_num']      = $_POST['recovery_cold_num'];

                $data['recovery_img']           = $_POST['recovery_img'];





                $this->db->query('begin');

                $data['addtime'] = time();

                if($recovery_id){

                    $this->flowerhua->db_update('whizmo_recovery',$data,"id = $recovery_id");

                }else{

                    $recovery_id = $this->flowerhua->db_add('whizmo_recovery',$data);

                }







                $this->db->query('commit');

                die(json_encode(array('code' => 1,'recovery_id'=>$recovery_id)));

//                $a = '[["",{"parameter_is_select":"on","parameter_name":"a","recovery_listorder":"","recovery_sale":"","recovery_desc":"","recovery_thumb":""}],["",{"parameter_is_select":"on","parameter_name":"a","recovery_listorder":"","recovery_sale":"","recovery_desc":"","recovery_thumb":""}]]';

//Array

//(

//    [0] => Array

//    (

//        [0] => 2

//            [1] => Array

//            (

//                [parameter_is_select] => on

//                [parameter_name] => a

//            [recovery_listorder] =>

//                [recovery_sale] =>

//                [recovery_desc] =>

//                [recovery_thumb] =>

//            )

//        )

//    [1] => Array

//        (

//            [0] => 1

//                [1] => Array

//        (

//            [parameter_is_select] => on

//            [parameter_name] => a

//            [recovery_listorder] =>

//            [recovery_sale] =>

//            [recovery_desc] =>

//            [recovery_thumb] =>

//        )

//    )

//)



            }

        }

        //删除回收

        public function admin_recovery_delAction()

        {

            $id = (int)$this->get('id');

            if ($id) {

                $list = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $id");

                foreach ($list as $key => $value) {

                    $this->del($value['id'],'whizmo_recovery_parameter');

                }

                $this->del($id,'whizmo_recovery');

                $this->adminMsg(lang('success'), url('admin/flowerhua/admin_recovery_list'), 3, 1, 1);

            }

        }

        //

        public function admin_technological_listAction()

        {



            $this->view->assign(array(

                'abc'        => $abc,

            ));

            $this->view->display('admin/admin_technological_list');

        }


        //ajax show or hide model

        public function show_hideAction(){

            if (IS_AJAX) {

                $id = $this->post('id');

                $state = $this->post('state');

                if (!$id) {

                    die(json_encode(array('code' => -1)));

                }

                if ($state == 0) {

                    $data['show_hide'] = 1;

                } else {

                    $data['show_hide'] = 0;

                }                

                $this->flowerhua->db_update('whizmo_recovery',$data,"id = $id");

                die(json_encode(array('code' => 1)));

            }

        }


        //ajax添加热门产品

        public function add_recovery_hitsAction(){

            if (IS_AJAX) {

                $id = $this->post('id');

                if (!$id) {

                    die(json_encode(array('code' => -1)));

                }

                $data['recovery_hits'] = 1;

                $this->flowerhua->db_update('whizmo_recovery',$data,"id = $id");

                die(json_encode(array('code' => 1)));

            }

        }



        //ajax取消热门产品

        public function cancel_recovery_hitsAction(){

            if (IS_AJAX) {

                $id = $this->post('id');

                if (!$id) {

                    die(json_encode(array('code' => -1)));

                }

                $data['recovery_hits'] = 0;

                $this->flowerhua->db_update('whizmo_recovery',$data,"id = $id");

                die(json_encode(array('code' => 1)));

            }

        }



        //订单详情页面提示语

        public function admin_order_noticeAction(){

            $order_notice = $this->flowerhua->get_field('whizmo_order_notice',"id = 113",'order_notice');

            if($this->post('submit')){

                $info= $this->post('data');

                $res = $this->flowerhua->db_update('whizmo_order_notice',$info,"id = 113");

                if($res){

                    $this->adminMsg(lang('success'),url('admin/flowerhua/admin_order_notice'),2,1,1);

                }

            }

            $this->view->assign(array('order_notice'=>$order_notice));

            $this->view->display('admin/admin_order_notice');

        }







































































        //区域删除

        public function area_delAction()

        {

            $del_id = (int)$this->get('del_id');

            if ($del_id) {

                $del_list = $this->flowerhua->get_all_where('area' , 'id' ,"id = $del_id");

                foreach ($del_list as $key => $value) {

                    $child_list = $this->flowerhua->get_all_where('area' , 'id' ,"parent_id = $value[id]");

                    foreach ($child_list as $key1 => $value1) {

                        $this->del($value1['id'],'area');

//                        $child_child_list = $this->flowerhua->get_all_where('area' , 'id' ,"parent_id = $value1[id]");

//                        foreach ($child_child_list as $key2 => $value2) {

//                            $this->del('area', 'id =' . $value2['id']);

//                        }

                    }

                    $this->del($value['id'],'area');

                }

                    $this->adminMsg(lang('success'), url('admin/flowerhua/area_list'), 3, 1, 1);

            }

        }

        //导入地区数据

        public  function dao_areaAction(){

            $table = 'area';

            if ($_POST) {//1覆盖，

                if ($_POST['fugai'] == 1) {

                    $res= $this->flowerhua->db_truncate($table);

                }

                $xls_url = $_POST['start_xls'];

                if (empty($xls_url)) {

                    // 提醒

                    die();

                }

                require_once 'Classes/PHPExcel.php';

                require_once 'Classes/PHPExcel/IOFactory.php';

                require_once 'Classes/PHPExcel/Reader/Excel5.php';

                try {

                    $inputFileType = PHPExcel_IOFactory::identify($xls_url);

                    $objReader = PHPExcel_IOFactory::createReader($inputFileType);

                    $objReader->setReadDataOnly(true);//只需要添加这个方法

                    $objPHPExcel = $objReader->load($xls_url);

                } catch(Exception $e) {

                    die('Error loading file "'.pathinfo($xls_url,PATHINFO_BASENAME).'": '.$e->getMessage());

                }

//                $objReader = PHPExcel_IOFactory::createReader('Excel5');//use excel2007 for 2007 format

//                $objReader->setReadDataOnly(true);//只需要添加这个方法

//                $objPHPExcel = $objReader->load($xls_url); //$filename可以是上传的文件，或者是指定的文件

//                dd($xls_url);

                $sheet = $objPHPExcel->getSheet(0);

                $highestRow = $sheet->getHighestRow(); // 取得总行数

                $highestColumn = $sheet->getHighestColumn(); // 取得总列数

                $k = 0;

                //循环读取excel文件,读取一条,插入一条

                $a = array();

                $b = array();

                $c = array();



                for($j=1;$j<=$highestRow;$j++)

                {

                    $a1 = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();//获取A列的值

                    $b1 = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();//获取B列的值

                    $c1 = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();//获取B列的值

                    // if ($a1!=NULL || $a1 !='') {

                    $a[] = $a1;

                    // }

                    // if ($b1!=NULL || $b1 !='') {

                    $b[] = $b1;

                    // }

                    // if ($c1!=NULL || $c1 !='') {

                    $c[] = $c1;

                    // }

                }

                foreach ($a as $key => $value) {

                    $res = $this->flowerhua->get_one('area','',"area_name = '$value'");

                    if (empty($res)) {

                        $arr = array();

                        $arr['area_name'] = $value;

                        $arr['parent_id'] = 0;

                        $arr['area_show'] = 1;

                        $arr['area_addtime'] = time();

                        $id = $this->flowerhua->db_add('area',$arr);

//                    var_dump($id);die;

                        if ($id !=false) {

                            if ($b[$key] == '') {



                            }else{

                                $brr = array();

                                $brr['area_name'] = $b[$key];

                                $brr['parent_id'] = $id;

                                $brr['area_show'] = 1;

                                $brr['area_addtime'] = time();

                                $id = $this->flowerhua->db_add('area',$brr);

                                if ($c[$key] != '') {

                                    $crr = array();

                                    $crr['area_name'] = $c[$key];

                                    $crr['parent_id'] = $id;

                                    $crr['area_show'] = 1;

                                    $crr['area_addtime'] = time();

                                    $this->flowerhua->db_add('area',$crr);

                                }

                            }

                        }

                    }else{

                        if ($b[$key] !='') {

                            $d = $b[$key];

                            $ress = $this->flowerhua->get_one('area','',"area_name = '$d'");

                            if ($ress) {

                                if ($c[$key] != '') {

                                    $crr = array();

                                    $crr['area_name'] = $c[$key];

                                    $crr['area_name'] = $c[$key];

                                    $crr['parent_id'] = $ress['id'];

                                    $crr['area_show'] = 1;

                                    $crr['area_addtime'] = time();

                                    $this->flowerhua->db_add('area',$crr);

                                }

                            }else{

                                $brr = array();

                                $brr['area_name'] = $b[$key];

                                $brr['parent_id'] = $res['id'];

                                $brr['area_show'] = 1;

                                $brr['area_addtime'] = time();

                                $id = $this->flowerhua->db_add('area',$brr);

                                if ($id !=false) {

                                    if ($c[$key] != '') {

                                        $crr = array();

                                        $crr['area_name'] = $c[$key];

                                        $crr['parent_id'] = $id;

                                        $crr['area_show'] = 1;

                                        $crr['area_addtime'] = time();

                                        $this->flowerhua->db_add('area',$crr);

                                    }

                                }

                            }

                        }



                    }

                }

                $this->adminMsg(lang('success'), url('admin/flowerhua/area_list'), 3, 1, 1);

                die();

            }

            $this->view->display('admin/admin_area_edit');

        }

        //区域模板下载

        public function down_areaAction(){

            $filename = "area.xls";

            $file = fopen(FCPATH.'uploadfiles/model/'.$filename,"r");

            Header("Content-type:application/octet-stream");

            Header("Accept-Ranges:bytes");

            header("Content-Type:application/msexcel");

            Header("Accept-Length:".filesize(FCPATH.'uploadfiles/model/'.$filename));

            Header("Content-Disposition:attachment;filename=".$filename);

            echo

            fread($file,filesize(FCPATH.'uploadfiles/model/'.$filename));

            fclose($file);

            $this->adminMsg(lang('success'), url('sima/flowerhua/area_list'),2, 1,1);

        }

        //资源分类列表

        public function type_listAction()

        {

            if ($this->post('listorder')) {

                foreach ($_POST as $var => $value) {

                    if (strpos($var, 'order_') !== false) {

                        $id = (int)str_replace('order_', '', $var);

                        if ($value > 0) {

                            $data = array();

                            $data['listorder'] = $value;

                            $this->flowerhua->db_update('whizmo_category_type', $data, "id = $id");

                        }

                    }

                }

                $this->adminMsg('Sort success', $_SERVER['HTTP_REFERER'], 3, 1, 1);

            }

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $parentid = $this->safe('parentid');

            $type = $this->safe('type');

            $order = "listorder desc,id asc";

            $where = "1=1";

            if ($parentid) {

                $where .= " and parentid = $parentid";

            } else {

                $where .= " and parentid = 0";

            }

            $urlparam = array('page' => '{page}', 'parentid' => $parentid, 'type' => $type);

            $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";

            $list = $this->flowerhua->get_all_list('whizmo_category_type', $order, $limit, $where); //列表

            $total = $this->flowerhua->get_all_list('whizmo_category_type', $order, $limit, $where, true); //统计数量

            $pagelist = $this->instance('pagelist');    //加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/type_list', $urlparam))->num($this->admin_pagesize)->page($page)->output();

            $this->view->assign(array(

                'parentid' => $parentid,

                'type'     => $type,

                'list'     => $list,

                'pagelist' => $pagelist,

            ));

            $this->view->display('admin/admin_type_list');

        }

        //添加删除资源分类

        public function type_editAction()

        {

            $id = (int)$this->safe('id');

            $type = (int)$this->safe('type');

            $parentid = (int)$this->safe('parentid');

            if ($id) {

                $data = $this->flowerhua->get_one('whizmo_category_type', 'id = ' . $id);

            }

            if ($this->post('submit')) {

                $data = $this->post('data');

                if ($type) {

                    $data['type'] = $type;

                }

                if ($parentid) {

                    $data['parentid'] = $parentid;

                }

                if ($id) {

                    $this->flowerhua->db_update('whizmo_category_type', $data, 'id = ' . $id);

                } else {

                    $this->flowerhua->db_add('whizmo_category_type', $data);

                }

                $this->adminMsg(lang('success'), url('admin/flowerhua/type_list', array('parentid' => $parentid, 'type' => $type)), 3, 1, 1);

            }

            $this->view->assign(array(

                'data'     => $data,

                'type'     => $type,

                'parentid' => $parentid,

            ));

            $this->view->display('admin/admin_type_add');

        }

        //删除分类列表

        public function type_delAction()

        {

            $id = (int)$this->safe('id');

            $this->flowerhua->db_del('whizmo_category_type', 'id = ' . $id);

            $this->adminMsg('Deleted', url('admin/flowerhua/type_list'), 3, 1, 1);

            die;

        }

        //删除表数据

        function del($id, $table)

        {

            $this->flowerhua->db_del($table, "id = $id");

        }

























































        public function testAction()

        {

            if ($this->post('submit_del')) {

                if ($this->post('del_') == '') {

                    $this->adminMsg('Please select the record you want to delete!', url('admin/flowerhua/'), 2, 1, 1);

                } else {

                    $this->adminMsg('Multiple record deleting success', url('admin/flowerhua_moban/'), 3, 1, 1);

                }

            }

            $list = array();

            $list[0] = 1;

            $list[1] = 2;

            $list[2] = 2;

            $list[3] = 2;

            $list[4] = 1;

            $list[5] = 2;

            $list[6] = 2;

            $list[7] = 2;

            $list[8] = 1;

            $list[9] = 2;

            $province = $this->linkage->get_all_list();//地区

            $this->view->assign(array(

                'list'     => $list,

                'province' => $province,

            ));

            $this->view->display('admin/flowerhua_list');

        }

        public function editAction()

        {

            $id = (int)$this->get('id');

            $list = $this->flowerhua->get_one('member', '', "id = $id");

            if ($this->post('submit')) {

                $data = $this->post('data');

                $this->flowerhua->set($id, $data);

                $this->adminMsg(lang('success'), url('admin/flowerhua_moban/edit', array('id' => $id)), 3, 1, 1);

            }

            $province = $this->linkage->get_all_list();//地区

            $this->view->assign(array(

                'province' => $province,

                'list'     => $list,

            ));

            $this->view->display('admin/flowerhua_add');

        }

        public function HuaAction()

        {

            $list = array();

            $list[0] = 1;

            $list[1] = 2;

            $list[2] = 2;

            $list[3] = 2;

            $this->view->assign(array(

                'list' => $list,

            ));

            $this->view->display('admin/flowerhua_iFrames');

        }

 //feifei方法 

        public function recovery_noticeAction()

        {   

            if ($this->post('submit')) {

                foreach ($_POST as $var => $value) {

                    if (strpos($var, 'data_') !== false) {

                        $id = (int)str_replace('data_', '', $var);

                        if ($value >= 0) {

                            $data = array();

                            $data['sort'] = $value;

                            $this->flowerhua->db_update('whizmo_recovery_notice', $data, "id = $id");

                        }

                    }

                }

                $this->adminMsg('Sort success', $_SERVER['HTTP_REFERER'], 3, 1, 1);

            }

            $list = $this->flowerhua->get_all_where_and_order('whizmo_recovery_notice','1=1',$order='sort desc','');

            $this->view->assign(array('list'=>$list));

            $this->view->display('admin/recovery_notice');

        }



        public function recovery_notice_addAction()

        {   

            $id = $this->get('id');

            if($id){

                $data = $this->flowerhua->get_one('whizmo_recovery_notice',"id = $id",'');

            }

            if($this->post('submit')){

                $arr =  $this->post('data');

                $arr['addtime'] = time();

                if($id){

                    $res = $this->flowerhua->db_update('whizmo_recovery_notice',$arr,"id = $id");

                } else {

                   $res = $this->flowerhua->db_add('whizmo_recovery_notice',$arr);

                }

                if($res){

                    $this->adminMsg(lang('success'),url('admin/flowerhua/recovery_notice'),2,1,1);

                }

            }

            $this->view->assign(array('data'=>$data));

            $this->view->display('admin/recovery_notice_add');

        }

        public function del_recovery_noticeAction(){

            $id = $this->post('id');

            $this->flowerhua->db_del('whizmo_recovery_notice',"id = $id");

            die(json_encode(array('code'=>1)));

        }



        public function del_postalAction(){

            $id = $this->post('id');

            $this->flowerhua->db_del('whizmo_postal',"id = $id");

            die(json_encode(array('code'=>1)));

        }

        public function del_allpostalAction(){

            $this->flowerhua->db_truncate('whizmo_postal');

            die(json_encode(array('code'=>1)));

        }

        public function postal_addAction(){

            $id = $this->get('id');

            if($id){

                 $data = $this->flowerhua->get_one('whizmo_postal',"id = $id",'');

            }

            if($this->post('submit')){

                $data = $this->post('data');

                if($id){

                    $res = $this->flowerhua->db_update('whizmo_postal',$data,"id = $id");

                }else{

                    $id1 = $this->flowerhua->get_field('whizmo_postal',"postal_code = $data[postal_code]",'id');

                    if($id1){

                        $res = $this->flowerhua->db_update('whizmo_postal',$data,"id = $id1");

                    }else{

                        $res = $this->flowerhua->db_add('whizmo_postal',$data);

                    }

                }

                if($res){

                    $this->adminMsg(lang('success'),url('admin/flowerhua/postal'),2,1,1);

                }

            }

            $this->view->assign(array('data'=>$data));

            $this->view->display('admin/postal_add');

        }



        public function postalAction(){

            $page = (int)$this->get('page') ? (int)$this->get('page') : 1;

            $order = "id desc";

            $where = "1=1";

            $postal_code = $this->safe('postal_code');

            if($postal_code){

                $where .= " and postal_code like '%$postal_code%'";

            }

            $urlparam = array('page' => '{page}','postal_code'=>$postal_code);

            $limit = ($page - 1) * $this->admin_pagesize . ",$this->admin_pagesize";

            $list = $this->flowerhua->get_all_list('whizmo_postal', $order, $limit, $where); //列表

            $total = $this->flowerhua->get_all_list('whizmo_postal', $order, $limit, $where, true); //统计数量

            $pagelist = $this->instance('pagelist');    //加载分页类

            $pagelist->loadconfig();

            $pagelist = $pagelist->total($total)->url(url('admin/flowerhua/postal', $urlparam))->num($this->admin_pagesize)->page($page)->output();

            $this->view->assign(array(

                'list'=>$list,

                'pagelist'=>$pagelist,

                'postal_code'=>$postal_code,

            ));

           $this->view->display('admin/postal');

        }



        public function upExecelAction(){

            //判断是否选择了要上传的表格

            if (empty($_FILES)){

                echo "<script>alert(您未选择表格);history.go(-1);</script>";

            }

            //获取表格的大小，限制上传表格的大小5M

            $file_size = $_FILES['myfile']['size'];

            if ($file_size>10*1024*1024) {

            echo "<script>alert('上传失败，上传的表格不能超过10M的大小');history.go(-1);</script>";

                exit();

            }



            //限制上传表格类型

           /* $file_type = $_FILES['myfile']['type'];

            //application/vnd.ms-excel  为xls文件类型

            if ($file_type!='application/vnd.ms-excel') {

                echo "<script>alert('上传失败，只能上传excel2003的xls格式!');history.go(-1)</script>";

             exit();

            }*/

            //判断表格是否上传成功

            $name = explode('.',$_FILES['myfile']['name']);

            $i = count($name)-1;

            $houzhui_name = $name[$i];

            if (is_uploaded_file($_FILES['myfile']['tmp_name'])) {

                require_once EXTENSION_DIR.'Classes/PHPExcel.php';

                require_once EXTENSION_DIR.'Classes/PHPExcel/IOFactory.php';

                require_once EXTENSION_DIR.'Classes/PHPExcel/Reader/Excel5.php';



                //以上三步加载phpExcel的类

                if($houzhui_name=='xls'){

                    $objReader = PHPExcel_IOFactory::createReader('Excel5');//use excel2007 for 2007 format 

                }

                if($houzhui_name=='xlsx'){

                    $objReader = PHPExcel_IOFactory::createReader('Excel2007');//use excel2007 for 2007 format 

                }

                //接收存在缓存中的excel表格

                $filename = $_FILES['myfile']['tmp_name'];



                $objPHPExcel = $objReader->load($filename); //$filename可以是上传的表格，或者是指定的表格

                $sheet = $objPHPExcel->getSheet(0); 

                $highestRow = $sheet->getHighestRow(); // 取得总行数 

                // $highestColumn = $sheet->getHighestColumn(); // 取得总列数

                //循环读取excel表格,读取一条,插入一条

                //j表示从哪一行开始读取  从第二行开始读取，因为第一行是标题不保存

                //$a表示列号

                set_time_limit(0);

                for($j=2;$j<=$highestRow;$j++)  

                {

                    $data['postal_code'] = $objPHPExcel->getActiveSheet()->getCell("A".$j)->getValue();//获取A(邮编)列的值

                    $data['building_name'] = $objPHPExcel->getActiveSheet()->getCell("B".$j)->getValue();//获取B(邮编地址)列的值

                    $data['street_number'] = $objPHPExcel->getActiveSheet()->getCell("C".$j)->getValue();//获取C(街道编号)列的值

                   $data['street_name'] = $objPHPExcel->getActiveSheet()->getCell("D".$j)->getValue();//获取D(街道名)列的值

                    $data['building_type'] = $objPHPExcel->getActiveSheet()->getCell("E".$j)->getValue();//获取E(建筑类型)列的值

                    //null 为主键id，自增可用null表示自动添加

                    // $sql = "INSERT INTO fn_whizmo_postal VALUES(null,'$a','$b','$c','$d','$e',0)";

                    // echo "$sql";

                    // exit();

                    // $res = mysql_query($sql);

                   $res = $this->flowerhua->db_add('whizmo_postal',$data);

                   

                }

                if ($res) {

                    echo "<script>alert('Add success!');window.location.href='./index.php?s=admin&c=flowerhua&a=postal';</script>";

                }else{

                    echo "<script>alert('Add failure!')</script>";

                    exit();

                }

            }

        }













    }