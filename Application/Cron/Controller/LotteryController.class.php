<?php
namespace Cron\Controller;
/**
 * Class LotteryController 开奖系列计划任务
 */
class LotteryController extends \Think\Controller
{
    /**
     *  开奖结果
     */
    function result(){
        ignore_user_abort(true); // 后台运行
        set_time_limit(0); // 取消脚本运行时间的超时上限
        do {
             $si++;
             $ssc_data = \Think\Cqssc::getCqssc(); // 不要 轻易 动这个
             if ($ssc_data) {
                 break;
             }
             sleep(4);

         } while ($si < 10);

         if (!$ssc_data) {
             \Think\Log::write('时时彩出问题了啊，快检查', 'WARN', '', 'run.txt');
             exit;
         }


        //最新时时彩彩票揭晓


        $add_time = $ssc_data['add_time'];
        $hour_code = $ssc_data['number'];
        $field = 'lottery_id,pid,need_count,total_time';
        //获取时时彩开奖时间之前所有即将揭晓商品的记录
        $lottery = M('lottery_product')->field($field)->where('status=1 and expecttime <=' . $add_time)->order('expecttime desc')->select();
        if (!$lottery) {
            echo '没有要开的奖';
            exit;
        }
        foreach ($lottery as $v) {
            //揭晓幸运码（幸运码计算规则：(用户购买完最后一条记录的时间时当时全站所有商品最近购买的50条记录时间之和+最新一期的时时彩号码)%商品的总需人次）+1
            $lottery_code = intval(fmod(floatval($v['total_time']) + floatval($hour_code), $v['need_count'])) + 10000001;
            $data = array();
            $data['hour_lottery'] = $hour_code; //时时彩号码
            $data['hour_lottery_id'] = $ssc_data['issue']; //时时彩id
            $data['lottery_code'] = $lottery_code; //已揭晓的幸运码
            $data['lottery_time'] = time(); //揭晓幸运码时间

            $con['lottery_id'] = $v['lottery_id'];
            $con['lucky_code'] = array('like', "%$lottery_code%");
            $lottery_info = M('lottery_attend')->field('id,uid,attend_count,create_date_time')->where($con)->find();
            if ($lottery_info) {
                $data['uid'] = $lottery_info['uid'];
                $data['status']='2';
                $attend_count=M('lottery_attend')->where(array('uid'=>$lottery_info['uid'],'lottery_id'=>$v['lottery_id']))->sum('attend_count');
                $data['userinfo']=serialize(array('attend_count'=>$attend_count));
                M('lottery_product')->where(array('lottery_id' => $v['lottery_id']))->save($data); //更新开奖表中奖用户id和参与记录id

            }


        }
        exit;

    }


    public function test(){
        echo '11111';
    }


}