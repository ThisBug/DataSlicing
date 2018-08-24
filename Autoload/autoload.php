<?php
/**
 * Description of autoload.php
 * @remark 自动加载类
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2017.12.31
 */
//设置包含目录
set_include_path(get_include_path() . PATH_SEPARATOR .implode(PATH_SEPARATOR, Autoload::$include_dir));
class Autoload
{
    //包含目录
    public static $include_dir =
        [
            'DataSlicing/Module',
            'DataSlicing/Autoload',
            'DataSlicing/Lib',
        ];

    /**
     * 回溯
     */
    public static function debug()
    {
        $backtrace = debug_backtrace();
        unset($backtrace[0]);
        var_dump($backtrace);
    }

    /**
     * 自动加载类库
     * @param string $class 类名
     */
    public static function auto_load_class($class = '')
    {
        $path = $class . '.class.php';
        //.class文件匹配不到则匹配.php结尾
        if(file_exists($path) == false)
        {
            $path = $class.'.php';
        }
        //以上两种类型结尾的文件都找不到则在Module目录下自动匹配第一个结果
        if(file_exists($path) == false)
        {
            foreach (Autoload::$include_dir as $item)
            {
                if(file_exists($path))continue;
                $path = @glob(SLICING_BASE_PATH.'/'.$item.'/'.$class.'*',GLOB_BRACE)[0];
            }
        }
        require_once($path);
    }
}
spl_autoload_register('Autoload::auto_load_class'); //spl注册自动加载

//$obj = new config(); //实例化
//$obj->方法名();

