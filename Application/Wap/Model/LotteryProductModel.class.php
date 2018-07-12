<?php

namespace Wap\Model;

use Think\Model;

/**
 * 个人中心--获取商品信息
 * 更新时间 2016/5/11
 */
class LotteryProductModel extends Model {

    /**
     * 参与记录(进行中、即将揭晓、已揭晓)
     */
    public function attend_List($page, $pagesize, $params) {
        $uid = $params["uid"];
        $state = $params["state"];
        $field1 = "la.id,lp.need_count,lp.thumb,lp.title,sum(la.attend_count) as attend_count_total,cast(lp.attend_count*100/lp.need_count as decimal(28,2)) as attend_ratio,lp.status,(lp.need_count-lp.attend_count)as remain_count,la.lottery_id,la.uid,lp.attend_count attend_counts";
        $field2 = "la.id,sum(la.attend_count)as attend_count,lp.thumb,lp.title,lp.status,lp.uid lp_uid,lp.lottery_time,lp.lottery_code,la.lottery_id,la.uid";
        switch ($state) {
            case 3://全部
                $fields = $field1 . "," . $field2;
                $where = "(la.uid=" . $uid . " and la.is_pay=1 )";
                break;
            case 2://已揭晓
                $fields = $field2;
                $where = "(la.uid=" . $uid . " and la.is_pay=1 and lp.status=" . $state . ")";
                $order = "la.pay_time desc";
                break;
            default://进行中、揭晓中
                $fields = $field1;
                $where = "(la.uid=" . $uid . " and lp.status=" . $state . " and la.is_pay=1 )";
                $order = "lp.lottery_time desc";
        }
        $count = M('lottery_attend la')->join('left join cg_lottery_product lp on la.lottery_id=lp.lottery_id')->where($where)->count("DISTINCT la.lottery_id"); //查询总条数

        $list = M('lottery_attend la')->field($fields)->join('left join cg_lottery_product as lp on la.lottery_id=lp.lottery_id')->where($where)->group('la.lottery_id')->page($page, $pagesize)->select(); //查询总条数
        // echo    M('lottery_attend la')->_sql();

        if ($state > 1) {
            foreach ($list as $k => &$v) {
                //   if ($uid != $v["lp_uid"]) {//判断获奖者是否是当前用户
                $lp_uid = $v["lp_uid"]; //中奖用户uid
                $lottery_id = $v["lottery_id"];
                $prize_field = "la.lottery_id,sum(la.attend_count) as attend_count,m.nickname";
                $prize = M('lottery_attend la')->field($prize_field)->join('left join cg_member as m on la.uid=m.uid')->where("la.lottery_id=" . $lottery_id . " and la.is_pay=1 and la.uid = " . $lp_uid)->find();

                // echo M('lottery_attend la')->_sql();
                $v["nickname"] = $prize["nickname"];
                $v["lp_attend_count"] = $prize["attend_count"];
                //  } else {
                //      $v["lp_attend_count"] = $v["attend_count"];
                //
            //    }
            }
        }
        //dump($list);
        return array("list" => array("data" => $list, "state" => $state), "total" => $count);
    }

    /*
     * 个人中心充值记录
     * 
     */

    public function chargeRecord($page, $pagesize, $params) {
        $where = "(r.uid=" . $params["uid"] . ")";
        $m = M('recharge r');
        $count = $m->where($where)->count(); //查询总条数
        $page_size = 10; //每页条数
        $total_page = ceil($count / $page_size); //总页码
        $field = "r.id,r.pay_type,r.rmb count_money,r.order_code,r.`is_pay`as status,FROM_UNIXTIME(r.add_time)pay_time";
        $list = $m->field($field)->where($where)->order('r.id')->page($page, $pagesize)->select();
        foreach ($list as $k => &$v) {
            switch ($v["pay_type"]) {
                case alipay:
                    $v["pay_type"] = "支付宝";
                    break;
                case tenpay:
                    $v["pay_type"] = "财付通";
                    break;
                case wxpay:
                    $v["pay_type"] = "微信支付";
                    break;
                default:
                    $v["pay_type"] = "网银支付";
            }
        }
        return array("list" => array("data" => $list), "total" => $count);
    }

    /**
     * 商品-晒单列表
     */
    public function displayRecord($page, $pagesize, $params) {
        $uid = $params["uid"];
        $state = $params["state"];
        $where = ($state > 0) ? "(d.uid=" . $uid . " and d.status=1)" : "(d.uid=" . $uid . " and d.status<>1)";
        $m = M('display_product d');
        $count = $m->where($where)->count(); //查询总条数
        if ($state > 0) {//已晒单
            $field = "d.id,m.nickname,m.face,d.apply_time,d.description,d.`status`,d.pics path,m.uid";
            $list = $m->field($field)->join("left join cg_member m on d.uid=m.uid")->where($where)->order('d.id')->page($page, $pagesize)->select();
            $picarr = array();
            foreach ($list as $k => $v) {
                if (!empty($list[$k]["path"])) {
                    $picarr[$k] = explode(',', $list[$k]["path"]);
                    $list[$k]["path"] = $picarr[$k];
                    foreach ($list[$k]["path"] as $k1 => $v1) {
                        $list[$k]["path"][$k1] = getfullImg($list[$k]["path"][$k1]);
                    }
                }
            }
        } else {//未晒单
            $field = "d.id,d.`type`,d.lottery_id,lp.thumb path,lp.title,FROM_UNIXTIME(lp.lottery_time) lottery_time";
            $list = $m->field($field)->join("left join cg_lottery_product lp on d.lottery_id=lp.lottery_id")->where($where)->order('d.id')->page($page, $pagesize)->select();
            foreach ($list as $k => &$v) {//获取图片绝对路径
                $v[path] = getfullImg($v[path]);
            }
        }
        return array("list" => array("data" => $list, "state" => $state), "total" => $count);
    }

    /*
     * 个人中心中奖记录
     * 
     */

    public function lotteryList($page, $pagesize, $params) {
        $where = "(lr.uid=" . $params["uid"] . ")";
        $m = M('lottery_record lr');
        $count = $m->where($where)->count(); //查询总条数
        $page_size = 10; //每页条数
        $total_page = ceil($count / $page_size); //总页码
        $field = "lr.id,lr.thumb,lr.title,lr.lottery_id,lr.express_name,lr.express_number,FROM_UNIXTIME(lr.apply_time)apply_time";
        $list = $m->field($field)->where($where)->order('lr.id')->page($page, $pagesize)->select();
        foreach ($list as $k => &$v) {
            $v["thumb"] = getfullImg($v["thumb"]);
        }
        return array("list" => array("data" => $list), "total" => $count);
    }

}
