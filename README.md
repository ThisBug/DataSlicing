DataSlicing 1.0
===============

DataSlicing旨在帮助接口提高访问速度,分担数据库压力,防止大量重复请求击穿数据库.使用时最好遵循28原则.
目前版本仅支持使用redis和mongo来分担数据库压力.

其主要特性包括：

 + 分析mysql查询日志功能
 + 自动恢复上次分析日志进度
 + 排序使用次数最多的前排数据
 + 冷热数据分层(多种方式,除redis和mongo同时使用的模式是完整的其他的模式待完善)
 + 自主配置数据更新时间
 + 数据阈值机制
 + 改进的Mongo类
 + 自主配置需要分层的表
 + 引导文件支持
 + 自动加载类
 + 无人工干预则自动区分数据
 + 报错抛错机制(待完善)
 + Mysql主从模式(待加入)
 + 防雪崩模式(待加入)
 + 自动分库分表(待加入)
 + 数据表优化建议(待加入)

> DataSlicing的运行环境要求PHP5.5以上,必要的扩展为redis,数据库为mysql。

## 目录结构

初始的目录结构如下：

~~~
DataSlicing  本轻框架根目录
├─Autoload                  主要配置目录
│  ├─autoload.php           自动加载模块
│  ├─config.php             配置文件
│  └─function.php           公共函数文件
│
├─Cache                     mysql日志分析缓存目录(不要删除)
│
├─Lib                       框架主要类目录
│  ├─FilterData.class.php   请求过滤类(主文件)
│  └─PidFile.class.php      记录日志类
│
├─Logs                      框架日志存放目录
│  ├─error_log              框架错误日志存放目录
│  ├─slicing_log            冷热数据分层日志目录
│  ├─split_log              分库分表日志目录
│  └─sql_log                SQL日志目录
│
├─Module                      框架日志存放目录
│  ├─DSEasyDBModel.class.php  MySQL连接类
│  ├─DSMongoModel.class.php   Mongo连接类
│  ├─DSRedisModel.class.php   Redis连接类
│  └─DSTSingleton.class.php   单例类
│
├─README_DIR                引用实例化类说明文件目录
│  ├─Mongo_README           Mongo类说明文件
│  └─MySQL_README           MySQL类说明文件
│
├─Script                    脚本文件目录
│  ├─MysqlAnalysis.php      分析mysql查询日志(只取select语句)
│  └─CacheUpdate.php        将分析过后的语句加入缓存
├─entrance.php              初始化框架
├─LICENSE.txt           授权说明文件
├─README.md             README 文件
~~~


![框架前期大体架构](https://gitee.com/uploads/images/2018/0118/173547_665633fe_1202701.png "DataSlicing.png")

> 推荐第一次使用先使用MysqlAnalysis.php和CacheUpdate.php分析下mysql脚本试试效果
> 上面的目录结构和名称是可以改变的，这取决于你的入口文件和配置参数。

## 额外引入

DataSlicing秉着不重复造轮子,故引入以下两个类:

DSEasyDBModel.class.php  MySQL连接类(http://www.oschina.net/p/easydb)

DSMongoModel.class.php   Mongo连接类(来源网络)


## 使用方式

1.只通过分析出的sql语句来使用缓存

2.任何查询语句都经过此框架过滤

例如ThinkPHP3.1
在TP的底层驱动中增加过滤
ThinkPHP/Lib/Core/Db.class.php  line:778

```
    /**
     * 查找记录
     * @access public
     * @param array $options 表达式
     * @return mixed
     */
    public function select($options=array()) {
        $this->model  =   $options['model'];
        $sql    = $this->buildSelectSql($options);
        /*DataSlicing检测缓存中是否有此查询语句(跳过项目框架自带缓存机制)*/
        $temp_DS = FilterData::main($sql);
        if($temp_DS != false)
        {
            $result = $temp_DS['result'];
            goto ds_temp;
        }

        $cache  =  isset($options['cache'])?$options['cache']:false;
        if($cache) { // 查询缓存检测
            $key    =  is_string($cache['key'])?$cache['key']:md5($sql);
            $value  =  S($key,'',$cache);
            if(false !== $value) {
                return $value;
            }
        }
        $result   = $this->query($sql,$this->parseBind(!empty($options['bind'])?$options['bind']:array()));
        if($cache && false !== $result ) { // 查询缓存写入
            S($key,$result,$cache);
        }

        /*缓存中没有此sql则加入缓存*/
        if($result != false)
        {
            FilterData::main($sql,$result);
        }
        ds_temp:
        return $result;
    }
```
## 参与开发
杨坤(this_bug@foxmail.com)
blog:http://www.this-bug.com

## 写在后面的话
 **

 一人智短两人智长

 也欢迎大家都来完善此框架

 本身是想将这些功能以中间件或者服务的方式嵌入到项目中

 奈何自身技术有限,所以目前框架还有许多不完善的地方

 请大家多多指正.

 **

## 版权信息

DataSlicing遵循Apache2开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有Copyright © 2006-2017 by DataSlicing (http://www.this-bug.com)

All rights reserved。
