<?php

namespace Home\Controller;

//include $_SERVER["DOCUMENT_ROOT"] . '/uc_client/config_ucenter.php';
//include $_SERVER["DOCUMENT_ROOT"] . '/uc_client/client.php';

/**
 * 个人中心
 * 包括：充值记录、参与记录、中奖纪录、我的晒单、我的资料
 */
class MemberController extends HomeController
{
	/*
	 * 个人中心--主页
	 */

	public function index()
	{
		if (!is_login()) {
			$this->redirect("User/login");
		}
		$uid = I("uid", is_login());
		$info = D("Home/member")->get_member_info($uid);
		$this->meta_title = '个人中心';
		$this->assign('info', $info); //赋值用户信息
		$this->display('Member/account');
	}

	/*
	 * 个人中心充值记录
	 *
	 */

	public function charge_list()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$states = I("state", 0); //参与状态
		if (I("is_ajax") == 1) {
			$state = I("state", 0); //参与状态
			$soso = I("soso"); //搜索条件
			$time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件

			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = $state;
			$result = D("Wap/LotteryProduct")->chargeRecord($page, $pagesize, $params);
			$this->ajaxReturn($result);
		} else {
			$display = 'display' . $states;
			$this->assign($display, 'active');
			$this->assign("states", $states);
			$this->meta_title = '充值记录';
			$this->display();
		}
	}

	/*
	 * 个人中心参与记录视图
	 */

	public function attend()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$userInfo = json_decode(cookie('user_auth'), true);
		$states = I("state", 0); //参与状态
		/*if (I("is_ajax") == 1) {
			$state = I("state", 0); //参与状态
			$soso = I("soso"); //搜索条件
			$time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件

			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = $state;
			$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
			foreach ($result["list"]["data"] as $k => &$v) {//获取图片绝对路径
				$v[thumb] = getfullImg($v[thumb]);
			}
			$this->ajaxReturn($result);
		} else {
			$display = 'display' . $states;
			$this->assign($display, 'active');
			$this->assign('user_info', $userInfo);
			$this->assign("states", $states);
			$this->meta_title = '参与记录';
			$this->display();
		}*/
		if (I("is_ajax") == 1) {
			$state = I("state", 0); //参与状态
			$soso = I("soso"); //搜索条件
			$time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件

			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = $state;
			$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
			foreach ($result["list"]["data"] as $k => &$v) {//获取图片绝对路径
				$v[thumb] = getfullImg($v[thumb]);
			}
			$this->ajaxReturn($result);
		} else {
			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = I("state", 0);
			$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
			foreach ($result["list"]["data"] as $k => &$v) {//获取图片绝对路径
				$v[thumb] = getfullImg($v[thumb]);
			}
			$display = 'display' . $states;
			$this->assign('result', $result['list']['data']);//print_r($result['list']['data']);
			$this->assign('user_info', $userInfo);
			$this->assign("states", $states);
			$this->meta_title = '参与记录';
			$this->display();
		}

	}

	/*
	 * 个人中心参与记录数据
	 */

	public function attend_data()
	{

		$params = array();
		$params['uid'] = $uid;
		$params['state'] = $state;
		$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
		foreach ($result["list"]["data"] as $k => &$v) {//获取图片绝对路径
			$v[thumb] = getfullImg($v[thumb]);
		}
		$this->ajaxReturn($result);
	}

	//个人中心--参与码、参与时间
	public function attend_code()
	{

		$uid = I("uid", is_login());
		if (!$uid) {
			$this->error('您还没有登录');
		}
		$lottery_id = I("lottery_id", "10000850");
		$where = "la.lottery_id=" . $lottery_id . " and la.is_pay=1 and la.uid=" . $uid;
		$field = "la.id,FROM_UNIXTIME(la.create_time)create_time,la.lucky_code,la.attend_count";
		$info = M("lottery_attend la")->field($field)->where($where)->order("la.id desc")->select();
		//echo M("lottery_attend la")->getLastSql();
		$sum = 0;
		foreach ($info as $key => &$value) {
			$sum += $value['attend_count'];
			$value['lucky_code'] = rtrim($value['lucky_code'], ',');
		}
		$result["sum"] = $sum;
		$result["list"] = $info;
		$this->ajaxReturn($result);
	}

	/*
	 * 中奖纪录
	 */

	public function lotteryRecord()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$states = I("state", 0); //参与状态
		$address_list = M("address")->where(array("uid" => $uid))->select();
		//dump($address_list);exit;
		if (I("is_ajax") == 1) {
			$state = I("state", 0); //参与状态
			$soso = I("soso"); //搜索条件
			$time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件

			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = $state;
			$result = D("Wap/LotteryProduct")->lotteryList($page, $pagesize, $params);
			$this->ajaxReturn($result);
		} else {
			$display = 'display' . $states;
			$this->assign($display, 'active');
			$this->assign("address_list", $address_list);
//echo count($address_list);dump($address_list);exit;
			$this->assign("address_list_length", count($address_list));
			$this->meta_title = '中奖纪录';
			$this->display();
		}
	}

	/**
	 * 晒单记录
	 */
	public function share_list()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$states = I("state", 0); //参与状态
		if (I("is_ajax") == 1) {
			$state = I("state", 0); //参与状态
			$soso = I("soso"); //搜索条件
			$time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件
			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = $uid;
			$params['state'] = $state;
			$result = D("Wap/LotteryProduct")->displayRecord($page, $pagesize, $params);

			$this->ajaxReturn($result);
		} else {
			$display = 'display' . $states;
			$this->assign($display, 'active');
			$this->assign("states", $states);
			$this->meta_title = '晒单记录';
			$this->display();
		}
	}

	/*
	 * 添加晒单
	 */

	public function share_add()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$lottery_id = I("lottery_id", 0);
		if (IS_POST) {
			$description = I("description"); //描述
			$path = I("path"); //图片流
			if (empty($description)) {
				$this->ajaxReturn(array("code" => 500, "msg" => "晒单描述不能为空"));
			}
			if (empty($path)) {
				$this->ajaxReturn(array("code" => 500, "msg" => "请上传图片"));
			}
			$img = str_replace('data:image/jpeg;base64', '', $path); //去头部
			$img = str_replace(' ', '+', $img);

			$config['accessKeySecret'] = 'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7';
			$config['accessKeyId'] = '08iJabGVcaucodBT';
			$config['endpoint'] = 'oss-cn-shenzhen.aliyuncs.com';
			$config['bucket'] = 'cgchengguo';

			$OssClient = new \Think\Upload\Driver\OSS\OssClient($config); //实例化上传类
			$img_arr = array();
			$img_arr = explode(",", $img);
			$pic_arr = array();
			foreach ($img_arr as $k => $v) {
				if (!empty($img_arr[$k])) {
					$data = base64_decode($img_arr[$k]); //解码
					$file = "AAA" . time() . uniqid() . ".jpg"; //图片名称
					$uploadFile = $OssClient->putObject($config['bucket'], $file, $data); //上传到阿里云，成功返回信息
					$pic_arr[$k] = $uploadFile["url"];
				}
			}
			$add_data = array(
				"description" => $description,
				"pics" => implode(",", $pic_arr),
				"apply_time" => date("Y-m-d H:i:s", time()),
				"uid" => $uid,
				"lottery_id" => $lottery_id,
				"user_name" => $uid, //昵称从session获取
				"type" => 1
			);
			$this_id = M("display_product")->add($add_data);
			($this_id) ? $this->ajaxReturn(array("code" => 200, "msg" => "晒单成功")) : $this->ajaxReturn(array("code" => 500, "msg" => "晒单失败"));
		} else {
			$this->meta_title = '我要晒单';
			$this->display("Display/add");
		}
	}

	/*
	 * 晒单详情
	 */

	public function share_info()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$id = I("id", "0");
		$m = M('display_product d');
		$field = "lp.thumb,d.user_name,la.attend_ip,d.uid,lp.lottery_id,lp.lottery_code,sum(la.attend_count)as attend_count,FROM_UNIXTIME(lp.lottery_time)lottery_time,FROM_UNIXTIME(d.create_time)create_time,d.description,d.pics path";
		$info = $m->field($field)->join("left join cg_lottery_product as lp on d.lottery_id=lp.lottery_id")->join("LEFT JOIN cg_lottery_attend as la on lp.lottery_id=la.lottery_id")->where(array("d.id" => $id))->order("la . lottery_id")->find();

		$picarr = array();
		if (!empty($info["path"])) {
			$picarr = explode(',', $info["path"]);
		}
		$info["path"] = $picarr;
		foreach ($info["path"] as $k1 => &$v1) {
			$v1 = getfullImg($v1);
		}

		//dump($info);
		$this->meta_title = '晒单详情';
		$this->assign("info", $info);
		$this->display();
	}

	/*
	 * 地址列表
	 */

	public function address_list()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$this->meta_title = '地址列表';
		$addressid = I("uid", 0);
		$condition = ($addressid > 0) ? "id=" . $addressid : "uid='$uid' and status>0";
		$field = "id,realname,cellphone,province,city,area,address,status,take_address";
		$list = M("address")->field($field)->where($condition)->select();
		$total = 3;
		$address_num = count($list);
		$remain = $total - $address_num;
		$display = ($remain < 1) ? "none" : "";
		$this->assign("list", $list);
		$this->assign("address_num", $address_num);
		$this->assign("remain", $remain);
		$this->assign("display", $display);
		$this->display();
	}

	/*
	 * 修改地址
	 */

	public function address_save()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$address = D('address');
		$uid = is_login();
		$info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
		$mobile = $info["mobile"];
		if (IS_POST) { //提交表单
			$data['realname'] = I('post.realname', '', 'strip_tags');
			$data['cellphone'] = I('post.cellphone', 0, 'floatval');
			$data['province'] = I('post.province', '', 'strip_tags');
			$data['address'] = I('post.address', '', 'strip_tags');
			$data['verify'] = I('post.verify', 0, '');
			$data['take_address'] = $data['province'] . $data['address'];
			$id = I('post.addressid', 0, 'intval');

			$data['uid'] = $uid;
			$data['status'] = 2; //默认地址状态
			$data['create_time'] = NOW_TIME;
			$verify_info = A("User")->checkRegisterVerify($mobile, $data['verify'], 5);
			if ($verify_info["code"] == 200) {
				$address_id = $address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
				if ($address_id) {
					$thisid = $address->where(array("id" => $id))->save($data);
					if ($thisid) {
						$this->ajaxReturn(array('code' => 200, 'message' => "修改成功"));
					} else {
						$this->ajaxReturn(array('code' => 500, 'message' => "修改失败"));
					}
				} else {
					$this->ajaxReturn(array('code' => 500, 'message' => "修改失败"));
				}
			} else {
				$this->ajaxReturn($verify_info);
			}
		} else {
			$id = I("id", "0");
			$info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
			$mobile = $info["mobile"];
			$result = $address->field("realname,cellphone,address,province,city,area")->where(array("id" => $id))->find();
			$this->meta_title = '修改地址';
			$this->assign("user_mobile", $mobile);
			$this->assign("result", $result);
			$this->assign("addressid", $id);
			$this->display();
		}
	}

	/*
	 * 新增地址
	 */

	public function address_add($id = null, $pid = 0)
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}

		$address = D('address');
		$uid = is_login();
		$info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
		$mobile = $info["mobile"];
		if (IS_POST) { //提交表单
			$data['realname'] = I('post.realname', '', 'strip_tags');
			$data['cellphone'] = I('post.cellphone', 0, 'floatval');
			$data['province'] = I('post.province', '', 'strip_tags');
			$data['address'] = I('post.address', '', 'strip_tags');
			$data['verify'] = I('post.verify', 0, 'strip_tags');
			$data['take_address'] = $data['province'] . $data['address'];

			$data['uid'] = $uid;
			$data['status'] = 2;
			$data['create_time'] = NOW_TIME;

			$address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
			$thisid = $address->add($data);
			if ($thisid) {
				$this->ajaxReturn(array('code' => 200, 'message' => "添加成功"));
			} else {
				$this->ajaxReturn(array('code' => 500, 'message' => "添加失败"));
			}
		} else {
			$this->meta_title = '新增地址';
			$this->assign("user_mobile", $mobile);
			$this->display();
		}
	}

	/*
	 * 删除地址
	 */

	public function address_delete()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$address = M("address");
		$uid = is_login();
		$id = I('id', 0, 'intval'); // 用intval过滤$_POST['id']
		$count = $address->where("uid='$uid'")->count(); //查询总条数
		if ($count < 2) {
			$this->ajaxreturn(array("code" => 500, "msg" => "删除失败"));
			exit;
		}
		if ($address->where("uid='$uid' and id='$id'")->delete()) {
			$this->ajaxreturn(array("code" => 500, "msg" => "删除成功"));
		} else {
			$this->ajaxreturn(array("code" => 500, "msg" => "删除失败"));
		}
	}

	/*
	 * 设置默认地址
	 */

	public function address_set()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		if (IS_AJAX) {
			$uid = is_login();
			$address = M("address");
			$id = I('id', 0, 'intval');
			$address->where("uid='$uid'and status=2")->save(array("status" => 1)); //将旧的默认地址设为普通
			$result = $address->where("id='$id'")->save(array("status" => 2)); //将当前选中地址设为默认

			if ($result) {
				$data = array('code' => 200, 'message' => '设置成功');
				$this->ajaxreturn($data);
			} else {
				$data = array('code' => 500, 'message' => '设置失败');
				$this->ajaxreturn($data);
			}
		}
	}

	//个人中心--设置
	public function setup()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$this->meta_title = '设置帮助';
		$this->display("Member/setup");
	}

	//个人中心--用户信息
	public function profile()
	{
		$this->meta_title = '个人资料';
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$info = D("Home/member")->get_member_info($uid);
		$picture = "http://" . $_SERVER['HTTP_HOST'] . "/Public/Home/images/user/user.png";
		$info["face"] = (empty($info["face"])) ? $picture : $info["face"];
		$info["nickname"] = (empty($info["nickname"])) ? str_replace(substr($info["mobile"], 3, 4), "****", $info["mobile"]) : $info["nickname"];
		$this->assign('info', $info);
		$this->display();
	}

	/*
	 * 更改头像
	 *
	 */

	public function up_photo()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		if (IS_POST) {
			$uid = is_login();
			$face = I("path", 0);
			if (empty($face)) {
				$this->ajaxReturn(array("code" => 500, "msg" => "请上传图片"));
			}
			$img = str_replace('data:image/jpeg;base64,', '', $face); //去头部
			$img = str_replace(' ', '+', $img);

			$config['accessKeySecret'] = 'RkyzXVJI7TRPlM0e8SrgyTsS2RU4P7';
			$config['accessKeyId'] = '08iJabGVcaucodBT';
			$config['endpoint'] = 'oss-cn-shenzhen.aliyuncs.com';
			$config['bucket'] = 'cgchengguo';

			$OssClient = new \Think\Upload\Driver\OSS\OssClient($config); //实例化上传类
			$data = base64_decode($img); //解码
			$file = $uid . time() . uniqid() . ".jpg"; //图片名称
			$uploadFile = $OssClient->putObject($config['bucket'], $file, $data); //上传到阿里云，成功返回信息
			if (empty($uploadFile["url"])) {
				$this->ajaxReturn(array("code" => 500, "msg" => "头像上传失败"));
			}
			$this_id = M("member")->where(array("uid" => $uid))->save(array("face" => $uploadFile["url"]));
			if ($this_id) {
				$this->ajaxReturn(array("code" => 200, "msg" => "修改成功"));
			} else {
				$this->ajaxReturn(array("code" => 500, "msg" => "修改失败"));
			}
		} else {
			$this->display();
		}
	}

	//个人中心--更改昵称
	public function up_name()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$condition = array("uid" => $uid);
		if (IS_POST) {
			$nickname = I("user_name");
			$pcre_name = "/^[\x{4e00}-\x{9fa5}a-zA-Z0-9_]{1,8}$/u";
			if (!preg_match($pcre_name, $nickname)) {//匹配用户名、昵称
				$data = array('code' => 500, 'message' => '昵称必须是1-8位的字母、数字、汉字或下划线组成');
			} else {
				M("member")->where($condition)->save(array("nickname" => $nickname));
				$data = array('code' => 200, 'message' => '成功');
			}
			$this->ajaxReturn($data);
		} else {
			$info = M("member")->field("nickname")->where($condition)->find();
			$this->assign("nickname", $info["nickname"]);
			$this->meta_title = '更改昵称';
			$this->display("Member/up_name");
		}
	}

	//个人中心--验证旧手机号
	public function check_oldmobile()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$info = M("member")->field("mobile")->where(array("uid" => $uid))->find();
		$this->meta_title = '验证手机号';
		$this->assign('oldmobile', $info["mobile"]);
		$this->display("Member/up_mobile");
	}

	//个人中心--更改手机号
	public function up_mobile()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		if (IS_POST) {
			$mobile = I("mobile", 0); //新手机号
			$mobile_verify = I("mobile_verify", 0); //验证码
			//验证手机验证码
			$verify_info = A("User")->checkRegisterVerify($mobile, $mobile_verify, 3);

			if ($verify_info["code"] == 200) {
				$cellphone = session("username");
				$user = json_decode(cookie('user_auth'), true); //获取cookie里面的用户信息
				//检测第一次验证的手机 与 cookie里面的是否一致
				if (floatval($cellphone) == floatval($user["username"])) {
					$ucresult = uc_user_edit($mobile, "", "", "", floatval($user["uid"]));
					if ($ucresult > 0) {
						$code = M("Member")->where(array("uid" => $uid))->save(array("mobile" => $mobile));
						$data = ($code) ? array('code' => 200, 'message' => '修改成功！') : array('code' => 200, 'message' => '修改失败！');
						session("username", null);
					} else {
						$data = array('code' => 200, 'message' => '修改失败！' . $ucresult);
					}
				} else {
					$data = array('code' => 200, 'message' => '非法修改！');
				}
				$this->ajaxReturn($data);
			} else {
				$this->ajaxReturn($verify_info);
			}
		} else {
			$this->meta_title = '更改手机号';
			$this->display("Member/up_mobile2");
		}
	}

	//个人中心--更改密码
	public function up_password()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$uid = is_login();
		$condition = array("uid" => $uid);
		if (IS_POST) {
			$password = I("password"); //旧密码
			$password_re = I("password_re"); //新密码
			$password_reg = I("password_reg"); //确认新密码
			if ((empty($password)) || (empty($password_re)) || (empty($password_reg))) {//密码为空
				$data = array('code' => 500, 'message' => '密码不能为空');
			} else {
				$user_info = M("member")->field("password,mobile")->where($condition)->find();
				$pass_word = $user_info["password"];
				$username = $user_info["mobile"];
				$ucresult = uc_user_edit($username, md5($password), md5($password_re), "");
				if (!$ucresult) {
					$data = array('code' => 500, 'message' => "旧密码不正确");
				} else {
					if ($password_re != $password_reg) {//密码不一致
						$data = array('code' => 500, 'message' => '两次新密码不一致');
					} else {
						$code = M("member")->where($condition)->save(array("password" => $password_re));
						$data = array('code' => 200, 'message' => '成功');
					}
				}
			}
			$this->ajaxReturn($data);
		} else {
			$this->meta_title = '更改密码';
			$this->display();
		}
	}

	/*
	 * 查看他人信息--视图
	 *
	 */

	public function other_info()
	{
		$uid = I("uid", "0", "floatval");
		if ($uid == is_login()) {//查看用户是自己->个人中心
			$this->redirect("Member/index");
		}
		$acts = I("act", 1); //参与状态
		$info = D("member")->get_member_info($uid);
		$this->meta_title = '查看他人';
		$nav_title = (empty($info["nickname"])) ? "TA" : $info["nickname"];
		switch ($acts) {
			case 3:
				$nav_title = $nav_title . "的晒单纪录";
				break;
			case 2:
				$nav_title = $nav_title . "的中奖纪录";
				break;
			default:
				$nav_title = $nav_title . "的参与记录";
		}
		$this->assign("nav_title", $nav_title); //导航标题
		$display = 'display' . $acts; //导航样式
		$this->assign($display, 'active');
		$this->assign("acts", $acts);
		$this->assign('info', $info); //赋值用户信息
		$this->display();
	}

	/*
	 * 查看他人信息--分页数据
	 *
	 */

	public function other_info_data()
	{
		if (I("is_ajax") == 1) {
			$act = I("act", 1); //参与状态
			$page = intval(I('get.page')) > 0 ? intval(I('get.page')) : 1;
			$pagesize = intval(I('get.count', '3'));
			$params = array();
			$params['uid'] = I("other_id", "0", "floatval");
			$params['state'] = $act;
			if ($act == 3) {//晒单
				$result = D("Wap/LotteryProduct")->displayRecord($page, $pagesize, $params);
			} else if ($act == 2) {//中奖
				$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
			} else {
				$params['state'] = 3;
				$result = D("Wap/LotteryProduct")->attend_List($page, $pagesize, $params);
				foreach ($result["list"]["data"] as $k => &$v) {//获取图片绝对路径
					$v[thumb] = getfullImg($v[thumb]);
				}
			}
			$result["list"]["act"] = $act;
			$this->ajaxReturn($result);
		}
	}

	/*
	 * 确认地址
	 */

	public function sureAddress()
	{
		if (!is_login()) {
			$this->error("您还没有登陆", U("User/login"));
		}
		$id = I("id", 0);
		$address = M("address")->field("province,city,area,address,realname,cellphone,youbian")->where(array("id" => $id))->find();
		M("LotteryProduct")->where($address);
	}

	//个人中心--常见问题
	public function frequently()
	{
		$this->meta_title = '常见问题';
		$this->display("Member/frequently");
	}

	//个人中心--关于我们
	public function aboutus()
	{
		$this->meta_title = '关于我们';
		$this->display("Member/aboutus");
	}

}
