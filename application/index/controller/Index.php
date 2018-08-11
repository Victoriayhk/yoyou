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

    /**
     * tp5邮件
     * @param
     * @author staitc7 <static7@qq.com>
     * @return mixed
     */
    public function email($email, $random_code) {
        $toemail = $email;
        $name ='尊敬的客户';
        $subject = '悠邮来信';
        $content = "<b> 您的验证码是：$random_code </b>";
        $errno = send_mail($toemail,$name,$subject,$content);
        return $errno;
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
            $retjson["errno"] = 1000;
            $retjson["data"]["is_registered"] = 0;
        }
        return json($retjson);

    }
    public function get_verification_code()
    {
        $arr = json_decode($_GET['data'], true);
        $email = $arr["email"];
        $vuid = $arr["vuid"];
        // 发邮件操作
        $random_code = get_random_code();
        Session::set($vuid."random_code", $random_code);
        Session::set($vuid."random_code_timestamp", time());
        $retjson["errno"] = email($email, $random_code);
        return json($retjson);
    }


    public function get_random_code($length = 4) 
    {
        $str = substr(md5(time()), 0, $length);//md5加密，time()当前时间戳
        return $str;
    }

    public function check_verification_code()
    {
        $arr = json_decode($_GET['data'], true);
        $vuid = $arr["vuid"];
        $email = $arr["email"];
        $check_code = $arr["check_code"];
        $user_img = $arr["user_img"];
        $user_name = $arr["user_name"];

        // yanzheng
        if (Session::has($vuid."random_code") && Session::get($vuid."random_code") == $check_code)
        {
            // db
            Session::delete($vuid."random_code");
            $data = ['user_name' => $user_name, 'user_img' => $user_img, 'email' => $email];
            $res = \think\Db::table('t_user')->insert($data);
            if ($res != 1)
            {
                $retjson['errno'] = 2000;
            }
            else
            {
                $retjson['errno'] = 0;
                $retjson['data']['user_id'] = \think\Db::name('t_user')->getLastInsID();
            }

        }
        else
        {
            $retjson['errno'] = 1001;
        }
        return json($retjson);

    }

    public function get_addr_and_poster()
    {
        $retjson['errno'] = 0;
        $res = \think\Db::table('t_poster')->select();
        for ($i = 0; $i < count($res); $i++)
        {
            $retjson['data']['poster'][$i]['poster_id'] = $res[$i]['poster_id'];
            $retjson['data']['poster'][$i]['poster_url'] = $res[$i]['poster_url'];
            $retjson['data']['poster'][$i]['poster_desc'] = $res[$i]['poster_desc'];
            $retjson['data']['poster'][$i]['poster_name'] = $res[$i]['poster_name'];
        }

        $res = \think\Db::table('t_address')->select();
        for ($i = 0; $i < count($res); $i++)
        {
            $retjson['data']['address'][$i]['address_id'] = $res[$i]['address_id'];
            $retjson['data']['address'][$i]['address'] = $res[$i]['addr'];
        }

        return json($retjson);

    }

    public function commit_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $user_id = $arr["user_id"];
        $poster_id = $arr["poster_id"];
        $friend_name = $arr["friend_name"];
        $address_id = $arr["friend_addr_id"];
        $friend_email = $arr["friend_email"];
        $content = $arr["content"];
        $pub_time = time();
        // poster day
        $res = \think\Db::table('t_poster')->where("id", $poster_id)->find();
        if (!$res)
        {
            $retjson['errno'] = 4000;
        }
        else
        {
            $expect_second = $res['expect_time'] * 24 * 60 * 60;
            $min_day = $expect_second - 3 * 24 * 60 * 60;
            $max_day = $expect_second + 3 * 24 * 60 * 60;
            $valid_time = mt_rand($min_day, $max_day);
            $sum_time = $pub_time + $valid_time;

            // 随机状态出来

            $retjson['errno'] = 0;
        }
        return json($retjson);

    }

    public function upload_image()
    {
        $file = request()->file("file_name");
        if ($file)
        {
            $info = $file->move(ROOT_PATH . 'public' . DS ."uploads/mail_img");
            if ($info)
            {
                $imgurl = Request::domain(). DS . 'public' . DS . 'uploads/mail_img' . DS . $info->getSaveName();
                $retjson['errno'] = 0;
                $retjson['data']['url'] = $imgurl;
            }
            else
            {
                $retjson['errno'] = 3000;
                $retjson['errmsg'] = $file->getError();
            }
        }
        return json($retjson);
    }

    public function get_all_send_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $user_id = $arr['user_id'];

        // db poster
        $poster_res = \think\Db::table('t_poster')->select();
        for ($i = 0; $i < count($poster_res); $i++)
        {
            $poster_arr[$poster_res[$i]['poster_id']] = $poster_res[$i]['poster_url'];
        }

        // db mail
        $res = \think\Db::table('t_mail')->where("user_id", $user_id)->select();

        // db state
        $all_mail_id = array();
        for ($i = 0; $i < count ($res); $i++)
        {
            array_push($all_mail_id, $res[$i]['mail_id'])
        }
        $all_mail_state = \think\Db::table("t_mail_state")->whereIn('mail_id', $all_mail_id)->select();
        $mail_to_state = array();
        for ($i = 0; $i < count($all_mail_state); $i++)
        {
            $mstate_id = $all_mail_state[$i]['mstate_id'];
            $mail_id = $all_mail_state[$i]['mail_id'];
            $mail_to_state[$mail_id][$mstate_id]['mstate_id'] = $mstate_id;
            $mail_to_state[$mail_id][$mstate_id]['mail_id'] = $all_mail_state[$i]['mail_id'];
            $mail_to_state[$mail_id][$mstate_id]['start_time'] = $all_mail_state[$i]['start_time'];
            $mail_to_state[$mail_id][$mstate_id]['end_time'] = $all_mail_state[$i]['end_time'];
            $mail_to_state[$mail_id][$mstate_id]['description'] = $all_mail_state[$i]['description'];
        }

        

        for ($i = 0; $i < count($res); $i++)
        {
                $mail['mail_id'] = $res[$i]['mail_id'];
                $mail['user_id'] = $res[$i]['user_id'];
                $poster_id = $res[$i]['poster_id'];
                $mail['poster_url'] = $poster_res[$poster_id];
                $mail['friend_name'] = $res[$i]['friend_name'];
                $mail['friend_email'] = $res[$i]['friend_email'];
                $mail['create_time'] = $res[$i]['pub_time'];
                $mail['arrive_time'] = $res[$i]['arrive_time'];
                $mail['is_read'] = $res[$i]['is_read'];
                
            if ($res[$i]['is_read'] == 0 && $res[$i]['arrive_time'] >= time())
            {
                // 未读最前
            }
            else
            {
                // 未到
                foreach ($mail_to_state as $key => $value)
                {
                    
                }

            }
        }
        

        // 已读

    }
}






