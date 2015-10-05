<?php
namespace Home\Controller;
use Common\Controller\ListPage;

class EmployeeController extends ListPage
{
    public function __construct()
    {
        parent::__construct();
        if (get_user_info("admin") != 4)
            $this->redirect("Index/index");
    }
    public function index()
    {
        $this->display();
    }

}
