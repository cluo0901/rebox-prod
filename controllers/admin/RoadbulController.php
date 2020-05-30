<?php

    class RoadbulController extends Admin {
        public $flowerhua;
        public $header;
        public $user_roadbul;//用户信息
        public $order_status;//订单所有状态
        public $roadbul_url; //接口地址
        public function __construct() {
            parent::__construct();
            $this->flowerhua    = $this->model('flowerhua');
            $this->roadbul_url = 'http://sandcds.roadbull.com/api';
            if(get_cookie('roadbul_token')){
                $this->header = array(
                    'authorization'=> 'Bearer '.get_cookie('roadbul_token'),
                    'content-type' => 'application/json'
                );
            }else{
//            set_cookie('back_url',$_SERVER['REQUEST_URI'],30);
//            echo '<script>window.location.href="index.php?s=admin&c=roadbul&a=roadbul_login&back_url='.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'";</script>';
            }
            $this->user_roadbul = array(
                'name'=>'testname',
                'zipcode'=>'099976',
                'address'=>'testaddress',
                'mobilephone'=> '99999999',
            );
            $this->order_status = array(
                0=> 'Order is Submitted',                          //订单已提交
                1=> 'Order is Confirmed',                          //订单已确认
                2=> 'Order is Rejected',                           //订单已拒绝
                3=> 'Item is Collected',                           //项目收集
                4=> 'Pickup Failed',                               //取货失败/拾取失败
                5=> 'Item is in Warehouse',                        //项目在仓库中
                6=> 'Delivery in progress',                        //交货进行中
                7=> 'Item is Delivered',                           //已交货
                8=> 'Order is Cancelled',                          //订单已取消
                9=> 'Delivery Failed',                             //发货失败
                10=> 'Order is Moved to Special Jobs for Pickup',  //订单转移到特殊的运输
                11=> 'Order is Moved to Special Jobs for Delivery',//订单转移到特殊的交付作业
                12=> 'En-route to Pickup',                         //在运输途中
                13=> 'Item is Picked up again',                    //再次操作
                14=> 'Re-Delivery in Progress',                    //正在派送
                15=> 'Item is Returned to Sender',                 //项目已返回给寄件人
                16=> 'Failed Delivery in Warehouse',               //仓库交付失败
                17=> 'Item is Re-Delivered',                       //项目再次交货
                18=> 'Re-Delivery Failed',                         //交货失败
                19=> '9 Return to Sender in progress',             //正在返回寄件人
                20=> 'Return to Sender',                           //退回寄件人
                21=> 'Return to Sender Failed',                    //退回寄件人失败
                22=> 'Lost',                                       //已丢失
                23=> 'Damaged',                                    //已损坏
            );

        }

        public function roadbul_loginAction()
        {
            $back_url = $this->get('back_url');
            $userinfo['UserName']  = $this->site['ROADBUL_USERNAME'];
            $userinfo['Password']  = $this->site['ROADBUL_PASSWORD'];
            $userinfo['RememberMe']= 'true';
            $obj = _post($this->roadbul_url.'/accounts/login',$userinfo);
            $userinfo_array = json_decode($obj, true);
            if($userinfo_array['Code']==0){
                set_cookie('roadbul_token',$userinfo_array['Token'],(strtotime($userinfo_array['ExpiredOn'])-time()));
                echo '<script>window.location.href="'.$back_url.'";</script>';
            }else{
                echo("$userinfo_array[Message]");die;
            }
        }
        //roadbul Api获取包裹类型
        public function roadbul_indexAction(){
            $obj = _post($this->roadbul_url.'/orders/producttypes','',$this->header);
            $roadbul_array = json_decode($obj, true);
//            dd($roadbul_array['ProductTypes']);//打印此数组
            $this->view->assign(array(
                'roadbul_array' => $roadbul_array['ProductTypes'],
                'roadbul_token' => get_cookie('roadbul_token'),
            ));
            $this->view->display('admin/admin_roadbul_test');
        }
        //roadbul Api获取尺寸大小数据
        public function ajax_ProductTypeId2SizesAction(){
            $ProductTypeId = $this->post('ProductTypeId');
            $postarr = array(
                'ProductTypeId'=>$ProductTypeId,
            );
            $obj = _post($this->roadbul_url.'/orders/sizes',json_encode($postarr),$this->header);
            $roadbul_array = json_decode($obj, true);
            if($roadbul_array['Message']){
                die(json_encode(array('code'=>400,'result_err'=>$roadbul_array['Message'])));
            }else{
                $result = '';
                $result .= '<option value="">Please choose Size</option>';
                foreach ($roadbul_array as $key => $value) {
                    $result .= '<option value="'.$value['Id'].'">'.$value['SizeName'].'</option>';
                }
                die(json_encode(array('code'=>1,'result'=>$result)));
            }
        }
        //roadbul Api获取尺寸大小服务
        public function ajax_SizeId2ServicesAction(){
            $SizeId = $this->post('SizeId');
            $ProductTypeId = $this->post('ProductTypeId');
            $postarr = array(
                'SizeId'=>$SizeId,
                'ProductTypeId'=>$ProductTypeId,
            );
            $obj = _post($this->roadbul_url.'/orders/services',json_encode($postarr),$this->header);
            $roadbul_array = json_decode($obj, true);
            if($roadbul_array['Message']){
                die(json_encode(array('code'=>400,'result_err'=>$roadbul_array['Message'])));
            }else{
                $result = '';
                $result .= '<option value="">Please choose Services</option>';
                foreach ($roadbul_array as $key => $value) {
                    $result .= '<option value="'.$value['Id'].'">'.$value['ServiceName'].'</option>';
                }
                die(json_encode(array('code'=>1,'result'=>$result)));
            }
        }
        //roadbul Api获取收货/发货时间
        public function ajax_ServicesId2TimeAction(){
            $ServiceId = $this->post('ServiceId')?$this->post('ServiceId'):2;
            $obj = _post($this->roadbul_url.'/orders/timeoptions/'.$ServiceId,'',$this->header);
            $roadbul_array = json_decode($obj, true);
            if($roadbul_array['Message']){
                die(json_encode(array('code'=>400,'result_err'=>$roadbul_array['Message'])));
            }else{
                $resultPickup = '';
                $resultPickup .= '<option value="">Please choose Pickup Time</option>';
                $resultDelivery = '';
                $resultDelivery .= '<option value="">Please choose Delivery Time</option>';
                foreach ($roadbul_array['AvailablePickupTimes'] as $key => $value) {
                    $resultPickup .= '<option value="'.$value['Value'].'">'.$value['Text'].'</option>';
                }
                foreach ($roadbul_array['AvailableDeliveryTimes'] as $key => $value) {
                    $resultDelivery .= '<option value="'.$value['Value'].'">'.$value['Text'].'</option>';
                }
                die(json_encode(array('code'=>1,'resultPickup'=>$resultPickup,'resultDelivery'=>$resultDelivery)));
            }
        }
        //roadbul Api下单/edit order
        public function ajax_saveorder_bookAction(){
            $FormorTo = $this->post('FormorTo') ? 2 : 1;// 1->从卖手机人的地址收 2->发货给回收商
            $isEdit = $this->post('edit') ? 1 : '';
            $data = array();
            if($isEdit === 1){//edit order
                $data['OrderNumber'] = $this->post('OrderNumber');
                //1->edit order 2->cancel order
                $data['Action'] = $this->post('Action') ? 2 : 1;
            }
            //else add order book
            if($FormorTo === 1){
                $data['FromName']           = 'FromName';
                $data['FromZipCode']        = '099661';
                $data['FromAddress']        = 'FromAddress';
                $data['FromMobilePhone']    = '00009999';
                $data['ToName']             = $this->user_roadbul['name'];
                $data['ToZipCode']          = $this->user_roadbul['zipcode'];
                $data['ToAddress']          = $this->user_roadbul['address'];
                $data['ToMobilePhone']      = $this->user_roadbul['mobilephone'];
            }else if($FormorTo === 2){
                $data['ToName']           = 'FromName';
                $data['ToZipCode']        = '099661';
                $data['ToAddress']        = 'FromAddress';
                $data['ToMobilePhone']    = '00009999';
                $data['FromName']             = $this->user_roadbul['name'];
                $data['FromZipCode']          = $this->user_roadbul['zipcode'];
                $data['FromAddress']          = $this->user_roadbul['address'];
                $data['FromMobilePhone']      = $this->user_roadbul['mobilephone'];
            }


//        $data['ProductTypeId']      = $this->post('ProductTypeId');
//        $data['ServiceId']          = $this->post('ServiceId');
//        $data['SizeId']             = $this->post('SizeId');
//        $data['PickupTimeSlotId']   = $this->post('PickupTimeSlotId');
//        $data['DeliveryTimeSlotId'] = $this->post('DeliveryTimeSlotId');
            $data['ProductTypeId']      = 2;
            $data['ServiceId']          = 1;
            $data['SizeId']             = 3;
            $data['PickupTimeSlotId']   = 26;
            $data['DeliveryTimeSlotId'] = 5;
            $data['PickupDate']         = date('d/m/Y',time()+3600*24);
            $data['DeliveryDate']       = date('d/m/Y',time()+3600*48);
            $obj = _post($this->roadbul_url.'/orders/book',json_encode($data),$this->header);
            $roadbul_array = json_decode($obj, true);
            dd($roadbul_array);
            if($roadbul_array['Code']==0){
                die(json_encode(array('code'=>1,'result'=>$roadbul_array)));
            }else{
                die(json_encode(array('code'=>$roadbul_array['Code'],'result_err'=>$roadbul_array['Message'])));
            }
        }
        //roadbul Api查询订单
        public function ajax_get_roadbulorder_infoAction(){
//        $OrderNumber = $this->post('OrderNumber');
            $OrderNumber = '8DA62436CE';
            $obj = _post($this->roadbul_url.'/orders/tracking/'.$OrderNumber,'',$this->header);
            $roadbul_array = json_decode($obj, true);
            dd($roadbul_array);
            if($roadbul_array['Code']==0){
                die(json_encode(array('code'=>1,'result_err'=>$roadbul_array['Orders']['Status'])));
            }else{
                die(json_encode(array('code'=>$roadbul_array['Code'],'result_err'=>$roadbul_array['Message'])));
            }
//        {
//            "Id": 509003,
//             "ConsignmentNumber": "8546ED7514",
//             "ToSignature": null,
//             "Reason": "it",
//             "Status": 14,
//             "ConfirmedOn": "2017-05-26T14:23:06.6",
//             "CollectedOn": "2017-05-26T14:26:43.913",
//             "ProceededOn": "2017-05-26T14:30:38.277",
//             "ProceededOutOn": "2017-05-26T14:31:57.713",
//             "DeliveredOn": "2017-05-26T14:41:22.653",
//             "CanceledOn": null,
//             "CreatedOn": "2017-05-26T14:23:06.487",
//             "RedeliveryCount": 1
//             }
        }

















    }