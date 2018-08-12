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
	    var_dump($res);
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
        $res = \think\Db::table('t_user')->where('vuid', $arr["vuid"])->find();
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
        $retjson['errno'] = 0;
        $arr = json_decode($_GET['data'], true);
        $email = $arr["email"];
        $vuid = $arr["vuid"];
        // 发邮件操作
        $random_code = $this->get_random_code();
        \think\Cache::set($vuid."random_code", $random_code);
        \think\Cache::set($vuid."random_code_timestamp", time());
        
        $retjson["check_code"] =  \think\Cache::get($vuid."random_code");
        $retjson["errno"] = $this->email($email, $random_code);
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
        //echo "vuid:".$vuid."\n";
        //echo "check_code".$check_code."\n";
        //var_dump(\think\Cache::get());
        //exit();
        if (\think\Cache::has($vuid."random_code") && \think\Cache::get($vuid."random_code") == $check_code)
        {
            // db
            \think\Cache::rm($vuid."random_code", NULL);
            $data = ['user_name' => $user_name, 'user_img' => $user_img, 'email' => $email, 'vuid' => $vuid];
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

    public function get_poster()
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

        return json($retjson);

    }

    public function get_addr()
    {   
        $retjson['errno'] = 0;
        $res = \think\Db::table('t_address')->select();
        for ($i = 0; $i < count($res); $i++)
        {
            $retjson['data']['address'][$i]['address_id'] = $res[$i]['address_id'];
            $retjson['data']['address'][$i]['address'] = $res[$i]['addr'];
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

    public function upload_image()
    {   
        var_dump(request()->file("file"));
        $retjson['errno'] = 0;
        $file = request()->file("file");
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
            array_push($all_mail_id, $res[$i]['mail_id']);
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
            $mail_to_state[$mail_id][$mstate_id]['mood'] = $all_mail_state[$i]['mood'];
            $mail_to_state[$mail_id][$mstate_id]['mood_time'] = $all_mail_state[$i]['mood_time'];
        }
        
        $retjson['data']['arrived_unread_mail'] = array();
        $retjson['data']['unarrived_mail'] = array();
        $retjson['data']['arrived_read_mail'] = array();
        //var_dump($poster_arr);
        for ($i = 0; $i < count($res); $i++)
        {   
            $mail = array();
            $mail['mail_id'] = $res[$i]['mail_id'];
            $mail['user_id'] = $res[$i]['user_id'];
            $poster_id = $res[$i]['poster_id'];
            $mail['poster_url'] = $poster_arr[$poster_id];
            $mail['friend_name'] = $res[$i]['friend_name'];
            $mail['friend_email'] = $res[$i]['email'];
            $mail['create_time'] = $res[$i]['pub_time'];
            $mail['arrive_time'] = $res[$i]['arrive_time'];
            $mail['is_read'] = $res[$i]['is_read'];
                
            if ($res[$i]['is_read'] == 0 && $res[$i]['arrive_time'] <= time())
            {
                // 未读最前
                //echo "arrived_unread:". $res[$i]['mail_id']."\n";
                array_push($retjson['data']['arrived_unread_mail'], $mail);
            }
            else if ($res[$i]['arrive_time'] > time())
            {
                // 未到
                //echo "unarrived :" . $res[$i]['mail_id'] . "\n";
                foreach ($mail_to_state[$mail['mail_id']] as $key => $value)
                {
                    $now = time();
                    if ($now < $value['end_time'] && $now >= $value['start_time'])
                    {
                        $mail['poster_status'] = $value['description'];
                        $mail['poster_status_stime'] = $value['start_time'];
                        $mail['poster_status_etime'] = $value['end_time'];
                        $mail['mood'] = $value['mood'];
                        $mail['mood_time'] = $value['mood_time'];
                        break;
                    }
                }
                array_push($retjson['data']['unarrived_mail'], $mail);
            }
            else
            {
                // 已读
                //echo "arrived_read :" . $res[$i]['mail_id'] . "\n";
                array_push($retjson['data']['arrived_read_mail'], $mail);
            }
        }
        
        if (!empty($retjson['data']['unarrived_unread_mail'])){
            $sort_val = array_column($retjson['data']['arrived_unread_mail'], 'arrive_time');
            array_multisort($sort_val, SORT_DESC, $retjson['data']['arrived_unread_mail']);
        
        }
        if (!empty($retjson['data']['unarrived_mail']))
        {
            $sort_val = array_column($retjson['data']['unarrived_mail'], 'create_time');
            array_multisort($sort_val, SORT_ASC, $retjson['data']['unarrived_mail']);
        }
        if (!empty($retjson['data']['arrived_read_mail']))
        {
            $sort_val = array_column($retjson['data']['arrived_read_mail'], 'arrive_time');
            array_multisort($sort_val, SORT_DESC, $retjson['data']['arrived_read_mail']);
        }
        
        $retjson['errno'] = 0;
        return json($retjson);
    }

    public function get_send_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $mail_id = $arr['mail_id'];

        $poster_res = \think\Db::table('t_poster')->select();
        for ($i = 0; $i < count($poster_res); $i++)
        {
            $poster_arr[$poster_res[$i]['poster_id']] = $poster_res[$i]['poster_url'];
        }

        $address_res = \think\Db::table('t_address')->select();
        for ($i = 0; $i < count($address_res); $i++)
        {
            $address_arr[$address_res[$i]['address_id']] = $address_res[$i]['addr'];
        }

        $res = \think\Db::table('t_mail')->where("mail_id", $mail_id)->find();
        $retjson['data']['user_id'] = $res['user_id'];
        $retjson['data']['mail_id'] = $res['mail_id'];
        $retjson['data']['poster_url'] = $poster_arr[$res['poster_id']];
        $retjson['data']['friend_name'] = $res['friend_name'];
        $retjson['data']['friend_addr'] = $address_arr[$res['address_id']];
        $retjson['data']['arrive_time'] = $res['arrive_time'];
        $retjson['data']['create_time'] = $res['pub_time'];
        $retjson['data']['content'] = $res['mail_content'];
        // 
        $mail_state = \think\Db::table("t_mail_state")->where('mail_id', $res['mail_id'])->select();
        $state = array();

        for ($i = 0; $i < count($mail_state); $i++)
        {
            $mstate_id = $mail_state[$i]['mstate_id'];
            $state[$mstate_id]['mstate_id'] = $mstate_id;
            $state[$mstate_id]['mail_id'] = $mail_state[$i]['mail_id'];
            $state[$mstate_id]['start_time'] = $mail_state[$i]['start_time'];
            $state[$mstate_id]['end_time'] = $mail_state[$i]['end_time'];
            $state[$mstate_id]['description'] = $mail_state[$i]['description'];
            $state[$mstate_id]['mood'] = $mail_state[$i]['mood'];
            $state[$mstate_id]['mood_time'] = $mail_state[$i]['mood_time'];
        }
        if ($res['arrive_time'] > time())
        {
            // 未送到
            foreach ($state as $key => $value)
            {
                $now = time();
                if ($now < $value['end_time'] && $now >= $value['start_time'])
                {
                    $retjson['data']['poster_status'] = $value['description'];
                    $retjson['data']['poster_status_stime'] = $value['start_time'];
                    $retjson['data']['poster_status_etime'] = $value['end_time'];
                    $retjson['data']['mood'] = $value['mood'];
                    $retjson['data']['mood_time'] = $value['mood_time'];
                    break;
                }
            }
        }
        else
        {
            // 已送到
            ksort($state);
            $time_line = array();
            foreach ($state as $key => $value)
            {
                $one_state['poster_status'] = $value['description'];
                $one_state['poster_status_stime'] = $value['start_time'];
                $one_state['poster_status_etime'] = $value['end_time'];
                $one_state['mood'] = $value['mood'];
                $one_state['mood_time'] = $value['mood_time'];
                array_push($time_line, $one_state);
            }
            $retjson['data']['time_line'] = $time_line;
        }
        $retjson['errno'] = 0;
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
        // poster DB
        $poster = \think\Db::table('t_poster')->where("id", $poster_id)->find();
        if (!$res)
        {
            $retjson['errno'] = 4000;
        }
        else
        {
            // 随机得到arrive_time, 在预期到达时间左右
            $expect_second = $poster['expect_time'] * 24 * 60 * 60;
            $eps_second = intval(ceil($expect_second * 0.2 * (1.0 - min($expect_time, 500) * 0.002)));
            $min_second = $expect_second - $eps_second;
            $max_second = $expect_second + $eps_second;
            $valid_time = mt_rand($min_second, $max_second);
            $arrive_time = $pub_time + $valid_time;

            // mail加到数据库
            $mail['user_id'] = $user_id;
            $mail['poster_id'] = $poster_id;
            $mail['email'] = $friend_email;
            $mail['pub_time'] = $pub_time;
            $mail['arrive_time'] = $arrive_time;
            $mail['mail_content'] = $content;
            $mail['is_read'] = 0;
            $mail['friend_name'] = $friend_name;
            $mail['address_id'] = $address_id;
            $ret = \think\Db::table('t_mail')->insert($mail);
            if ($ret != 0)
            {
                $retjson['errno'] = 5000;
                $retjson['errmsg'] = "插入数据失败";
            }
            $mail_id = \think\Db::table('t_mail')->getLastInsID();

            // 随机状态的地点, 当前mail_state没有存这个字段
            $all_addr = \think\Db::table("t_address")->select();
            $address_tuple = array_rand(all_addr);
            $addr = $address['addr'];
            // mail_state['address_id'] = $address['address_id']

            // 获取信使的名字, 当前没有这个字段
            $poster_name = $poster['poster_name'];

            // 状态描述
            $COMMON_MAIL_STATES_DESCRIBE = [
                $poster_name . '终于到达了'. $addr . ', 不幸的是, 遭到暴风雨, 接下来只能小步伐前进了',
                $poster_name . '在森林里迷路了, 但愿信使能找到路',
                '天气晴朗, 还搭上了好友的顺风快车' . $poster_name . '快马加鞭地赶去',
                '信件在经过' . $addr . '被污损了, 还好遇到李师傅, 李师傅在故宫修过文物',
                '路漫漫其修远兮, 吾将上下而求索, 可把' . $poster_name . '累坏了',
                '已经到' . $addr . '了, 胜利就在眼前, 就快到了呢, ' . $poster_name . '加快了前进的步伐'];

            // 随机mail状态序列
            $num_state = 0;
            $point_time = $pub_time;
            if ($expect_time > 50) {
                $min_during_time = 5 * 24 * 60 * 60;
                $max_during_time = 9 * 24 * 60 * 60;
            } else {
                $min_during_time = 1 * 24 * 60 * 60;
                $max_during_time = 3 * 24 * 60 * 60;
            }
            $mail_state['mail_id'] = $mail_id;
            while ($point_time < $arrive_time)
            {
                $mail_state['start_time'] = $point_time;
                $during_time = mt_rand($min_during_time, $max_during_time);
                if ($point_time + $during_time + 24 * 60 * 60 > $arrive_time)
                {
                    $mail_state['end_time'] = $arrive_time;
                    $mail_state['description'] = end($COMMON_MAIL_STATES_DESCRIBE);
                } else
                {     
                    $mail_state['end_time'] = $point_time + $during_time;
                    $mail_state['description'] = array_rand($COMMON_MAIL_STATES_DESCRIBE);
                }

                $ret = \think\Db::table('t_mail_state')->insert($mail_state);
                if (!$ret)
                {
                    $retjson['errno'] = 5000;
                    $retjson['errmsg'] = "错误";
                    return json($retjson);
                }

                $point_time = $mail_state['end_time'] + 1;
            }
            $retjson['errno'] = 0;
        }
        return json($retjson);

    }

    public function get_all_receive_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $user_id = $arr['user_id'];
        $res = \think\Db::table("t_user")->where('user_id', $user_id)->find();

        $poster_res = \think\Db::table('t_poster')->select();
        for ($i = 0; $i < count($poster_res); $i++)
        {
            $poster_arr[$poster_res[$i]['poster_id']] = $poster_res[$i]['poster_url'];
        }

        $email = $res['email'];
        $now = time();
        $mail_data = \think\Db::table("t_mail")->where('email', $email)->where('arrive_time', '<', $now)->select();
        $arr_friend_id = array();
        for ($i = 0; $i < count($mail_data); $i++)
        {
            array_push($arr_friend_id, $mail_data[$i]['user_id']);
        }
        //var_dump($mail_data);
        $arr_user_info = \think\Db::table("t_user")->whereIn('user_id', $arr_friend_id)->select();
        $user_info_by_id = array();
        for ($i = 0; $i < count($arr_user_info); $i++)
        {
            $tmp_user_id = $arr_user_info[$i]['user_id'];
            $user_info_by_id[$tmp_user_id]['user_id'] = $arr_user_info[$i]['user_id'];
            $user_info_by_id[$tmp_user_id]['user_name'] = $arr_user_info[$i]['user_name'];
            $user_info_by_id[$tmp_user_id]['email'] = $arr_user_info[$i]['email'];
            $user_info_by_id[$tmp_user_id]['user_img'] = $arr_user_info[$i]['user_img'];
        }
        $retjson['data'] = array();
        for($i = 0; $i < count($mail_data); $i++)
        {
            $friend_id = $mail_data[$i]['user_id'];
            $one_mail = array();
            $one_mail['friend_user_id'] = $friend_id;
            $one_mail['friend_name'] = $user_info_by_id[$friend_id]['user_name'];
            $one_mail['friend_email'] = $user_info_by_id[$friend_id]['email'];
            $one_mail['user_img'] = $user_info_by_id[$friend_id]['user_img'];
            $one_mail['arrive_time'] = $mail_data[$i]['arrive_time'];
            $one_mail['create_time'] = $mail_data[$i]['pub_time'];
            $one_mail['is_read'] = $mail_data[$i]['is_read'];
            $one_mail['poster_url'] = $poster_arr[$mail_data[$i]['poster_id']];
            array_push($retjson['data'], $one_mail);
        }
        
        $sort_val = array_column($retjson['data'], 'arrive_time');
        //var_dump($sort_val); 
        array_multisort($sort_val, SORT_DESC, $retjson['data']);
        return json($retjson);
    }

    public function append_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $mail_id = $arr['mail_id'];
        $mood = $arr['mood'];
        $retjson['errno'] = 0;
        // 找到当前时间所在的mail_state
        $cur_time = time();
        //$where_condition['mail_id'] = $mail_id;
        //$where_condition['_string'] = 'mstate_start_time <= '$cur_time' AND '$cur_time' <= mstate_end_time';
        $mail_state = \think\Db::table("t_mail_state")->where('mail_id', $mail_id)->where('start_time', '<=', $cur_time)->where('end_time', '>', $cur_time)->find();

        // 没有对应的mail_state
        if ($mail_state == null)
        {
            // 奇怪的事情发生了
            $retjson['errno'] = 5000;
            $retjson['errmsg'] = "这里是找到没有对应的mail_state的出错";
            return json($retjson);
        }
        
        // update对应的mood
        $data['mood'] = $mood;
        $data['mood_time'] = $cur_time;
        $ret = \think\Db::table("t_mail_state")->where('mstate_id', $mail_state['mstate_id'])->update($data);

        if (!$res)
        {
            $retjson['errno'] = 5001;
        }
        return $retjson;
    }

    public function get_receive_mail()
    {
        $arr = json_decode($_GET['data'], true);
        $mail_id = $arr['mail_id'];
        $poster_res = \think\Db::table('t_poster')->select();
        for ($i = 0; $i < count($poster_res); $i++)
        {
            $poster_arr[$poster_res[$i]['poster_id']] = $poster_res[$i]['poster_url'];
        }

        $address_res = \think\Db::table('t_address')->select();
        for ($i = 0; $i < count($address_res); $i++)
        {
            $address_arr[$address_res[$i]['address_id']] = $address_res[$i]['addr'];
        }



        $res = \think\Db::table('t_mail')->where("mail_id", $mail_id)->find();
        $user_info = \think\Db::table("t_user")->where('user_id', $res['user_id'])->find();

        $retjson['data']['user_id'] = $res['user_id'];
        $retjson['data']['mail_id'] = $res['mail_id'];
        $retjson['data']['poster_url'] = $poster_arr[$res['poster_id']];
        $retjson['data']['friend_name'] = $user_info['user_name'];
        $retjson['data']['friend_img'] = $user_info['user_img'];
        $retjson['data']['friend_email'] = $user_info['email'];
        $retjson['data']['arrived_time'] = $res['arrive_time'];
        $retjson['data']['create_time'] = $res['pub_time'];
        $retjson['data']['content'] = $res['mail_content'];
        $retjson['data']['is_read'] = $res['is_read'];

        $mail_state = \think\Db::table("t_mail_state")->where('mail_id', $res['mail_id'])->select();
        $state = array();
        for ($i = 0; $i < count($mail_state); $i++)
        {
            $mstate_id = $mail_state[$i]['mstate_id'];
            $state[$mstate_id]['mstate_id'] = $mstate_id;
            $state[$mstate_id]['mail_id'] = $mail_state[$i]['mail_id'];
            $state[$mstate_id]['start_time'] = $mail_state[$i]['start_time'];
            $state[$mstate_id]['end_time'] = $mail_state[$i]['end_time'];
            $state[$mstate_id]['description'] = $mail_state[$i]['description'];
            $state[$mstate_id]['mood'] = $mail_state[$i]['mood'];
            $state[$mstate_id]['mood_time'] = $mail_state[$i]['mood_time'];
        }        
        ksort($state);
        $time_line = array();
        foreach ($state as $key => $value)
        {
            $one_state['poster_status'] = $value['description'];
            $one_state['poster_status_stime'] = $value['start_time'];
            $one_state['poster_status_etime'] = $value['end_time'];
            $one_state['mood'] = $value['mood'];
            $one_state['mood_time'] = $value['mood_time'];
            array_push($time_line, $one_state);
        }
        $retjson['data']['time_line'] = $time_line;

       /** if ($res['is_read'] == 0)
        {
            // update
            $updateres = \think\Db::table("t_mail")->where('mail_id', $res['mail_id'])->update(['is_read' => 1]);
            if ($updateres == 0)
            {
                $retjson['data'] = null;
                $retjson['errno'] = 4001;
                return json($retjson);
            }
        }*/

        $retjson['errno'] = 0;
        return json($retjson);
    } 

}

