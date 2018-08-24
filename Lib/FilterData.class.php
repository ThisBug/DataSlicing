<?php
/**
 * Description of FileData.class.php
 * @remark 过滤SQL语句,被缓存的数据从缓存中取出返回,否则查库
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2018.01.13
 */

error_reporting(1);
if(strrpos(dirname(__FILE__),'\\') == true)
{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'\\')).'/entrance.php';
}else{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'/')).'/entrance.php';
}
/***
 * Class FilterData
 * @param---------------------------------
 * $_redis          缓存数据redis
 * $_key_redis      缓存大块统计数据redis
 * $_mysql          mysql资源
 * $_mongo          mongo资源
 * $_sql            需要执行的select语句
 * $_table          需要查询的表名
 * $cache_indexes   已总缓存数量
 * $data_count      可总缓存数量
 * @param---------------------------------
 *
 * @function------------------------------
 * func init()          初始化资源
 * func main()          主函数,需要数据直接调用此函数即可
 * func dataCount()     统计可总缓存数量
 * func dataExists()    查询当前sql对应数据是否存在
 * func readData()      使用自带sql引擎查询数据
 * func storeData()     储存数据至缓存中
 * func takeData()      负责取出数据
 * func expire()        检查数据是否过期
 * func testing()       检查配置项是否正确
 * func isFilter()      过滤数据进入缓存规则
 * @function------------------------------
 *
 * @class---------------------------------
 * FilterData       过滤sql语句,查询数据
 * Advance          调用FilterData类前处理
 * @class---------------------------------
 */
class FilterData
{
    use Advance{testing as public;}

    public $_redis       = null;
    public $_key_redis   = null;
    protected $_mysql       = null;
    protected $_mongo       = null;
    public static $_sql     = '';
    public static $_table   = '';
    public    $cache_indexes= 0;
    public    $data_count   = 0;

    public function init()
    {
        if(DsConfig::dsConst()['CHECK_FRAME'] == true)
            self::testing();

        $this->_redis     = new DSRedisModel(1);
        $this->_key_redis = new DSRedisModel(2);
        $this->_mysql     = new DSEasyDBModel();
        if(DsConfig::dsConst()['SLICING_TYPE'] == (3||4)){ $this->_mongo = new DSMongoModel();$this->_mongo->selectDB('DataSlicing');}
        $this->cache_indexes = slicingType(1,$this->_redis,$this->_mongo);
        self::dataCount();
    }

    /***
     * @param string $sql       //SQL语句
     * @param array  $result    //SQL查询后的结果集
     * @return mixed
     */
    public function main($sql = '',$result = [])
    {
        if(DsConfig::dsConst()['DS_STATS'] == false)
        {
            return false;
        }

        self::init();
        preg_match('/SELECT(.*?)+from\s+([`\w_\(\)]+)[\s+where\s+]?.*(?!\))$/i', $sql, $matches);
        self::$_sql     = trim(rtrim($matches[0],')'));
        self::$_table   = trim($matches[2],'`');
        /*获取结果集*/
        $data = self::takeData(self::$_table,$result);
        return $data;
    }

    public function dataCount()
    {
        $redis_count = $this->_redis->get('DataSlicing:Cache:redis_count');
        $mongo_count = $this->_redis->get('DataSlicing:Cache:mongo_count');
        $mysql_count = $this->_redis->get('DataSlicing:Cache:mysql_count');
        $this->data_count = $redis_count+$mongo_count+$mysql_count;
    }

    public function dataExists($sql = '',$table = false)
    {
        $md5_sql = md5($sql);
        $judge = json_decode($this->_key_redis->hget('DataSlicing:mysql_record',$md5_sql),true);
        if($judge == false && $this->data_count >= $this->cache_indexes['count'])return false;

        return ['table'=>$table,'md5_sql'=>$md5_sql,'judge'=>$judge];
    }

    public function readData()
    {
        $data = (array)$this->_mysql->queryAll(self::$_sql);
        if(isset($data[1]) == false && DsConfig::dsConst()['FIND_ONE'] == true)
        {
            $data = $data[0];
        }
        return $data;
    }

    public function storeData($data)
    {
        slicingType(2,$this->_redis,$this->_mongo,$data['md5_sql'],self::$_table,$data['result'],$this->cache_indexes,self::$_sql);
        return $data['result'];
    }

    public function takeData($table = '',$result = '')
    {
        $data =  self::dataExists(self::$_sql,$table);

        if($data == false && $result == false)
        {
            return false;
        }elseif ($data == false){
            $data['md5_sql'] = md5(self::$_sql);
        }

        self::isFilter($data['md5_sql'],$data['judge']);

        $data['result'] = $result;

        if($data['result'] == false)
        {
            $is_expire = self::expire($data);
        }

        if($is_expire == false)
        {
            if(DsConfig::dsConst()['SELF_MYSQL'] == true && $data['result'] == false)
            {
                $data['result'] = self::readData();
            }elseif(DsConfig::dsConst()['SELF_MYSQL'] == false && $data['result'] == false){
                return false;
            }

            if($data['result'] == true)
            {
                self::storeData($data);
            }
        }else{
            if($data['result'] == false)
            {
                $data['result'] = $is_expire;
            }
        }
        return $data;
    }

    public function expire($data)
    {
        if($data['judge'] == false) return false;
        if(time() - $data['judge'][4] > DsConfig::dsConst()['HOT_DATA_UPDATE'])
        {
            /*清除过期数据*/
            $this->_key_redis->hdel('DataSlicing:mysql_record',$data['md5_sql']);
            return false;
        }else{
            $result = json_decode(slicingType(3,$this->_redis,$this->_mongo,$data['md5_sql'],self::$_table),true);
            return $result;
        }
    }

    public function isFilter($task_id,$judge = false)
    {
        if(DsConfig::dsConst()['IS_FILTER'] == false)
        {
            if($judge == false)
            {
                $this->_redis->hset("DataSlicing:mysql_record",$task_id,json_encode(self::$_sql));
                $num = $this->_redis->zIncrBy('DataSlicing:mysql_sql',1,$task_id);
            }
            if($num >= DsConfig::dsConst()['FILTER_NUM'])
            {
                return true;
            }

            return false;
        }else{
            return true;
        }
    }

}

trait Advance
{
    /*检查配置项*/
    private static function testing()
    {
        $warring = function ($func,$filename = 'config')
        {
            return $return = "Fatal error,".strval($func)." is unlawful definition in $filename!\n";
        };

        /*判断是否启用数据分层*/
        if(DsConfig::dsConst()['SLICING_TYPE'] != (1||2||3||4))
        {
            exit($warring('SLICING_TYPE'));
        }

        /*判断是否填写数据库配置项*/
        if((isset(DsConfig::dsConst()['DB_HOST']) || isset(DsConfig::dsConst()['DB_PORT']) || isset(DsConfig::dsConst()['DB_NAME']) || isset(DsConfig::dsConst()['DB_USER']) || isset(DsConfig::dsConst()['DB_CHARSET'])) === (false || '' || Null))
        {
            exit($warring('MySQL'));
        }

        /*判断是否填写Redis配置项*/
        if((isset(DsConfig::dsConst()['REDIS_HOST']) || isset(DsConfig::dsConst()['REDIS_PORT']) || isset(DsConfig::dsConst()['REDIS_DB']) || isset(DsConfig::dsConst()['REDIS_OCCUPY'])) === (false || '' || Null))
        {
            exit($warring('Redis'));
        }

        /*判断是否填写Redis配置项*/
        if((isset(DsConfig::dsConst()['BIG_REDIS_HOST']) || isset(DsConfig::dsConst()['BIG_REDIS_PORT']) || isset(DsConfig::dsConst()['BIG_REDIS_DB'])) === (false || '' || Null))
        {
            exit($warring('BIG_Redis'));
        }

        /*检测自动加载类是否存在*/
        if(class_exists('Autoload') == false)
        {
            exit($warring('Autoload','class'));
        }

        /*检测Redis类是否存在*/
        if(class_exists('DSRedisModel') == false)
        {
            exit($warring('DSRedisModel','Model'));
        }

        /*检测MySQL类是否存在*/
        if(class_exists('DSEasyDBModel') == false)
        {
            exit($warring('DSEasyDBModel','Model'));
        }

        /*检测Mongo类是否存在*/
        if(class_exists('DSMongoModel') == false)
        {
            exit($warring('DSMongoModel','Model'));
        }

        return true;
    }

}