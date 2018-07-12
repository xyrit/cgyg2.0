<?php

namespace Wap\Controller;

/**
 * 查看他人信息 joan
 */
class LotteryController extends HomeController {
    /* 中奖纪录 */

    public function index() {
        if (!is_login()) {
            $this->error("您还没有登陆", U("User/login"));
        }
        $uid = is_login();
        //$uid = 10000065;
        $page_index = intval(I('get.page_index')) > 0 ? intval(I('get.page_index')) : 0; //第几页
        $soso = I("soso"); //搜索条件
        $time_so = ($soso > 0) ? transForm($soso, "r.add_time") : ""; //获取搜索条件
        $where = "(r.uid=" . $uid . $time_so . ")";
        $m = M('order_pay r');
        $count = $m->where($where)->count(); //查询总条数
        $page_size = 10; //每页条数
        $total_page = ceil($count / $page_size); //总页码
        $field = "r.id,r.pay_type,r.count_money,r.pay_code,r.`status`,FROM_UNIXTIME(r.add_time)pay_time";
        $list = $m->field($field)->where($where)->order('r.id')->limit($page_index * $page_size, $page_size)->select();
        $this->assign('charge_list', $list);
        $data = $this->fetch('index'); //获取默认循环模块
        if (I('get.is_ajax') == 1) {//判断是否滑动加载
            $result = array('code' => 200, 'data' => $data);
            $this->ajaxReturn($result);
        }
        $this->assign('current_page', $page_index);
        $this->assign('total_page', $total_page);
        $this->assign('list', $data); //赋值充值信息
        $this->meta_title = '中奖纪录';
        $this->display();
    }

    /*
     * 查看他人信息--视图
     * 
     */

    public function other_info() {
        $uid = I("uid", "0", "floatval");
        if ($uid == is_login()) {//查看用户是自己->个人中心  
            header("Location: /Member/index.html");
            exit;
        }
        $acts = I("act", 1); //参与状态
        $info = D("Home/member")->get_member_info($uid);
        $this->meta_title = '查看他人';
        $picture = "http://" . $_SERVER['HTTP_HOST'] . "/Public/Home/images/user/user.png";
        $info["face"] = (empty($info["face"])) ? $picture : $info["face"];
        $info["nickname"] = (empty($info["nickname"])) ? str_replace(substr($info["mobile"], 3, 4), "****", $info["mobile"]) : $info["nickname"];
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

    public function other_info_data() {
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
                $result = D("Wap/LotteryProduct")->displayRecord($page, $pagesize, $params);
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

}
