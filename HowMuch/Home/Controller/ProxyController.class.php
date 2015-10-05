<?php
namespace Home\Controller;
use Common\Controller\ListPage;

class ProxyController extends ListPage 
{
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 8)
            $this->redirect("Index/index");
    }

    public function index()
    {
        $this->display();
    }

}
