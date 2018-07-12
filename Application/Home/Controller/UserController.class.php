<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 烟消云散 <1010422715@qq.com> <http://www.yershop.com>
// +----------------------------------------------------------------------
namespace Home\Controller;
//include DOC_ROOT_PATH . 'uc_client/config.inc.php'; //引入ucenter数据库配置文件
include DOC_ROOT_PATH . 'uc_client/config_ucenter.php'; //引入ucenter数据库配置文件
include DOC_ROOT_PATH . 'uc_client/client.php'; //引入ucenter客户端文件
/**
 * 用户控制器
 * 包括用户中心，用户登录及注册
 */
class UserController extends HomeController {

	/* 注册页面 */
	public function register($mobile = "", $password = "", $repassword = "", $mobile_verify = ""){
		if(!C("USER_ALLOW_REGISTER")){
            $this->error("注册已关闭");
        }
		//$username =safe_replace($username);//过滤
       
		if(IS_POST){ //注册用户
            $data = array('code'=>0,'message'=>'注册成功！');
            //验证手机验证码
            $verify_info = D('verify')->getVerify($mobile,1);
            if($mobile_verify !== $verify_info['verify']){
                $data['code'] = 1;
                $data['message'] = '手机验证码错误！';
            }
			/* 检测密码 */
			elseif($password != $repassword)
            {
                $data['code'] = 2;
                $data['message'] = '密码和重复密码不一致！';
			}else{
                //注册
                $uc_uid = uc_user_register($mobile, md5($password), $mobile.'@mobile.com');
                if(intval($uc_uid) > 0){
                    $uid = D('member')->register(array('uc_uid'=>$uc_uid,'mobile'=>$mobile));
    			     if(0 < $uid){ //注册成功
                        D('member')->login($uid);
                        $ucsynlogin = uc_user_synlogin($uc_uid); 
                        		
                    } else { //注册失败，显示错误信息
                        $data['code'] = 3;
                        $data['message'] = '注册失败！';
                        //$this->error($this->showRegError($uid));
                    }    
                }else{
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
			}
            
            $this->ajaxReturn($data);
            
		} 
		else 
        { 	
    	    $this->meta_title = '会员注册';
    		$this->display();
		}
	}

	/* 登录页面 */
	public function login($username = "", $password = "", $verify = ""){
		if (is_login()) {
			$this->error("ni登陆", U("Member/index"));
		}
		if(IS_POST){ //登录验证
            $data = array('code'=>0,'message'=>'登陆成功！');
            $username =safe_replace($username);//过滤
			// if(!check_verify($verify)){
   //              $data['code'] = 1;
   //              $data['message'] = '图片验证码错误！';
   //              $this->ajaxReturn($data);
			// }
            
            //list($uc_uid, $username, $password, $email) = uc_user_login($username, $password);
			$mobile=I('mobile',0);
			$password=md5(I('password',0));
			$userInfo=M("Member")->where(array('mobile'=>$mobile,'password'=>$password))->find();
			//echo M("Member")->getLastSql();exit;
			$uid=$userInfo['uid'];

			if(0 < $uid){ //UC登录成功
//                $ucsynlogin = uc_user_synlogin($uc_uid);
//                $data['success_js'] = $ucsynlogin;
//                $user_info = D('Member')->getUserInfo(array('uc_uid'=>$uc_uid));
//                $uid = $user_info['uid'];
                if(I('post.remember')){ 
					   cookie('username',$username,2592000); // 指定cookie保存30天时间
					   cookie('password',$password,2592000); // 指定cookie保存30天时间
					   addUserLog('保存cookie自动登录',$uid);		 
                }
				/* 登录用户 */
				$Member = D("Member");
				if($Member->login($uid)){ //登录用户
                    //全平台同步登陆
                    
                    //exit($ucsynlogin);
				} else {
                    $data['code'] = 2;
                    $data['message'] = $Member->getError();
				}

			} else { //登录失败
                $data['code'] = 3;
				switch($uc_uid) {
					case -1: $data['message'] = "用户不存在或被禁用！"; break; //系统级别禁用
					case -2: $data['message'] = "密码错误！"; break;
					default: $data['message'] = "未知错误！"; break; // 0-接口参数错误（调试阶段使用）
				}
			}
            $this->ajaxReturn($data);

		} else {  	
		    $this->meta_title = '会员登录';		
			//显示登录表单
			$this->display();
		}
	}


    /**
     * 验证手机验证码（跳转到下一步设置密码）
     * 
     */ 
    public function checkRegisterVerify($mobile = '', $mobile_verify = ''){
        
        $data = array('code'=>0,'message'=>'验证成功！');
        //验证手机验证码
        $verify_info = D('verify')->getVerify($mobile,1);
        if($mobile_verify !== $verify_info['verify']){
            $this->error("手机验证码错误！"); 
            $data['code'] = 1;
            $data['message'] = '手机验证码错误！';   
        }

        $is_ajax = I('post.is_ajax',0,'intval');
        if($is_ajax){            
            $this->ajaxReturn($data);    
        }else{
            return $data;   
        } 
    }
    
    /**
     * 发送手机验证码
     * 
     */ 
    public function sendVerify($mobile = '', $image_verify = ''){
        $data = array('code' => 0,'message' => '发送成功！');
        if(!check_verify($image_verify)){
            $data['code'] = 1;
            $data['message'] = '图片验证码错误！';
            $this->ajaxReturn($data);
        }
        $user_info = uc_get_user($mobile);
        if(intval($user_info[0]) > 0){
            $data['code'] = 2;
            $data['message'] = '该手机号码已经注册！'; 
            $this->ajaxReturn($data); 
        }
        
        Vendor('Sms.Rest');   
        //发送手机号码setAccount
        $sms_config = C('SMS');
        $sms = new \Vendor\SMS\Rest($sms_config['serverIP'],$sms_config['serverPort'],$sms_config['softVersion']);
        $sms->setAccount($sms_config['accountSid'], $sms_config['accountToken']);
        $sms->setAppId($sms_config['appId']);
        $verify = randomString(0,4);
        $tiparr = array($verify,10);
        $send_result = $sms->sendTemplateSMS($mobile, $tiparr, 67474);
        $send_result = (array)$send_result;
        if($send_result['statusCode'] != '000000'){
            $data['code'] = 2;
            $data['message'] = $send_result['statusMsg']; 
        }else{
            $add_data = array();
            $add_data['verify'] = $verify;
            $add_data['cellphone'] = $mobile;
            $add_data['creat_time'] = time();
            $add_data['type'] = 1;
            D('Verify')->verifyAdd($add_data);   
        } 

        $this->ajaxReturn($data); 
    }
    
    public function checkRegister($mobile){
        $uc_center = uc_get_user($mobile);
        $data = array('code'=>0,'message'=>'已注册！');
        if($uc_center[0] == 0){
            $data = array('code'=>1,'message'=>'未注册！');   
        }
        $this->ajaxReturn($data);
    }
    
    
    
    public function loginfromdialog($username = "", $password = ""){
		if(IS_POST){ //登录验证
			/* 调用UC登录接口登录 */
			$username =safe_replace($username);//过滤
			$user = new UserApi;
			$uid = $user->login($username, $password);
			if(0 < $uid){ //UC登录成功
				/* 登录用户 */
				$Member = D("Member");
				if($Member->login($uid)){ //登录用户
					//TODO:跳转到登录前页面
		           $data["status"] =1;
                   $data["info"] = "登录成功";
                   $this->ajaxReturn($data);
				} else {
					$this->error($Member->getError());
				}

			} else { //登录失败
				switch($uid) {
					case -1: $error = "用户不存在或被禁用！"; break; //系统级别禁用
					case -2: $error = "密码错误！"; break;
					default: $error ="未知错误！"; break; // 0-接口参数错误（调试阶段使用）
				}
				$this->error($error);
			}

		} else { //显示登录表单
			$this->display();
		}
	}
    


	/* 退出登录 */
	public function logout(){
		if(is_login()){
			D("Member")->logout();
            uc_user_synlogout();
			$this->success("退出成功！");
		} else {
			$this->redirect("User/login");
		}
	}

	/* 验证码，用于登录和注册 */
	public function getVerify(){
		$verify = new \Think\Verify();
		$verify->entry(1);
	}

	/**
	 * 获取用户注册错误信息
	 * @param  integer $code 错误编码
	 * @return string        错误信息
	 */
	private function showRegError($code = 0){
		switch ($code) {
			case -1:  $error = "用户名长度必须在16个字符以内！"; break;
			case -2:  $error = "用户名被禁止注册！"; break;
			case -3:  $error = "用户名被占用！"; break;
			case -4:  $error = "密码长度必须在6-30个字符之间！"; break;
			case -5:  $error = "邮箱格式不正确！"; break;
			case -6:  $error = "邮箱长度必须在1-32个字符之间！"; break;
			case -7:  $error = "邮箱被禁止注册！"; break;
			case -8:  $error = "邮箱被占用！"; break;
			case -9:  $error = "手机格式不正确！"; break;
			case -10: $error = "手机被禁止注册！"; break;
			case -11: $error = "手机号被占用！"; break;
			default:  $error = "未知错误";
		}
		return $error;
	}

    /**
     * 修改密码提交
     * @author huajie <banhuajie@163.com>
     */
    public function profile(){
		if ( !is_login() ) {
			$this->error( "您还没有登陆",U("User/login") );
		}
        if (IS_POST) {
            //获取参数
            $uid = is_login();
            $password   =   I("post.old");
            $repassword = I("post.repassword");
            $data["password"] = I("post.password");
            empty($password) && $this->error("请输入原密码");
            empty($data["password"]) && $this->error("请输入新密码");
            empty($repassword) && $this->error("请输入确认密码");

            if($data["password"] !== $repassword){
                $this->error("您输入的新密码与确认密码不一致");
            }

            $Api = new UserApi();
            $res = $Api->updateInfo($uid, $password, $data);
            if($res['status']){
                $this->success("修改密码成功！");
            }else{
                $this->error($res["info"]);
            }
        }else{    $this->meta_title = '修改密码';
            $this->display();
        }
    }
    
    /**
     * 第三方账号绑定
     * 
     */ 
    public function thirdBind(){
        
        if(IS_POST){
            //exit('a');
            //查询用户表是否有这条记录
            $type = I('post.type');
            $open_id = I('post.open_id');
            $sign = I('post.sign');
            $mobile = I('post.mobile');
            $mobile_verify = I('post.mobile_verify');
            $password = I('post.password');
            $repassword = I('post.repassword');
            
            $verify_info = D('verify')->getVerify($mobile,1);
            if($mobile_verify !== $verify_info['verify']){
                $this->error("手机验证码错误！"); 
                $data['code'] = 1;
                $data['message'] = '手机验证码错误！'; 
                $this->ajaxReturn($data);  
            }
            
            $third_user = M('ThirdMember')->where(array('open_id'=>$open_id,'type'=>$type));
            if($third_user){
                D('member')->login($third_user['uid']);
                header('Location:'.U('Index/index')); 
            }else{
                
                $uc_member = uc_get_user($mobile);
                if(intval($uc_member[0]) == 0){
                    //注册用户
                    $uc_uid = uc_user_register($mobile, md5($password), $mobile.'@mobile.com');
                    if(intval($uc_uid) > 0){
                        $uid = D('member')->register(array('uc_uid'=>$uc_uid,'mobile'=>$mobile));
        			     if(0 < $uid){ //注册成功
                            D('member')->login($uid);
                            $ucsynlogin = uc_user_synlogin($uc_uid); 
                            		
                        } else { //注册失败，显示错误信息
                            $data['code'] = 3;
                            $data['message'] = '注册失败！';
                            $this->ajaxReturn($data);
                        }    
                    }else{
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
                        
                }else{
                    $user_info = D('Member')->getUserInfo(array('mobile'=>$mobile));
                    $uid = $user_info['uid'];
                } 
                if($uid > 0){
                    //绑定第三方用户
                    D('ThirdMember')->update(array('uid'=>$uid,'open_id'=>$open_id,'type'=>$type));     
                }else{
                    $data['code'] = 5;
                    $data['message'] = '账号错误！';     
                }
 
                $this->ajaxReturn($data);
            }  
             
        }else{
            $open_id = I('get.open_id');
            $sign = I('get.sign');
            if($sign !== think_encrypt($open_id,'THIRD_BIND')){
                //$this->error('非法访问！',U('User/login'));    
            }
            $this->assign('open_id',$open_id);
            $this->assign('sign',$sign);
            
            
        }
        
    
    }
    
    
    
}
