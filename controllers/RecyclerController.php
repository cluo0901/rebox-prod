<?php
    require(SMS_DIR . 'APIClient2.php');
    require_once('tcpdf/tcpdf.php'); 
    class RecyclerController extends Common
    {
        public $fei;
        public $flowerhua;
        public $smsApi;

        public function __construct()
        {
            parent::__construct();
            $this->fei = $this->model('fei');
            $this->flowerhua = $this->model('flowerhua');
            $this->smsApi = new transmitsmsAPI($this->site['SMS_USERNAME'], $this->site['SMS_PASSWORD']);
            if (!get_cookie('recycler_id')) {
                $url = url('recycler_login/partner_login');
                header("Location:" . $url);die;
            }
        }

        public function login_outAction(){
            if (IS_AJAX) {
                set_cookie('recycler_id',null);
                die(json_encode(array('code' => 1)));
            }
        }

        // 发送短信验证码
        public function ajax_sendcodeAction()
        {
            if (IS_AJAX) {
                $code = randomkeys(5);
                $phone = $this->post('phone');
                $prefix = strlen($phone) > 10 ? '86' : '65';
                if (!empty($phone)) {
                    $result = $this->smsApi->sendSms('Rebox One-Time Passcode (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                    if ($result->error->code == 'SUCCESS') {
                        set_cookie('back_sms_code', $code, 60 * 5);
                        set_cookie('back_sms_phone', $phone, 60 * 5);
                        die(json_encode(array('code' => 1)));
                    } else {
                        die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                    }
                }
            }
        }

        public function ajax_backsendcode1Action()
        {
            if (IS_AJAX) {
                $id = get_cookie('recycler_id');
                $code = randomkeys(5);
                $phone = $this->post('phone');
//                $recycler = $this->fei->get_field('whizmo_recycler_lessphone', "less_phone = '$phone' and recycler_id = $id",'id');
//                if (!$recycler) {
//                    die(json_encode(array('code' => 2)));
//                }
                $prefix = strlen($phone) > 10 ? '86' : '65';
                if (!empty($phone)) {
                    $result = $this->smsApi->sendSms('Rebox One-Time Passcode (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                    // dd($result);die;
                    if ($result->error->code == 'SUCCESS') {
                        set_cookie('back_sms_code', $code, 60 * 5);
                        set_cookie('back_sms_phone', $phone, 60 * 5);
                        die(json_encode(array('code' => 1)));
                    } else {
                        die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                    }
                }
            }
        }
        //找回密码-发送短信验证码
        public function ajax_backsendcodeAction()
        {
            if (IS_AJAX) {
                $code = randomkeys(5);
                $phone = $this->post('phone');
                $recycler = $this->fei->get_field('whizmo_recycler', "phone = $phone", 'id');
                if (!$recycler) {
                    die(json_encode(array('code' => 2)));
                }
                $prefix = strlen($phone) > 10 ? '86' : '65';
                if (!empty($phone)) {
                    $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                    // dd($result);die;
                    if ($result->error->code == 'SUCCESS') {
                        set_cookie('back_sms_code', $code, 60 * 5);
                        set_cookie('back_sms_phone', $phone, 60 * 5);
                        die(json_encode(array('code' => 1)));
                    } else {
                        die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                    }
                }
            }
        }
        //找回密码-验证验证码
        //ajax判断验证码是否正确
        public function ajaxbackcodeAction()
        {
            $code = trim($this->post('code'));
            $phone = trim($this->post('phone'));
            if ($code != get_cookie('back_sms_code') || $phone != get_cookie('back_sms_phone')) {
                echo 0;
                exit;
            } else {
                set_cookie('back_sms_code', null);
                set_cookie('back_sms_phone', null);
                echo 1;
                exit;
            }
        }

        //会员修改密码
        public function ajax_edit_member_passwordAction()
        {
            if (IS_AJAX) {
                $phone = $this->post('phone');
                $password = $this->post('password');
                $data = [];
                $data['password'] = md5(md5($password) . md5($password));
                $return = $this->flowerhua->db_update('whizmo_recycler', $data, "phone = $phone");
                if ($return) {
                    die(json_encode(array('code' => 1)));
                }
            }
        }

        public function recycler_listingsAction()
        {
            if (IS_AJAX) {
                $parentid = (int)$this->post('parentid');
                $recovery_parent_id = $this->post('recovery_parent_id');
                if ($parentid) {
                    $arr = $this->fei->get_all_where('whizmo_category_type', "parentid=$parentid", 'id,name');
                }
                if ($recovery_parent_id) {
                    $arr = $this->fei->get_all_where('whizmo_recovery', "recovery_parent_id=$recovery_parent_id", 'id,recovery_title');
                }
                die(json_encode(array('arr' => $arr)));
            } else {
                $id = get_cookie('recycler_id');
                $data = $this->fei->get_one('whizmo_recycler', "id = $id", 'phone');
                $banner = $this->flowerhua->get_join_all('content_1 as a','content_1_banner as b',"a.id = b.id","a.catid = 182 and b.leixing = 2 order by a.listorder desc");
                $parentid = $this->fei->get_field('whizmo_category_type', "parentid=0 and type=11", 'id');
                $recycler_type = $this->fei->get_all_where('whizmo_category_type', "parentid=$parentid", 'id,name');
                $info = $this->fei->get_one('whizmo_recovery_clause','id=1','personal_information_title3,personal_information3');
                $info['personal_information3']=htmlspecialchars_decode($info['personal_information3']);
                $recycler_id = get_cookie('recycler_id');
                $less_phone = $this->fei->get_all_where('whizmo_recycler_lessphone',"recycler_id = $recycler_id",'less_phone');
                $arr[]['less_phone'] = $data['phone'];
                foreach ($less_phone as $key => $value) {
                    $arr[]['less_phone'] = $value['less_phone'];
                }
                $this->view->assign(array(
                    'recycler_type' => $recycler_type,
                    'banner' => $banner,
                    'info'=>$info,
                    'less_phone'=>$arr,
                ));
                $this->view->display('listings');
            }
        }

        public function ajax_recyclerAction()
        {
            //连表查询   a.主表 商品表 b.附表 订单表 c.附表 回收表 d.附表 竞价表
//            if (IS_AJAX){
                $where = "1=1";
                $recovery_parent_id = $this->post('recovery_parent_id');
                $recovery_type_id = $this->post('recovery_type_id');
                $clear_parameter = $this->post('clear_parameter');
                $search = $this->post('search');
                $is_pai = $this->post('is_pai') ? $this->post('is_pai') : 1;
                //还没有拍过的列表
                $pai_list = $this->flowerhua->get_all_where('whizmo_bidding',"recycler_id = ".get_cookie('recycler_id'),'shelve_id,status');
                $pai_arr[0] = 99999999;
                foreach ($pai_list as $key => $value) {
                    $pai_arr[] = $value['shelve_id'];
                }
                $pai_arr = implode(',',$pai_arr);
                if($is_pai==1){
                    $where .= " and a.status = 1 and a.id not in (".$pai_arr.")";
                //已经竞拍过的列表
                }else if($is_pai==2){
                    if(count($pai_list)>0){
                        $where .= " and a.id in (".$pai_arr.")";
                    }
                    //below is original 
                    // $where .= " and a.status = 1 and d.status = 1 and d.recycler_id = ".get_cookie('recycler_id'); 
                    $where .= " and ((a.status = 1 and d.status = 1) or (a.status = 3 and d.status = 6) or d.status = -6) and d.recycler_id = ".get_cookie('recycler_id');

                //不感兴趣的列表
                }else if($is_pai==3){
                    $where .= " and a.status = 1 and d.status = -2 and d.recycler_id = ".get_cookie('recycler_id')."";
                }
                if ($search) {
                    $where .= " and c.recovery_title like '%$search%'";
                }
                if($recovery_parent_id){
                    $where .= " and b.order_recovery_parentid = $recovery_parent_id";
                }
                if($recovery_type_id){
                    $where .= " and b.order_recovery_typeid = $recovery_type_id";
                }
                $page = (int)$this->safe('page') ? (int)$this->safe('page') : 0;
                $table = 'whizmo_shelve as a left join fn_whizmo_order as b on a.order_id = b.order_id left join fn_whizmo_recovery as c on b.order_recovery_id = c.id left join fn_whizmo_bidding as d on a.id = d.shelve_id and d.recycler_id = '.get_cookie('recycler_id');
                $total = $this->flowerhua->get_count($table, 'a.id', $where);

                $where .= " order by a.sort desc";
                $where .= " limit ".$page*$this->web_pagesize.','.$this->web_pagesize;
                // $list = $this->flowerhua->get_all_where($table,$where,'a.*,b.order_recovery_id,c.recovery_title,d.status as dstatus,d.bidding_price,d.recycler_id');
                $list = $this->flowerhua->get_all_where($table,$where,'a.*,a.shelf_time as ashelf_time, b.order_recovery_id,c.recovery_title,d.status as dstatus,d.bidding_price,d.recycler_id'); // pass shelf time
                
                foreach ($list as $key => $value) {
                    $order_recovery = $this->fei->get_one('whizmo_order',"order_id = $value[order_id]",'order_recovery_id,order_member_id');
                    if($order_recovery){
                        $info = $this->fei->get_one('whizmo_recovery',"id = $order_recovery[order_recovery_id]",'recovery_title,recovery_title2,recovery_high_price,recovery_img');
                        $list[$key]['recovery_title'] = $info['recovery_title'];
                        $list[$key]['recovery_title2'] = $info['recovery_title2'];
                        $list[$key]['recovery_high_price'] = $info['recovery_high_price'];
                        $list[$key]['recovery_img'] = $info['recovery_img'];
                        $list[$key]['bidding_price1'] = $list[$key]['bidding_price'] + 5;

                        // if($value['now_price']){
                        //     $list[$key]['order_final_price'] = $value['now_price'];
                        // }
                        //查询商品详细图片
                        $arr_img = $this->fei->get_all_where('whizmo_shelve_img',"shelve_id = $value[id]",'');
                        $content = '';
                        foreach ($arr_img as $k => $val) {
                            $content .= "<img src=".$val['img_url'].">";
                        }
                        $list[$key]['img'] = $content;
                        // 查询商品单选参数
                        $contents = '';
                        $arr_recovery_parameter = $this->fei->get_all_where('whizmo_order_paramemter',"order_id = $value[order_id] and recovery_parameter_type=1",'recovery_parameter_title,recovery_parameter_parent_title');

                        if($arr_recovery_parameter){
                            foreach ($arr_recovery_parameter as $m => $va) {

                                $contents .= $va['recovery_parameter_parent_title'].': '.$va['recovery_parameter_title'].'<br>';
                            }
                        }



                        // 查询商品多选参数
                        $content1 = '';
                        $arr1_recovery_parameter = $this->fei->get_all_where('whizmo_order_paramemter',"order_id = $value[order_id] and recovery_parameter_type=2",'recovery_parameter_title');
                        if($arr1_recovery_parameter){
                            $content1 = 'Functional Issues & Missing Accessories:<br>';
                            // $content1 = "<font style='font-weight:bold; text-decoration:underline;''>Functional Issues & Missing Accessories:</font><br>";
                            foreach ($arr1_recovery_parameter as $p => $v) {
                                $content1 .=$v['recovery_parameter_title'].'<br>';
                            }
                        } else {
                            $content1 = 'Functional Issues & Missing Accessories: 100% working condition with full set of original accessories.<br>';
                        }
                        // $list[$key]['details'] = $contents.$content1;
                        $list[$key]['details'] = str_replace('&','or',$contents).str_replace('&','or',$content1);
                        $list[$key]['multiples'] = str_replace('&','or',$content1);  // only need display 
                    }
                }
                if (ceil($total / $this->web_pagesize > $page)) {
                    if($clear_parameter==2){
                        die(json_encode(array('code' => 2, 'list' => $list)));
                    }else{
                        die(json_encode(array('code' => 1, 'list' => $list)));
                    }
                } else {
                    if($clear_parameter==2){
                        die(json_encode(array('code' => -2)));
                    }else{
                        die(json_encode(array('code' => -1)));
                    }
                }
//            }
        }


        public function company_informationAction()
        {   
            $id = get_cookie('recycler_id');
            $data = $this->fei->get_one('whizmo_recycler', "id = $id", '*');
            $banner = $this->flowerhua->get_join_all('content_1 as a','content_1_banner as b',"a.id = b.id","a.catid = 182 and b.leixing = 2 order by a.listorder desc");
            $this->view->assign(array('banner' => $banner,'data'=>$data));
            $this->view->display('company_information');
        }


        //grading page for mobile
        public function gradingAction()
        {   
            // $id = get_cookie('recycler_id');
            // $data = $this->fei->get_one('whizmo_recycler', "id = $id", '*');
            $banner = $this->flowerhua->get_join_all('content_1 as a','content_1_banner as b',"a.id = b.id","a.catid = 182 and b.leixing = 2 order by a.listorder desc");
            $this->view->assign(array('banner' => $banner,'data'=>$data));
            $this->view->display('grading');
        }

        public function change_informationAction()
        {
            $data['company_name'] = $this->post('company_name');
            $data['phone'] = $this->post('phone');
            $data['person'] = $this->post('person');
            $data['ued'] = $this->post('ued');
            $data['address'] = $this->post('address');
            $data['company_file'] = $this->post('file');
            $id = get_cookie('recycler_id');
            $this->fei->db_update('whizmo_recycler', $data, "id = $id");
            die(json_encode(array('code' => 1)));
        }


        public function company_settingAction()
        {
            $id = get_cookie('recycler_id');
            $data = $this->fei->get_one('whizmo_recycler', "id = $id", '*');
            $less_phone = $this->fei->get_all_where('whizmo_recycler_lessphone', "recycler_id = $id", '');
            $this->view->assign(array('less_phone' => $less_phone,'data'=>$data));
            $this->view->display('company_setting');
        }

        public function company_setting2Action()
        {   
            $id = get_cookie('recycler_id');
            $data = $this->fei->get_one('whizmo_recycler', "id = $id", '*');
            $this->view->assign(array('data' => $data));
            $this->view->display('company_setting2');
        }

        public function change_passwordAction()
        {
            $id = get_cookie('recycler_id');
            $password = $this->fei->get_field('whizmo_recycler', "id = $id", 'password');
            $currentpwd = $this->post('currentpwd');
            $currentpwd = md5(md5($currentpwd) . md5($currentpwd));
            if ($currentpwd != $password) {
                die(json_encode(array('code' => 2, 'info' => 'Original password error')));
            } else {
                $newpwd = $this->post('newpwd');
                $newpwd = md5(md5($newpwd) . md5($newpwd));
                $res = $this->fei->db_update('whizmo_recycler', array('password' => $newpwd), "id = $id");
                if ($res) {
                    set_cookie('recycler_id',null);
                    set_cookie('phone', null);
                    die(json_encode(array('code' => 1, 'info' => 'If the modification is successful, please log on to the system again!')));
                }
            }
        }

        public function ajax_addlessphoneAction()
        {
            $data['recycler_id'] = get_cookie('recycler_id');
            $data['less_phone'] = $this->post('phone');
            $data['linkman'] = $this->post('linkman');
            $result = $this->fei->get_one('whizmo_recycler_lessphone',"less_phone = ".$data['less_phone'],'');
            if($result){
                die(json_encode(array('code'=>2)));
            }else{
                $res = $this->fei->db_add('whizmo_recycler_lessphone', $data);
                if ($res) {
                    die(json_encode(array('code' => 1)));
                }
            }
           
        }

        public function ajax_dellessphoneAction()
        {
            $less_phone = $this->post('phone');
            $res = $this->fei->db_del('whizmo_recycler_lessphone', "less_phone = $less_phone");
            if ($res) {
                die(json_encode(array('code' => 1)));
            }
        }

        public function ajax_successful_bidsAction()
        {   
            if(IS_AJAX){ 
                $id = get_cookie('recycler_id');
                if($this->post('status')){
                    $status = $this->post('status');
                    if($status==3){
                        $where = "recycler_id = $id and status in(3)";
                    }
                    else if($status==2){
                        $where = "recycler_id = $id and status in(2)";
                    }else{
                        $where = "recycler_id = $id and status=$status";
                    }
                }else {
                     $where = "recycler_id = $id and status=6";
                }
                $page = (int)$this->safe('page') ? (int)$this->safe('page') : 0;
                $list = $this->fei->get_pagelist('whizmo_bidding', $where, $page*$this->web_pagesize.",$this->web_pagesize",'');
                foreach ($list as $key => $value) {
                    if($value['bidding_time']){
                        $list[$key]['bidding_time'] = date('d.m.Y',$value['bidding_time']);
                        $list[$key]['bided_time'] = date('d.m.Y',$value['bided_time']);
                    }
                    $order_info = $this->fei->get_one('whizmo_order',"order_id = $value[order_id]",'order_recovery_id,order_sn_number');
                    $list[$key]['order_sn_number'] = $order_info['order_sn_number'];
                    $recovery_info = $this->fei->get_one('whizmo_recovery',"id = $order_info[order_recovery_id]",'recovery_title,recovery_title2,recovery_img');
                    $list[$key]['title'] = $recovery_info['recovery_title'];
                    $list[$key]['title2'] = $recovery_info['recovery_title2'];
                    $list[$key]['recovery_img'] = $recovery_info['recovery_img'];
                    $list[$key]['number'] = $this->fei->get_field('whizmo_shelve',"id = $value[shelve_id]",'number');
                    // $list[$key]['recycler_phone'] = $this->fei->get_field('whizmo_recycler',"id = $id",'phone');
                    $list[$key]['recycler_phone'] = $this->fei->get_field('whizmo_bidding',"shelve_id = $value[shelve_id]",'bidder_phone'); // get right bidder phone number


                    $list[$key]['description'] = $this->fei->get_field('whizmo_shelve',"id = $value[shelve_id]",'description');

                    $list[$key]['grade'] = $this->fei->get_field('whizmo_shelve',"id = $value[shelve_id]",'grade'); // get grade info

                   // 查询商品单选参数
                    $contents = '';
                    $arr_recovery_parameter = $this->fei->get_all_where('whizmo_order_paramemter',"order_id = $value[order_id] and recovery_parameter_type=1",'recovery_parameter_title,recovery_parameter_parent_title');

                    if($arr_recovery_parameter){
                        foreach ($arr_recovery_parameter as $m => $va) {
                            $contents .= $va['recovery_parameter_parent_title'].': '.$va['recovery_parameter_title'].'<br>';
                       }
                    }
                    // 查询商品多选参数
                    $content1 = '';
                    $arr1_recovery_parameter = $this->fei->get_all_where('whizmo_order_paramemter',"order_id = $value[order_id] and recovery_parameter_type=2",'recovery_parameter_title');
                    if($arr1_recovery_parameter){
                        $content1 = '<font style="font-weight:bold; text-decoration:underline;">Functional Issues & Missing Accessories:</font><br>';
                        foreach ($arr1_recovery_parameter as $p => $v) {
                           $content1 .=$v['recovery_parameter_title'].'<br>';
                        }
                    } else {
                        $content1 = '<font style="font-weight:bold;">Functional Issues & Missing Accessories: 100% working condition with full set of original accessories.</font><br>';
                    }
                    

                    $list[$key]['details'] = str_replace('&','or',$contents).str_replace('&','or',$content1);
                    $list[$key]['multiples'] = str_replace('&','or',$content1);  // only need display 
                     //查询商品详细图片
                    $arr_img = $this->fei->get_all_where('whizmo_shelve_img',"shelve_id = $value[shelve_id]",'');
                    $content2 = '';
                    foreach ($arr_img as $k => $val) {
                       $content2 .= "<div><img src=".$val['img_url']."></div>";
                    }
                    $list[$key]['img'] = ($content2);
                }
                $total = $this->flowerhua->get_count('whizmo_bidding', 'id', $where);
               
                if (ceil($total / $this->web_pagesize > $page)) {
                    die(json_encode(array('code' => 1, 'list' => $list)));
                } else {
                    die(json_encode(array('code' => -1)));
                }
               
            }
           
        }
        public function successful_bidsAction()
        {   
            $banner = $this->flowerhua->get_join_all('content_1 as a','content_1_banner as b',"a.id = b.id","a.catid = 182 and b.leixing = 2 order by a.listorder desc");
            $this->view->assign(array(
                'banner' => $banner,
                ));
            $this->view->display('successful_bids');
        }

       
        public function ajax_tijiaochujiaAction(){
            $data=[];
            $data['order_id'] = $this->post('order_id');
            $data['bidding_price'] = $this->post('bidding_price');
            $data['recycler_id'] = get_cookie('recycler_id');
            $data['bidding_time'] = time();
            $data['status'] = 1;
            $data['shelve_id'] = $this->post('shelve_id');
            $data['bidder_phone'] = $this->post('phone'); //get bidder phone number
            $id = $this->fei->get_field('whizmo_bidding',"recycler_id = $data[recycler_id] and shelve_id = $data[shelve_id]",'id');
            if($id){
                 $res = $this->fei->db_update('whizmo_bidding',array('status'=>1,'bidding_time'=>$data['bidding_time'],'bidding_price'=>$data['bidding_price'], 'bidder_phone'=>$data['bidder_phone']),"id = $id");
            }else{
                 $res = $this->fei->db_add('whizmo_bidding',$data);
            }
            if($res){
                $now_price = $this->fei->get_field('whizmo_shelve',"id = $data[shelve_id]",'now_price');
                if($data['bidding_price']>$now_price){
                     $this->fei->db_update('whizmo_shelve',array('now_price'=>$data['bidding_price'],'last_auction_time'=>time(),'recycler_id'=>$data['recycler_id'], 'bidder_phone'=>$data['bidder_phone']),"id = ". $data['shelve_id']);
                }
                die(json_encode(array('code'=>2)));
            }
        }
        public function ajax_nointerestedAction(){
            $data=[];
            $data['order_id'] = $this->post('order_id');
            $data['shelve_id'] = $this->post('shelve_id');
            $data['recycler_id'] = get_cookie('recycler_id');
            $data['status'] = -2;
            $id = $this->fei->get_field('whizmo_bidding',"shelve_id = $data[shelve_id] and recycler_id = $data[recycler_id]",'id');
            if($id){
                $res = $this->fei->db_update('whizmo_bidding',array('status'=>-2,'bidding_time'=>'','bidding_price'=>''),"id = $id");
            }else{
                $res = $this->fei->db_add('whizmo_bidding',$data);
            }
            if($res){
                die(json_encode(array('code'=>2)));
            }
        }
        public function notify_paymentAction(){
            $id = $this->post('id');
            $shelve_id = $this->fei->get_field('whizmo_bidding',"id = $id",'shelve_id');
            $res = $this->fei->db_update('whizmo_bidding',array('status'=>2),"id = $id and bided_time > 0");
            $this->fei->db_update('whizmo_shelve',['status'=>4],"id = $shelve_id");
            if($res){
                die(json_encode(array('code'=>1, 'shelve_id'=>$shelve_id)));
            }
        }
// pdf 文件下载
       public function pdfAction(){ 
           //实例化 
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false); 
             
            // 设置文档信息 
            $pdf->SetCreator('Helloweba'); 
            $pdf->SetAuthor('yueguangguang'); 
            $pdf->SetTitle('Welcome to helloweba.com!'); 
            $pdf->SetSubject('TCPDF Tutorial'); 
            $pdf->SetKeywords('TCPDF, PDF, PHP'); 
             
            // 设置页眉和页脚信息
            $pdf->SetHeaderData('logo.png', 20, 'www.rebox.com.sg', 'Thank you and hope to serve you again',
                  array(0,64,255), array(0,64,128));
            $pdf->setFooterData(array(0,64,0), array(0,64,128)); 
             
            // 设置页眉和页脚字体 
            $pdf->setHeaderFont(Array('stsongstdlight', '', '10')); 
            $pdf->setFooterFont(Array('helvetica', '', '8')); 
             
            // 设置默认等宽字体 
            $pdf->SetDefaultMonospacedFont('courier'); 
             
            // 设置间距 
            $pdf->SetMargins(15, 27, 15); 
            $pdf->SetHeaderMargin(5); 
            $pdf->SetFooterMargin(10); 
             
            // 设置分页 
            $pdf->SetAutoPageBreak(TRUE, 25); 
             
            // set image scale factor 
            $pdf->setImageScale(1);
             
            // set default font subsetting mode 
            $pdf->setFontSubsetting(true); 
             
            //设置字体 
            $pdf->SetFont('stsongstdlight', '', 14);
             
            $pdf->AddPage();
            $img = $this->get('img');
            $images = $this->get('images');
            $details = $this->get('details');
            $bidding_price = $this->get('bidding_price');
            $bided_time = $this->get('bided_time');
            $strContent = "<div>
                    <h1><u>RECEIPT</u></h1>
                    <h3>WHIZMO TECH PTE LTD</h3>
                    <h4>3 Ang Mo Kio Street 62, #06-16Link@AMK Singapore 569139</h4>
                    <h5>".$img."</h5>
                    <h5>".$bidding_price."</h5>
                    <h5>".$bided_time."</h5>
                    <span>".$details."</span>
                    <a class='images2'>".$images."</a>
              </div>";
//           dd($strContent);
//           die;

//            $pdf->Write(0,htmlspecialchars_decode($strContent),'', 0, 'L', true, 0, false, false, 0);

//           $this->view->assign('a',htmlspecialchars_decode($strContent));
//           $this->view->display('a');die;
           $pdf->writeHTML(htmlspecialchars_decode($strContent));
            //输出PDF
            $pdf->Output('Receipt.pdf', 'D');

    }
}