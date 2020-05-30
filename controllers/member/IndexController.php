<?php

class IndexController extends Member {
    public $linkage;
    public $order;
    public $member;

    public function __construct() {
		parent::__construct();
		$this->linkage = $this->model('linkage');
        $this->member = $this->model('member');
        $this->order = $this->model('order');

		if(!get_cookie('openid')){
		 $this->oauthlogin();//微信授权跟获取
		}
		
	}
	
	public function indexAction() {
        $member_id = (int)get_cookie('member_id');
        if(empty($member_id)){
            $back = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('member/login/member');
            $this->redirect(url('member/login/member', array('back' => urlencode($back))));
        }
        /*
        *会员资料获取
        */
        $where = '1=1';
        $member = $this->member->get_memberinfo($member_id,$where);
        $where = "member_id = $member_id";
        $ordernum = $this->order->get_num($where);
        $dianpingnum = $this->yuangong_dianping->get_num2($where);
        $quanyinum = $this->quanyi->get_num($where);
        $pinglun = $this->pinglun->get_num($where);
	    $this->view->assign(array(
	        'pinglunnum'=>$pinglun,
            'member'   => $member,
            'ordernum'   => $ordernum,
            'dianpingnum'   => $dianpingnum,
            'quanyinum'   => $quanyinum,
			'indexc'     => 5,
			'model'		 => $this->get_model(),
			'form'       => $this->getFormMember(),
		    'meta_title' => lang('会员中心'),
		));
	    $this->view->display(member_info);
	}
	
	
	
	/**
	 * 退出登录
	 */
	public function outAction() {
		if (get_cookie('member_id'))            set_cookie('member_id', 0);
		if (get_cookie('member_code'))          set_cookie('member_code', 0);
		echo "<script>alert('".lang('m-log-11')."');location.href='index.php';</script>";
	}
	
	public function lianxiAction(){
		$this->view->assign(array(
		    'meta_title' => lang('联系客服'),
		));
	    $this->view->display('member/member_lianxi');
	}
	
	
	
	
	
	
	
	/**
     * 文件上传
     * @param  $fields		上传字段 'file'
     * @param  $type		文件类型  array(jpg,gif)
     * @param  $size		文件大小  MB
     * @param  $img			图片配置参数
     * @param  $mark		图片水印
     * @param  $admin		是否来自后台
     * @param  $stype		上传方式  swf或者ke
     * @param  $ofile		原文件
     * @param  $document	后台栏目归档目录
     * @return Array		返回数组
     */
	private function upload($fields, $type, $size=10, $img=null, $mark=true, $admin=0, $stype=null, $ofile=null, $document=null) {
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
            $uid = (int)get_cookie('yuangong_id');
		} else {
            //$this->attMsg(lang('att-0'), $stype);
            $uid = 0;
            $patp = 'uploadfiles/guest/';
        }
	    $upload->set($_FILES[$fields])->set_limit_size(1024*1024*$size)->set_limit_type($type);
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