<?php
/**
 * Description of function.php
 * @remark 功能函数文件
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2018.01.08
 */

/**
 * 按传入键名输出数组的值
 * @param array $arr
 * @param string $k
 * @return string
 */
function showVal($arr, $k) {
   return $arr[$k];
}

/**
 * 把 HTML 实体转换为字符。
 * @param string $str
 * @return string
 */
function html_en($str) {
   return htmlentities($str, ENT_QUOTES, 'UTF-8');
}

;

/**
 * 缩略图地址处理
 * @param string $imgUrl 图片地址
 * @param string $Prefix 前缀代码 如：m 或 s
 * @return string
 */
function imgThumb($imgUrl = '', $Prefix = '') {
   $info = pathinfo($imgUrl);
   $result = $info['dirname'] . '/' . $Prefix . '_' . $info["basename"];
   return $result;
}

function imgThumbs($imgUrl = '', $Prefix = '') { //xiaolin 14-4-23
   $info = pathinfo($imgUrl);
   $result = $info['dirname'] . '/' . $Prefix . $info["basename"];
   return $result;
}

/**
 * 截取字符串(支持汉字)
 * @param string $str 字符串
 * @param int $start 起始位置
 * @param int $len 长度
 * @return string
 */
function utf8Substr($str, $start, $len) {
   $str = preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $start . '}' . '((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . $len . '}).*#s', '$1', $str);
   return $str;
}


/***********************************
 * 获取当前毫秒时间戳
 * 时间：2017年11月20日14:19:44
 * @return float
 */
function msectime() {
   list($msec, $sec) = explode(' ', microtime());
   $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
   return $msectime;
}

/**
 * 获取数组大小(支持多维数组)
 * @param $arr
 * @return int|mixed|string
 */
function array_size($arr) {
    ob_start();
    print_r($arr);
    $mem = ob_get_contents();
    ob_end_clean();
    $mem = preg_replace("/\n +/", "", $mem);
    $mem = strlen($mem);
    return $mem;
}

/**
 * 取文件最后$n行
 * @param string $file 文件路径
 * @param int $line 最后几行
 * @return mixed 成功则返回字符串
 */
function getLastLines($file,$line=1){
    if(!$fp=fopen($file,'r')){
        echo "打开文件失败";
        return false;
    }
    $pos = -2;      //偏移量
    $eof = " ";     //行尾标识
    $data = "";
    while ($line > 0){//逐行遍历
        while ($eof != "\n"){ //不是行尾
            if(count(file($file)) == 1) break;//只有一行时直接读取
            if(fseek($fp, $pos, SEEK_END) == -1) break;//fseek成功返回0，失败返回-1
            $eof = fgetc($fp);//读取一个字符并赋给行尾标识
            $pos--;//向前偏移
        }

        $eof = " ";
        $data .= fgets($fp);//读取一行
        $line--;
    }
    fclose($fp);
    return $data;
}


// 区间调试开始
function debug_start($label='') {
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    if (MEMORY_LIMIT_ON)
        $GLOBALS[$label]['_beginMem'] = memory_get_usage();
}

// 区间调试结束，显示指定标记到当前位置的调试
function debug_end($label='') {
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    echo '<div style="text-align:center;width:100%">Process ' . $label . ': Times ' . number_format($GLOBALS[$label]['_endTime'] - $GLOBALS[$label]['_beginTime'], 6) . 's ';
    if (MEMORY_LIMIT_ON) {
        $GLOBALS[$label]['_endMem'] = memory_get_usage();
        echo ' Memories ' . number_format(($GLOBALS[$label]['_endMem'] - $GLOBALS[$label]['_beginMem']) / 1024) . ' k';
    }
    echo '</div>';
}

/**
 * 冷热数据分层的方式
 * @param int $type 1:返回分层方式所需要的数据条数 2:写入分层数据 3:查询数据
 * @param resource $redis    redis实例
 * @param resource $mongo    mongo实例
 * @param string $task_id    md5后的sql语句
 * @param string $table      表名
 * @param array $result      数据库中查询完成的结果集
 * @param array $cache       cache统计数组
 * @param string $sql        原始sql
 * @return mixed
 */
function slicingType($type = 1,$redis = null,$mongo = null,$task_id = '',$table = '',$result = [],$cache = [],$sql = '')
{
    /*检查是否是需要做数据分层的表*/
    if(in_array($table,DsConfig::dsConst()['DATA_SLICING']) && array_filter(DsConfig::dsConst()['DATA_SLICING'],function($item){return $item!=='';}) != false)
    {
        return false;
    }

    switch (TRUE)
    {
        case (DsConfig::dsConst()['SLICING_TYPE'] == 1 && $type == 1):     //mysql临时表条数统计
            $cache_indexes = 0;
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 2 && $type == 1):     //仅使用redis做热数据分层数据-条数统计
            $cache_indexes = DsConfig::dsConst()['REDIS_OCCUPY']?:ceil(($redis->info('memory')['used_memory_peak'])/DsConfig::dsConst()['COUNT_BYTE']);
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 3 && $type == 1):     //仅使用mongodb做热数据分层条数统计
            $cache_indexes = DsConfig::dsConst()['MONGO_OCCUPY']?:DsConfig::dsConst()['MONGO_OCCUPY'];
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 4 && $type == 1):     //使用redis做热数据中的小数据处理,mongodb储存大数据条数统计
            $cache_indexes['redis'] = DsConfig::dsConst()['REDIS_OCCUPY']?:ceil($redis->zCount('DataSlicing:mysql_sql','-inf','+inf')*0.24);
            $cache_indexes['mongo'] = DsConfig::dsConst()['MONGO_OCCUPY']?:ceil($redis->zCount('DataSlicing:mysql_sql','-inf','+inf')*0.66);
            $cache_indexes['count'] = $cache_indexes['redis']+$cache_indexes['mongo'];
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 1 && $type == 2):     //mysql临时表缓存分层
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 2 && $type == 2):     //redis缓存分层
            $redis->hset("DataSlicing:Cache:$table",$task_id,json_encode($result));
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 3 && $type == 2):     //mongo缓存分层
            $mongo->getMongo()->getIndexInfo();
            $mongo->getMongo()->createIndex(['task_id' => 1], ['unique' => true]);
            $mongo->save($table,['task_id'=>$task_id,'data'=>json_encode($result)],false);
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 4 && $type == 2):     //redis,mongo缓存分层
            if(array_size($result) <= DsConfig::dsConst()['COUNT_BYTE'] && $redis->get("DataSlicing:Cache:redis_count") <= $cache['redis'])
            {
                if($redis->hExists('DataSlicing:Cache:'.$table,$task_id) == false)
                {
                    /*统计已经储存的数据条数*/
                    $redis->increment("DataSlicing:Cache:redis_count",1);
                    /*总统计*/
                    $redis->hIncrBy("DataSlicing:Cache:redis_total",$table,1);
                }
                $redis->hset("DataSlicing:Cache:$table",$task_id,json_encode($result));
                /*是否过滤的每条数据都需要缓存*/
                if(DsConfig::dsConst()['IS_FILTER'] == true)
                {
                    /*统计SQL*/
                    $redis->hset("DataSlicing:mysql_record",$task_id,json_encode($sql));
                    $redis->zIncrBy('DataSlicing:mysql_sql',1,$task_id);
                }
            }elseif($redis->get("DataSlicing:Cache:redis_count") <= $cache['mongo']){
                /*检查是否已经创建mongo索引*/
                if($redis->hExists('DataSlicing:mongo_index',$table) == false)
                {
                    /*检查mongo索引*/
                    $mongo_table = $mongo->getMongo()->selectCollection('DataSlicing', $table);
                    $key_index   = array_key_exists('keysPerIndex',$mongo_table->validate(false));
                    /*if($key_index == false)
                    {
                        $key_index = array_key_exists('DataSlicing.'.$table.'.$task_id_1',$mongo_table->validate(false)['keysPerIndex']);
                    }*/
                    /*创建mongo索引*/
                    if($key_index == false)
                    {
                        $mongo_table->ensureIndex(['task_id' => 1], ['unique' => true,'background'=>true]);
                    }
                    $redis->hset('DataSlicing:mongo_index',$table,1);
                }
                if($mongo->fetchOne($table,['task_id'=>$task_id]) == true)
                {
                    $mongo->update($table,['data'=>json_encode($result)],['task_id'=>$task_id],'set');
                }else{
                    /*统计已经储存的数据条数*/
                    $redis->increment("DataSlicing:Cache:mongo_count",1);
                    /*总统计*/
                    $redis->hIncrBy("DataSlicing:Cache:mongo_total",$table,1);
                    /*储存数据*/
                    $mongo->insert($table,['task_id'=>$task_id,'data'=>json_encode($result)],false);
                }
                /*是否过滤的每条数据都需要缓存*/
                if(DsConfig::dsConst()['IS_FILTER'] == true)
                {
                    /*统计SQL*/
                    $redis->hset("DataSlicing:mysql_record",$task_id,json_encode($sql));
                    $redis->zIncrBy('DataSlicing:mysql_sql',1,$task_id);
                }
            }
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 1 && $type == 3):     //查询mysql临时表数据
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 2 && $type == 3):     //查询redis缓存数据
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 3 && $type == 3):     //查询mongo缓存数据
            break;
        case (DsConfig::dsConst()['SLICING_TYPE'] == 4 && $type == 3):     //查询redis和mongo缓存数据
            $result = $redis->hget('DataSlicing:Cache:'.$table,$task_id);
            if($result == false)
            {
                $result = $mongo->fetchOne($table,['task_id'=>$task_id],'data');
            }
            break;
        default:
            return false;
            break;
    }
    if($type == 1)
    {
        return $cache_indexes;
    }elseif($type == 3){
        return $result;
    }else{
        return true;
    }
}