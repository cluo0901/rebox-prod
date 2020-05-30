<?php

class IndexController extends Common
{
    public $flowerhua;
    public function __construct()
    {
        parent::__construct();
        $this->flowerhua = $this->model('flowerhua');
    }
//        $params = get_defined_vars();
//        dd($params);
//		if(!get_cookie('openid')){
//		    $this->oauthlogin();//微信授权跟获取
//		}
    public function indexAction()
    {
//        set_cookie('member_id','258',3600000);
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
        $this->view->display('index');
    }
    public function searchAction(){
        $str = $this->post('str');
        if($str){
        $where = "recovery_title like '%$str%'";
        $list = $this->flowerhua->get_all_where('whizmo_recovery',$where,'recovery_type_id,recovery_title,id');
        }
        die(json_encode(array('list'=>$list)));
    }

































    /**
     *                    _ooOoo_
     *                   o8888888o
     *                   88" . "88
     *                   (| -_- |)
     *                    O\ = /O
     *                ____/`---'\____
     *              .   ' \\| |// `.
     *               / \\||| : |||// \
     *             / _||||| -:- |||||- \
     *               | | \\\ - /// | |
     *             | \_| ''\---/'' | |
     *              \ .-\__ `-` ___/-. /
     *           ___`. .' /--.--\ `. . __
     *        ."" '< `.___\_<|>_/___.' >'"".
     *       | | : `- \`.;`\ _ /`;.`/ - ` : | |
     *         \ \ `-. \_ __\ /__ _/ .-` / /
     * ======`-.____`-.___\_____/___.-`____.-'======
     *                    `=---='
     *
     * .............................................
     *          佛祖保佑             永无BUG
     */
}