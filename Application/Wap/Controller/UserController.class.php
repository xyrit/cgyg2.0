<?php

namespace Wap\Controller;

//include DOC_ROOT_PATH . 'uc_client/config_ucenter.php'; //引入ucenter数据库配置文件
//include DOC_ROOT_PATH . 'uc_client/client.php'; //引入ucenter客户端文件
include $_SERVER["DOCUMENT_ROOT"] . '/uc_client/config_ucenter.php';
include $_SERVER["DOCUMENT_ROOT"] . '/uc_client/client.php';

/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController {

    public function index() {
        if (!is_login()) {
            header('Location:' . U('User/login'));
        }
    }

    /* 注册页面 */

    public function register($mobile = "", $password = "", $repassword = "", $mobile_verify = "") {
        if (is_login()) {
            header('Location:' . U('Index/index'));
        }

        if (!C("USER_ALLOW_REGISTER")) {
            $this->error("注册已关闭");
        }
        //$username =safe_replace($username);//过滤

        if (IS_POST) { //注册用户
            $data = array('code' => 0, 'message' => '注册成功！');
            //验证手机验证码
//            $verify_info = D('verify')->getVerify($mobile, 1);
//            if ($mobile_verify !== $verify_info['verify']) {
//                $data['code'] = 1;
//                $data['message'] = '手机验证码错误！';
//            } else
            if ($password != $repassword) {///* 检测密码 */
                $data['code'] = 2;
                $data['message'] = '密码和重复密码不一致！';
            } else {
                $register_mobile = session("register_mobile");
                //验证提交密码时的手机号 与 短信验证码成功后session里面的手机号是否一致
                if (floatval($register_mobile) == floatval($mobile)) {
                    //注册
                    $uc_uid = uc_user_register($mobile, md5($password), $mobile . '@mobile.com');

                    if (intval($uc_uid) > 0) {
                        $ucsynlogin = uc_user_synlogin($uc_uid);
                        preg_match_all('/\s+src=\"([^\"]+)\"/isU', $ucsynlogin, $js_url);
                        $data['success_js'] = $js_url[1];
                        $nickname = str_replace(substr($mobile, 3, 4), "****", $mobile);
                        $picture = "http://" . $_SERVER['HTTP_HOST'] . "/Public/Home/images/user/user.png"; //默认图像
                        $uid = D('member')->register(array('uc_uid' => $uc_uid, 'nickname' => $nickname, 'mobile' => $mobile, 'password' => md5($password), 'face' => $picture, 'status' => 1));
                        A('Connect')->ZcToChange($uid);
                        if (0 < $uid) { //注册成功
                            D('member')->login($uid);
                        } else { //注册失败，显示错误信息
                            $data['code'] = 3;
                            $data['message'] = '注册失败！';
                            //$this->error($this->showRegError($uid));
                        }
                    } else {
                        $uc_user_register_config = array(
                            '-1' => '用户名不合法',
                            '-2' => '包含不允许注册的词语',
                            '-3' => '用户名已经存在',
                            '-4' => '格式有误',
                            '-5' => '不允许注册',
                            '-6' => '该 Email 已经被注册',
                        );
                        $data['code'] = 4;
                        $data['message'] = $uc_user_register_config[$uc_uid];
                    }
                } else {
                    $data['code'] = -7;
                    $data['message'] = '非法注册！';
                }
            }

            $this->ajaxReturn($data);
        } else {
            $this->meta_title = '会员注册';
            $this->display();
        }
    }

    /* 登录页面 */

    public function login($username = "", $password = "") {


        if (is_login()) {
            header('Location:' . U('Index/index'));
            exit;
        }

         dump(is_login());      exit;     
        $from_url = cookie('from_url');
        $URL = (empty($from_url)) ? cookie('from_url', $_SERVER["HTTP_REFERER"], 600) : $from_url;
        if (IS_POST) { //登录验证
            $rs_arr = $this->login_action($username, md5($password));

            if (is_array($rs_arr)) {    //UC登录成功  本地登录成功
                $data['code'] = 200;
                $data['success_js'] = $rs_arr['success_js'];
                $data['uc_uid'] = $rs_arr['uc_uid'];
                $data['url'] = $URL;
                $data['message'] = '登陆成功！';
                cookie('from_url', null);
            } else { //登录失败
                $data['code'] = 3;
                switch ($rs_arr) {
                    case -1: $data['message'] = "用户不存在或被禁用！";
                        break; //系统级别禁用
                    case -2: $data['message'] = "密码错误！";
                        break;
                    default: $data['message'] = "未知错误！";
                        break; // 0-接口参数错误（调试阶段使用）
                }
            }
            $this->ajaxReturn($data);
        } else {
            $this->meta_title = '会员登录';
            //显示登录表单
            $this->display();
        }
    }

    /*
     * 登录
     * mobile  password
     * return  1 -1 -2 -3
     */

    public function login_action($username = 0, $password = "") {

        list($uc_uid, $username, $password, $email) = uc_user_login($username, $password);

        if ($uc_uid > 0) { //UC登录成功

            /* 同步登陆 */
            $ucsynlogin = uc_user_synlogin($uc_uid);

            preg_match_all('/\s+src=\"([^\"]+)\"/isU', $ucsynlogin, $js_url);
            $data['success_js'] = $js_url[1];
            $data['uc_uid'] = $uc_uid;
            /* 同步登陆 */
            /* 本地用户登录 */
            $user_info = D('Member')->getUserInfo(array('uc_uid' => $uc_uid));
            $uid = $user_info['uid'];
            $Member = D("Member"); //dump($uid);exit;
            if ($Member->login($uid, $uc_uid, $username)) { //登录用户
                return $data;
            } else {
                return false;
            }
            /* 本地用户登录 */
        }
        return $uc_uid; //未知错误
    }

    /**
     * 验证手机验证码（跳转到下一步设置密码）
     * 
     */
    public function checkRegisterVerify($mobile = 0, $mobile_verify = '', $type = 1) {

        $data = array('code' => 200, 'message' => '验证成功！');
        //验证手机验证码
        $verify_info = D('verify')->getVerify(floatval($mobile), $mobile_verify, $type);

        if (empty($verify_info) || ($mobile_verify !== $verify_info['verify'])) {
            $data['code'] = 1;
            $data['message'] = '手机验证码错误！';
        } else {
            //将获取的缓存时间转换成时间戳加上600秒后与当前时间比较，小于当前时间即为过期 
            if (!empty($verify_info['creat_time']) && ((floatval($verify_info['creat_time']) + 600) < time())) {
                $data = array('code' => 500, 'message' => '短信验证码已过期，请重新获取！');
            }
            if (!empty($mobile_verify) && !empty($verify_info['verify'])) {//更改验证后的验证码状态
                M("verify")->where(array("cellphone" => floatval($mobile)))->save(array("status" => 1, "type" => -1));
            }
        }
        /* 修改手机号时使用 */
        if (($type == 3) && ($data["code"] == 200)) {
            $user = json_decode(cookie('user_auth'), true); //获取cookie里面的用户名手机号
            $username = floatval($user["username"]); //用户名手机号
            if ($username == $mobile) {
                session("username", $mobile);
            }
        }
        if (($type == 1) && ($data["code"] == 200)) {
            session("register_mobile", $mobile);
        }
        /* 修改手机号时使用 */
        $is_ajax = I('post.is_ajax', 0, 'intval');
        if ($is_ajax) {
            $this->ajaxReturn($data);
        } else {//dump($data);
            return $data;
        }
    }

    /**
     * 发送手机验证码
     * 
     */
    public function sendVerify($mobile = 0, $state = 0, $type = 1, $lottery_arr = array()) {
        $port_type = I("type"); //短信类型
        $type = (empty($port_type)) ? $type : $port_type;
        $data = array('code' => 200, 'message' => '发送成功！');
        $state = ($state == 0) ? (I("state", 0)) : $state;
        $mobile = (empty($mobile)) ? (I("mobile", 0)) : $mobile;
        if ($state < 2) {//state>=2时用于通用发送验证码
//            if ($state < 1) {
//                if (!check_verify($image_verify)) {
//                    $data['code'] = 1;
//                    $data['message'] = '图片验证码错误！';
//                    $this->ajaxReturn($data);
//                }
//            }
//            $user_info = uc_get_user($mobile);
//            if (intval($user_info[0]) > 0) {
//                $data['code'] = 2;
//                $data['message'] = '该手机号码已经注册！';
//                $this->ajaxReturn($data);
//            }
        }
        Vendor('Sms.Rest');
        //发送手机号码setAccount
        $sms_config = C('SMS');
        $sms = new \Vendor\SMS\Rest($sms_config['serverIP'], $sms_config['serverPort'], $sms_config['softVersion']);
        $sms->setAccount($sms_config['accountSid'], $sms_config['accountToken']);
        $sms->setAppId($sms_config['appId']);
        $verify = randomString(0, 4);
        $tiparr = ($type == 6) ? array($lottery_arr["nickname"], $lottery_arr["lottery_id"], $lottery_arr["product"]) : array($verify, 10);
        $template_id = $this->get_template_id($type);
        $send_result = $sms->sendTemplateSMS($mobile, $tiparr, $template_id);
        $send_result = (array) $send_result;
        if ($send_result['statusCode'] != '000000') {
            $data['code'] = 2;
            $data['message'] = $send_result['statusMsg'];
        } else {
            $add_data = array();
            $add_data['verify'] = $verify;
            $add_data['cellphone'] = $mobile;
            $add_data['creat_time'] = time();
            $add_data['type'] = $type;
            D('Verify')->verifyAdd($add_data);
        }

        $this->ajaxReturn($data);
    }

    /*
     * 
     * 获取短信模板id
     */

    public function get_template_id($type = 1) {
        switch ($type) {
            case 1://注册会员
                $templateid = '67474';
                break;
            case 2://忘记密码
                $templateid = '68276';
                break;
            case 3://修改手机号
                $templateid = '68277';
                break;
            case 4://增加收货地址
                $templateid = '68388';
                break;
            case 5://修改收货地址
                $templateid = '68279';
                break;
            case 6://中奖通知
                $templateid = '78615';
                break;
            case 15://绑定手机号
                $templateid = '68747';
                break;
            case 7://佣金提现
                $templateid = '77535';
                break;
            case 8://添加银行卡
                $templateid = '77536';
                break;
            case 9://修改银行卡
                $templateid = '77537';
                break;
            default:
                $templateid = '67474';
        }
        return $templateid;
    }

    /**
     * 验证手机号码是否注册过
     *
     */
    public function checkRegister($mobile) {
        $uc_center = uc_get_user($mobile);
        $data = array('code' => 0, 'message' => '已注册！');
        if ($uc_center[0] == 0) {
            $data = array('code' => 1, 'message' => '未注册！');
        }
        $this->ajaxReturn($data);
    }

    public function loginfromdialog($username = "", $password = "") {
        if (IS_POST) { //登录验证
            /* 调用UC登录接口登录 */
            $username = safe_replace($username); //过滤
            $user = new UserApi;
            $uid = $user->login($username, $password);
            if (0 < $uid) { //UC登录成功
                /* 登录用户 */
                $Member = D("Member");
                if ($Member->login($uid)) { //登录用户
                    //TODO:跳转到登录前页面
                    $data["status"] = 1;
                    $data["info"] = "登录成功";
                    $this->ajaxReturn($data);
                } else {
                    $this->error($Member->getError());
                }
            } else { //登录失败
                switch ($uid) {
                    case -1: $error = "用户不存在！";
                        break; //系统级别禁用
                    case -2: $error = "密码错误！";
                        break;
                    default: $error = "未知错误！";
                        break; // 0-接口参数错误（调试阶段使用）
                }
                $this->error($error);
            }
        } else { //显示登录表单
            $this->display();
        }
    }

    /* 退出登录 */

    public function logout() {
        if (is_login()) {
            D("Member")->logout();
            uc_user_synlogout();
            //$this->success("退出成功！");
            $this->redirect("User/login");
        } else {
            $this->redirect("User/login");
        }
    }

    /* 验证码，用于登录和注册 */

    public function getVerify() {
        $verify = new \Think\Verify();
        $verify->entry(1);
    }

    /**
     * 获取用户注册错误信息
     * @param  integer $code 错误编码
     * @return string        错误信息
     */
    private function showRegError($code = 0) {
        switch ($code) {
            case -1: $error = "用户名长度必须在16个字符以内！";
                break;
            case -2: $error = "用户名被禁止注册！";
                break;
            case -3: $error = "用户名被占用！";
                break;
            case -4: $error = "密码长度必须在6-30个字符之间！";
                break;
            case -5: $error = "邮箱格式不正确！";
                break;
            case -6: $error = "邮箱长度必须在1-32个字符之间！";
                break;
            case -7: $error = "邮箱被禁止注册！";
                break;
            case -8: $error = "邮箱被占用！";
                break;
            case -9: $error = "手机格式不正确！";
                break;
            case -10: $error = "手机被禁止注册！";
                break;
            case -11: $error = "手机号被占用！";
                break;
            default: $error = "未知错误";
        }
        return $error;
    }

    /**
     * 忘记密码提交
     * @author joan
     */
    public function forgot_password() {
        if (IS_POST) {
            $password = I("post.password");
            $repassword = I("post.repassword");
            $mobile = I("post.mobile", 0, "floatval");
            if ((empty($password)) || (empty($repassword))) {//密码为空
                $data = array('code' => 500, 'message' => '密码不能为空');
            } else {
                if ($password_re != $password_reg) {//密码不一致
                    $data = array('code' => 500, 'message' => '两次新密码不一致');
                } else {
                    $ucresult = uc_user_edit($mobile, md5($password), md5($repassword), "", 1);
                    if (!$ucresult) {
                        $data = array('code' => 500, 'message' => "密码不正确");
                    } else {
                        M("member")->where(array("mobile" => $mobile))->save(array("password" => md5($password)));
                        $data = array('code' => 200, 'message' => '成功');
                    }
                }
            }
            $this->ajaxReturn($data);
        } else {
            $this->meta_title = '忘记密码';
            $this->display();
        }
    }

    /**
     * 第三方账号绑定
     * 
     */
    public function thirdBind() {

        if (IS_POST) {
            //查询用户表是否有这条记录
            $type = I('post.type');
            $open_id = I('post.open_id');
            $sign = I('post.sign');
            $mobile = I('post.mobile');
            $nickname = I('post.nickname');
            $headimgurl = I('post.headimgurl');
            $mobile_verify = I('post.mobile_verify');
            $password = I('post.password');
            $repassword = I('post.repassword');

            //验证手机验证码
            $verify_info = D('verify')->getVerify($mobile, 1);
            if ($mobile_verify !== $verify_info['verify']) {
                $data['code'] = 1;
                $data['message'] = '手机验证码错误！';
                $this->ajaxReturn($data);
            }

            $third_user = M('ThirdMember')->where(array('open_id' => $open_id, 'type' => $type))->find();
            if ($third_user) {

                D('Member')->login($third_user['uid']);
                header('Location:' . U('Index/index'));
            } else {
                $data = array('code' => 0, 'message' => '成功！');
                $uc_member = uc_get_user($mobile);
                if (intval($uc_member[0]) == 0) {
                    //注册用户
                    $uc_uid = uc_user_register($mobile, md5($password), $mobile . '@mobile.com');
                    if (intval($uc_uid) > 0) {
                        $uid = D('Member')->register(array('uc_uid' => $uc_uid, 'mobile' => $mobile, 'status' => 1, 'nickname' => $nickname, 'head_img' => $headimgurl));
                        if (0 < $uid) { //注册成功
                            D('Member')->login($uid);
                            //$ucsynlogin = uc_user_synlogin($uc_uid); 
                        } else { //注册失败，显示错误信息
                            $data['code'] = 3;
                            $data['message'] = '注册失败！';
                            $this->ajaxReturn($data);
                        }
                    } else {
                        $uc_user_register_config = array(
                            '-1' => '用户名不合法',
                            '-2' => '包含不允许注册的词语',
                            '-3' => '用户名已经存在',
                            '-4' => '格式有误',
                            '-5' => '不允许注册',
                            '-6' => '该 Email 已经被注册',
                        );
                        $data['code'] = 4;
                        $data['message'] = $uc_user_register_config[$uc_uid];
                        $this->ajaxReturn($data);
                    }
                } else {
                    $user_info = D('Member')->getUserInfo(array('mobile' => $mobile));
                    //是否需要更新头像
                    $uid = $user_info['uid'];
                    D('Member')->login($uid);
                }
                if ($uid > 0) {
                    //绑定第三方用户
                    D('ThirdMember')->add(array('uid' => $uid, 'open_id' => $open_id, 'type' => $type, 'add_time' => time()));
                } else {
                    $data['code'] = 5;
                    $data['message'] = '账号错误！';
                }

                $this->ajaxReturn($data);
            }
        } else {
            $open_id = I('get.open_id');
            $sign = I('get.sign');
            if ($sign !== think_encrypt($open_id, 'THIRD_BIND')) {
                $this->error('非法访问！', U('User/login'));
            }
            $this->assign('open_id', $open_id);
            $this->assign('sign', $sign);
            $this->display();
        }
    }

    public function discover() {
        $this->meta_title = '发现';
        $this->display();
    }

    //浏览记录

    public function BrowsingRecord() {
        $this->meta_title = '我看过的';
        $pageIndex = (I('page', 1) - 1) * 20;
        $count = I('count', 10);
        $type = I('type');
        $user_id = is_login();
        if (!$user_id) {
            $this->redirect('User/login');
            exit;
        }
        if ($user_id) {
            //查询数据库
            $list = M('history h')->field('lp.lottery_id,lp.title,lp.thumb,lp.need_count,lp.attend_count,lp.remain_count')->join('cg_lottery_product lp on h.gid=lp.pid')->where('h.uid=' . $user_id . ' and lp.status=0')->limit($pageIndex, $count)->order('h.time desc')->select();
            foreach ($list as &$row) {
                $row['thumb'] = getfullImg($row['thumb']);
            }
            $sum = M('history h')->join('cg_lottery_product lp on h.gid=lp.pid')->where('h.uid=' . $user_id . ' and lp.status=0')->count();
        }
        if ($type == 'view') {
            $this->display('BrowsingRecord');
        } else {
            $data = array(
                'total' => $sum,
                'list' => $list
            );
            $this->ajaxReturn($data);
        }
    }

    //服务协议
    public function Protocol() {
        $this->meta_title = '服务协议';
        $this->display();
    }

}
