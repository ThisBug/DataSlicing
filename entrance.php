<?php
/**
 * Description of entrance.php
 * @remark 首页文件
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2017.12.31
 * @Version 1.0
 */

define('SLICING_ROOT_PATH', realpath(__DIR__));
define('SLICING_BASE_PATH', dirname( SLICING_ROOT_PATH ));
define('SLICING_AUTO_PATH', SLICING_ROOT_PATH.'/Autoload');
define('SLICING_MODULE_PATH', SLICING_ROOT_PATH.'/Module');
define('SLICING_LOG_PATH', SLICING_ROOT_PATH.'/Logs/');

require_once SLICING_AUTO_PATH.'/config.php';
require_once SLICING_AUTO_PATH.'/function.php';
require SLICING_ROOT_PATH.'/Autoload/autoload.php';

if (method_exists('DsConfig', 'dsConst') == false)
{
    exit('配置未定义,定义地址'.substr(dirname(__FILE__),0,strrpos(dirname(__FILE__),'\\')).'/Autoload/config.php');
}