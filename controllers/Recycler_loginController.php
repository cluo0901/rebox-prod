<?php
     require(SMS_DIR . 'APIClient2.php');
    class Recycler_loginController extends Common
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
        }

        public function registerAction()
        {
            if (IS_AJAX) {
                $data = [];
                $data['company_name'] = $this->post('company_name');
                $data['person'] = $this->post('person');
                $data['phone'] = $this->post('phone');
                $data['addtime'] = time();
                $phone = $this->fei->get_field('whizmo_recycler', 'phone = ' . $data['phone'], 'phone');
                if ($phone) {
                    die(json_encode(array('code' => 2, 'info' => 'The phone number has been submitted')));
                } else {
                    $this->fei->db_add('whizmo_recycler', $data);
                    die(json_encode(array('code' => 1)));
                }
            } else {
                $this->view->display('register');
            }
        }
        public function partner_loginAction()
        {
            if (IS_AJAX) {
                $username = $this->post('username');
                $info = $this->fei->get_one('whizmo_recycler', "username = "."'".$username."'", 'password,id,status');
                if($info['status']==1){
                    die(json_encode(array('code' => -6)));
                }
                $password = $this->post('password');
                $password = md5(md5($password) . md5($password));
                if (empty($info)) {
                    die(json_encode(array('code' => -1)));
                }
                if ($password != $info['password']) {
                    die(json_encode(array('code' => 2)));
                } else {
                    $check = $this->post('check');
                    if ($check == 'true') {
                        set_cookie('username', $username, 3600*24*30);
                        set_cookie('password', $this->post('password'), 3600*24*30);
                    }else{
                        set_cookie('username', null);
                        set_cookie('password', null);
                    }
                    set_cookie('recycler_id', $info['id'], 3600*24);
                    die(json_encode(array('code' => 1, 'username' => $username, 'password' => $this->post('password'))));
                }
            } else {
                $username = get_cookie('username');
                $password = get_cookie('password');
                $this->view->assign(array('username' => $username, 'password' => $password));
                $this->view->display('partner_login');
            }
        }

        public function ajax_sendcodeAction()
        {
            if (IS_AJAX) {
                $code = randomkeys(5);
                $phone = $this->post('phone');
                $prefix = strlen($phone) > 10 ? '86' : '65';
                if (!empty($phone)) {
                    $result = $this->smsApi->sendSms('Rebox One-Time Password (OTP) is '.$code.'. Have a Great Day!', $prefix . $phone);
                    if ($result->error->code == 'SUCCESS') {
                        set_cookie('back_sms_code', $code, 60 * 15);
                        set_cookie('back_sms_phone', $phone, 60 * 15);
                        die(json_encode(array('code' => 1)));
                    } else {
                        die(json_encode(array('code' => 3, 'sms_err' => $result->error->description)));
                    }
                }
            }
        }
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
    }