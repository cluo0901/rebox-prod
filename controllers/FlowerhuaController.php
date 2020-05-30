<?php
    date_default_timezone_set('PRC');
    require('utilities.php');

    class FlowerhuaController extends Common
    {
        public $flowerhua;
        public $roadbul_url; //接口地址
        public $header;

        public function __construct(){
            parent::__construct();
            $this->flowerhua = $this->model('flowerhua');
            $this->roadbul_url = 'http://sandcds.roadbull.com/api';
            if(get_cookie('roadbul_token')){
                $this->header = array(
                    'authorization'=> 'Bearer '.get_cookie('roadbul_token'),
                    'content-type' => 'application/json'
                );
            }
        }
        public function recovery_listAction()
        {
            $recovery_type_id = $this->safe('recovery_type_id');
            $recovery_parent_id = $this->safe('recovery_parent_id');
            $about = $this->flowerhua->get_one('category',"catid = 187","catid,catname,image");
            $banner_list = $this->flowerhua->get_join_all('content_1 as a','content_1_banner as b',"a.id = b.id","a.catid = 182 and b.leixing = 1 order by a.listorder desc");
            $partners_list = $this->flowerhua->get_join_all('content_1 as a','content_1_partners as b',"a.id = b.id","a.catid = 183 order by a.listorder desc limit 11");
            $testimonials_list = $this->flowerhua->get_join_all('content_1 as a','content_1_testimonials as b',"a.id = b.id","a.catid = 184 order by a.listorder desc");
            $blog_list = $this->flowerhua->get_join_all('content_1 as a','content_1_blog as b',"a.id = b.id","a.catid = 185 order by a.listorder desc");
            $blog_catname = $this->flowerhua->get_field('category',"catid = 185",'catname');
            $topics_list = $this->flowerhua->get_join_all('content_1 as a','content_1_topics as b',"a.id = b.id","a.catid = 186 order by a.listorder desc limit 3");
            $topics_catname = $this->flowerhua->get_field('category',"catid = 186",'catname');
            $type_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid = 28");
            $recovery_step = $this->flowerhua->get_one('system_selected',"id = 1");//回收步骤
            //fefei
            $partner1 = $this->flowerhua->get_all_list('whizmo_partner', 'sort desc', '0,5', 'status=1', $ifcount = false);
            $partner2 = $this->flowerhua->get_all_list('whizmo_partner', 'sort desc', '5,3', 'status=1', $ifcount = false);
            $partner3 = $this->flowerhua->get_all_list('whizmo_partner', 'sort desc', '8,3', 'status=1', $ifcount = false);
            //end
            $this->view->assign([
                'recovery_type_id'=>$recovery_type_id,
                'recovery_parent_id'=>$recovery_parent_id,
                'recovery_step'=>$recovery_step,
                'blog_catname'=>$blog_catname,
                'topics_catname'=>$topics_catname,
                'type_list'=>$type_list,
                'banner_list'=>$banner_list,
                'partners_list'=>$partners_list,
                'testimonials_list'=>$testimonials_list,
                'blog_list'=>$blog_list,
                'topics_list'=>$topics_list,
                'about'=>$about,
                'meta_title' => $this->site['SITE_TITLE'],
                'meta_keywords' => $this->site['SITE_KEYWORDS'],
                'meta_description' => $this->site['SITE_DESCRIPTION'],
                "partner1"=>$partner1,
                'partner2'=>$partner2,
                'partner3'=>$partner3,
            ]);
            $this->view->display('recovery_list');
        }
        //手机回收详情-选择参数
        public function recovery_detailAction()
        {   
            //check if logged in as agent
            if(get_cookie('member_id')){
                $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            }

            $recovery_id = $this->get('recovery_id') ? $this->get('recovery_id') : 0;
            $priceValue = $this->get('priceValue');
            $chpriceValue = $this->get('chpriceValue');
            $chpriceValue2 = $this->get('chpriceValue2');
            $data = $this->flowerhua->get_one('whizmo_recovery',"id = $recovery_id");
            if(!$data){
                header("location:index.php");die;
            }
            $data['parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $data[id] and parentid = 0 and parameter_is_select = 1");
            $data['ch_parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $data[id] and parentid = 2 and parameter_is_select = 1");
            $data['ch_parameter2'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"recovery_id = $data[id] and parentid = 22 and parameter_is_select = 1");
            foreach ($data['parameter'] as $key => $value) {
                $data['parameter'][$key]['parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $value[id] and parameter_is_select = 1");
            }
            foreach ($data['parameter'] as $key => $value) {
                foreach ($value['parameter'] as $key2 => $value2) {
                    $data['parameter'][$key]['parameter'][$key2]['parameter'] = $this->flowerhua->get_all_where('whizmo_recovery_parameter',"parentid = $value2[id] and parameter_is_select = 1");
                }
            }
            $recovery_tips = $this->flowerhua->get_one('system_selected',"id = 1",'recovery_tips_title,recovery_tips_content');
            $recovery_step = $this->flowerhua->get_one('system_selected',"id = 1");//回收步骤
            //单选项
            foreach (array_filter(explode(',',$priceValue)) as $key => $value) {
                $price_arr[$key]['parent_id'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parentid');
                $price_arr[$key]['id'] = $value;
                $price_arr[$key]['name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_name');
                $price_arr[$key]['price'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_sale_price');
            }
            // dd($priceValue);
            $this->view->assign(array(
                'price_arr'=>$price_arr,
                'priceValue'=>$priceValue,
                'chpriceValue'=>$chpriceValue,
                'chpriceValue2'=>$chpriceValue2,
                'recovery_step'=>$recovery_step,
                'data'        => $data,
                'recovery_tips'   => $recovery_tips,
                'recovery_id'=>$recovery_id,
                'meta_title' => 'recovery_detail',
                'agent_check' => $agent_check, //pass check value for agent
            ));
            $this->view->display('recovery_detail');
        }
        //手机回收参数选定完毕后评估价格
        public function recovery_instant_quoteAction()
        {
//            set_cookie('member_id',null);die;

            //check if logged in as agent
            if(get_cookie('member_id')){
                $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            }

            $recovery_id = $this->get('recovery_id') ? $this->get('recovery_id') : 0;
            $priceValue = $this->get('priceValue');
            $chpriceValue = $this->get('chpriceValue');
            $chpriceValue2 = $this->get('chpriceValue2');
            $data = $this->flowerhua->get_one('whizmo_recovery',"id = $recovery_id");
            if(!$data || !$recovery_id || !$priceValue){
                header("location:index.php");die;
            }
            $price_arr = [];
            $ch_price_arr = [];
            //单选项扣除

            // reset all the taggings
            $housing_repair_tag = 0;
            $glass_lcd_repair_tag = 0;
            $housing_damage_tag = 0;
            $glass_damage_tag = 0;
            $glass_minor_tag = 0;
            $lcd_damage_tag = 0;
            $lcd_minor_tag = 0;

            $housing_addback = 0;
            $glass_lcd_repair_addback = 0;
            $glass_addback = 0;
            $lcd_minor_addback = 0;


            foreach (array_filter(explode(',',$priceValue)) as $key => $value) {
                $price_arr[$key]['id'] = $value;
                $price_arr[$key]['name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_name');
                

                //find capacity id starts
                if (strpos($price_arr[$key]['name'], 'GB') !== false) {
                    $capacity_id = $value;
                }
                //find capacity id ends
                $price_arr[$key]['price'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_sale_price');     
                           

                //category name
                $parentid = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parentid');
                $price_arr[$key]['parameter'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $parentid",'parameter_name');

                // take note of tag
                $this_tag = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_tag');                
                //housing tag check
                if ($this_tag == 'HR' || $this_tag == 'BR') {
                    $housing_repair_tag = 1;
                    $housing_addback = $price_arr[$key]['price'];
                } else if ($this_tag == 'HD'){
                    $housing_damage_tag = 1;
                }

                //glass and lcd tag check
                if ($this_tag == 'GLR' || $this_tag == 'BR') {
                    $glass_lcd_repair_tag = 0;
                    $glass_lcd_repair_addback = $price_arr[$key]['price'];
                } else if ($this_tag == 'LD') {
                    $lcd_damage_tag = 1;
                } else if ($this_tag == 'LM') {
                    $lcd_minor_tag = 1;
                    $lcd_minor_addback = $price_arr[$key]['price'];
                } else if ($this_tag == 'GD') {
                    $glass_damage_tag = 1;
                    $glass_addback = $price_arr[$key]['price'];
                } else if ($this_tag == 'GM') {
                    $glass_minor_tag = 1;
                    $glass_addback = $price_arr[$key]['price'];
                }

            }
            //多选项扣除
            foreach (array_filter(explode(',',$chpriceValue)) as $key => $value) {
                $ch_price_arr[$key]['id'] = $value;
                $ch_price_arr[$key]['name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_name');
                $ch_price_arr[$key]['price'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_sale_price');
            }
            //多选附件项扣除
            foreach (array_filter(explode(',',$chpriceValue2)) as $key => $value) {
                $ch_price_arr2[$key]['id'] = $value;
                $ch_price_arr2[$key]['name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_name');
                $ch_price_arr2[$key]['price'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value",'parameter_sale_price');
            }

            $deducted_money = 0;
            $ch_deducted_money = 0;
            $ch_deducted_money2 = 0;
            //计算需要扣除的价格
            foreach ($price_arr as $key => $value) {
                $deducted_money += (int)$value['price'];
            }

            // add back housing double deduction
            if ($housing_repair_tag == 1 && $housing_damage_tag == 1) {
                $deducted_money -= (int)$housing_addback;
            }
            // add back LCD or glass double deduction
            $lcd_damage_addback = $this->flowerhua->get_field('whizmo_recovery_parameter',"parameter_tag = 'LD' AND recovery_id = $recovery_id",'parameter_sale_price');

            if ($lcd_damage_tag == 1) {
                $deducted_money -= (int)$glass_lcd_repair_addback;
                $deducted_money -= (int)$glass_addback;
            }

            if ($lcd_minor_tag == 1 && $glass_damage_tag == 1) {
                $deducted_money += (int)$lcd_damage_addback;
                $deducted_money -= (int)$glass_lcd_repair_addback;
                $deducted_money -= (int)$glass_addback;
                $deducted_money -= (int)$lcd_minor_addback;
            }

            if ($lcd_minor_tag == 1 && $glass_minor_tag == 1) {
                $subtotal = $lcd_minor_addback + $glass_addback + $glass_lcd_repair_addback;
                if ($subtotal > $lcd_damage_addback) {
                    $deducted_money += (int)$lcd_damage_addback;
                    $deducted_money -= (int)$glass_lcd_repair_addback;
                    $deducted_money -= (int)$glass_addback;
                    $deducted_money -= (int)$lcd_minor_addback;
                }
            }


            foreach ($ch_price_arr as $key => $value) {
                $ch_deducted_money += (int)$value['price'];
            }
            foreach ($ch_price_arr2 as $key => $value) {
                $ch_deducted_money2 += (int)$value['price'];
            }
            $recovery_product_price = $this->flowerhua->get_field('whizmo_recovery',"id = $recovery_id",'recovery_high_price');
            $instant_quote_price = (int)$recovery_product_price - $deducted_money - $ch_deducted_money - $ch_deducted_money2;

            // check housing was repaired and housing damaged, should add back HR starts


            // check housing was repaired and housing damaged, should add back HR ends

            //if it is samsung buds, $capacity_id is not defined.
            if ($recovery_id != 344) {
                //check if deduction is only on capacity difference starts
                $capacity_deduct = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $capacity_id",'parameter_sale_price');

                $flawless_price = (int)$recovery_product_price - (int)$capacity_deduct;
                $price_boost = 0;
                if ($instant_quote_price == $flawless_price) {
                    $price_boost0 = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $capacity_id",'parameter_boost');
                    $price_boost = (int)$price_boost0;
                }            
                //check if deduction is only on capacity difference ends
            }

            //若产品比回收最低价更低,默认给设置的回收最低价
            if($instant_quote_price < $data['recovery_low_price']){
                $instant_quote_price = $data['recovery_low_price'];
            }
//            dd($price_arr);
            $recovery_step = $this->flowerhua->get_one('system_selected',"id = 1");//回收步骤
            $higherprice = $this->flowerhua->get_one('whizmo_popup',"id=1",'');
            //获取五条最新的数据来进行echarts 画图
//            $_list = $this->flowerhua->get_all_where('whizmo_recovery_prices',"recovery_id = $recovery_id order by recovery_years desc,id desc limit 5");
//            foreach ($_list as $key => $value) {
//                $id[$key] = $value['recovery_moths'];
//            }
            //重新使用月份排序
//            array_multisort( $id, SORT_ASC, $_list);
//            $moths_arr = [];
//            $prices_sum = 0;
//            foreach ($_list as $key => $value) {
//                $moths_arr[$key] = $value['recovery_moths_title'];
//                $prices_arr[$key] = (int)$value['recovery_prices'];
//                $prices_sum += (int)$value['recovery_prices'];
//            }
            //下个月预计减少的价钱
//            $next_price = number_format($prices_arr[0] - $prices_sum/count($prices_arr),2);
//            if((int)$next_price <= 0 ){
//                $next_price = 0;
//            }
            //曲线图固定比例
            $moths_arr[0] = date("m/Y",strtotime("-3 month"));
            $moths_arr[1] = date("m/Y",strtotime("-2 month"));
            $moths_arr[2] = date("m/Y",strtotime("-1 month"));
            $moths_arr[3] = date("m/Y",strtotime("0 month"));
            $moths_arr[4] = date("m/Y",strtotime("+1 month"));
            $prices_arr[0] = ceil($instant_quote_price*1.17);
            $prices_arr[1] = ceil($instant_quote_price*1.15);
            $prices_arr[2] = ceil($instant_quote_price*1.0);
            $prices_arr[3] = ceil($instant_quote_price*1);
            $prices_arr[4] = ceil($instant_quote_price*0.88);
            $next_price = ceil(($instant_quote_price - $prices_arr[4]));
            if((int)$next_price <= 0 ){
                $next_price = 0;
            }
            $this->view->assign(array(
                'next_price'=>$next_price,
                'priceValue'=>$priceValue,
                'chpriceValue'=>$chpriceValue,
                'chpriceValue2'=>$chpriceValue2,
                'recovery_step'=>$recovery_step,
                'instant_quote_price'        => (int)$instant_quote_price,
                'recovery_id'        => $recovery_id,
                'data'        => $data,
                'moths_arr'        => json_encode($moths_arr),
                'prices_arr'        => json_encode($prices_arr),
                'price_arr'        => $price_arr,
                'ch_price_arr'        => $ch_price_arr,
                'ch_price_arr2'        => $ch_price_arr2,
                'meta_title' => 'recovery_instant_quote',
                'higherprice' => $higherprice,
                'price_boost' => $price_boost, //if flawless condition, boost price (not in use)
                'agent_check' => $agent_check, //pass check value for agent

            ));
            $this->view->display('recovery_instant_quote');
        }
        //手机回收评价完成提交订单后
        public function recovery_free_pickupAction()
        {
            // Hide by LC
            // if(!get_cookie('member_id')){
            //     header("location:index.php");
            // }
            //check if logged in as agent
            if(get_cookie('member_id')){
                $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            }

            $recovery_step = $this->flowerhua->get_one('system_selected',"id = 1");//回收步骤
            $recovery_tips = $this->flowerhua->get_one('system_selected',"id = 1",'recovery_tips_title,recovery_tips_content');//回收小贴士
            $recovery_id = $this->get('recovery_id');
            $order_id = $this->get('order_id');
            $recovery = $this->flowerhua->get_one('whizmo_recovery',"id = $recovery_id");
//            $name1 = $this->flowerhua->get_field('whizmo_category_type',"id = $recovery[recovery_type_id]",'name');
//            $name2 = $this->flowerhua->get_field('whizmo_category_type',"id = $recovery[recovery_parent_id]",'name');
//            $recovery['recovery_title'] =  $name1.' - '.$name2.' - '.$recovery['recovery_title'];
            $order = $this->flowerhua->get_one('whizmo_order',"order_id = $order_id");
            $order_parameter = $this->flowerhua->get_join_all('whizmo_order_paramemter as a','whizmo_recovery_parameter as b',"a.recovery_parameter_id = b.id","a.order_id = $order_id",'a.*,b.parameter_name,b.parentid');
            foreach ($order_parameter as $key => $value) {
//                $order_parameter[$key]['parent_parameter_name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value[parentid]","parameter_name");
//                $order_parameter[$key]['parent_parameter_name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value[parentid]","parameter_name");
            }
//            dd($order_parameter);
            //物流接口
//            $ServiceId = 1;//默认选择标准配送方式
//            if (file_exists(FCPATH.'cache/views/timeoptions_html.txt')) {
//                if (fileatime(FCPATH.'cache/views/timeoptions_html.txt')>=(time()-86400)) {
//                    $timeoptions_html = file_get_contents(FCPATH.'cache/views/timeoptions_html.txt');
//                    $resultPickup = $timeoptions_html;
//                }else{
//                    $obj = _post($this->roadbul_url.'/orders/timeoptions/'.$ServiceId,'',$this->header);
//                    $roadbul_array = json_decode($obj, true);
//                    if($roadbul_array['Message']){
//                        die(json_encode(array('code'=>400,'result_err'=>$roadbul_array['Message'])));
//                    }else{
//                        $resultPickup = '';
//                        $resultPickup .= '<option value=""> Select Pickup Time</option>';
//                        foreach ($roadbul_array['AvailablePickupTimes'] as $key => $value) {
//                            $resultPickup .= '<option value="'.$value['Value'].'" data-value="'.$value['Text'].'">'.$value['Text'].'</option>';
//                        }
//                    }
//                    file_put_contents(FCPATH.'cache/views/timeoptions_html.txt',$resultPickup);
//                }
//            }else{
//                $obj = _post($this->roadbul_url.'/orders/timeoptions/'.$ServiceId,'',$this->header);
//                $roadbul_array = json_decode($obj, true);
//                if($roadbul_array['Message']){
//                    die(json_encode(array('code'=>400,'result_err'=>$roadbul_array['Message'])));
//                }else{
//                    $resultPickup = '';
//                    $resultPickup .= '<option value=""> Select Pickup Time</option>';
//                    foreach ($roadbul_array['AvailablePickupTimes'] as $key => $value) {
//                        $resultPickup .= '<option value="'.$value['Value'].'" data-value="'.$value['Text'].'">'.$value['Text'].'</option>';
//                    }
//                }
//                file_put_contents(FCPATH.'cache/views/timeoptions_html.txt',$resultPickup);
//            }
//            $resultPickup = '';
//            $resultPickup .='<option value=""> Select Pickup Time</option>';
//            $resultPickup .='<option value="26" data-value="Noon Collection (12:00 - 16:00)" >Noon Collection (12:00 - 16:00)</option>';
//            if(date('w',time())<5){
//                $resultPickup .='<option value="27" data-value="Evening Collection (16:00 - 20:00)" >Evening Collection (16:00 - 20:00)</option>';
//            }
            //付款详情
            $payment_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid=45");


            // //取件时间(默认明天到三天后)
            // $pickup_time_start = [];   
            // //if new phone only today roadbull or tmr morning pickup
            // if ($recovery[recovery_type_id] == 68) {
            //     if (date('H') >= 13) { // after 1pm only tmr morning pickup
            //         if(date('w',time())==6){
            //             $pickup_time_start[0] = time()+3600*24*2;  //if saturday only monday morning
            //         }else{
            //             $pickup_time_start[0] = time()+3600*24;
            //         }
            //     }

            //     if (date('H') < 13) {
            //         if(date('w',time())==6){
            //             if (date('H') >=9) {
            //                 $pickup_time_start[0] = time()+3600*24*2;
            //             }else{
            //                 $pickup_time_start[0] = time();
            //                 $pickup_time_start[1] = time()+3600*24*2;
            //             }                    
            //         }else if(date('w',time())==0){
            //             $pickup_time_start[0] = time()+3600*24;
            //         }else{
            //             $pickup_time_start[0] = time();
            //             $pickup_time_start[1] = time()+3600*24;
            //         }
            //     }

            // } else {

            //     if (date('H') >= 13) {

            //         if(date('w',time())==6){
            //             $pickup_time_start[0] = time()+3600*24*2;
            //             $pickup_time_start[1] = time()+3600*24*3;

            //         }else if(date('w',time())==5){
            //             $pickup_time_start[0] = time()+3600*24;
            //             $pickup_time_start[1] = time()+3600*24*3;

            //         }else{
            //             $pickup_time_start[0] = time()+3600*24;
            //             $pickup_time_start[1] = time()+3600*24*2;

            //         }
            //     }

            //     //to include roadbull today option

            //     if (date('H') < 13) {
            //         if(date('w',time())==6){
            //             if (date('H') >=9) {
            //                 $pickup_time_start[0] = time()+3600*24*2;
            //                 $pickup_time_start[1] = time()+3600*24*3;
            //             }else{
            //                 $pickup_time_start[0] = time();
            //                 $pickup_time_start[1] = time()+3600*24*2;
            //                 $pickup_time_start[2] = time()+3600*24*3;
            //             }
            //         }else if(date('w',time())==5){
            //             $pickup_time_start[0] = time();
            //             $pickup_time_start[1] = time()+3600*24;
            //             $pickup_time_start[2] = time()+3600*24*3;
            //         //check if sunday no same day
            //         }else if(date('w',time())==0){
            //             $pickup_time_start[0] = time()+3600*24;
            //             $pickup_time_start[1] = time()+3600*24*2;
            //         }else{
            //             $pickup_time_start[0] = time();
            //             $pickup_time_start[1] = time()+3600*24;
            //             $pickup_time_start[2] = time()+3600*24*2;
            //         }
            //     }

            // }



            // $phdays = array("19/04/2019","01/05/2019","20/05/2019","09/08/2019", "12/08/2019", "28/10/2019", "25/12/2018");
            // if (in_array(date('d/m/Y', $pickup_time_start[0]), $phdays)) {
                   
            //         $pickup_time_start[0] = $pickup_time_start[1];
            //         // $pickup_time_start[1] = $pickup_time_start[2];
            //         unset($pickup_time_start[1]);
            // }

            // if (in_array(date('d/m/Y', $pickup_time_start[1]), $phdays)) {
            //         // $pickup_time_start[1] = $pickup_time_start[2];
            //         unset($pickup_time_start[1]);
            // }

            $pickup_time_start = [];
            $hour = date('G');
            $minutes = date('i');

            $phdays = array("25/12/2019","01/01/2020","24/01/2020","25/01/2020","26/01/2020","27/01/2020","28/01/2020");
            
/*             switch ($recovery['recovery_type_id']) {

                case 68:

                    if($hour <= 8 && $minutes <= 30) {

                        $pickup_time_start[0] = time();
                        $pickup_time_start[1] = time()+3600*24*1;
                        
                    } elseif ($hour <= 8 AND $minutes > 30) {
                        
                        $pickup_time_start[1] = time()+3600*24*1;
        
                    } elseif ($hour >= 9 && $hour <= 17) {
                        
                        $pickup_time_start[1] = time()+3600*24*1;
        
                    } elseif ($hour >= 18 && $hour <= 24) {
        
                        $pickup_time_start[1] = time()+3600*24*1;
                        
                    }

                    if (in_array(date('d/m/Y', $pickup_time_start[0]), $phdays)) {
                   
                        $pickup_time_start[0] = $pickup_time_start[1];
                        $strTom = date('m/d/Y', $pickup_time_start[1]);
                        $pickup_time_start[1] = strtotime($strTom . "+1 days");
                        $pickup_time_start = array_reverse($pickup_time_start);
                        // unset($pickup_time_start[1]);
                    }
    
                    if (in_array(date('d/m/Y', $pickup_time_start[1]), $phdays)) {
                            $strTom = date('m/d/Y', $pickup_time_start[1]);
                            $pickup_time_start[1] = strtotime($strTom . "+1 days");
                            $pickup_time_start = array_reverse($pickup_time_start);
                    }

                    break;

                    
                
                default:

                

                    
                    if($hour <= 8 AND $minutes <= 30) {
                        $pickup_time_start[0] = time();
                        
                    } elseif ($hour <= 8 AND $minutes > 30) {

                        $pickup_time_start[0] = time();
                        $pickup_time_start[1] = time()+3600*24*1;

        
                    } elseif ($hour >= 9 AND $hour < 12) {
                        $pickup_time_start[0] = time();
                        $pickup_time_start[1] = time()+3600*24*1;
        
                    } elseif ($hour >= 12 AND $hour <= 24) {
   
                        $pickup_time_start[1]  = time()+3600*24*1;

                    }

                    if (in_array(date('d/m/Y', $pickup_time_start[0]), $phdays)) {
                        unset($pickup_time_start[0]);
                    }

                    if (in_array(date('d/m/Y', $pickup_time_start[1]), $phdays)) {
                   
                        $pickup_time_start[0] = $pickup_time_start[1];
                        $strTom = date('m/d/Y', $pickup_time_start[1]);
                        $pickup_time_start[0] = strtotime($strTom . "+1 days");
                        $pickup_time_start = array_reverse($pickup_time_start);
                        // unset($pickup_time_start[1]);
                    }
    
                    // if (in_array(date('d/m/Y', $pickup_time_start[2]), $phdays)) {
                    //         $strTom = date('m/d/Y', $pickup_time_start[2]);
                    //         $pickup_time_start[2] = strtotime($strTom . "+1 days");
                    //         $pickup_time_start = array_reverse($pickup_time_start);
                    // }

                    break;

            } */

            if($hour < 12) {

                $pickup_time_start[0] = time();
                $pickup_time_start[1] = time()+3600*24*1;
                
            } else {

                $pickup_time_start[0] = time()+3600*24*1;

            }



            

            
            //walkin_date: used = 3 days; new = 1 day
            $walkin_date = [];
            $max_day = 2;
            if ($recovery[recovery_type_id] != 68) {
                $max_day = 4;
            }

            if (date('H') < 21) {
                for ($i=0; $i<$max_day; $i++) {
                    if ((time()+3600*24*$i) >= strtotime('2019-04-01 00:00:00')) {
                        if(in_array(date('d/m/Y', time()+3600*24*$i), $phdays)){
                            
                        }else{
                            $walkin_date[$counter] = date('d/m/Y', time()+3600*24*$i);
                            $counter ++;
                        }
                        
                    }
                }
            } else {
                for ($i=0; $i<$max_day; $i++) {
                    if ((time()+3600*24*$i) >= strtotime('2019-04-01 00:00:00')) {
                        if(in_array(date('d/m/Y', time()+3600*24*$i), $phdays)){

                        } else {
                            $walkin_date[$counter] = date('d/m/Y', time()+3600*24*$i);
                            $counter ++;
                        }
                        
                    }
                }
            }
            
            //get ID type list
            $id_type = $this->flowerhua->get_all_where('whizmo_type',"idtype IS NOT NULL ORDER BY sequence");
            //get country list
            $id_nationality = $this->flowerhua->get_all_where('whizmo_type',"country IS NOT NULL");                            

            //个人条款
            $clause = $this->flowerhua->get_one('whizmo_recovery_clause',"id = 1");
            $clause['personal_information3'] = htmlspecialchars_decode($clause['personal_information3']);
            // if added by LC
            if(get_cookie('member_id')){
                $member = $this->flowerhua->get_one('member',"id = ".get_cookie('member_id'));
                $address = $this->flowerhua->get_one('whizmo_address',"member_id = ".get_cookie('member_id'),'');
            }

            $surveyResponse = callAPI('GET', '/customer-survey');
            $surveyOptions = json_decode($surveyResponse->getBody(),true);

            $this->view->assign(array(
                'address'=>$address,
                'surveyOptions' => $surveyOptions,
                'member'=>$member,
                'recovery_step'        => $recovery_step,
                'recovery'        => $recovery,
                'order_parameter'        => $order_parameter,
                'recovery_tips'        => $recovery_tips,
                'order'        => $order,
                'clause'        => $clause,
                'payment_list'        => $payment_list,
//                'resultPickup'        => $resultPickup,
                'pickup_time_start'        => $pickup_time_start,
                'walkin_date' => $walkin_date,
                'meta_title' => 'recovery_free_pickup',
                'agent_check' => $agent_check, //pass check value for agent
                'id_type' => $id_type, // pass id type list
                'id_nationality' => $id_nationality, //pass nationality list
            ));
            $this->view->display('recovery_free_pickup');
        }

        public function search_postalcodeAction(){
            $postal_code = $this->post('postal_code');
            $arr = $this->flowerhua->get_one('whizmo_postal',"postal_code = $postal_code",'');
            if($arr){
                die(json_encode(array('code'=>1,'arr'=>$arr)));
            }else{
                die(json_encode(array('code'=>2)));
            }
        }
        //前台订单
        public function orderAction()
        {
            $member_id = get_cookie('member_id');
            //check if logged in as agent
            $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            if ($agent_check ==1) {
                $d = new DateTime('first day of this month');
                $time = strtotime(date('Y-m-01'));
                $order_id_list = $this->flowerhua->get_all_where('whizmo_order',"order_member_id = $member_id and order_status = 6 and order_addtime >= $time", 'order_id');
                $order_counter = 0;
                foreach ($order_id_list as $counter1 => $counter2) {
                    $order_counter = $order_counter + 1;
                }
            }
            
            $where = "1=1 and order_member_id = $member_id";
            $order = "order_id desc";
            $page = $this->post('page')?$this->post('page'):1;
            $limit = ($page-1)*$this->web_pagesize.",$this->web_pagesize";
            $order = $this->flowerhua->get_order_list('whizmo_order',$order,$limit,$where);
            $total = $this->flowerhua->get_order_list('whizmo_order',$order,$limit,$where,true);
            foreach ($order as $key => $value) {
//                $order[$key]['parameter'] = $this->flowerhua->get_join_all('whizmo_order_paramemter as a','whizmo_recovery_parameter as b',"a.recovery_parameter_id = b.id","a.order_id = $value[order_id]",'a.recovery_parameter_id,a.recovery_parameter_type,b.parameter_name,b.parentid');
                $order[$key]['recovery'] = $this->flowerhua->get_one('whizmo_recovery',"id = $value[order_recovery_id]",'recovery_title,recovery_img');
//                $order[$key]['parameter'] = $this->flowerhua->get_all_where('whizmo_order_paramemter as a,fn_whizmo_recovery_parameter as b,fn_whizmo_recovery_parameter as c',"a.recovery_parameter_id = b.id and a.order_id = $value[order_id] and c.id = b.parentid",'a.recovery_parameter_id,a.recovery_parameter_type,b.parameter_name,b.parentid,c.*');
            }

            foreach ($order as $key => $value) {
                foreach ($value[parameter] as $key2 => $value2) {
                    if($value2['recovery_parameter_type']==1){
                        $order[$key]['parameter'][$key2]['parent_parameter_name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value2[parentid]","parameter_name");
                    }
                }
            }
            if(IS_AJAX){
                if (!empty($order)) {
                    $this->view->assign(array('order'=> $order));
                    $this->view->display('ajax_order');die;
                } else {
                    echo 1;die;
                }
            }
            $recovery_tips = $this->flowerhua->get_one('system_selected',"id = 1",'recovery_tips_title,recovery_tips_content');//回收小贴士
            $this->view->assign(array(
                'order'        => $order,
                'recovery_tips'        => $recovery_tips,
                'meta_title' => 'order',
                'agent_check' => $agent_check, //pass check value for agent
                'order_counter' => $order_counter,
            ));
            $this->view->display('order');
        }
        //回收订单详情
        public function order_detailAction()
        {

            //check if agent
            if(get_cookie('member_id')){
                $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            }

            $order_id = $this->get('order_id');
            if($order_id){
                $order = $this->flowerhua->get_one('whizmo_order',"order_id = $order_id");
                if(empty($order) || $order['order_member_id']!=get_cookie('member_id')){
                    header("location:index.php");
                }
                $order['recovery'] = $this->flowerhua->get_one('whizmo_recovery',"id = $order[order_recovery_id]",'recovery_title,recovery_img');
                $order['phone'] = $this->flowerhua->get_field('member',"id = $order[order_member_id]",'phone');
                $order['roadbul'] = $this->flowerhua->get_one('whizmo_order2roadbul',"order_id = $order_id",'roadbul_PickupDate,roadbul_PickupTimeSlotId,roadbul_FromZipCode');
            }else{
                header("location:index.php");
            }
            $recovery_tips = $this->flowerhua->get_one('system_selected',"id = 1",'recovery_tips_title,recovery_tips_content');//回收小贴士
            //付款详情方式
            $payment_list = $this->flowerhua->get_all_where('whizmo_category_type',"parentid=45");
            $payment_arr = tran_one($payment_list,'id','name');

            //已选择的回收参数
            $order_parameter = $this->flowerhua->get_join_all('whizmo_order_paramemter as a','whizmo_recovery_parameter as b',"a.recovery_parameter_id = b.id","a.order_id = $order_id",'a.*,b.parameter_name,b.parentid');
//            foreach ($order_parameter as $key => $value) {
//                $order_parameter[$key]['parent_parameter_name'] = $this->flowerhua->get_field('whizmo_recovery_parameter',"id = $value[parentid]","parameter_name");
//            }
            $order_notice = $this->flowerhua->get_field('whizmo_order_notice','id = 113','order_notice');
            $orderType = [26=>'Noon Collection (12:00PM - 16:00PM)',27=>'Evening Collection (16:00PM - 20:00PM)',28=>'Afternoon Collection (16:00PM - 18:00PM)', 29=>'Morning Collection (09:00AM - 13:00AM)'];  // 29 added for qoo10 pickup slot
            $order_delivery_number = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_delivery_number'); // get delivery number
            $order_addtime = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_addtime'); // get order time
            $time_passed = (int)((time() - $order_addtime) / 3600 / 24);



            $this->view->assign(array(
                'order'        => $order,
                'orderType'        => $orderType,
                'order_id'        => $order_id,
                'recovery_tips'        => $recovery_tips,
                'payment_arr'        => $payment_arr,
                'order_parameter'        => $order_parameter,
                'meta_title' => 'order_detail',
                'order_notice'=> $order_notice,
                'agent_check' => $agent_check, //pass check value for agent
                'order_delivery_number' => $order_delivery_number, //pass order delivery number
                'time_passed' => $time_passed, // pass order time
            ));
            $this->view->display('order_detail');
        }
        //提交完快递信息之后
        public function order_submit_okAction()
        {
            //check if agent
            if(get_cookie('member_id')){
                $agent_check = $this->flowerhua->get_field('member',"id = ".get_cookie('member_id'),'agent');
            }

            $order_id = $this->get('order_id');
            if($order_id){
                $order_sn = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_sn_number');
                $order_delivery_method_type = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_delivery_method_type');
                $order_delivery_number = $this->flowerhua->get_field('whizmo_order',"order_id = $order_id",'order_delivery_number'); //add delivery number info to differentiate qoo10 and roadbull order
            }
            $data = $this->flowerhua->get_one('system_selected',"id=1",'');
            $list = $this->flowerhua->get_all_list('whizmo_recovery_notice', 'sort desc', '0,3', '1=1', $ifcount = false);
            foreach ($list as $key => $value) {
              $list[$key]['content'] = htmlspecialchars_decode($value['content']);
            }


            /*********************************************
             * 
             *    Pikcupp integration
             * 
             * 
             ********************************************/

            $data = array(
                "orderId" => $order_id,
            );

            $apiResponse = callAPI('POST', '/pickupp/findByOrderId',$data);
            $json = json_decode($apiResponse->getBody(),true);

            $pickupDate = date('Y-m-d h:i A',strtotime($json[0]['pickupDatetime']));
            $pickupDateRange  = date('h:i A',strtotime($json[0]['pickupDatetime']."+ 3hours"));
            $merchantDefinedCode = $json[0]['merchantDefinedCode'];

            $this->view->assign(array(
                'order_sn'        => $order_sn,
                'meta_title' => 'Order Confirmed!',
                'data'=>$data,
                'list'=>$list,
                'merchantDefinedCode' => $merchantDefinedCode,
                'pickupTime'=> $pickupDate,
                'pickupDateRange' => $pickupDateRange,
                'order_delivery_method_type'=>$order_delivery_method_type,
                'order_delivery_number' => $order_delivery_number, //add delivery number info to differentiate qoo10 and roadbull order
                'agent_check' => $agent_check, //pass check value for agent
            ));
            $this->view->display('order_submit_ok');
        }

        //获取文字
        function getContentAction(){
            $id = $this->post('id');
            $content = $this->flowerhua->get_field('whizmo_recovery_notice',"id = $id",'content');
            die(json_encode(['code'=>1,'des'=>htmlspecialchars_decode($content)]));
        }










        //文章页
        public function detail_and_helpAction(){
            $category_id = $this->get('category_id');
            if($category_id){
                $data = $this->flowerhua->get_one('category',"catid = $category_id",'content,catname,catid');
            }
            $this->view->assign(array(
                'data'        => $data,
                'category_id'        => $category_id,
                'meta_title' => $data['catname'],
            ));
            $this->view->display('detail_and_help');
        }
    }