<?php

namespace Wap\Model;

use Think\Model;

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
        $member = M("member");
        $where = array("uid" => $uid);
        $field_member = "face,nickname,uid,mobile,account,red_packet,brokerage,brokerage";
        $info = $member->field($field_member)->where($where)->find();
        if (empty($info["nickname"]) || empty($info["face"])) {
            $picture = "http://" . $_SERVER['HTTP_HOST'] . "/Public/Home/images/user/user.png";
            $info["face"] = (empty($info["face"])) ? $picture : $info["face"];
            $info["nickname"] = (empty($info["nickname"])) ? str_replace(substr($info["mobile"], 3, 4), "****", $info["mobile"]) : $info["nickname"];
            M("Member")->where(array("uid" => $uid))->save(array("face" => $picture, "nickname" => $info["nickname"]));
        }
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
    public function login($uid, $uc_uid, $mobile) {

        /* 检测是否在当前应用注册 */
        $uid = (floatval($uid) < 1) ? $this->register(array('uc_uid' => $uc_uid, 'mobile' => $mobile, 'status' => 1)) : $uid;
        $user = $this->field(true)->find(floatval($uid));
        if (!$user) { //未注册  执行插入操作
        } elseif (1 != $user["status"]) {
            $this->error = "用户未激活或已禁用！"; //应用级别禁用
            return false;
        }

        /* 登录用户 */
        $this->autoLogin($user);
        /* 登录历史 */
        //history($uid);
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
    public function register($params) {

        $result = $this->data($params)->add();
        return $result;
    }

    /**
     * 获取用户信息
     * 
     */
    public function getUserInfo($params) {
        return $this->where($params)->find();
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout() {
        session("user_auth", null);
        unset($_SESSION['cart']);
        session("user_auth_sign", null);

        cookie('user_auth', null);
        cookie('user_auth_sign', null);
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
    }

    public function uid() {
        $user = session("user_auth");
        $uid = $user["uid"];
        return $uid;
    }

}
