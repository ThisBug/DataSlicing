<?php
/**
 * Description of MysqlAnalysis.php
 * @remark 分析mysql查询日志
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2018.01.04
 */

if(strrpos(dirname(__FILE__),'\\') == true)
{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'\\')).'/entrance.php';
}else{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'/')).'/entrance.php';
}

set_time_limit(0);

/*定义mysql查询日志路径*/
$sql_path = DsConfig::dsConst()['SQL_LOG_PATH'];

/*判断文件是否存在*/
$fp = fopen($sql_path, "r") or die("Unable to open file!");

/*读取文件记录*/
$file_record = false;
$record_name = SLICING_ROOT_PATH.'/Cache/record_'.basename($sql_path);
if(file_exists($record_name) && strlen(file_get_contents($record_name)) > 10)
    $file_record = getLastLines(SLICING_ROOT_PATH.'/Cache/record_'.basename($sql_path),1);

/*创建redis连接*/
$redis = new DSRedisModel(2);
/*
 * 读取记录
 * 格式为:2018-01-05_17:37:43,1515145063,test-ryj-81-12.log,19
 * Array ( [0] => 2018-01-05_17:50:10 [1] => 1515145810 [2] => test-ryj-81-12.log [3] => 19 )
 */
if($file_record)
{
    $file_record = explode(',',$file_record);
    fseek($fp,intval($file_record[3]));
}

/*脚本开始执行分析*/
$start_time = microtime(true);
echo "开始分析...".PHP_EOL;
while (!feof($fp))
{
    /*逐行读取数据*/
    $file_text = fgetss($fp);
    if($file_text)
    {
        /*初始化匹配结果数组*/
        $matches = [];
        /*读取到的数据储存在redis中做数据统计*/
//        preg_match('/^select(.*?)+from\s+([`\w_\(\)]+)(\s+where\s+)?.*/i', $file_text, $matches);
        preg_match('/SELECT(.*?)+from\s+([`\w_\(\)]+)[\s+where\s+]?.*/i', $file_text, $matches);
        if($matches == true)
        {
            /*映射表名*/
            if(isset($matches[3]) == false)
            {
                $matches[3] = $matches[2];
            }
            $matches[0] = trim(rtrim($matches[0],')'));
            $matches[3] = trim($matches[3],'`');
            $matches[4] = time();
            /*加入待分层队列*/
            $redis->zIncrBy('DataSlicing:mysql_sql',1,md5($matches[0]));
            $redis->hset('DataSlicing:mysql_record',md5($matches[0]),json_encode($matches));
            echo $matches[0].PHP_EOL;
        }
    }
}

/*记录此次执行进度*/
$data = date('Y-m-d_H:i:s').','.time().','.basename($sql_path).','.ftell($fp).PHP_EOL;
file_put_contents($record_name, $data,FILE_APPEND);

/*脚本执行结束*/
$end_time = microtime(true);


/*脚本执行报告*/
echo str_replace('|',PHP_EOL,sprintf("分析完成...|共计%d个字节|其中sql查询语句%d条|将自动取出%d条作为热数据处理|当前Redis所占内存为:%s|此次脚本分析执行时间为:%F秒|消耗内存为:%s byte(s)|",ftell($fp),$redis->zCount('DataSlicing_mysql_sql','-inf','+inf'),DsConfig::dsConst()['REDIS_OCCUPY'],$redis->info('memory')['used_memory_rss_human'],round($end_time-$start_time,4),memory_get_usage()));

/*更新缓存队列*/
$redis->set('DataSlicing:cache_update',1);

/*关闭连接*/
fclose($fp);
//$redis->close();