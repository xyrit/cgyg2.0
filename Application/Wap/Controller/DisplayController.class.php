<?php

namespace Wap\Controller;

//use Think\Upload\Driver\OSS\OssClient;

/**
 * 晒单
 * 已晒单、未晒单、晒单详情、去晒单
 * Author: joan
 */
class DisplayController extends HomeController {
    /*
     * 晒单列表
     */

    public function index() {
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
     * 晒单详情
     */

    public function display_info() {
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
        $this->display("Display/info");
    }

    /*
     * 添加晒单
     */

    public function add() {
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

    public function testsss() {
        echo "ajax test";
    }

}
