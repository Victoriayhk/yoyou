<?php
namespace app\index\controller;

class Index
{
    public function index()
    {
	echo "aaa";
    }
    public function hello()
    {
        $res = \think\Db::table('t_user')->select();
	//var_dump($res);
	$data[0] = ['name'=>'yxc', 'url'=>'www.hahaha.com'];
	$data[1] = ['name'=>'yxc2', 'url'=>'wwwss'];
	$res = ['data'=>$data, 'code'=>0, 'message'=>'very good'];
	return json($res);
    }
    public function get_name()
    {       
        echo "haha";
    }
    
}
