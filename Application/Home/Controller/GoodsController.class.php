<?php

/*
 *  商品类
 * @2016年4月12日 15:30:01
 */

namespace Home\Controller;

class GoodsController extends HomeController
{

    //商品详情
    public function detail(){

        $lottery_id=$_GET['lottery_id'];
      //  $user_id=1;   //测试；

        if(!$lottery_id)
        {
            echo '该商品不存在';
            exit;
        }



   $data=M('lottery_product')->where('lottery_id='.$lottery_id)->find(); //查询分类名称        $data['cat_name'] =M('category')->where('id='.$data['cat_id'])->getField('title as cat_name');
        if(!$data)
        {
            echo '该商品不存在';
            exit;
        }


        //登录查询购买次数
        if($user_id)
        {

            $arr=array(
                'uid'=>$user_id,
                'lottery_id'=>$data['lottery_id']
            );
            $user_count= M('lottery_attend')->where($arr)->count();
        }


        if($data['status']==0)
        {
            //未开奖

            $infos= M('shop_detail')->where('id='.$data['pid'])->find();
            $infos['images'] = unserialize($infos['images']);
            $images=  explode(',',$infos['images'] );
            $data['images']=$images;

            $data['info']= $infos['content'];
            
            $where = array(
              'lp.lottery_pid'=>$data['lottery_pid']-1,
                'la.pid'=>$data['pid'],
            );
            //查询上一期开奖
            $up_lettery=M('lottery_product lp')->field('sum(la.attend_count) as s_attend_count,lp.lottery_id,m.nickname,m.uid,la.ip_address,m.face,lp.lottery_code,lp.lottery_time,lp.last_attend_date_time')->join('left join cg_member m on lp.uid=m.uid')->join('left join cg_lottery_attend la on la.lottery_id=lp.lottery_id')->where($where)->find();


            $this->assign('user_id',$user_id);
            $this->assign('user_count',$user_count);      //登录购买次数
            $this->assign('up_lettery',$up_lettery);
            $this->assign('data',$data);
            $this->display();
            exit;
        }
        else
        {

            if($data['status']==1)            //等待开奖
            {
               $data['nowtime']=time();


            }

            if($data['status']==2)               //已开奖
            {
                //查询中奖用户信息
                if($data['uid'])
                {
                    $userInfo = M('lottery_attend la')->field("m.uid,m.nickname,m.face,sum(la.attend_count) as ucount")->join('cg_member m on la.uid=m.uid')->where('m.uid='.$data['uid'])->find();
                    //查询本期参与总次数
                    $this->assign('userInfo',$userInfo);
                }
                else
                {
                    dump('错误');
                }
                exit;


            }

            //查询计算结果  最近50条购买记录
           $purchase_list= M('lottery_attend la')->field('la.create_time,m.nickname,la.attend_count,la.lottery_id,doi.title')->join('cg_member m on la.uid=m.uid')->join('cg_shop doi on doi.id=la.pid')->limit(0,50)->order('la.create_time desc')->select();
            $purchase_time_sum =M('lottery_attend la')->limit(0,50)->sum('la.create_time');


            $this->assign('purchase_time_sum',$purchase_time_sum ? $purchase_time_sum:0 );
            $this->assign('purchase_list',$purchase_list);    //所有商品50条购买记录
            $this->assign('user_id',$user_id);
            $this->assign('user_count',$user_count);      //登录购买次数
            $this->assign('data',$data);
            $this->display('detail_2');

        }
        

    }
    
    
    
    public function lists(){
        
        $index = intval(I('get.p')) > 0 ? intval(I('get.p')) : 1;
        $pagesize = 8;
        
        $category_tree = S('category_tree');
        if(!$category_tree){
            $category_tree = D('category')->getTree();
            S('category_tree',$category_tree);
        }
        $this->assign('category_tree',$category_tree);
        
        
        $params = array();
        $params['cat_id'] = intval(I('get.cat_id'));
        $params['keyword'] = trim(I('get.keyword'));
        
        $goods_data = D('goods')->getGoodsList($index,$pagesize,$params);
        //print_r($goods_data);
        $this->assign('goods_data',$goods_data);
        
        $page = new \Think\Page($goods_data['total_count'],$pagesize);
        $page_str = $page->show();
        $this->assign('page_str',$page_str);
        
        //echo $page_str;
        $this->display();

    }


    //购买记录分页

    public function purchase_records(){

        $pageIndex = (I('pageIndex')-1)*20;
        $lottery_id = I('lottery_id');
       $list=  M('lottery_attend la')->field('la.id,la.lucky_code,la.create_time,m.face,m.nickname,m.uid,la.attend_count,la.attend_ip,la.ip_address,la.attend_device')->join('cg_member m on m.uid=la.uid')->where('lottery_id='.$lottery_id)->limit($pageIndex,20)->select();
        $total= M('lottery_attend la')->join('left join cg_member m on m.uid=la.uid')->where('lottery_id='.$lottery_id)->count();


        $data=array(
            'code'=>200,
            'list'=>$list,
            'total'=>$total,

        );
        echo json_encode($data);

    }

    //晒单分页
    public function show_single_list(){

        $pageIndex = (I('pageIndex')-1)*20;
        $pid = I('pid');
       $display_product= M('display_product dp');
       $list= $display_product->field('m.face,m.nickname,m.uid,dp.title,dp.create_time,dp.pid,dp.lottery_id,dp.description,dp.pics')->join('left join cg_member m on m.uid=dp.uid')->limit($pageIndex,20)->where('dp.pid='.$pid.' and dp.status=1')->select();
      //  $list=$display_product->getLastSql();
        $total= $display_product->field('m.face,m.nickname,m.uid,dp.title,dp.create_time,dp.pid,dp.lottery_id,dp.description,dp.pics')->join('left join cg_member m on m.uid=dp.uid')->where('dp.pid='.$pid.' and dp.status=1')->count();
        $data=array(
            'code'=>200,
            'list'=>$list,
            'total'=>$total,
        );

        echo json_encode($data);

    }


    //购买完成,进入正在揭晓
    public function lettery_way($lottery_id='10000851'){

      $lpInfo=  M('lottery_product')->where('lottery_id='.$lottery_id)->find();

        if($lpInfo['need_count']==$lpInfo['attend_count'] && $lpInfo['status']==0 )
        {
            $time = time();
            $last_attend_time=date('ymdhis',$time);

            $total_time=M('lottery_attend')->where('is_pay=1')->limit(50)->sum('pay_time');
            $updata=array(
                'last_attend_time'=>$time,
                'last_attend_date_time'=> $last_attend_time,
                'total_time'=>$total_time,
                'expecttime'=> \Think\Cqssc::getExpectTime(),                   //开奖倒计时
                'status'=>1
            );
            M('lottery_product')->startTrans();
            $rs1=M('lottery_product')->where('lottery_id='.$lottery_id)->save($updata);  //更新表

          $goodInfo= M('shop')->where('id='.$lpInfo['pid'])->find();

            if($goodInfo['stocks']>0 && $goodInfo['max_period'] >=$lpInfo['lottery_pid'])
            {
                //开始新的一期
               $need_count= intval($goodInfo['marketprice']/$goodInfo['single_price']);   //计算总人次

                $new_lottery =array(
                   'pid'=>$goodInfo['id'],               //商品id
                    'lottery_pid'=>$lpInfo['lottery_pid']+1,         //单个商品的开奖期号
                    'need_count'=>$need_count,          //需要总人次(总价)
                    'attend_count'=>0,      //参加人次，(该期商品已购买人次)
                    'attend_ratio'=>0,      //参加人次完成比例
                    'create_time'=>time(),   //创建时间
                    'cat_id'=>$goodInfo['cat_id'],
                    'title'=>$goodInfo['title'],
                    'description'=>$goodInfo['description'],
                    'f_title'=>$goodInfo['f_title'],
                    'position'=>$goodInfo['position'],
                    'thumb'=>$goodInfo['thumb'],
                    'marketprice'=>$goodInfo['marketprice'],
                    'single_price'=>$goodInfo['single_price'],
                    'max_period'=>$goodInfo['max_period'],
                    'purchase_frequency'=>$goodInfo['purchase_frequency'],
                    'status'=>0
                );
               $new_id= M('lottery_product')->add($new_lottery);
                $new_lettery_id=10000000+$new_id;
                $rs2= M('lottery_product')->where('id='.$new_id)->save(array('lottery_id'=>$new_lettery_id));

                if($rs1 && $new_id && $rs2 )
                {
                    M('lottery_product')->commit();
                }
                else
                {
                    M('lottery_product')->rollback();
                }

            }

        }
        return true;
    }







}

