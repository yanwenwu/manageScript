<?php
header("Content-type: text/html; charset=utf-8");
date_default_timezone_set('PRC');//设置时区
set_time_limit(0);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', dirname(__DIR__) . DS);//定义根目录路径
define('CORE_PATH', ROOT_PATH . 'core' . DS);//定义类文件存储路径
define('LOG_PATH', ROOT_PATH . 'log' . DS);//定义日志路径
define('CONFIG_PATH', ROOT_PATH . 'config' .DS);//定义配置文件路径
define('HELPER_PATH', ROOT_PATH . 'helper' . DS);//助手函数
define('EXT','.php');//定义文件后缀

include_once CORE_PATH .'start'. EXT;
//助手函数
include_once HELPER_PATH . 'helper' . EXT;
//引入基类
include_once ROOT_PATH . DS . 'scripts' .DS. 'Base' . EXT;
//引入脚本文件
include_once ROOT_PATH . DS . 'scripts' .DS. $argv[1] . EXT;


require_once 'Autoload.php';

\Core\AutoLoad::regiser();
\Core\Cen\Config::init();