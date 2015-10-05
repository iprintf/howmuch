<?php

return array(
    /* 应用设定 */
    'MODULE_DENY_LIST'      =>  array('Common','Runtime'),
    // 'MODULE_ALLOW_LIST'     =>  array('Install'),
    'DEFAULT_MODULE'        =>  'Home',  // 默认模块

    /* 数据库设置 */
    'DB_TYPE'               =>  'pdo',     // 数据库类型
    'DB_DSN'                =>  'mysql:host=127.0.0.1;dbname=howmuch;charset=utf8', //pdo dsn
    // 'DB_TYPE'               =>  'mysql',
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    //'DB_HOST'               =>  '2.2.2.2', // 服务器地址
    'DB_NAME'               =>  'howmuch',          // 数据库名
    //'DB_USER'               =>  'howmuch',      // 用户名
    'DB_USER'               =>  'root',      // 用户名
    //'DB_PWD'                =>  'ka4aiGac',          // 密码
    'DB_PWD'                =>  '123321',          // 密码
    'DB_PORT'               =>  '3306',        // 端口
    'DB_PREFIX'             =>  '',

    'LOG_RECORD'            =>  false,   //是否记录操作日志
    /* SESSION设置 */
    'SESSION_OPTIONS'       =>  array(), // session 配置数组 支持type name id path expire domain 等参数
    'SESSION_TYPE'          =>  'Db', // session hander类型 默认无需设置 除非扩展了session hander驱动

    /* 模板引擎设置 */
    'TMPL_TEMPLATE_SUFFIX'  =>  '.html',     // 默认模板文件后缀
    'TMPL_STRIP_SPACE'      =>  true,       // 是否去除模板文件里面的html空格与换行
    //'TMPL_CACHE_ON'         =>  true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
    //配置资源路径
    'TMPL_PARSE_STRING' => array(
        '__IMG__'    => __ROOT__ . '/Public/img',
        '__CSS__'    => __ROOT__ . '/Public/css',
        '__JS__'     => __ROOT__ . '/Public/js',
        '__PB__'     => __ROOT__ . '/Public',
    ),
        
     //关闭缓冲    
    'DB_FIELDS_CACHE'       =>  false,        // 关闭字段缓存
    'TMPL_CACHE_ON'         =>  false,        // 是否开启模板编译缓存,设为false则每次都会重新编译

//     'TMPL_ACTION_ERROR'     =>  'KyoCommon@Public/error', // 默认错误跳转对应的模板文件
//     'TMPL_ACTION_SUCCESS'   =>  'KyoCommon@Public/success', // 默认成功跳转对应的模板文件
//     'TMPL_EXCEPTION_FILE'   =>  'KyoCommon@Public/exception',// 异常页面的模板文件

    /* URL设置 */
    'URL_CASE_INSENSITIVE'  =>  false,   // 默认false 表示URL区分大小写 true则表示不区分大小写
    'URL_MODEL'             =>  3,       // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
    
    //调试选项
    //'SHOW_PAGE_TRACE' => true,

    //注册新的命名空间
    'AUTOLOAD_NAMESPACE' => array(
        // 'Kyo' => COMMON_PATH.'Kyo',
    ),
    
    //配置数据每页显示多少条  15
    'PAGE_SIZE' => 15,
    
    //平台对应名称
    'PLATFROM_NAME' => array("Manage", "Finance", "Operator", "Salesman", "Employee", "Customer", "Master", "Assist", "Proxy", "Main"),

    //职位名字，用于数据库里存储职位ID对应显示
    'PERM_NAME' => array("超级管理员", "财务主管", "操作员", "拓展员", "卡主雇员", "客户", "站长", "超级助理", "代理商", "DYK"),

    //系统状态值
    'STATUS_TEXT' => array(NORMAL => "正常状", DRAFT => "草稿件", SUB_AUDIT => "待初审", AUDIT => "待终审", 
                           NO_PASS => "不通过", ADD_LOCK => "新增加", LOCK => "锁定状", BLACKLIST => "黑名单", 
                           RESCIND => "解约状", EDIT_AUDIT => "再编辑", RENEWAL => "再续约", APPLY => "申请状",
                           WAIVE => "放弃状", UNLOCK => "解锁状", ERROR => "故障状", SEARCH => "临查状", 
                           RISING => "增值状", FILING => "报备状"),

    //操作日志类型文本
    'RECORD_TEXT' => array(CUST_ADD => "客户录入系统", 
            CUST_EDIT => "客户资料变更", 
            CUST_AUDIT_NORMAL => "新客户审核通过", 
            CUST_AUDIT_NO_PASS => "新客户审核拒绝", 

            CARD_ADD => "新信用卡录入系统", 
            CARD_EDIT => "信用卡变更新卡", 
            CARD_MOD => "信用卡信息修改", 
            CARD_SUB => "新信用卡初审通过", 
            CARD_SUB_NOPASS => "新信用卡初审拒绝",
            CARD_AUDIT => "新信用卡终审通过", 
            CARD_AUDIT_NO_PASS => "新信用卡终审拒绝", 
            CARD_SOS_LOCK => "SOS紧急信用卡锁定", 
            CARD_SOS_UNLOCK => "SOS紧急信用卡解锁", 
            CARD_SOS_APPLY => "SOS紧急信用卡解锁申请", 
            CARD_RENEWAL => "信用卡续约",
            CARD_RENEWAL_APPLY  => "信用卡续约申请",
            CARD_RENEWAL_NOPASS  => "信用卡续约拒绝",
            CARD_RENEWAL_WAIVE => "信用卡放弃续约",
            
            CARD_RESCIND => "信用卡解约提交", 
            CARD_RESCIND_PRINT => "信用卡解约打印结账单", 
            
            CARD_RISING_APPLY => "信用卡增值申请提交", 
            CARD_RISING_VERIFY => "信用卡增值等待操作员提交残值", 
            CARD_RISING_ERROR => "信用卡增值验证残值失败", 
            CARD_RISING => "信用卡增值成功", 
            CARD_RISING_FAIL => "信用卡增值失败", 
            
            CARD_SURPLUS => "信用卡残值临查提交", 
            CARD_SURPLUS_END => "信用卡残值临查结果", 

    		SALES_ADD => "拓展员录入系统", 
            SALES_EDIT => "拓展员资料变更", 

            POS_ERROR => "POS机故障", 
            POS_ERROR_END => "POS机故障解决",
            
            USR_SUCCESS => "登录成功",
            USR_ERR_PWD => "因密码错误登录失败",
            USR_ERR_LOCK => "因用户锁定登录失败",
            USR_ERR_ILL => "因非法用户登录失败",
            USR_ERR_MAIN => "因系统维护登录失败",
            USR_ERR_EXIST => "因用户已在线登录失败",
            USR_ERR_NOT => "因用户不存在登录失败",
            USR_QUIT =>    "用户退出",
            USR_ABORT => "用户异常退出",
            USR_MOD => "更改用户登录用户名或登录密码",
            ),

    //性别文本
    'SEX_TEXT' => array("女", "男"),

    //支付方式
    'PAYTYPE_TEXT' => array(1 => "卡内留存", 2 => "现金预付", 3 => "增值后补"),
        
    //划账类型
    'REPAYTYPE_TEXT' => array(1 => "账单还款", 2 => "客户增值", 3 => "签单佣金", 4 => "增值佣金", 5 => "服务佣金", 6 => "续约佣金"),
        
    //POS机站内编码首字符        
    'POS_SUB_CODE' => array('1.25' => 'A',  '0.78' => 'B', '0.38' =>  'C', '0.4' => 'D', '0.45' => 'E', '0.5' => 'F', '0.6' => 'G'),
        
    //系统版本类型
    'VERSION_TEXT' => array('1' => "全部更新", "2" => "分组更新", "3" => "个体更新"),
        
);
?>
