<?php

// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

use User\Api\UserApi;

include DOC_ROOT_PATH . 'uc_client/config_ucenter.php'; //引入ucenter数据库配置文件
include DOC_ROOT_PATH . 'uc_client/client.php'; //引入ucenter客户端文件

/**
 * 后台首页控制器
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
class PublicController extends \Think\Controller {

    /**
     * 后台用户登录
     * @author 麦当苗儿 <zuojiazi@vip.qq.com>
     */
    public function login($username = null, $password = null, $verify = null) {
        if (IS_POST) {
            /* 检测验证码 TODO: */
            // if(!check_verify($verify)){
            //  $this->error('验证码输入错误！');
            // }
            $username = safe_replace($username); //过滤
            //list($uc_uid, $username, $password, $email) = uc_user_login($username, md5($password));
            $Member = D('Member');
            $admin = $Member->field("nickname,uid")->where(array("nickname" => $username, "password" => md5($password)))->find();
           
            $uid = $admin["uid"];
            /* 调用UC登录接口登录 */
            if (0 < $uid) { //UC登录成功
                /* 登录用户 */
                if ($Member->login($uid)) { //登录用户
                    //TODO:跳转到登录前页面
                    $this->success('登录成功！', U('Admin/Index/index'));
                } else {
                    $this->error($Member->getError());
                }
            } else { //登录失败
                switch ($uc_uid) {
                    case -1: $error = '用户不存在或被禁用！';
                        break; //系统级别禁用
                    case -2: $error = '密码错误！';
                        break;
                    default: $error = '未知错误！';
                        break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else {//dump(is_login());exit;
            if (is_login()) {
                $this->redirect('Index/index');
            } else {
                /* 读取数据库中的配置 */
                $config = S('DB_CONFIG_DATA');
                if (!$config) {
                    $config = D('Config')->lists();
                    S('DB_CONFIG_DATA', $config);
                }
                C($config); //添加配置

                $this->display();
            }
        }
    }

    /* 退出登录 */

    public function logout() {
        if (is_login()) {
            D('Member')->logout();
            session('[destroy]');
            $this->success('退出成功！', U('login'));
        } else {
            $this->redirect('login');
        }
    }

    public function verify() {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

}
