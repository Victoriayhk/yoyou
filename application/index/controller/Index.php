<?php
namespace app\index\controller;
use phpmailer\Phpmailer;

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

    /**
     * tp5邮件
     * @param
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function email() {
        $toemail='675314520@qq.com';
        $name='你好';
        $subject='QQ邮件发送测试';
        $content='恭喜你，邮件测试成功。';
        dump(send_mail($toemail,$name,$subject,$content));
    }

    public function send_email_plugin()
    {
        $address = "675314520@qq.com";
        $title = "hello";
        $message = "msg";
        $Email = new Phpmailer();
        $Email->IsSMTP();
        $Email->CharSet = 'UTF-8';
        $Email->AddAddress($address);
        $Email->Body = $message;
        $Email->From = "yexuechao_914@163.com";
        $Email->FromName = "悠邮";
        $Email->Subject = $title;
        $Email->Host = "smtp.163.com";
        $Email->SMTPAuth = true;
        $Email->Username = "yexuechao_914@163.com";
        $Email->Password = "yxc30718";
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






