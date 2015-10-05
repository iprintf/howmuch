<?php

//密钥
define ('UC_AUTH_KEY', '198107235817');

//卡片和身份证图片上传路径
define('__UP__' , 'Public/uploads/');

//系统状态值
define ('NORMAL', "0");  //审核通过，正常状态
define ('DRAFT', "1");     //草稿状态
define ('SUB_AUDIT', "2");  //二级审核状态，分站审核状态
define ('AUDIT', "3");  //一级审核状态
define ('NO_PASS', "4");   //审核不通过状态
define ('ADD_LOCK', "5");  //添加客户锁定卡片
define ('LOCK', "6");		//锁定状态
define ('BLACKLIST', "7");  //黑名单
define ('RESCIND', "8");  //分站解约状态
define ('EDIT_AUDIT', "9"); //二次编辑审核
define ('RENEWAL', "10"); //续约
define ('APPLY', "13"); //申请
define ('WAIVE', "15"); //放弃
define ('UNLOCK', "11"); //解锁
define ('ERROR', "12"); //故障
define ('SUCCESS', "14"); //成功
define ('SEARCH', "16"); //查询
define ('RISING', "17");  //增值状
define ('FILING', "18");   //报备状

//操作记录类型
define ('CUST_ADD', "300");  //新客户录入
define ('CUST_EDIT', "310");  //客户编辑提交
define ('CUST_AUDIT_NORMAL', "352");  //审核新客户成功
define ('CUST_AUDIT_NO_PASS', "353");  //审核新客户不成功

define ('CARD_ADD', "200");  //新卡片录入
define ('CARD_EDIT', "210");  //变更卡片
define ('CARD_MOD', "211");  //变更卡片
define ('CARD_SUB', "250");     //新卡初审通过
define ('CARD_SUB_NOPASS', "251");  //新卡初审不通过
define ('CARD_AUDIT', "252");       //新卡终审通过
define ('CARD_AUDIT_NO_PASS', "253");  //新卡终审不通过
define ('CARD_SOS_LOCK', "260"); //锁定卡片
define ('CARD_SOS_UNLOCK', "261");  //卡片解锁
define ('CARD_SOS_APPLY', "262");  //卡片解锁申请
define ('CARD_RENEWAL', "271");   //卡片续约
define ('CARD_RENEWAL_APPLY', "272");   //卡片续约申请
define ('CARD_RENEWAL_NOPASS', "273");   //卡片续约申请不通过
define ('CARD_RENEWAL_WAIVE', "274");   //放弃续约状态
define ('CARD_RESCIND', "240");  //卡片解约
define ('CARD_RESCIND_PRINT', "241");  //卡片解约

define ('CARD_RISING_APPLY', "280");  //增值申请提交
define ('CARD_RISING_VERIFY', "281");  //增值验证操作
define ('CARD_RISING_ERROR', "284");  //增值验证失败
define ('CARD_RISING', "282");  //增值成功
define ('CARD_RISING_FAIL', "283");  //增值失败

define ('CARD_SURPLUS', "290");  //残值临查提交成功
define ('CARD_SURPLUS_END', "291");  //残值监查确认成功

//拓展员操作日志
define ('SALES_ADD', "100");
define ('SALES_EDIT', "110");

//POS操作日志
define ('POS_ERROR', "400");
define ('POS_ERROR_END', "401");

//用户登录操作日志
define ('USR_SUCCESS', "800");    //登录成功
define ('USR_ERR_PWD', "801");    //因密码错误登录失败
define ('USR_ERR_LOCK', "802");   //因用户锁定登录失败
define ('USR_ERR_ILL', "803");    //因非法用户登录失败
define ('USR_ERR_MAIN', "804");   //因系统维护登录失败
define ('USR_ERR_EXIST', "805");  //因用户已在线登录失败
define ('USR_ERR_NOT', "806");    //因用户不存在登录失败
define ('USR_QUIT', "807");       //用户退出
define ('USR_ABORT', "808");      //异常退出
define ('USR_MOD', "809");        //更改登录信息


//服务器序号
define ('SERVER_NUM', 1);


//非法访问跳转地址
define ('ERROR_URL', "http://www.baidu.com");
