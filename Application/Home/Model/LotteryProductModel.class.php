<?php

namespace Home\Model;

//use Home\Common\TimeUtil;
use Think\Model;

/**
 * 用户参与明细
 * 更新时间 2015.12.20
 */
class LotteryProductModel extends Model {
    /* 合并数组函数 */

    public function turnArrays($info) {
        $arr = array(); //用于存放数据
        // $count = count($info,0);//获取二维数组的长度，即包含以为数组的个数
        //检测二维数组中元素的长度，只检测第一个关联数组
        $sum = 0;
        foreach ($info as $key => $value) {
            if ($sum === 0) {
                $count = count($value);
            }
            break;
        }
        //开始拼接目标数组
        for ($i = 0; $i < $count; $i++) {
            foreach ($info as $key => $value) {
                $arr[$i][$key] = $value[$i];
            }
        }
        return $arr;
    }

    /**
     * 参与记录(进行中、即将揭晓、已揭晓)
     */
    public function attend_List($uid = 0, $state = 0) {
        $where = "(la.uid=" . $uid . " and lp.status=" . $state . ")";
        $count = M('lottery_attend la')->join('join cg_lottery_product lp on la.lottery_id=lp.lottery_id')->where($where)->count('distinct la.lottery_id'); //查询总条数
        $page_num = 5;
        $p = getpage($count, $page_num); //页码
        $page_total = ceil($count / $page_num);
        $fields = ($state < 2) ? "la.id,sum(la.attend_count)as attend_count,lp.thumb,lp.title,lp.attend_count as attend_count_total,lp.need_count,lp.attend_ratio ,lp.status,(lp.need_count-lp.attend_count)remain_count,la.lottery_id,la.uid" : "la.id,sum(la.attend_count)as attend_count,lp.thumb,lp.title,lp.status,lp.uid lp_uid,lp.lottery_time,lp.lottery_code,la.lottery_id,la.uid";
        $list = M('lottery_attend la')->field($fields)->join('left join cg_lottery_product as lp on la.lottery_id=lp.lottery_id')->where($where)->group('la.lottery_id')->limit($p->firstRow, $p->listRows)->select(); //查询总条数 

        if ($state == 2) {
            foreach ($list as $k => $v) {
                if ($uid != $list[$k]["lp_uid"]) {//判断获奖者是否是当前用户
                    $lp_uid = $list[$k]["lp_uid"]; //中奖用户uid
                    $lottery_id = $list[$k]["lottery_id"];
                    $prize_field = "la.lottery_id,la.attend_count,m.nickname";
                    $prize = M('lottery_attend la')->field($prize_field)->join('left join cg_member as m on la.uid=m.uid')->where("la.lottery_id=" . $lottery_id . " and la.uid = " . $lp_uid)->group('la.id')->find();

                    $list[$k]["nickname"] = $prize["nickname"];
                    $list[$k]["lp_attend_count"] = $prize["attend_count"];
                } else {
                    $list[$k]["lp_attend_count"] = $list[$k]["attend_count"];
                    $list[$k]["nickname"] = $uid;
                }
            }
        }
        return array($list, $p->show(), $page_total);
    }

    /* 参与明细查询总数 */

    public function getAttendCount($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        if (!empty($timeStart) && !empty($timeEnd)) {
            $BETWEEN = " and (la.create_time BETWEEN " . $timeStart . " AND " . $timeEnd . ")";
        } else {
            $BETWEEN = '';
        }
        if ($state == '0') {//进行中商品
            $WHERE = "WHERE ((la.uid=" . $uid . ")" . $BETWEEN . " and (lp.last_attend_time<1)) ";
        } else if ($state == '1') {//即将揭晓商品
            $WHERE = " WHERE ((la.uid=" . $uid . ")" . $BETWEEN . " and (lp.last_attend_time>0) and (lp.lottery_code=0) and (lp.uid<1)) "; //dump($WHERE);exit;
        } else if ($state == '2') {//已揭晓商品
            $WHERE = "WHERE ((la.uid=" . $uid . ")" . $BETWEEN . " and (lp.lottery_time>0) and (lp.uid>0)) ";
        }
        $sql = "select count(*) as total from(select la.id from  " . $prefix . "lottery_attend as la left join " .
                $prefix . "lottery_product as lp on la.lottery_id=lp.lottery_id " . $WHERE . "  group by la.lottery_id ) temp  ";
        //echo $sql;
        $count = $this->query($sql);
        $count = $count[0]["total"]; //echo $count;exit;
        return $count;
    }

    /* 根据商品表图片ids获取图片集 */

    public function getPictures($list = '') {
        $arr = array();
        foreach ($list as $k => $v) {
            $arr = $list[$k]['pics'];
            $detail = D("lotteryProduct")->getImagePath($arr);
            $list[$k]['pics'] = $detail;
        }
        return $list;
    }

    /* 获取参与明细的剩余人次 */

    public function getRemainCount($list = '') {
        $arr = array();
        foreach ($list as $k => $v) {
            $arr = $list[$k]['pics'];
            $list[$k]["remain_count"] = $list[$k]["need_count"] - $list[$k]["attend_all"];
        }
        return $list;
    }

    /**
     * 个人中心-中奖纪录
     */
    public function lotteryRecord($uid, $pageSize, $pageIndex, $timeStart, $timeEnd, $state) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $timeStart = (!empty($timeStart)) ? date("Y-m-d H:i:s", $timeStart) : $timeStart;
        $timeEnd = (!empty($timeEnd)) ? date("Y-m-d H:i:s", $timeEnd) : $timeEnd;
        if (!empty($timeStart) && !empty($timeEnd)) {
            $BETWEEN = " and (w.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')";
        } else {
            $BETWEEN = '';
        }
        if ($state == '1') {//实物商品
            $WHERE = "WHERE ((w.uid=" . $uid . ") and (w.virtual=0)" . $BETWEEN . " ) ";
        } else if ($state == '0') {//虚拟商品
            $WHERE = "WHERE ((w.uid=" . $uid . ") and (w.virtual=1)" . $BETWEEN . " ) ";
        }
        $sql = "select w.id,w.title,w.thumbnail as path,lp.need_count,lp.lottery_id,lp.lottery_code,w.attend_count,w.apply_time,w.attend_time,w.`status`," .
                " dd.express_name,dd.express_number,lp.pid" .
                " from  " . $prefix . "delivered as dd right join " . $prefix . "win_prize as w on dd.win_id=w.id" .
                " left join " . $prefix . "lottery_product as lp on w.lottery_id=lp.lottery_id " .
                $WHERE . "  group by w.id desc limit $start,$end ";
        //echo $sql;
        $list = $this->query($sql); //dump($list);exit;
        //$list = A("Home/Home")->reset_null($list);//dump($list);exit;
        return $list;
    }

    /**
     * 个人中心-中奖纪录总数量
     */
    public function lotteryRecordCount($uid, $pageSize, $pageIndex, $timeStart, $timeEnd, $state) {
        $prefix = C('DB_PREFIX');
        $timeStart = (!empty($timeStart)) ? date("Y-m-d H:i:s", $timeStart) : $timeStart; //转成时间格式
        $timeEnd = (!empty($timeEnd)) ? date("Y-m-d H:i:s", $timeEnd) : $timeEnd; //转成时间格式
        if (!empty($timeStart) && !empty($timeEnd)) {
            $BETWEEN = " and (w.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')";
        } else {
            $BETWEEN = '';
        }
        if ($state == '1') {//实物商品
            $WHERE = "WHERE ((w.uid=" . $uid . ") and (w.virtual=0)" . $BETWEEN . " ) ";
        } else if ($state == '0') {//虚拟商品
            $WHERE = "WHERE ((w.uid=" . $uid . ") and (w.virtual=1)" . $BETWEEN . " ) ";
        }
        $sql = "select count(*) as total from(select w.id  from  " . $prefix . "win_prize as w " .
                " left join " . $prefix . "lottery_product as lp on w.lottery_id=lp.lottery_id " .
                $WHERE . "  order by w.id )temp ";
        //echo $sql;
        $count = $this->query($sql);
        $count = $count[0]["total"];
        return $count;
    }

    /**
     * 个人中心-晒单列表
     */
    public function orderRecord($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $timeStart = (!empty($timeStart)) ? date("Y-m-d H:i:s", $timeStart) : $timeStart;
        $timeEnd = (!empty($timeEnd)) ? date("Y-m-d H:i:s", $timeEnd) : $timeEnd;
        //dump($timeEnd);exit;
        if ($state > '0') {//=1已晒单
            $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (dp.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')" : '';
            $WHERE = "WHERE ((dp.uid=" . $uid . ")" . "and (dp.status =1)" . $BETWEEN . ")";
            $sql = "select dp.id,w.title as product,m.face,dp.pics as path,UNIX_TIMESTAMP(dp.apply_time) as create_time  ,dp.title,dp.description,dp.score ,dp.uid,m.nickname,dp.status" .
                    " ,dp.lottery_id,dp.pid from " . $prefix . "member as m left join " . $prefix . "display_product as dp " .
                    " on m.uid=dp.uid left join os_win_prize as w on dp.win_id=w.id " .
                    $WHERE . "  group by dp.id desc limit $start,$end ";
        } else {//=0未晒单
            $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (dp.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')" : '';
            $WHERE = "WHERE ((dp.uid=" . $uid . ")" . "and (dp.status <>1)" . $BETWEEN . ")";
            $sql = "select dp.id,w.title,w.thumbnail as path,lp.need_count,w.attend_count,lp.create_time as attend_time," .
                    " lp.lottery_code,lp.lottery_time,lp.uid,m.nickname,dp.status,dp.lottery_id,dp.pid from " . $prefix . "member as m" .
                    " right join " . $prefix . "display_product as dp on m.uid=dp.uid" .
                    " left join " . $prefix . "win_prize as w on dp.win_id=w.id" .
                    " left join " . $prefix . "lottery_product as lp on w.lottery_id=lp.lottery_id  " .
                    $WHERE . "  group by dp.id desc limit $start,$end ";
        }
        // echo $sql;
        $list = $this->query($sql);
        return $list;
    }

    /**
     * 个人中心-晒单总数量
     */
    public function orderRecordCount($uid, $pageIndex, $pageSize, $timeStart, $timeEnd, $state) {
        $prefix = C('DB_PREFIX');
        $timeStart = (!empty($timeStart)) ? date("Y-m-d H:i:s", $timeStart) : $timeStart;
        $timeEnd = (!empty($timeEnd)) ? date("Y-m-d H:i:s", $timeEnd) : $timeEnd;
        if ($state > '0') {//=1已晒单
            $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (dp.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')" : '';
            $WHERE = "WHERE ((dp.uid=" . $uid . ")" . " and (dp.status =1) " . $BETWEEN . ")";
            $sql = "select count(*) as total from(select dp.id from os_display_product as dp " . $WHERE . "  order by dp.id)temp  ";
        } else {//=0未晒单
            $BETWEEN = (!empty($timeStart) && !empty($timeEnd)) ? " and (dp.apply_time BETWEEN '" . $timeStart . "' AND '" . $timeEnd . "')" : '';
            $WHERE = "WHERE ((dp.uid=" . $uid . ")" . "and (dp.status <>1) " . $BETWEEN . ")";
            $sql = "select count(*) as total from(select dp.id from os_display_product as dp " . $WHERE . "  order by dp.id)temp  ";
        }
        //echo $sql;
        $count = $this->query($sql);
        $count = $count[0]["total"];
        //echo $count;
        return $count;
    }

    /**
     * 商品-晒单列表
     */
    public function displayRecord($pid) {
        $prefix = C('DB_PREFIX');
        $con['pid'] = $pid;
        $sum = M('display_product')->where($con)->count();
        $sql = "select dp.lottery_id,dp.title,dp.description,dp.pics,dp.apply_time,u.uid,u.nickname,u.face path from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u.uid=dp.uid "
                . "where dp.pid=" . $pid . "  and dp.status=1";
        $list = $this->query($sql);
        return array('count' => $sum, 'display' => $list);
    }

    /**
     * wap 所有晒单列表
     */
    public function ordershare($pageIndex, $pageSize) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;

        $sql = "select dp.id,dp.audit_time,dp.lottery_id,dp.title,dp.description,dp.pics,dp.apply_time,u.uid,u.nickname,u.face path from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u.uid=dp.uid "
                . "where dp.audit_time > 0 "
                . "ORDER BY dp.audit_time desc "
                . "limit $start,$end";

        $sql1 = "select COUNT(u.uid) as sharecount from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u .uid=dp.uid "
                . "where dp.audit_time > 0 ";
        $list = $this->query($sql);
        $sharecount = $this->query($sql1);
        $page = ceil($sharecount[0]['sharecount'] / $pageSize);
        return array('list' => $list, 'sharecount' => $sharecount[0], 'page' => $page);
    }

    /**
     * wap 所有晒单详情
     */
    public function ordershareInfo($did) {
        $prefix = C('DB_PREFIX');
        $sql = "select u.face,SUM(la.attend_count) as attend_count,la.attend_ip,la.ip_address,u.uid,lp.lottery_code,lp.need_count,lp.lottery_time,dp.audit_time,dp.lottery_id,dp.title,dp.description,dp.pics,dp.apply_time,u.uid,u.nickname,u.face path from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u.uid=dp.uid "
                . "left join " . $prefix . "lottery_product lp on dp.lottery_id=lp.lottery_id "
                . "left join " . $prefix . "lottery_attend la on dp.lottery_id=la.lottery_id "
                . "where dp.id = $did and u.uid=la.uid "
                . "ORDER BY dp.audit_time desc ";
        // . "limit 1";
        $list = $this->query($sql);
        return array('list' => $list);
    }

    /**
     * wap 单个商品所有晒单列表
     */
    public function proOrderShare($pid, $pageIndex, $pageSize) {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;

        $sql = "select dp.id,dp.audit_time,dp.lottery_id,dp.title,dp.description,dp.pics,dp.apply_time,u.uid,u.nickname,u.face path from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u.uid=dp.uid "
                . "where dp.audit_time > 0 and dp.pid=" . $pid
                . " ORDER BY dp.audit_time desc "
                . "limit $start,$end";

        $sql1 = "select COUNT(u.uid) as sharecount from " . $prefix . "display_product dp "
                . "left join " . $prefix . "member u on u .uid=dp.uid "
                . "where dp.audit_time > 0 and dp.pid=" . $pid;
        $list = $this->query($sql);
        $sharecount = $this->query($sql1);
        return array('list' => $list, 'sharecount' => $sharecount[0]);
    }

    /*
     * 个人中心-浏览商品数量
     */

    public function readCount($uid) {
        $prefix = C('DB_PREFIX');
        $sql = "select count(lottery_id) c from " . $prefix . "read where uid=$uid";
        $count = $this->query($sql);
        $total = $count[0]['c'];
        return $total;
    }

    /*
     * 个人中心-浏览商品列表
     */

    public function readList($uid, $pageIndex, $pageSize) {
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');
        $sql = "select c.lottery_id,c.pid,c.create_time,d.cover_id,d.title,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,p.path from " . $prefix . "read c "
                . "join " . $prefix . "document d on c.pid=d.id "
                . "join " . $prefix . "lottery_product lp on c.lottery_id=lp.lottery_id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where c.uid=$uid limit $start,$end";
        $list = M('collect')->query($sql);
        return $list;
    }

    /*
     * 个人中心-记录浏览商品
     */

    public function readProduct($uid, $pid, $lotteryId) {
        $condition['uid'] = $uid;
        $condition['pid'] = $pid;
        $condition['lottery_id'] = $lotteryId;
        $list = M('read')->where($condition)->select();
        if ($list) {
            return -1;
        } else {
            $con['uid'] = $uid;
            $count = M('read')->where($con)->count();
            $yesterday_time = time() - ( 1 * 24 * 60 * 60 ); //昨天的毫秒数
            if ($count >= 10) {
                $del_con['create_time'] = array('lt', $yesterday_time);
                M('read')->where($del_con)->delete(); //删除全部小于昨天的浏览数据
            }
            $data['uid'] = $uid;
            $data['pid'] = $pid;
            $data['lottery_id'] = $lotteryId;
            $data['create_time'] = TimeUtil::timeStamp();
            $flag = M('read')->add($data);
            if ($flag) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /*
     * 个人中心-关注商品
     */

    public function focusProduct($uid, $pid, $lotteryId) {
        $condition['uid'] = $uid;
        $condition['pid'] = $pid;
        $condition['lottery_id'] = $lotteryId;
        $list = M('collect')->where($condition)->select();
        if ($list) {
            return -1;
        } else {
            $data['uid'] = $uid;
            $data['pid'] = $pid;
            $data['lottery_id'] = $lotteryId;
            $data['create_time'] = TimeUtil::timeStamp();
            $flag = M('collect')->add($data);
            if ($flag) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /*
     * 个人中心-关注商品数量
     */

    public function focusCount($uid) {
        $prefix = C('DB_PREFIX');
        $sql = "select count(lottery_id) c from " . $prefix . "collect where uid=$uid";
        $count = $this->query($sql);
        $total = $count[0]['c'];
        return $total;
    }

    /*
     * 个人中心-关注商品列表
     */

    public function focusList($uid, $pageIndex, $pageSize) {
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $prefix = C('DB_PREFIX');
        $sql = "select c.lottery_id,c.pid,c.create_time,d.cover_id,d.title,lp.need_count,lp.attend_count,lp.attend_limit,lp.max_attend_limit,p.path from " . $prefix . "collect c "
                . "join " . $prefix . "document d on c.pid=d.id "
                . "join " . $prefix . "lottery_product lp on c.lottery_id=lp.lottery_id "
                . "left join " . $prefix . "picture p on d.cover_id=p.id "
                . "where c.uid=$uid limit $start,$end";
        $list = M('collect')->query($sql);
        return $list;
    }

    /*
     * 晒单详情
     */

    public function orderInfo($id = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select m.nickname,m.face,la.attend_count,lp.lottery_code,FROM_UNIXTIME(la.create_time,'%Y-%m-%d %H:%i:%s') as attend_time, dp.apply_time" .
                " as dp_time,dp.score,dp.title as dp_title,dp.description,dp.pics,w.title,w.thumbnail,w.apply_time,lp.need_count as price,lp.lottery_id,lp.pid" .
                " from " . $prefix . "member as m right join " . $prefix . "win_prize as w on m.uid = w.uid" .
                " right join " . $prefix . "display_product as dp on w.id=dp.win_id " .
                " left join " . $prefix . "lottery_product as lp on dp.lottery_id=lp.lottery_id " .
                " left join " . $prefix . "lottery_attend as la on lp.lottery_id=la.lottery_id " .
                " where dp.id=" . $id . " group by dp.id";
        //echo $sql;
        $list = $this->query($sql);
        return $list[0];
    }

    /*
     * 晒单评论
     * 
     */

    public function commentInfo($dpid = '', $pageIndex = '', $pageSize = '') {
        $prefix = C('DB_PREFIX');
        $start = $pageIndex * $pageSize;
        $end = $pageSize;
        $sql = "select c.id,m.nickname,m.face,c.create_time,c.content from " . $prefix . "comment " .
                "as c left join " . $prefix . "member as m on c.uid=m.uid where did=" . $dpid . " order by c.id desc limit $start,$end";
        //echo $sql;
        $list = $this->query($sql);
        return $list;
    }

    /*
     * 根据期号、用户id查询当前用户的参与时间、参与码集合
     */

    public function getAttendInfo($uid = '', $lottery_id = '') {
        $prefix = C('DB_PREFIX');
        $sql = "select la.id,la.create_time,la.lucky_code,la.attend_count from   " . $prefix . "lottery_attend as la " .
                " WHERE (la.uid=" . $uid . " and la.lottery_id = " . $lottery_id . ") group by la.id desc ";
        /// echo $sql;
        $list = $this->query($sql);
        $sum = 0;
        foreach ($list as $key => $value) {
            $sum += $list[$key]['attend_count'];
        }
        $result["sum"] = $sum;
        $result["list"] = $list;
        return $result;
    }

}
