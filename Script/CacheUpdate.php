<?php
/**
 * Description of CacheUpdate.php
 * @remark 批量更新缓存脚本(建议守护进程)
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2018.01.07
 */
if(strrpos(dirname(__FILE__),'\\') == true)
{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'\\')).'/entrance.php';
}else{
    require_once substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'/')).'/entrance.php';
}
set_time_limit(0);

/*创建储存数据的redis连接*/
$redis = new DSRedisModel(1);
/*创建统计key的redis连接*/
$key_redis = new DSRedisModel(2);
/*创建mongo连接*/
if(DsConfig::dsConst()['SLICING_TYPE'] == (3||4)){$mongo = new DSMongoModel();$mongo->selectDB('DataSlicing');}

while (TRUE)
{
    usleep(2000);

    if(DsConfig::dsConst()['SLICING_TYPE'] != (1||2||3||4)) continue;

    if($redis->get('DataSlicing:cache_update') != 1) continue;

//    /*重置更新缓存命令通道*/
    $redis->set('DataSlicing:cache_update',false);

    /*重新统计redis计数(未完成)*/
//    $cache_total = $redis->hGetall("DataSlicing:Cache:mongo_total");

    /*冷热数据分层的总条数*/
    $cache_indexes = call_user_func_array('slicingType',[1,$redis]);
    /*创建mysql实例*/
    $mysql = new DSEasyDBModel();
    $data = $key_redis->zRevRangeByScore('DataSlicing:mysql_sql','+inf','-inf',array('withscores' => TRUE, 'limit' => array(0, $cache_indexes['count'])));

//    while(list($task_id,$score)=each($data))
    /*按照分类存入缓存*/
    $i=0;
    foreach ($data as $key=>$val)
    {
        $sql = json_decode($key_redis->hget("DataSlicing:mysql_record",$key),true);

        if($sql == false) continue;

        $result = $mysql->queryAll(trim($sql[0]));
        if($result)
        {
            $i++;
            echo $i.PHP_EOL;
            /*创建数据分层*/
            call_user_func_array('slicingType',[2,$redis,$mongo,$key,$sql[3],$result,$cache_indexes]);
        }
    }

    /*记录log*/
    if(DsConfig::dsConst()['SPLIT_LOG'] == true)
    {
        PidFile::SlogToFile(sprintf("[redis_count:%d][mongo_count:%d][redis_totle:%s][mongo_totle:%s]",$redis->get("DataSlicing:Cache:redis_count"),$redis->get("DataSlicing:Cache:mongo_count"),json_encode($redis->hGetall("DataSlicing:Cache:redis_total")),json_encode($redis->hGetall("DataSlicing:Cache:mongo_total"))),true,SLICING_LOG_PATH.'split_log/','CacheUpdate');
    }
}