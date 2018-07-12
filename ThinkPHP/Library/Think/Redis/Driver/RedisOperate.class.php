<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace Think\Redis\Driver;

/**
 * Redis缓存驱动
 * 要求安装phpredis扩展：https://github.com/nicolasff/phpredis
 * @author    charles <huichen1221@qq.com>
 */
class RedisOperate
{
    protected static $_instance = null;
    protected static $options;


    public function __construct()
    {
        //加载配置文件
        self::$options=C('redis_config');
    }

    /*创建__clone方法防止对象被复制克隆*/
    public function __clone(){
        trigger_error('Clone is not allow!',E_USER_ERROR);
    }

    public static function getInstance($options = array()){
  /*      if (!extension_loaded('redis')) {
            throw new Exception('_NOT_SUPPERT_:redis');
        }*/
        if (!empty($options)) {
            self::$options = array_merge(self::$options, $options);
        }
        if (null === self::$_instance) {
            self::$_instance = new \Redis;;
            $func          = self::$options['persistent'] ? 'pconnect' : 'connect';

            false === self::$options['timeout'] ?
            self::$_instance->$func(self::$options['host'], self::$options['port']) :
            self::$_instance->$func(self::$options['host'], self::$options['port'], self::$options['timeout']);
                if ('' != self::$options['password']) {
                    self::$_instance->auth(self::$options['password']);
                }


        }
        return self::$_instance;
    }


}

