<?php
namespace Card\Controller;
use Think\Controller;
class IndexController extends Controller {
    public function index()
    {
        $this->redirect("http://www.baidu.com");
    }
}
