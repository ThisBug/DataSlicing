<?php
/**
 * Description of PidFile.class.php
 * @remark 记录日志
 * @auth   yangkun
 * @e-mail this_bug@foxmail.com
 * @date   2018.01.13
 */

class PidFile
{
    public static function createPidFile($pid_filename)
    {
        if (file_exists($pid_filename))
        {
            $old_pid = trim(file_get_contents($pid_filename));
            if (file_exists('/proc/' . $old_pid . '/cmdline'))
            {
                return TRUE;
            }
        }
        $pid = posix_getpid();
        $pid = strval($pid);
        if (!is_dir(dirname($pid_filename))) {
            @mkdir(dirname($pid_filename), 0777, true);
        }
        file_put_contents($pid_filename, $pid);
        return FALSE;
    }

    public static function deletePidFile($pid_filename)
    {
        @unlink($pid_filename);
    }

    public static function run($pid_filename,$func,$argument)
    {
        if ( self::createPidFile($pid_filename))
            exit();
        $func($argument);
        self::deletePidFile($pid_filename);
    }
    /*
     * 写日志
     */
    public static function SlogToFile($message_r = '', $is_success=TRUE,$file_path,$pid_prefix)
    {
        $time = time();
        if($is_success)
        {
            $file = $file_path.$pid_prefix.'_access'. date("_Y-m-d", $time) . '.log';
        }else {
            $file = $file_path.$pid_prefix.'_error'. date("_Y-m-d", $time) . '.log';
        }

        $message_r = '['.date("Y-m-d H:i:s", $time).'] '.$message_r."\n";
        error_log($message_r, 3, $file);
    }
}

/*
 * 写日志
 */
function LogToFile($message_r = '', $is_success=TRUE,$pid_prefix)
{
    $time = time();
    if($is_success){
        $file = realpath(SLICING_LOG_PATH).'/'.$pid_prefix.'_access'.date("_Y-m-d",$time).'.log';
    }else{
        $file = realpath(SLICING_LOG_PATH).'/'.$pid_prefix.'_error'.date("_Y-m-d",$time).'.log';
    }
    if (!is_dir(dirname($file))) {
        @mkdir(dirname($file), 0777, true);
    }
    file_put_contents($file, '[' . date('Y-m-d H:i:s') . ']['.$message_r.']' . PHP_EOL, FILE_APPEND);
    return true;
}
                                    