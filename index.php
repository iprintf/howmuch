<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用入口文件

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG', true);

//自动生成模块
// define('BIND_MODULE', 'Public');
// define('BIND_MODULE', 'KyoCommon');
//
//自动生成控制器
//define('BUILD_CONTROLLER_LIST', 'Index, User, Menu');
//手动创建 \Think\Build:buildController('MyModule', 'MyController');
//
//自动生成模型
//define('BUILD_MODEL_LIST', 'User,Menu');
//手动创建 \Think\Build:buildModel('MyModule', 'MyModel');

// 定义应用目录
define('APP_PATH','./HowMuch/');

/* kyo Start  2014-06-13 11:12 */
//加载本系统配置
//require APP_PATH.'Common/Conf/kyo.php';
/* kyo End 2014-06-13 11:12 */

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单
