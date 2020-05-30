<?php
require(SMS_DIR.'APIClient2.php');
class LoginController extends Common
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

    public function login_systemAction()
    {
        $phone = get_cookie('phone');
        $password = get_cookie('password');
        $this->view->assign(array(
            'meta_title' => 'login',
            'phone'=>$phone,
            'password'=>$password,
        ));
        $this->view->display('member/login');
    }

    public function registerAction()
    {
        $recovery_parameters = $this->get('recovery_parameters');
        $info = $this->flowerhua->get_one('whizmo_recovery_clause','id=1','personal_information_title3,personal_information3');
        $info['personal_information3']=htmlspecialchars_decode($info['personal_information3']);
        $this->view->assign(array(
            'recovery_parameters'=>$recovery_parameters,
            'meta_title' => 'register',
            'info'=>$info,
        ));
        $this->view->display('member/register');
    }
    //发送短信验证码
    public function ajax_sendcodeAction(){
//        die(json_encode(array('code' => 1)));
        if(IS_AJAX) {
            $code = randomkeys(4);
            $phone = $this->post('phone');

            $member = $this->flowerhua->get_field('member',"phone = $phone",'id');
           if($member){
               die(json_encode(array('code' => 2)));
           }
            $prefix = strlen($phone) > 10 ? '86' : '65';
            // sending to a set of numbers
            if (!empty($phone)) {
                $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                if ($result->error->code == 'SUCCESS') {
                    set_cookie('sms_code', $code, 60 * 10);
                    set_cookie('sms_phone', $phone, 60 * 10);
                    die(json_encode(array('code' => 1)));
                } else {
                    die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                }
            }
        }
    }

    //send sms on kiosk mode on free pickup page
    public function ajax_sendcode_kioskAction(){
//        die(json_encode(array('code' => 1)));
        if(IS_AJAX) {
            $code = randomkeys(4);
            $phone = $this->post('phone');
            $prefix = strlen($phone) > 10 ? '86' : '65';
            // sending to a set of numbers
            if (!empty($phone)) {
                $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                if ($result->error->code == 'SUCCESS') {
                    set_cookie('sms_code', $code, 60 * 10);
                    set_cookie('sms_phone', $phone, 60 * 10);
                    die(json_encode(array('code' => 1)));
                } else {
                    die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                }
            }
        }
    }

    // send sms coupon
    public function ajax_sendcouponAction(){
//        die(json_encode(array('code' => 1)));
        if(IS_AJAX) {
            
            //get code from database
            $conn = mysqli_connect('localhost', 'whizmotech', 'H@ppy2018', 'coupon_auto');

            // if (mysqli_connect_errno()){
            //     echo 'failed to connect '. mysqli_connect_errno();
            // }

            $query = 'SELECT * FROM coupon WHERE status=0 limit 1';

            $result = mysqli_query($conn, $query);

            $codes = mysqli_fetch_all($result, MYSQLI_ASSOC);
            foreach ($codes as $code) {
                $coupon = $code['no'];
                $update_id = $code['id'];
            }

            $sql = "DELETE FROM coupon WHERE id={$update_id}";    

            if ($conn->query($sql) === TRUE) {
                mysqli_free_result($result);
            } else {
                echo "Error deleting record: " . $conn->error;
            }
            mysqli_free_result($result);

            mysqli_close($conn);



            // $coupon = 'SN123456789'; // need to get from database
            $phone = $this->post('phone');

            // $member = $this->flowerhua->get_field('member',"phone = $phone",'id');
//            if($member){
//                die(json_encode(array('code' => 2)));
//            }
            $prefix = strlen($phone) > 10 ? '86' : '65';
            // sending to a set of numbers
            if (!empty($phone)) {
                $result = $this->smsApi->sendSms('Welcome to Rebox.com.sg! Here is your $10 signup bonus code: '.$coupon.'. Apply the code to get $10 more for selling your phone!', $prefix . $phone);
                // if ($result->error->code == 'SUCCESS') {
                //     set_cookie('sms_code', $code, 60 * 10);
                //     set_cookie('sms_phone', $phone, 60 * 10);
                //     die(json_encode(array('code' => 1)));
                // } else {
                //     die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                // }
            }
        }
    }

    //验证验证码
    //ajax判断验证码是否正确
    public function ajaxcodeAction()
    {
        $code = trim($this->post('code'));
        $phone = trim($this->post('phone'));
        if ($code != get_cookie('sms_code') || $phone != get_cookie('sms_phone')) {
            echo 0;exit;
        } else {
            set_cookie('sms_code', null);
            set_cookie('sms_phone', null);
            echo 1;exit;
        }
    }
    
    //保存注册的会员信息
    public function ajax_save_memberinfoAction(){
        if (IS_AJAX) {
            $phone = $this->post('phone');
            $password = $this->post('password');
            $member = $this->flowerhua->get_field('member',"phone = $phone",'id');
            if(!$member){
                $data = [];
                $data['phone'] = $phone;
                $data['password'] = md5(md5($password).md5($password));
                $data['addtime'] = time();
                $return = $this->flowerhua->db_add('member',$data);
                set_cookie('member_id',$return,3600*24*7);
                if ($return) {
                    die(json_encode(array('code' => 1)));
                }
            }else{
                die(json_encode(array('code' => -3)));
            }
        }
    }
    //会员登录到系统
    public function ajax_login_systemAction(){
        if (IS_AJAX) {
            $phone = $this->post('phone');
            $pwd = $this->post('password');
            $password = md5(md5($pwd).md5($pwd));
            $check = $this->post('check');
            //验证手机号和密码
            $member = $this->flowerhua->get_one('member',"phone = $phone",'id,password');
            if(!$member){
                die(json_encode(array('code' => -1)));
            }else if($member['password']!=$password){
                die(json_encode(array('code' => -2)));
            }else{
                set_cookie('member_id',$member['id'],3600*24*7);
                if($check=='true'){
                    set_cookie('phone',$phone,3600*24*30);
                    set_cookie('password',$pwd,3600*24*30);
                }else{
                    set_cookie('phone',null);
                    set_cookie('password',null);
                }
                die(json_encode(array('code' => 1)));
            }
        }
    }









    //找回密码-发送短信验证码
    public function ajax_backsendcodeAction(){
        if(IS_AJAX) {
            $code = randomkeys(5);
            $phone = $this->post('phone');
            $member = $this->flowerhua->get_field('member',"phone = $phone",'id');
            if(!$member){
                die(json_encode(array('code' => 2)));
            }
            $prefix = strlen($phone) > 10 ? '86' : '65';
            set_cookie('back_sms_code', 1233, 60 * 10);
            set_cookie('back_sms_phone', $phone, 60 * 10);

            // sending to a set of numbers
            if (!empty($phone)) {
                $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                if ($result->error->code == 'SUCCESS') {
                    set_cookie('back_sms_code', $code, 60 * 10);
                    set_cookie('back_sms_phone', $phone, 60 * 10);
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
            echo 0;exit;
        } else {
            set_cookie('back_sms_code', null);
            set_cookie('back_sms_phone', null);
            echo 1;exit;
        }
    }
    //会员修改密码
    public function ajax_edit_member_passwordAction(){
        if (IS_AJAX) {
            $phone = $this->post('phone');
            $password = $this->post('password');
            $data = [];
            $data['password'] = md5(md5($password).md5($password));
            $return = $this->flowerhua->db_update('member',$data,"phone = $phone");
            if ($return) {
                die(json_encode(array('code' => 1)));
            }
        }
    }
    //会员退出系统
    public function login_outAction(){
        if (IS_AJAX) {
            set_cookie('member_id',null);
            die(json_encode(array('code' => 1)));
        }
    }

}