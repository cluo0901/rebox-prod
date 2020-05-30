<?php

class Update_recordController extends Common
{
    public $flowerhua;
    public function __construct()
    {
        parent::__construct();
        $this->flowerhua = $this->model('flowerhua');
    }

    public function indexAction()
    {
        $this->view->assign(array(
            'list' => $list,
        ));
        $this->view->display('admin/update_record');
    }

    public function auth_useAction()
    {
        $this->view->assign(array(
            'abc'        => $abc,
        ));
        $this->view->display('admin/update_use');
    }

}