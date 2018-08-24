<?php
/**
 * Description of config.php
 * @remark 配置文件,为防止本文件配置的常量与项目使用的常量起冲突,特写成静态方法调用
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2017.12.31
 */


class DsConfig
{
    public static function dsConst()
    {
       return [
           /*主要配置*/
           'DS_STATS'           => false,                                        //是否启用此框架
           'SQL_LOG_PATH'       => SLICING_ROOT_PATH.'/test-ryj-81-12.log',     //mysql查询日志位置
           'SLOW_LOG_PATH'      => '/home/3306/test.log',                       //mysql慢查询日志位置
           'SPLIT_JUDGE'        => false,                                       //是否需要自动分表
           'SPLIT_TABLE'        => ['user'=>100],                               //到达一定的数据量后需要自动拆分的表
           'DATA_SLICING'       => [],                                          //有哪些表需要做冷热数据分层,值为空数组时则全部分层
           'SLICING_TYPE'       => 4,                                           //冷热数据分层的方式  0:不分层 1:mysql临时表 2:仅使用redis做热数据分层 3:仅使用mongodb做热数据分层 4:使用redis做热数据中的小数据处理,mongodb储存大数据
           'HOT_DATA_UPDATE'    => 86400,                                       //多久更新一次热数据(秒)
           'AVALANCHE'          => false,                                       //防雪崩模式
           'CACHE_DATA_OVERDUE' => 86400,                                       //缓存数据一致性更新时间(秒)
           'CACHE_DATA_RAND'    => true,                                        //不同时间更新缓存数据,防止击穿数据库
           'COUNT_BYTE'         => 128,                                         //字节数阈值,小于或等于则将数据分配给redis,大于分配给mongo 需要SLICING_TYPE为4时才有作用
           'CHECK_FRAME'        => true,                                        //检测框架主要配置和文件是否缺失
           'SELF_MYSQL'         => true,                                        //冷热数据区分是否走本框架的SQLModel
           'FIND_ONE'           => false,                                       //查找单条数据是否需要程序自动处理数据结构
           'IS_FILTER'          => false,                                       //是否过滤的每条数据都需要缓存
           'FILTER_NUM'         => 5,                                           //IS_FILTER为false的时候启用此配置,配置数据通过DS几次后才需加入缓存
           /*数据缓存redis配置(redis必须开启)*/
           'REDIS_HOST'         => '127.0.0.1',                                 //Redis地址
           'REDIS_PORT'         => 6379,                                        //Redis端口
           'REDIS_DB'           => 0,                                           //Redis库
           'REDIS_OCCUPY'       => 0,                                           //Redis储存数据最大条数0表示系统自动分配
           /*
            * 储存统计KEY的redis配置(redis必须开启)
            * 如果做了redis的codis架构后大key不好迁移
            * 最好单独配置一个redis端口
           */
           'BIG_REDIS_HOST'     => '127.0.0.1',
           'BIG_REDIS_PORT'     => 6379,
           'BIG_REDIS_DB'       => 0,
           /*mysql配置(主库,需要建表权限的账号)*/
           'DB_HOST'            => '127.0.0.1',                                 //MySQL地址
           'DB_PORT'            => 3306,                                        //MySQL端口
           'DB_NAME'            => 'root',                                      //MySQL账号
           'DB_PWD'             => 'root',                                      //MySQL密码
           'DB_USER'            => 'test',                                      //MySQL库名
           'DB_CHARSET'         => 'utf8',                                      //MySQL字符集
           /*mongodb配置*/
           'MONGO_HOST'         => '127.0.0.1',                                 //MongoDB地址
           'MONGO_PORT'         => 27017,                                       //MongoDB端口
           'MONGO_OCCUPY'       => 1000,                                        //MongoDB储存数据最大条数0表示系统自动分配
           /*log配置*/
           'SLICING_LOG'        => false,                                       //是否记录分表日志
           'SPLIT_LOG'          => true,                                        //是否记录区分冷热数据日志
           'ERROR_LOG'          => true,                                        //是否记录框架报错日志
       ];
    }
}