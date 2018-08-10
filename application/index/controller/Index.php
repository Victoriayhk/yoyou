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

       $str = '{"data":[{"name":"yxc","url":"www.hahaha.com"},{"name":"yxc2","url":"wwwss"}],"code":0,"message":"very good"}';
        $res2 = json_decode($str, true);
        var_dump($res2);
        echo "code : ";
        echo $res2["message"];
        echo "\n";
        echo $res2["data"][0]["name"];
        echo "\n";
        echo $res2["data"][0]["url"];
    	return json($res);
    }
    public function get_name()
    {       
 

    }

    public function is_registered()
    {
        $arr = json_decode($_GET['data'], true);
        $res = \think\Db::table('t_user')->where('vuid', $arr["vuid"]);
        if ($res != null)
        {
            $retjson["errno"] = 0;
            $retjson["data"]["is_registered"] = 1;
            $retjson["data"]["user_id"] = $res["user_id"];
            $retjson["data"]["user_name"] = $res["user_name"];
            $retjson["data"]["email"] = $res["email"];
        }
        else
        {
            $retjson["errno"] = 0;
            $retjson["data"]["is_registered"] = 0;
        }
        return json($retjson);

    }
    public function get_verification_code()
    {
        $arr = json_decode($_GET['data'], true);
        $email = $arr["email"];
        // 发邮件操作

        $retjson["errno"] = 0;
        return json($retjson);
    }

}






