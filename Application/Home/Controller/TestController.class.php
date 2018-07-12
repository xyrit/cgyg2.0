<?php
namespace Home\Controller;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/18
 * Time: 10:44
    单元测试入口
 */

class TestController extends HomeController
{

    /* 订单支付  */

    public function testpay(){

    $orders=array(
        'pay_type'=>1,             //支付类型   1 余额支付  2线上支付
        'orders'=>array(
            '0'=>array(
            'lottery_id'=>'10000851',
            'attend_count'=>'10',
            'attend_ip'=>'11111111111',
            'ip_address'=>'asdasd',
            'attend_device'=>'电脑',
            ),
            '1'=>array(
                'lottery_id'=>'10000851',
                'attend_count'=>'10',
                'attend_ip'=>'11111111111',
                'ip_address'=>'asdasd',
                'attend_device'=>'电脑',
            ),
        )
    ) ;
    $rs= A('Shop')->pay_add($orders);
        dump($rs);
    }
      function ground(){
           $ids=S('ground_ids');
           $ids=  unserialize($ids);
          
               foreach ($ids as $k => $v) {
            $gid = $ids[$k];
//print($gid);//eixt;
            $pdata = M('shop')->where('id=' . $gid)->find();
            if (!$pdata) {
                $this->error("上架失败！");
            }
            if ($pdata['stocks'] == 1) {
                $this->error("商品已经上架！");
            }
            $pdata['pid'] = $pdata['id'];

            //删除id
            unset($pdata['id']);

            //查询商品开奖期数
            $maxlottery_pid = M('lottery_product')->where('pid=' . $gid)->order('lottery_pid desc')->getField('lottery_pid');
            $lettery_status = M('lottery_product')->where('pid=' . $gid)->order('lottery_pid desc')->getField('status');
            if ($lettery_status != null) {
                if ($lettery_status == 0) {
                    $this->error("该商品正在购买中，不能上架！");
                }
            }
            if ($maxlottery_pid >= $pdata['max_period']) {
                $this->error("最大开奖期数不能小于累计期数！");
            }
            $need_count = ceil(intval($pdata['marketprice']) / intval($pdata['single_price']));  //总次数
            $pdata['lottery_pid'] = $maxlottery_pid + 1;
            $pdata['need_count'] = $need_count;
            $pdata['create_time'] = time();
            $pdata['remain_count'] = $need_count;  //剩余人次
            //开启事务
            $lottery_product_obj = M('lottery_product');
            $lottery_product_obj->startTrans();

            $lid = $lottery_product_obj->add($pdata);
            $updata['id'] = $lid;
            $updata['lottery_id'] = 10000000 + $lid;
            $pdata['stutas'] = 0;   //改变商品状态
            $key = 'code_' . $pdata['pid'] . '_' . $updata['lottery_id'];
            $rs = $this->set_code($key, $pdata['need_count']);
            if (!$rs) {
                $lottery_product_obj->rollback();
                $this->error("上架失败！");
            }

            $rs1 = M('lottery_product')->save($updata);
            $rs2 = M('shop')->where('id=' . $gid)->save(array('stocks' => 1));
            // print_r($lid);echo "<br/>";print_r($rs1);echo "<br/>";print_r($rs2);exit;
            file_put_contents("put.txt", $gid,'FILE_APPEND');
            //file_get_contents($key, $use_include_path, $context, $offset, $maxlen)
            if ($lid && $rs1 && $rs2) {
                $lottery_product_obj->commit();
                //$this->success("上架成功！", Cookie('__forward__'));
                continue;
            } else {
                $lottery_product_obj->rollback();
                $this->error("上架失败！");
            }
        }
    }

}
