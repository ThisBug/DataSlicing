<?php
/**
 * Description of RedisModel
 * @remark redis操作类
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2017.12.31
 */

class DSRedisModel {
    use DSTSingleton;

    protected $redis;
    protected $host;
    protected $port;

    public function __construct($type = 0, $host=false, $port=false ,$db=0,$pconnect = false) {
        switch ($type)
        {
            case 1:
                $host = DsConfig::dsConst()['REDIS_HOST'];
                $port = DsConfig::dsConst()['REDIS_PORT'];
                $db   = DsConfig::dsConst()['REDIS_DB'];
                break;
            case 2:
                $host = DsConfig::dsConst()['BIG_REDIS_HOST'];
                $port = DsConfig::dsConst()['BIG_REDIS_PORT'];
                $db   = DsConfig::dsConst()['BIG_REDIS_DB'];
                break;
            default:
                $host = $host?:DsConfig::dsConst()['REDIS_HOST'];
                $port = $port?:DsConfig::dsConst()['REDIS_PORT'];
                $db   = $db?:DsConfig::dsConst()['REDIS_DB'];
                break;
        }
        $this->redis = new Redis;
        /**
         * 1.connect：脚本结束之后连接就释放了。
         * 2.pconnect：脚本结束之后连接不释放，连接保持在php-fpm进程中。所以使用pconnect代替connect，可以减少频繁建立redis连接的消耗。
         */
        if($pconnect === true){
            $this->redis->pconnect($host,$port);
        }else{
            $this->redis->connect($host,$port);
        }
        //选择db
        $this->redis->select(intval($db));
    }

    /**
     *
     * @param string $key    key值
     * @param string $val    存入的值
     * @param int    $outTime 过期时间,默认60秒 //设置为0表明此数据永不过期
     * @return boolean
     */
    public function set($key, $val, $outTime = 60)
    {
        if(is_string($val) == false && is_numeric($val) == false){
            $val = serialize($val);
        }
        if (empty($key)) {
            return false;
        }
        if ( $outTime <= 0 )
            return $this->redis->set($key,$val);

        return $this->redis->setex($key, $outTime,$val);
    }

    //字符串类型获取
    public function get($key)
    {
        if (empty($key)) {
            return false;
        }
        $res = $this->redis->get($key);
        return $res;
    }
    //增加元素的值 $num 数值大小
    public function increment($key, $num = 1)
    {
        if (empty($key)) {
            return false;
        }
        return $this->redis->incrBy($key,$num);
    }
    //减少元素的值 $num 数值大小
    public function decrement($key, $num = 1)
    {
        if (empty($key)) {
            return false;
        }
        $this->redis->decrBy($key, $num);
    }
    //判断key是否存在。存在 true 不在 false
    public function exists($key) {
        return $this->redis->exists($key);
    }

    //散列类型添加
    public function hset($key, $field, $value) {
        return $this->redis->hSet($key, $field, $value);
    }

    //散列返回hash中所有元素的个数
    public function hLen($key)
    {
        if(empty($key)) return false;
        return $this->redis->hLen($key);
    }

    //散列类型获取
    public function hget($key, $field) {
        return $this->redis->hGet($key, $field);
    }
    //散列 获取所有
    public function hGetall($key)
    {
        if(empty($key)) return false;
        return $this->redis->hGetall($key);
    }

    //散列判断key是否存在
    public function hExists( $key, $hashKey )
    {
        return $this->redis->hExists($key,$hashKey);
    }

    //散列 获取字段名
    public function hkeys($key) {
        return $this->redis->hKeys($key);
    }

    //散列 增加数字
    public function hIncrBy($key, $field, $increment) {
        return $this->redis->hIncrBy($key, $field, $increment);
    }

    //添加有序集合元素
    public function zadd($key, $score, $member) {
        return $this->redis->zadd($key, $score, $member);
    }
    //有序 增加数字
    public function zrem($key, $member) {
        return $this->redis->zrem($key, $member);
    }

    //获取指定时间范围内的元素个数 
    public function zCount($key, $min, $max) {
        return $this->redis->zCount($key, $min, $max);
    }
    
    //通过key和value获取指定score
    public function zScore($key,$val){
        return $this->redis->zScore($key, $val);
    }

    //获取有序集合某个范围的元素列表(升序)
    public function zRange($key, $start, $stop) {
        return $this->redis->zRange($key, $start, $stop);
    }

    //获取有序集合某个范围的元素列表(升序)
    public function zRangeByScore($key, $start, $end,array $options = array()) {
        return $this->redis->zRangeByScore($key, $start, $end,$options);
    }

    //获取有序集合某个范围的元素列表(降序)
    public function zRevRangeByScore($key, $end,$start, array $options = array()) {
        return $this->redis->zRevRangeByScore($key, $end, $start,$options);
    }
    
    // 给有序集合某个值增加数值
    public function zIncrBy( $key, $increment, $member ){
        return $this->redis->zIncrBy( $key, $increment, $member );
    }

    // 删除有序集合中某个值
    public function zDelete( $key, $member1, $member2 = null, $memberN = null ){
        return $this->redis->zDelete( $key, $member1, $member2,$memberN);
    }

    public function rm($name) {
        return $this->redis->del($name);
    }

    //删除当前key
    public function clear($key)
    {
        if (empty($key))
        {
            return false;
        }
        return $this->redis->del($key);
    }

    //获取集合中的所有元素
    public function smembers($key) {
        return $this->redis->sMembers($key);
    }

    //添加集合元素
    public function sadd($key, $member) {
        return $this->redis->sAdd($key, $member);
    }

    //删除集合元素
    public function srem($key, $member) {
        return $this->redis->srem($key, $member);
    }
    
    //字符串增加数字
    public function incr($key){
    	return $this->redis->incr($key);
    }
    
    //字符串设置有效期
    public function expire($key,$time){
    	return $this->redis->expire($key,$time);
    }
    
    //压入队列
    public function rpush($key,$data){
    	return $this->redis->rPush($key,$data);
    }
    
    
    //删除key
    public function del($key){
    	return $this->redis->del($key);
    }

    //删除散列表的字段
    public function hdel($key,$field){
        return $this->redis->hDel($key,$field);
    }
    
    //获取有序集合元素从大到小排序
    public function zrevrange($key,$min,$max,$withscores = false){
    	return $this->redis->zrevrange($key,$min,$max,$withscores);
    }
    
    //获取有序集合某个元素的排名
    public function zrevrank($key,$member){
    	return $this->redis->zRevRank($key,$member);
    }

    //查看数据库
    public function select($key) {
        $res = $this->redis->select($key);
        return $res;
    }
    //读取数据并且删除
    public function get_and_del($key){
        if (empty($key)) {
            return false;
        }
        $res = $this->redis->get($key);
        $this->redis->delete($key);
        return $res;
    }
    //查看队列长度
    public function list_len($key) {
        $res = $this->redis->Llen($key);
        return $res;
    }

    public function keys($str){
        return $this->redis->keys($str);
    }

    /*查看redis系统信息*/
    public function info( $option = null ) {
        return $this->redis->info($option);
    }

    public function close()
    {
        $this->redis->close();
    }
}
