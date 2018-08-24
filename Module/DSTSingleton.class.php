<?php

trait DSTSingleton {
    private $_serialize_id = 1234567890;
    private static $_flag = false ;
    //private 构造函数
    private function __construct() {
        if ( self::$_flag ) {
            throw new Exception("I'm a Singleton");
        }
        else {
            self::$_flag = true;
        }
        $this->setSerializeId(rand(1,1000000000000));
        echo $this . " I'm construct! process id is " . getmypid() . " and thread id is " . Thread::getCurrentThreadId() . "\n";
    }
    private function __clone() {
        echo $this . " I'm clone! process id is " . getmypid() . " and thread id is " . Thread::getCurrentThreadId() . "\n";
    }

    /**
     * @return mixed
     */
    public function getSerializeId() {
        return $this->_serialize_id;
    }

    /**
     * @param mixed $serialize_id
     */
    public function setSerializeId($serialize_id) {
        $this->_serialize_id = $serialize_id;
    }

    //单例方法访问类实例
    public static function getInstance() {
        static $instance ;
        if (!($instance instanceof self )) {
            $ref = new ReflectionClass( get_called_class() );
            $ctor = $ref->getConstructor();
            $ctor->setAccessible(true);
            $instance = $ref->newInstanceWithoutConstructor();
            $ctor->invokeArgs($instance, func_get_args());
        }
        return $instance;
    }
    public function __toString()
    {
        return __CLASS__ . " " . $this->getSerializeId() ;
    }

    public function __sleep()
    {
        // TODO: Implement __sleep() method.
        throw new Exception("I'm Singleton! Can't serialize");
    }

    public function __wakeup()
    {
        // TODO: Implement __wakeup() method.
        throw new Exception("I'm Singleton! Can't unserialize");
    }
}