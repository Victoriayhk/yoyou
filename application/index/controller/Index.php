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
        $str = '{"data":[{"name":"yxc","url":"www.hahaha.com"},{"name":"yxc2","url":"wwwss"}],"code":0,"message":"very good"}';
        $res2 = json_decode($str);
        var_dump($res2);
        echo "code : ";
        echo $res2["message"];
        echo "\n";
        echo $res2["data"][0]["name"];
        echo "\n";
        echo $res2["data"][0]["url"];

    }

    public function is_registered(){

    }
    
}
