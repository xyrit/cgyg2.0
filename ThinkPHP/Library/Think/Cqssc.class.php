<?php

/*
 * 重庆时时彩API
 */

namespace Think;

class Cqssc{
    /*
     * 获取最近1条时时彩记录，每隔10s更新新的数据
     */
    public static $uid='152677';
    public static $name='cqssc';
    public static $token='c680b755bcec4773c204e72783b75675ee926c5f';

    public static function getCqssc() {

        $ssc_data=self::getLastSsc();
        if((NOW_TIME-$ssc_data['dateline'] ) < 10){ //如果10秒之类请求直接返回
            return false;
        }
        $url='http://api.caipiaokong.com/lottery/?name=' . self::$name . '&format=json&uid=' . self::$uid . '&token=' . self::$token . '&num=2' ;

        $data=file_get_contents($url);
         //$data
        $array = json_decode($data, true);
        if(is_array($array)){
            $issue=key($array); //期号
            $number=current($array); //号码跟时时彩的时间
            if(isset($number['code'])){ // 接口报错
                return false;
            }
            $adata['number'] =str_replace(',','',$number['number']); //时时彩号码
            $adata['ssc_time']=strtotime($number['dateline']);
            
            if($adata['ssc_time']> $ssc_data['ssc_time']){ //表示 拿到了最新的时时彩数据
                  $adata['issue']=$issue;
                  $adata['add_time']=NOW_TIME; 
                  M('Ssc')->add($adata);  
                  $ssc_data=$number;
                  S('ssc_data',$adata);
            }
          
        }
        if(NOW_TIME - $ssc_data['ssc_time'] > 4*60 ){
          return false;
        }
        return $ssc_data;
    }


       /*
     * 获取时时彩开奖的间隔时间
     */

    public static function getCqsscSpace($time) {
       $h = $time?date('H',$time):date('H');
        if ($h >= 10 && $h <= 21) { //时时彩开奖间隔10分钟
            $s = 10 * 60; 
            $divider=10;
        } else if ($h >= 22 && $h <= 23) { //时时彩开奖间隔5分钟
            $s = 5 * 60;
            $divider=5;
        } else if ($h >= 0 && $h < 2) { //时时彩开奖间隔5分钟
            $s = 5 * 60; 
            $divider=5;
        }
        return array('s'=>$s,'divider'=>$divider);
    }

    // 获取已经已保留的时时彩
    public static function getLastSsc(){
        $ssc_data=S('ssc_data');
        if(!$ssc_data){
            $ssc_data=M('Ssc')->field('issue,number,ssc_time,add_time')->order('ssc_time desc')->find();
            if(!$ssc_data){
              return false;
            }
        }
        return $ssc_data;
    }
   

    public static function getExpectTime($now=''){
      $now=$now?$now:time();
      /*
         对于凌晨1:55后 到 上午10点时时彩 不开奖的 特殊情况
       */
      $start_time=strtotime('01:55');
      $end_time=strtotime('10:00');
      $end_three_time=strtotime('10:03');
      if($now >= $start_time && $now <$end_time){
        return strtotime('10:03');
      }elseif($now >=$end_time && $now <=$end_three_time){
        return strtotime('10:13');
      }

      $hour=date('H',$now);
      $minute=date('i',$now);
      $timedata=self::getCqsscSpace($now);
      extract($timedata);
      $min= floor($minute/$divider)*$divider;  
      $time=$hour.':'.$min;
      $pre = strtotime($time); //当时最接近的那个时间
      $next=$pre+$s;  //下一个时间
      return $next+3*60;
    }
    
}
