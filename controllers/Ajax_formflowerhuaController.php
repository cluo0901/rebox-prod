<?php
    require(SMS_DIR.'APIClient2.php');
    require('utilities.php');

class Ajax_formflowerhuaController extends Common
{
    public $flowerhua;
    public $smsApi;
    public function __construct()
    {
        parent::__construct();
        $this->flowerhua = $this->model('flowerhua');
        //实例化短信发送SFO类
        $this->smsApi = new transmitsmsAPI($this->site['SMS_USERNAME'],$this->site['SMS_PASSWORD']);
    }

    public function ajax_send_smscodeAction(){
//        die(json_encode(array('code'=>1)));
        if(IS_AJAX){
            $code = randomkeys(5);
            $phone = $this->post('phone');
            $prefix = strlen($phone) > 10 ? '86' : '65';
            // sending to a set of numbers
            if(!empty($phone)){
                $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!',$prefix.$phone);
                if($result->error->code=='SUCCESS') {
                    set_cookie('sms_code',$phone,60*15);
                    die(json_encode(array('code'=>1)));
                } else {
                    die(json_encode(array('code'=>-1,'sms_err'=>$result->error->description)));
                }
            }
        }
    }

    public function ajax_indexAction(){
        if (IS_AJAX) {
            $page = $this->safe('page') ? $this->safe('page') : 0;
            $pageSize = $this->safe('index') ? 8 : 200;
            $where = "1=1";
            $recovery_type_id = $this->post('recovery_type_id');
            $recovery_parent_id = $this->post('recovery_parent_id');
            if($recovery_type_id){
                $where .= " and recovery_type_id = $recovery_type_id";
            }
            if($recovery_parent_id=='hits'){
                $where .= " and recovery_hits = 1";
            }else if($recovery_parent_id){
                $where .= " and recovery_parent_id = $recovery_parent_id";
            }
            $where .= " order by listorder desc,id desc";
            $total = $this->flowerhua->get_count('whizmo_recovery','id',$where);
            $result = $this->flowerhua->get_pagelist('whizmo_recovery',$where,($page*$pageSize).",$pageSize");
            if (ceil($total / $pageSize > $page)) {
                die(json_encode(array('code' => 1,'result'=>$result)));
            } else {
                echo json_encode(array('code' => -1));
            }
        }
    }

    public function ajax_get_typelistAction(){
        if (IS_AJAX) {
            $id = $this->post('id');
            $where = " and 1=1";
            $where .= " order by listorder desc,id desc";
            $result = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = $id $where limit 11");
            die(json_encode(array('code' => 1 ,'result'=>$result)));
        }
    }

    public function ajax_get_typelist2Action(){
        if (IS_AJAX) {
            $id = $this->post('id');
            $where = " and 1=1";
            $where .= " order by listorder desc,id desc";
            $result = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = $id $where");
            die(json_encode(array('code' => 1 ,'result'=>$result)));
        }
    }

    public function ajax_set_thumbAction()
    {
        $img = $_FILES['addlogo'];
        $up = $this->upload($img, array('jpeg', 'jpg', 'gif', 'png', 'bmp'),$this->site['FILE_SIZE']);//上传原图图片
//        echo json_encode($up);die;
        $path_thumb = "uploadfiles/thumb/" . md5($up['path']) . ".png";
        img2thumb($up['path'], $path_thumb);//生成缩略图
        if($up['result']===false){
            $arr = array(
                'result' => $up['result'],
                'access_url' => $path_thumb,
                'size' => $img['size']
            );
        }
        echo json_encode($arr);
    }
    //验证优惠券是否存在
    public function ajax_apply_couponAction(){
        if (IS_AJAX) {
            $apply_coupon_code = $this->post('apply_coupon_code');
            if ($apply_coupon_code) {
                $data = $this->flowerhua->get_one('whizmo_recovery_coupon',"coupon_sn = '$apply_coupon_code'");
                if(!$data){
                    die(json_encode(array('code' => -1)));
                }else if($data['coupon_status']==2){
                    $member_name = $this->flowerhua->get_field('member',"id = $data[coupon_member_id]","phone");
                    die(json_encode(array('code' => -2,'member_name'=>$member_name)));
                }else{
                    die(json_encode(array('code' => 1,'result'=>$data)));
                }
            }
        }
    }
    //验证是否登录
    public function ajax_is_login_systemAction(){
        if (IS_AJAX) {
            $member_id = get_cookie('member_id');
            if (!$member_id) {
                die(json_encode(array('code' => -1)));
            }
            die(json_encode(array('code' => 1)));
        }
    }

    // get random model name and highest price

    public function ajax_random_modelAction(){
        if (IS_AJAX) {
            $model_id = $this->post('model_id');
            $model_name = $this->flowerhua->get_field('whizmo_recovery',"id = $model_id",'recovery_title');
            $model_price = $this->flowerhua->get_field('whizmo_recovery',"id = $model_id",'recovery_high_price');
            if($model_name){
                die(json_encode(array('code' => 1,'model_name'=>$model_name, 'model_price'=>$model_price)));
            }
        }
    }




    //提交回收参数到订单
    public function ajax_instantly_submit2orderAction(){
        if (IS_AJAX) {
            $this->flowerhua->begin();
            $member_id = get_cookie('member_id');
            //Hide below by LC
            // if (!$member_id) {
            //     die(json_encode(array('code' => -1)));
            // }
            $recovery_id = $this->post('recovery_id');

            $instant_quote_price = $this->post('instant_quote_price');
            $priceValue = $this->post('priceValue');
            $chpriceValue = $this->post('chpriceValue');
            $chpriceValue2 = $this->post('chpriceValue2');
            $apply_coupon_code_sn = $this->post('apply_coupon_code_sn');
            $array = [];
            $array['order_sn_number'] = 'WH'.date('Ymd',time()).rand(0,9).rand(0,9).rand(0,9).rand(0,9).randomkeys(5);
            $array['order_member_id'] = $member_id;
            //pass agent check into order
            if (get_cookie('member_id')) {
                $array['agent'] = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
                //if agent order_status is 2
                if ($array['agent'] == 1) {
                    $array['order_status'] = 2;
                } else {
                    $array['order_status'] = 1;
                }
            }
            
            $array['order_totalprice'] = round($instant_quote_price,2);
            $array['order_recovery_id'] = $recovery_id;
            $array['order_addtime'] = time();
            if($recovery_id){
                $recovery = $this->flowerhua->get_one('whizmo_recovery',"id = $recovery_id",'recovery_type_id,recovery_parent_id');
                $array['order_recovery_parentid'] = $recovery['recovery_type_id'];
                $array['order_recovery_typeid'] = $recovery['recovery_parent_id'];
            }
            if(!empty($apply_coupon_code_sn)){
                $coupon = $this->flowerhua->get_one('whizmo_recovery_coupon',"coupon_sn = '$apply_coupon_code_sn'",'id,coupon_money');
                if($coupon){
                    $array['order_coupon_code_id'] = $coupon['id'];                    
                    $array['order_coupon_code_money'] = $coupon['coupon_money'];
                    $array['order_coupon_code_sn'] = $apply_coupon_code_sn; // add coupon code number to database
                    $array['order_totalprice'] += $coupon['coupon_money'];
                }
            }
            $order_id = $this->flowerhua->db_add('whizmo_order',$array);
            if(!$order_id){
                $this->flowerhua->rollback();
                die(json_encode(array('code' => -404)));
            }
            if(!empty($priceValue)){
                $priceValue_arr = explode(',',ltrim($priceValue,','));
                $array = [];
                foreach ($priceValue_arr as $key => $value) {
                    $recovery_title = $this->flowerhua->get_one('whizmo_recovery_parameter',"id = $value",'');
                    $recovery_parent = $this->flowerhua->get_one('whizmo_recovery_parameter',"id = $recovery_title[parentid]",'parameter_name');
                    $array['recovery_parameter_parent_title'] = $recovery_parent['parameter_name'];
                    $array['recovery_parameter_title'] = $recovery_title['parameter_name'];
                    $array['order_id'] = $order_id;
                    $array['recovery_parameter_id'] = $value;
                    $array['recovery_parameter_type'] = 1;
                    $order_parameter_id = $this->flowerhua->db_add('whizmo_order_paramemter',$array);
                }
            }
            if(!empty($chpriceValue)){
                $chpriceValue_arr = explode(',',$chpriceValue);
                $array = [];
                foreach ($chpriceValue_arr as $key => $value) {
                    $recovery_title = $this->flowerhua->get_one('whizmo_recovery_parameter',"id = $value",'parameter_name');
                    $array['recovery_parameter_parent_title'] = '功能问题';
                    $array['recovery_parameter_title'] = $recovery_title['parameter_name'];
                    $array['order_id'] = $order_id;
                    $array['recovery_parameter_id'] = $value;
                    $array['recovery_parameter_type'] = 2;
                    $order_chparameter_id = $this->flowerhua->db_add('whizmo_order_paramemter',$array);
                }
            }
            if(!empty($chpriceValue2)){
                $chpriceValue_arr2 = explode(',',$chpriceValue2);
                $array = [];
                foreach ($chpriceValue_arr2 as $key => $value) {
                    $recovery_title = $this->flowerhua->get_one('whizmo_recovery_parameter',"id = $value",'parameter_name');
                    $array['recovery_parameter_parent_title'] = '配件问题';
                    $array['recovery_parameter_title'] = $recovery_title['parameter_name'];
                    $array['order_id'] = $order_id;
                    $array['recovery_parameter_id'] = $value;
                    $array['recovery_parameter_type'] = 2;
                    $order_chparameter_id = $this->flowerhua->db_add('whizmo_order_paramemter',$array);
                }
            }
            $this->flowerhua->commit();
            die(json_encode(array('code' => 1,'order_id'=>$order_id,'recovery_id'=>$recovery_id)));
        }
    }
    //取件日期筛选
//    public function ajax_select_picktimeAction(){
//        if (IS_AJAX) {
//            $time = $this->post('time');
//            $resultPickup = '';
//            $resultPickup .='<option value=""> Select Pickup Time</option>';
//            $resultPickup .='<option value="26" data-value="Noon Collection (12:00 - 16:00)" >Noon Collection (12:00 - 16:00)</option>';
//            if((int)(date('w',strtotime($time)))!=6){
//                $resultPickup .='<option value="27" data-value="Evening Collection (16:00 - 20:00)" >Evening Collection (16:00 - 20:00)</option>';
//            }
//            die(json_encode(array('code' => 1,'str'=>$resultPickup)));
//        }
//    }
    public function ajax_select_picktimeAction(){

        /*********************************************
         * 
         *    Pikcupp integration
         * 
         * 
         ********************************************/

        if (IS_AJAX) {

            $recovery_type_id = $this->post('recovery_type_id');
            $time = $this->post('time');
            $resultPickup .='<option value=""> Select Pickup Time</option>';
            $hour = date('H');
            $minutes = date('i');
            $day = date('w',strtotime($time));

            switch ($recovery_type_id) {

                case 68: 

                    if(strtotime(date('m/d/Y')) == strtotime($time)){

                        // $resultPickup .='<option value="26">12:00PM - 15:00PM</option>';
                        $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                        // $resultPickup .='<option value="27">16:00PM - 20:00PM</option>';

                    } else {

                        if ($hour >= 18 && $hour <= 24) {

                            if(strtotime(date('m/d/Y'). "+ 1 day") == strtotime($time)){
                                

                                $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                // $resultPickup .='<option value="27">16:00PM - 20:00PM</option>';
                            
                            } else {

                                if($day == 6 || $day == 0){
                                    // $resultPickup .='<option value="29">09:00AM - 12:00PM</option>';
                                    $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                    $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                } else {
                                    $resultPickup .='<option value="29">11:00AM - 14:00PM</option>';
                                    $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                    $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                }

                                
                                // $resultPickup .='<option value="27">16:00PM - 20:00PM</option>';
                           
                            }
                            
                        } else {

                            if($day == 6 || $day == 0){
                                // $resultPickup .='<option value="29">09:00AM - 12:00PM</option>';
                                $resultPickup .='<option value="26">11:00AM - 14:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                            } else {
                                $resultPickup .='<option value="29">11:00AM - 14:00PM</option>';
                                $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';

                            }
                        }

                    }

                    break;
                
                default:

                    if(strtotime(date('m/d/Y')) == strtotime($time)){

                        // $resultPickup .='<option value="26">12:00PM - 15:00PM</option>';
                        $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                        // $resultPickup .='<option value="27">16:00PM - 20:00PM</option>';

                    } else {

                        if ($hour >= 18 && $hour <= 24) {
        
                            if(strtotime(date('m/d/Y'). "+ 1 day") == strtotime($time)){

                                $resultPickup .='<option value="26">11:00AM - 14:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                $resultPickup .='<option value="27">16:00PM - 20:00PM</option>';
                            
                            } else {

                                 if($day == 6 || $day == 0){
                                    // $resultPickup .='<option value="29">09:00AM - 12:00PM</option>';
                                    $resultPickup .='<option value="26">11:00AM - 14:00PM</option>';
                                    $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                } else {
                                    $resultPickup .='<option value="29">11:00AM - 14:00PM</option>';
                                    $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                    $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                                }
                           
                            }
                            
                        } else {

                            if($day == 6 || $day == 0){
                                $resultPickup .='<option value="29">09:00AM - 12:00PM</option>';
                                $resultPickup .='<option value="26">11:00AM - 14:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                            } else {
                                $resultPickup .='<option value="29">11:00AM - 14:00PM</option>';
                                $resultPickup .='<option value="26">13:00PM - 16:00PM</option>';
                                $resultPickup .='<option value="28">15:00PM - 18:00PM</option>';
                            }

                        }

                        

                    }

                    break;
            }

            die(json_encode(array('code' => 1,'str'=>$resultPickup)));

            /*********************************************
             * 
             *  End of Pikcupp integration
             * 
             * 
             ********************************************/



            // $recovery_type_id = $this->post('recovery_type_id');
            // $time = $this->post('time');
            // $resultPickup = '';
            // $resultPickup .='<option value=""> Select Pickup Time</option>';
            // if ($recovery_type_id == 68) {
            //     //same day
            //     if((int)(date('w',strtotime($time))) == (int)(date('w',strtotime(date('d-m-Y'))))) {
            //         if((int)(date('w',strtotime($time)))==6){
            //             $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //             $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //         }else{
            //             if(date('H')>=9) {
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';      
            //             }else{
            //                 $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //                 $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';
            //             }
            //         }
            //     // next day
            //     }else {
            //         $resultPickup .='<option value="29" data-value="Morning Collection (09:00AM - 13:00PM)" >Morning Collection (09:00AM - 13:00PM)</option>';                    
            //     }               



            // } else { // not new phone    
            //     //same day
            //     if((int)(date('w',strtotime($time))) == (int)(date('w',strtotime(date('d-m-Y'))))) {
            //         if((int)(date('w',strtotime($time)))==6){
            //             $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //             $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //         }else{
            //             if(date('H')>=9) {
            //                 $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';      
            //             }else{
            //                 $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //                 $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';
            //             }
            //         }

            //     // next day
            //     }else if((int)(date('w',strtotime($time))) == fmod((int)(date('w',strtotime(date('d-m-Y'))))+1,7)){
            //         if((int)(date('w',strtotime($time)))==6){
            //             // $resultPickup .='<option value="29" data-value="Morning Collection (09:30AM - 10:30AM)" >Morning Collection (09:30AM - 10:30AM)</option>'; //no qoo100 pickup slot on sat
            //             $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //             $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //         }else{

            //             if(date('H')<=16){                        
     
            //                 //temporarily remove morning pickup  
            //                 $resultPickup .='<option value="29" data-value="Morning Collection (09:00AM - 13:00PM)" >Morning Collection (09:00AM - 13:00PM)</option>'; 
                            
            //                 $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //                 $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Noon Collection (15:00PM - 18:00PM)</option>';
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';
            //             }else{
            //                 $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //                 $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //                 $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';

            //             } 

            //         } 

            //     // the day after
            //     }else{
            //         if((int)(date('w',strtotime($time)))==6){
            //             $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //             $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //         }else{

            //             $resultPickup .='<option value="29" data-value="Morning Collection (09:00AM - 12:00PM)" >Morning Collection (09:00AM - 12:00PM)</option>';  
            //             $resultPickup .='<option value="26" data-value="Noon Collection (12:00PM - 15:00PM)" >Noon Collection (12:00PM - 15:00PM)</option>';
            //             $resultPickup .='<option value="28" data-value="Afternoon Collection (15:00PM - 18:00PM)" >Afternoon Collection (15:00PM - 18:00PM)</option>';
            //             $resultPickup .='<option value="27" data-value="Evening Collection (16:00PM - 20:00PM)" >Evening Collection (16:00PM - 20:00PM)</option>';
            //         }          
            //     }
            // }

            // die(json_encode(array('code' => 1,'str'=>$resultPickup)));
        }
    }
    //提交补充物流订单信息
    public function ajax_submit2orderinfoAction(){
        if (IS_AJAX) {
            $this->flowerhua->begin();
            // if (!get_cookie('member_id')) {
            //     die(json_encode(array('code' => -1)));
            // }else{
                $order_id = $this->post('order_id');
                $member = $this->flowerhua->get_one('member',"id = ".get_cookie('member_id'),'phone');
                $member_id = get_cookie('member_id');
                $member_name = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'phone');
                $order = $this->flowerhua->get_one('whizmo_order',"order_id = $order_id",'order_coupon_code_id');
                                
                $order_sn = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_sn_number'); // get order_sn

                if($order['order_coupon_code_id']){
                    $coupon = [];
                    // check if the coupon is evergreen coupon, no need to change status and record user
                    $coupon_title = $this->flowerhua->get_field('whizmo_recovery_coupon', "id = $order[order_coupon_code_id]", 'coupon_title');
                    if($coupon_title!=='EVERGREEN'){

                        $coupon['coupon_status'] = 2;
                        $coupon['coupon_member_id'] = $member_id;
                        $coupon['coupon_member_phone'] = $member_name; 

                    }

                    $coupon['coupon_apply_time'] = time();
                    $res = $this->flowerhua->db_update('whizmo_recovery_coupon',$coupon,"id = $order[order_coupon_code_id]");
                    if(!$res){
                        $this->flowerhua->rollback();
                        die(json_encode(array('code' => -1,'msg'=>'Promo code is invalid. Please try again!')));
                    }
                }



                //send new phone receipt remincder via sms                                        
                $prefix = '65'; 
                $order_recovery_parentid = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_recovery_parentid');
                if ($order_recovery_parentid == 68) {   
                    if ($this->post('fang')==1 || $this->post('fang')==2) {                  
                        if (!empty($member['phone'])) {
                            $result = $this->smsApi->sendSms('REBOX reminder: please put in the original receipt indicating purchase date and device IMEI number into the parcel. If you have any question, please WhatsApp +65 9895 8996.', $prefix . $member['phone']);                            
                        }
                    }
                }


                $data = [];
                $roadbul = [];


                //如果寄送方式是快递上门就开始下单处理
                if($this->post('fang')==1){
                    $roadbul['FromName'] = $this->post('payment_name');
                    $roadbul['FromZipCode'] = $this->post('postal_code');
                    $roadbul['FromAddress'] = $this->post('postal_address').' '.$this->post('postal_unit');
                    $roadbul['FromMobilePhone'] = $member['phone'];
                    $roadbul['ToName'] = $this->site['DELIVERY_NAME'];
                    $roadbul['ToZipCode'] = $this->site['DELIVERY_CODE'];
                    $roadbul['ToAddress'] = $this->site['DELIVERY_ADDRESS'];
                    $roadbul['ToMobilePhone'] = $this->site['DELIVERY_PHONE'];
                    $roadbul['ProductTypeId'] = 2;//默认项
                    $roadbul['SizeId'] = 1;//默认项
                    $roadbul['ServiceId'] = 2;//默认项
                    $roadbul['PickupTimeSlotId']   = $this->post('resultPickup');
                    $roadbul['PickupDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date')));
                    $roadbul['DeliveryTimeSlotId'] = 1;//默认项


                    //during Jurong Point (JP) launch period => 1. send to JP 2. deliver in the afternoon starts
                    // $first_pickup_date = "29/03/2019";
                    // $last_pickup_date = "04/04/2019";

                    // if (strtotime($roadbul['PickupDate']) >= strtotime($first_pickup_date) && strtotime($roadbul['PickupDate']) <= strtotime($last_pickup_date)) {
                    //     $roadbul['ToName'] = "REBOX (Annie)";
                    //     $roadbul['ToZipCode'] = "648886";
                    //     $roadbul['ToAddress'] = "Jurong Point Shopping Centre, 1 Jurong West Central 2, Basement 1 (outside Starhub #B1-12)";
                    //     $roadbul['ToMobilePhone'] = "82985526";
                    //     $roadbul['DeliveryTimeSlotId'] = 2;
                    // }
                    //during Jurong Point (JP) launch period => 1. send to JP 2. deliver in the afternoon starts


                    //check if it is the day before public holiday
                    $pheves = array("19/04/2019","01/05/2019","20/05/2019","09/08/2019", "12/08/2019", "28/10/2019", "25/12/2018");


                    if (in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1), $pheves) || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*1))==6 || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*1))==0) {

                        if (in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*2), $pheves) || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*2))==6 || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*2))==0) {

                            if (in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*3), $pheves) || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*3))==6 || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*3))==0) {

                                if (in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*4), $pheves) || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*4))==6 || (int)(date('w',strtotime($this->post('resultPickup_date'))+86400*4))==0) {
                                    $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*5);
                                } else {
                                    $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*4);
                                }
                            } else {
                                $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*3);
                            }
                        } else {
                            $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*2);
                        }

                    } else {
                        $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1);
                    }

                    // if pickup on friday, deliver on monday
                    // if((int)(date('w',strtotime($this->post('resultPickup_date'))))==5) {
                    //     if (in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*3), $pheves)) {
                    //         //if next monday holiday then next tuesday
                    //         $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*4);
                    //     } else {
                    //         $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*3);
                    //     }
                    // } elseif((int)(date('w',strtotime($this->post('resultPickup_date'))))==6 || in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1), $pheves)){
                    //     $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*2);
                    // } else {
                    //     $roadbul['DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1);
                    // }

                    
                    
                    //请求接口下单    // if check 29 is qoo10 pickup slot
                    if ($this->post('resultPickup') != 29) {
                        $obj = _post($this->roadbul_url.'/orders/book',json_encode($roadbul),$this->header);
                        $roadbul_array = json_decode($obj, true);
                    }

                    if($roadbul_array['Code']==0){
                        $data['order_status'] = 2;//已填写物流信息
                        $data['order_wuliu_number'] = $roadbul_array['OrderNumber'];
                        $data['order_delivery_number'] = $roadbul_array['OrderNumber'];

                        //保存物流信息在数据库中
                        $roadbul_mysql = [];
                        $roadbul_mysql['roadbul_whizmo_type'] = 100;
                        $roadbul_mysql['order_id'] = $order_id;
                        $roadbul_mysql['roadbul_OrderNumber'] = $roadbul_array['OrderNumber'];
                        $roadbul_mysql['roadbul_FromName'] = $this->post('payment_name');
                        $roadbul_mysql['roadbul_FromZipCode'] = $this->post('postal_code');
                        $roadbul_mysql['roadbul_FromAddress'] = $this->post('postal_address').' '.$this->post('postal_unit');
                        $roadbul_mysql['roadbul_FromMobilePhone'] = $member['phone'];
                        $roadbul_mysql['roadbul_ToName'] = $this->site['DELIVERY_NAME'];
                        $roadbul_mysql['roadbul_ToZipCode'] = $this->site['DELIVERY_CODE'];
                        $roadbul_mysql['roadbul_ToAddress'] = $this->site['DELIVERY_ADDRESS'];
                        $roadbul_mysql['roadbul_ToMobilePhone'] = $this->site['DELIVERY_PHONE'];
                        $roadbul_mysql['roadbul_ProductTypeId'] = 2;//默认项
                        $roadbul_mysql['roadbul_SizeId'] = 1;//默认项
                        $roadbul_mysql['roadbul_ServiceId'] = 1;//默认项
                        $roadbul_mysql['roadbul_PickupTimeSlotId']   = $this->post('resultPickup');
                        $roadbul_mysql['roadbul_PickupDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date')));
                        $roadbul_mysql['roadbul_DeliveryTimeSlotId'] = 5;//默认项


                        //during Jurong Point (JP) launch period => 1. send to JP 2. deliver in the afternoon starts

                        $roadbul_mysql['roadbul_ToName'] = $roadbul['ToName'];
                        $roadbul_mysql['roadbul_ToZipCode'] = $roadbul['ToZipCode'];
                        $roadbul_mysql['roadbul_ToAddress'] = $roadbul['ToAddress'];
                        $roadbul_mysql['roadbul_ToMobilePhone'] = $roadbul['ToMobilePhone'];
                        $roadbul_mysql['roadbul_DeliveryTimeSlotId'] = 5;
                        //during Jurong Point (JP) launch period => 1. send to JP 2. deliver in the afternoon starts



                        // auto download label pdf starts 
                        if ($this->post('resultPickup') != 29) {
                                                  

                            $roadbull_label_url = file_get_contents('https://cds.roadbull.com/api/orders/label/'.$roadbul_array['OrderNumber']);                        
                            $label_url = json_decode($roadbull_label_url, true);
                            $label_pdf = $label_url['data']['label_pdf'];
                            $decoded = base64_decode($label_pdf);
                            file_put_contents("shipping_label/".$roadbul_array['OrderNumber'].".pdf", $decoded);

                        //     //send shipping label via sms                                        
                        //     $prefix = '65';                        
                        //     if (!empty($member['phone'])) {
                        //         $result = $this->smsApi->sendSms('Thank you for using Rebox. We have received your order details. Please download & print the following shipping label https://rebox.com.sg/shipping_label/'.$roadbul_array['OrderNumber'].'.pdf'.', and paste it on to your parcel. To enjoy FULL INSURANCE COVERAGE, please sign off during pickup or make sure the courier scans the QR/bar code on the label. The parcel will be delivered to us the next working day after pickup. Kindly note that proper packaging of your used device is required by the courier service provider. Please refer to the following link for packaging tips. https://bit.ly/2nR7S9g If you have any enquiry, please WhatsApp +65 8315 3756.', $prefix . $member['phone']);                            
                        //     }
                        }

                        //auto download label pdf ends


                        // if((int)(date('w',strtotime($this->post('resultPickup_date'))))==6 || in_array(($this->post('resultPickup_date')), $pheves)){
                        if((int)(date('w',strtotime($this->post('resultPickup_date'))))==6 || in_array(date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1), $pheves)){
                            $roadbul_mysql['roadbul_DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*2);
                        }else{
                            $roadbul_mysql['roadbul_DeliveryDate'] = date('d/m/Y',strtotime($this->post('resultPickup_date'))+86400*1);
                        }

                        /********************************************
                         * 
                         *  Pickupp API Integration
                         * 
                         ********************************************/

                        $pickuppFormat = formatOrderForPickupp($roadbul_mysql,$order_sn);
                        $pickuppFormat['customerNote'] = $this->post('customerNote');
                        $apiResponse = callAPI('POST', '/pickupp/createOrder', $pickuppFormat);
                        $json = json_decode($apiResponse->getBody());
                        if($apiResponse->getStatusCode() == '201' || $apiResponse->getStatusCode() == '200')  {
                            $this->smsApi->sendSms('Thank you for using Rebox. Pickup is confirmed on '.$json->pickupDatetime.'. Your pickup code is RB'.substr($order_sn, -6).'. Please provide during collection. Have a nice day!', '65' . $member['phone']);
                            $return = 1;
                        } else{ 
                            die(json_encode(array('code' => -1,'msg'=>'Pickupp Endpoint issue')));
                        }

                        // $return = $this->flowerhua->db_add('whizmo_order2roadbul',$roadbul_mysql);

                        /********************************************
                         * 
                         *  End of Pickupp API Integration
                         * 
                         ********************************************/


                    }else{
                        $this->flowerhua->rollback();
                        die(json_encode(array('code'=>-100,'result_err'=>$roadbul_array['Message'])));
                    }
                }elseif($this->post('fang')==2){
                    //not courier order meaning POPStation order, send auto shipping label
                    //get POPStation shipping number
                    
                    $conn = mysqli_connect('localhost', 'whizmo_pop', 'H@ppy2018', 'popstation');

                    // if (mysqli_connect_errno()){
                    //     echo 'failed to connect '. mysqli_connect_errno();
                    // }

                    $query = 'SELECT * FROM shipping_label WHERE status=0 limit 1';

                    $result = mysqli_query($conn, $query);

                    $labels = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    foreach ($labels as $label) {
                        $popstation_number = $label['no'];
                        $update_id = $label['id'];
                    }
                    
                    $sql = "DELETE FROM shipping_label WHERE id={$update_id}";    
                    $result2 = mysqli_query($conn, $sql);
                    mysqli_close($conn);     
                
                    //send shipping label via sms starts                                        
                    // $prefix = '65';                        
                    // if (!empty($popstation_number)) {
                    //     $result = $this->smsApi->sendSms('Thank you for using Rebox. We have received your order details. Please use the following shipping label number: '. $popstation_number . ' and drop off your phone at any SingPost POPStation within 2 calender days. SingPost will develier the parcel to us within 1-2 working days. For detailed POPStation dropoff instructions, please refer to https://bit.ly/2uGTNzi. Kindly note that proper packaging of your used device is required by SingPost. Please refer to the following link for packaging tips. https://bit.ly/2nR7S9g', $prefix . $member['phone']);
                    // }
                    //send popstation label via sms ends


                    // add popstation label to database
                    $data['order_wuliu_number'] = $popstation_number;
                    $data['order_delivery_number'] = $popstation_number;
                    

                    $return = 1;
                } else { //walkin orders

                    $data['order_wuliu_number'] = $this->post('walkin_date');
                    $data['order_delivery_number'] = $this->post('walkin_date');
                    $return = 1; 
                }
                //需要保存在订单中的信息
                $data['order_delivery_method_type'] = $this->post('fang');                
                $data['order_pickup_code']          = $this->post('postal_code');
                $data['order_pickup_address']       = $this->post('postal_address');
                $data['order_pickup_unit']          = $this->post('postal_unit');
                $data['order_payment_type']         = $this->post('payment_id');
                $data['order_payment_name']         = $this->post('payment_name');
                $data['order_payment_number']       = $this->post('payment_number');
                $data['order_member_id']            = get_cookie('member_id');   //add mobile phone by LC  
                $data['customerSurvey']             = $this->post('customerSurvey');       
                $return2 = $this->flowerhua->db_update('whizmo_order',$data,"order_id = $order_id");

                $agent_check = $this->post('agent_check');
                if($return && $return2){
                    $this->flowerhua->commit();
                    // die(json_encode(array('code' => 1,'order_id'=>$order_id)));
                    die(json_encode(array('code' => 1,'order_id'=>$order_id, 'order_sn'=>$order_sn,'order_delivery_method_type'=>$data['order_delivery_method_type'], 'order_delivery_number'=>$data['order_delivery_number'], 'agent_check'=>$agent_check))); //add more info to pass on for order email sending

                }else{
                    $this->flowerhua->rollback();
                    die(json_encode(array('code' => -1,'msg'=>'Fail to submit order')));
                }
            // }
        }
    }


    //kiosk mode order submission starts
    public function ajax_submit2orderinfo_kioskAction(){
        if (IS_AJAX) {
            $this->flowerhua->begin();

            $order_id = $this->post('order_id');
            $order = $this->flowerhua->get_one('whizmo_order',"order_id = $order_id",'order_sn_number');                                
            $order_sn = $order['order_sn_number'];

            $phone = $this->post('phone');
            $member = $this->flowerhua->get_one('member',"phone = $phone",'id');
            //send order number + initial password for new user
            if(!$member) {
                $new_member = [];
                $new_member['phone'] = $phone;                    

                $password = mt_rand(10000000, 99999999);
                $prefix = '65';
                $result = $this->smsApi->sendSms('Rebox order#: '.$order_sn.'. Your new Rebox account has been created. Username: '.$phone.' Password: '.$password.'. Log in to your Rebox account to see order status. https://bit.ly/2Oeu8Xa', $prefix . $phone);

                $new_member['password'] = md5(md5($password).md5($password));
                $new_member['addtime'] = time();
                $return0 = $this->flowerhua->db_add('member',$new_member);
                $member = $this->flowerhua->get_one('member',"phone = $phone",'id');
            } else {
                $prefix = '65';
                $result = $this->smsApi->sendSms('Rebox order#: '.$order_sn.'. Log in to your Rebox account to see order status.', $prefix . $phone);
            }
            //send order number

            //update id info

            $member_id_nric = $this->flowerhua->get_field('member',"phone = $phone",'id');

            $nric = [];
            $nric['sftype'] = $this->post('id_type');
            $nric['fullname'] = $this->post('id_name');
            $nric['nric'] = $this->post('id_number');
            $nric['address'] = $this->post('id_address');
            $nric['postal'] = $this->post('id_postal');
            $nric['unit'] = $this->post('id_unit');

            $nric['country'] = $this->post('id_nationality');
            if ($nric['sftype'] == 22) {
                $nric['country'] =29;
            }

            $nric['date_of_birth'] = $this->post('date_of_birth');
            
            $return_nric = $this->flowerhua->db_update('member',$nric,"id = $member_id_nric");


            if($order['order_coupon_code_id']){
                $coupon = [];
                $coupon_title = $this->flowerhua->get_field('whizmo_recovery_coupon', "id = $order[order_coupon_code_id]", 'coupon_title');
                if($coupon_title!=='EVERGREEN'){
                    $coupon['coupon_status'] = 2;
                    $coupon['coupon_member_id'] = $member['id'];
                    $coupon['coupon_member_phone'] = $phone; 
                }
                $coupon['coupon_apply_time'] = time();
                $res = $this->flowerhua->db_update('whizmo_recovery_coupon',$coupon,"id = $order[order_coupon_code_id]");
                if(!$res){
                    $this->flowerhua->rollback();
                    die(json_encode(array('code' => -1,'msg'=>'Promo code is invalid. Please try again!')));
                }
            }

            $data = [];
            $data['order_wuliu_number'] = $this->post('walkin_date');
            $data['order_delivery_number'] = $this->post('walkin_date');
            $data['order_delivery_method_type'] = $this->post('fang');
            $data['order_payment_type']         = $this->post('payment_id');
            $data['order_payment_name']         = $this->post('payment_name');
            $data['order_payment_number']       = $this->post('payment_number');
            $data['order_member_id']            = $member['id'];       
            $data['order_status']               = 2;
            $data['customerSurvey']             = $this->post('customerSurvey');
            $return2 = $this->flowerhua->db_update('whizmo_order',$data,"order_id = $order_id");
            if($return2){
                $this->flowerhua->commit();
                die(json_encode(array('code' => 1,'order_id'=>$order_id, 'order_sn'=>$order_sn,'order_delivery_method_type'=>$data['order_delivery_method_type'], 'order_delivery_number'=>$data['order_delivery_number']))); 
            }else{
                $this->flowerhua->rollback();
                die(json_encode(array('code' => -1,'msg'=>'Fail to submit order')));
            }
        }
    }
    //kiosk mode order submission ends


    //更改订单状态为已提交订单
    public function ajax_change_orderstatus2Action(){
        if (IS_AJAX) {
            $order_id = $this->post('order_id');
            if($order_id){
                $array = [];
                $array['order_status'] = 2;
                $this->flowerhua->db_update('whizmo_order',$array,"order_id = $order_id");
                die(json_encode(array('code' => 1)));
            }
        }
    }


















    /**
     * 文件上传
     * @param  $fields      上传字段 'file'
     * @param  $type        文件类型  array(jpg,gif)
     * @param  $size        文件大小  KB
     * @param  $img         图片配置参数
     * @param  $mark        图片水印
     * @param  $admin       是否来自后台
     * @param  $stype       上传方式  swf或者ke
     * @param  $ofile       原文件
     * @param  $document    后台栏目归档目录
     * @return Array        返回数组
     */
    private function upload($fields, $type, $size = 20, $img = null, $mark = true, $admin = 0, $stype = null, $ofile = null, $document = null)
    {
        $path = 'uploadfiles/';
        $upload = $this->instance('file_upload');
        if (empty($admin) && $this->memberinfo) {
            $uid = $this->memberinfo['id']; //会员附件归类
            if ($uid) {
                $path .= 'member/' . $uid . '/';
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
        $upload->set($fields)->set_limit_size(1024 * $size)->set_limit_type($type);
        //设置路径和名称
        $ext = $upload->fileext();
        if (stripos($ext, 'php') !== FALSE
            || stripos($ext, 'asp') !== FALSE
            || stripos($ext, 'aspx') !== FALSE
        ) {
            return array('result' => '文件格式被系统禁止');
        }
        if (in_array($ext, array('jpg', 'jpeg', 'bmp', 'png', 'gif'))) {
            $dir = 'image';
            $upload->set_image($img['w'], $img['h'], $img['t']);
        } else {
            $dir = 'file';
        }
        $path .= $dir . '/' . (empty($document) || $document == 'undefined' || !preg_match('/^[a-zA-Z_0-9]+$/', $document) ? '' : $document . '/');
        if ($ofile && is_file($ofile) && strpos($path, dirname(dirname($ofile))) === 0) { //判断原文件
            $path = dirname($ofile) . '/';
            $file = $fname = basename($ofile);
        } else {
            $path .= date('Ym') . '/';
            $data = file_list::get_file_list($path);
            $time = time();
            $random = randomkeys(6);
            $name = count($data) + 1 .$time.$random;
            $name = is_file($path . $name . '.' . $ext) ? $name . str_replace('0.', '_', (double)microtime()) : $name;
            $file = $upload->filename();
            $fname = $name . '.' . $ext;
        }
        $result = $upload->upload($path, $fname);
        //上传成功处理图片
        if (!$result && $dir == 'image') {
            $this->watermark($path . $fname);
        }
        return array('result' => $result, 'path' => $path.$fname, 'file' => $file, 'ext' => $dir == 'image' ? 'png' : $ext);
    }



 public function ajax_set_imgAction()
    {
        $img = $_FILES['addlogo'];
        $up = $this->upload($img, array('jpeg', 'jpg', 'gif', 'png', 'bmp'),$this->site['FILE_SIZE']);//上传原图图片
//        echo json_encode($up);die;
//        $path_thumb = "uploadfiles/thumb/" . md5($up['path']) . ".png";
//        img2thumb($up['path'], $path_thumb);//生成缩略图
//        $data['logo'] = $path_thumb;
//        $this->member->set($member_id,$data);
            $arr = array(
                'result' => $up['result'],
                'access_url' => $up['path'],
                'size' => $img['size']
            );
        unset($up);unset($res);unset($img);
        echo json_encode($arr);
    }









































}