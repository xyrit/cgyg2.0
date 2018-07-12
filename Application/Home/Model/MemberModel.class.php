<?php

namespace Home\Model;

use Think\Model;
use User\Api\UserApi;

/**
 * 文档基础模型
 */
class MemberModel extends Model {
    /* 用户模型自动完成 */

    protected $_auto = array(
        array("login", 0, self::MODEL_INSERT),
        array("reg_ip", "get_client_ip", self::MODEL_INSERT, "function", 1),
        array("reg_time", NOW_TIME, self::MODEL_INSERT),
        array("last_login_ip", 0, self::MODEL_INSERT),
        array("last_login_time", 0, self::MODEL_INSERT),
        array("status", 1, self::MODEL_INSERT),
    );

    //获取个人中心用户信息
    public function get_member_info($uid = 0) {
        $field_member = "face,nickname,uid,mobile,floor(account)account,red_packet,brokerage,brokerage";
        $where = array("uid" => $uid);
        $info = M("member")->field($field_member)->where($where)->find();
        return $info;
    }

    //获取个人中心用户信息
    public function get_my_goods($uid = 0) {
        $where = "c.uid=" . $uid;
        $count = M('collect c')->where($where)->count(); //查询总条数
        $p = getpage($count, 2); //页码
        $field_goods = "c.lottery_id,c.pid,c.create_time,d.cover_id,d.title,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,p.path";
        $list = M('collect c')->field($field_goods)->join('cg_document d on c.pid=d.id')->join('cg_lottery_product lp on c.lottery_id=lp.lottery_id')->join('left join cg_picture p on d.cover_id=p.id')->where($where)->order('c.id')->limit($p->firstRow, $p->listRows)->select();
        return array($list, $p->show());
    }

    /**
     * 登录指定用户
     * @param  integer $uid 用户ID
     * @return boolean      ture-登录成功，false-登录失败
     */
    public function login($uid) {
        /* 检测是否在当前应用注册 */
        $user = $this->field(true)->find($uid);
        if (!$user) { //未注册
            /* 在当前应用中注册用户 */
            $Api = new UserApi();
            $info = $Api->info($uid);
            $user = $this->create(array("nickname" => $info[1], "status" => 1));
            $user["uid"] = $uid;
            if (!$this->add($user)) {
                $this->error = "前台用户信息注册失败，请重试！";
                return false;
            }
        } elseif (1 != $user["status"]) {
            $this->error = "用户未激活或已禁用！"; //应用级别禁用
            return false;
        }
        /* 登录用户 */
        $this->autoLogin($user);

        /* 登录历史 */
       // history($uid);
        /* 登录购物车处理函数 */
        addintocart($uid);
        //记录行为
        action_log("user_login", "member", $uid, $uid);

        return true;
    }
    
    
    /**
     * 用户注册
     * 
     */
    public function register($params){
        $data = array();
        $data['uc_uid'] = intval($params['uc_uid']);
        $data['mobile'] = $params['mobile'];
        
        $result = $this->data($data)->add(); 
        return $result;      
    }
    
    
    /**
     * 获取用户信息
     * 
     */ 
    public function getUserInfo($params){
        return $this->where($params)->find();    
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout() {
        session("user_info", null);
        session("uid", null);
        session("user_auth", null);
        unset($_SESSION['cart']);
        session("user_auth_sign", null);
        cookie('password', null);
        cookie('username', null);
    }

    public function update() {
        $data = $this->create();
        if (!$data) { //数据对象创建错误
            return false;
        }

        /* 添加或更新数据 */
        if (empty($data['id'])) {
            $res = $this->add();
        } else {
            $res = $this->save();
        }



        return $res;
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    private function autoLogin($user) {
        /* 更新登录信息 */
        $data = array(
            "uid" => $user["uid"],
            "login" => array("exp", "`login`+1"),
            "last_login_time" => NOW_TIME,
            "last_login_ip" => get_client_ip(1),
        );
        $this->save($data);
    
        /* 记录登录SESSION和COOKIES */
        $auth = array(
            "uid" => $user["uc_uid"],
            "username" => $user["mobile"],
            "nickname" => $user["nickname"],
            "user_id" => $user["uid"]
        );
    
        cookie("user_auth", json_encode($auth));
        cookie("user_auth_sign", data_auth_sign($auth));
        //var_dump(json_decode(cookie('user_auth'), true));exit;
    }

    public function uid() {
        $user = session("user_auth");
        $uid = $user["uid"];
        return $uid;
    }

}
