<?php
    class Take_timeController extends Common
    {
        public $flowerhua;
        public function __construct()
        {
            parent::__construct();
            $this->flowerhua = $this->model('flowerhua');
        }

        public function indexAction(){
            $arr = [];
            $arr['desc'] = date('Y-m-d H:i:s',time()).'执行定时任务~';
            $arr['time'] = date('Y-m-d H:i:s',time());
            $this->flowerhua->db_add('whizmo_time',$arr);

            $where = "order_status = 1 and order_addtime < ".(time()-3600*24*3);
            $order_list = $this->flowerhua->get_all_where("whizmo_order",$where,"order_id,order_addtime");
            foreach ($order_list as $key => $value) {
                $this->flowerhua->db_update("whizmo_order",['order_status'=>-1],"order_id = ".$value['order_id']);
            }
        }

    }