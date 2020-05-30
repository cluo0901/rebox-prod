<?php

class UserController extends Admin {
	public $linkage;
    public function __construct() {
		parent::__construct();
		$this -> linkage = $this -> model('linkage');
	}

    // 会员同步转移
    public function synAction() {
        //变量初始化
        $is_syn = FALSE;//是否已经转移
        $domain = '';//高级版域名
        $has_config = FALSE;//是否有配置文件
        $newDataBase = NULL;//高级版数据库连接
        $newUserModels = array();//新用户组

        if(file_exists(APP_ROOT.'cache\member.lock'))
        {
            $is_syn = TRUE;
            $domain = explode(';',file_get_contents(APP_ROOT.'cache\member.lock'));
            $domain = $domain[1];
        }
        if(file_exists((APP_ROOT.'config\database.user.ini.php')))
        {
            $has_config = TRUE;
            $config = self::load_config('database.user');
            $newDataBase = $this->load->database($config,TRUE);
            $newUserModels = $newDataBase->get('member_group')->result_array();
        }
        $userModel = $this->db->where('typeid','2')->get('model')->result_array();


        if (IS_POST) {
            $options = $this->post('data');
            $users = $this->db->get('member')->result_array();//当前成员表中的数据

            $count = 0;
            foreach ($users as $user) {

                $data['username'] = $user['username'];
                $data['password'] = $user['password'];
                $data['salt'] = $user['salt'];
                $data['email'] = $user['email'];
                $data['avatar'] = $user['avatar'];
                if($options['exp'])
                    $data['experience'] = $user['credits'];
                $data['regtime'] = $user['regdate'];
                $data['regip'] = $user['regip'];
                $temp = 1;

                foreach ($options['model'] as $key => $value)
                {
                    if($user['modelid'] == $key)
                    {
                        $temp = $value;
                    }
                }

                $data['groupid'] = $temp;

                $exist = $newDataBase->where('username',$data['username'])->count_all_results('member');

                if(!$exist) {
                    $newDataBase->insert('member', $data);
                    $count ++;
                }
            }

            if($count) {
                file_put_contents(APP_ROOT.'cache\member.lock',date('Y-m-d h:i:s',time()).';'.$options['domain']);
                $this->adminMsg(lang('success') . ',转移' . $count . '条会员数据！', url('admin/index/main'), 3, 1, 1);
            }

        }


        $this->template->assign(
            array(
                'userModels' => $userModel,
                'newUserModels' => $newUserModels,
                'is_syn' => $is_syn,
                'domain' => $domain,
                'has_config' => $has_config
            )
        );

        $this->template->display('admin/user_syn');
    }

	public function indexAction() {

	    $roleid = $this->get('roleid');
	    $data   = $this->user->get_user_list($roleid, 1);
		foreach($data as $k=>$v){
			$data[$k]['province'] = $this->linkage->get_name($v['province_id']);
			$data[$k]['city'] = $this->linkage->get_name($v['city_id']);
			$data[$k]['diqu'] = $data[$k]['province'].",".$data[$k]['city'];
		}
	    $this->view->assign('list', $data);
		$this->view->display('admin/user_list');
	}

	public function addAction() {
	    $role = $this->user->get_role_list();
	    if ($this->post('submit')) {
	        $username = $this->post('username');
	        if (!$username) $this->adminMsg(lang('a-use-0'));
	        if ($this->user->getOne('username=?', $username)) $this->adminMsg(lang('a-use-2'));
			$usermenu = $this->post('menu');
			$menu     = array();
			foreach ($usermenu['name'] as $id=>$v) {
			    if ($v && $usermenu['url'][$id]) {
				    $menu[$id] = array('name'=>$v, 'url'=>$usermenu['url'][$id]);
				}
			}
	        $data = array(
				'province_id' => $this->post('province_id'),
				'city_id' => $this->post('city_id'),
	            'username' => $username,
	            'realname' => $this->post('realname'),
	            'email'    => $this->post('email'),
	            'roleid'   => $this->post('roleid'),
				'site'	   => $this->post('site'),
				'usermenu' => array2string($menu),
	        );
			$data['salt']     = substr(md5(time()), 0, 10);
	        $data['password'] = md5(md5($this->post('password')) . $data['salt'] . md5($this->post('password')));
	        $this->user->insert($data);
	        $this->adminMsg(lang('success'), url('admin/user/index/'), 3, 1, 1);
	    }
		$province = $this->linkage->get_all_list();//地区
		$this->view->assign('province',$province);

	    $this->view->assign('role', $role);
	    $this->view->assign('pwd', '');

	    $this->view->display('admin/user_add');
	}

	public function editAction() {
	    $role = $this->user->get_role_list();
	    if ($this->post('submit')) {
	        $userid   = (int)$this->post('userid');
	        $data = array(
                'username' => $this->post('username'),
	            'realname' => $this->post('realname'),
	            'email'    => $this->post('email'),
                'logo'	   => $this->post('logo'),
	        );
            if ($this->post('password')) {
                $data['salt']     = substr(md5(time()), 0, 10);
                $data['password'] = md5(md5($this->post('password')) . $data['salt'] . md5($this->post('password')));
            }
	        if($userid){
                $this->user->update($data, 'userid=' . $userid);
            }else{
                $this->user->insert($data);
            }
	        $this->adminMsg(lang('success'), '', 3, 1, 1);
	    }
	    $userid = $this->get('userid');
	    if($userid){
            $user   = $this->user->find($userid);
	        if (empty($user)) $this->adminMsg(lang('a-use-3'));
        }
        $province = $this->linkage->get_all_list();//地区
        if(!empty($user['province_id'])){
            $citylist = $this->linkage->get_all_list($user['province_id']);
            $this->view->assign('citylist',$citylist);
        }
	    $this->view->assign(array(
			'province' => $province,
	        'data' => $user,
	        'role' => $role,
			'menu' => string2array($user['usermenu']),
	    ));
	    $this->view->display('admin/user_edit');
	}
	public function delAction() {
	    $userid = (int)$this->get('userid');
	    if (empty($userid)) $this->adminMsg(lang('a-use-3'));
	    if ($this->userinfo['userid'] == $userid) $this->adminMsg(lang('a-use-4'));
	    $this->user->delete('userid=' . $userid);
	    $this->adminMsg(lang('success'), url('admin/user/index/'), 3, 1, 1);
	}
}