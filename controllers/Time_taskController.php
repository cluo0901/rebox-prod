<?php

class Time_taskController extends Common
{
    public $flowerhua;
    public function __construct()
    {
        parent::__construct();
        $this->flowerhua = $this->model('flowerhua');
    }
    //定时任务
    public function indexAction()
    {
        //每月1号添加一次商品的最新售价
        $recovery_list = $this->flowerhua->get_all_where('whizmo_recovery',"1=1",'id,recovery_high_price');
        foreach ($recovery_list as $key => $value) {
            $array['recovery_prices'] = $value['recovery_high_price'];
            $array['recovery_id'] = $value['id'];
            $array['recovery_moths'] = date('n',time());
            $array['recovery_moths_title'] = translate(date('n',time()).'月份');
            $array['recovery_years'] = date('Y',time());
            $this->flowerhua->db_add('whizmo_recovery_prices',$array);
        }
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