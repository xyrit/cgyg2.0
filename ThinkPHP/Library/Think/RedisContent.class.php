<?php
namespace Think;


use Think\Redis\Driver\RedisOperate;

class RedisContent
{

    private $level;  //第几层数据库
    private  static $redisObj;
  public function __construct($level)
  {

      //$RedisOperate= new RedisOperate;
      $obj = new \Think\Redis\Driver\RedisOperate;
      $this->level=$level;
      $this->redisObj= $obj::getInstance();
      //切换到第二层
      $this->select();

  }
    public function select(){

        $this->redisObj->select($this->level);
    }

    //存入单个数据
    public function set($key,$value)
    {
        $this->redisObj->set($key,$value);
        return true;
    }

    //取出单个数据
    public function get($key)
    {
        $value=$this->redisObj->get($key);
        return $value;
    }

    //删除数据
    public function del($key)
    {
        $value=$this->redisObj->del($key);
        return $value;
    }

    //原生
    public function redis()
    {

        return $this->redisObj;
    }

    //存入到固定的key
    public function setcode($key,$value)
    {
        $redis=$this->redisObj;
      $rs=  $redis->sAdd($key,$value);
        return $rs;
    }


    //随机取出 code    复杂 以后用
    public function getcode($data)
    {

        $arrleve=array();
        $redis=$this->redisObj;

        $level=ceil($data['num']/3000);
        $remain=$data['num']%3000;   //剩余
        //层次
        for($i=0;$i<$level;$i++)
        {
            $arrleve[$i]['count']=3000;
           if($level-1 ==$i && $remain > 0)
           {
               $arrleve[$i]['count']=$remain;
           }
        }

        foreach($arrleve as &$row)
        {
            $codes='';
            for($i=0;$i<$row['count'];$i++)
            {
                $code=$redis->SPOP($data['code']);
                if(!$code)
                {
                return $arrleve;           //如果没有了 返回已经购买好的
                }

                $codes.=$code.',';
                dump($codes);
            }

           $row['locky_code']=$codes;
        }


        return $arrleve;

    }

    public function easygetcode($data)
    {

        $arr = array();
        $redis = $this->redisObj;
        $codes = '';
        for ($i = 1; $i <= $data['num']; $i++) {

            $code = $redis->SPOP($data['code']);
            if (!$code) {
                $no_remain = $data['num'] - $i;              //未购买的个数
                $arr['no_remain'] = $no_remain;
                return $arr;           //如果没有了 返回已经购买好的

            }
            $codes .= $code . ',';
            $arr['locky_code'] = $codes;
            $arr['no_remain'] = 0;
        }

             $arr['no_remain'] = 0;
             return $arr;
    }

}