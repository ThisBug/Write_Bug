<?php
namespace app\index\controller;
use think\Controller;

class Love extends Controller
{
    public function index()
    {
        $arr = [
            '此时',
            '此刻',
            '我在',
            '想你',
            '❤❤'
        ];

        $this->assign('arr',$arr);
        return $this->fetch();
    }
}
