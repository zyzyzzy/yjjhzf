<?php
/*
 * 后台菜单列表配置文件
 */
//2019-3-18 任梦龙：重新排版管理后台菜单
//2019-3-22 任梦龙：将登录记录和操作记录移到个人信息中登录设置移到基本设置,风控设置移到基本设置
return array(
    'MENU_JSON' =>  array(
        '系统设置' => array(
            'icon' => '&#xe716;',
            'menu' => array(
                '基本设置' => "SystemSeting/BasicsSeting",
                '银行设置' => "BankSeting/bankSeting",
                '登录设置' => 'LoginTemplate/templateList',
                '统计模板' => 'DataCount/dataTemplateList',
                '风控设置' => 'SafeSeting/safeSeting',
            ),
        ),
        '管理员管理' => array(
            'icon' => '&#xe66f;',
            'menu' => array(
                '管理员列表' => 'AdminUser/AdminUserList',
                '角色列表' => 'AdminAuthGroup/RoleList',
                '菜单列表' => 'AdminAuthRule/MenuList',
            )
        ),
        '用户管理' => array(
            'icon' => '&#xe770;',
            'menu' => array(
                '用户列表' => 'User/UserList',
                '邀请码列表' => 'UserInviteCode/InviteList',
                '用户菜单' => 'UserAuthRule/userListMenu',
            )
        ),
        '通道管理' => array(
            'icon' => '&#xe857;',
            'menu' => array(
                '通道商家' => 'Payapi/PayapiShangjia',
                '支付通道' => 'Payapi/PayapiList',
                '通道分类' => 'Payapi/PayapiClass',
                '通道账号' => 'Payapi/PayapiAccount',
                '代付通道' => 'Daifu/DaifuList',
            )
        ),
        '交易设置' => array(
            'icon' => '&#xe716;',
            'menu' => array(
                '到账设置' => "MoneySeting/moneyClassList",
                '版本设置' => 'VersionSeting/versionList',
                '扫码模板' => 'Qrcode/qrcodeTemplateList',
                '广告模板' => 'AdvSetting/advTemplateList',
            ),
        ),
        '交易管理' => array(
            'icon' => '&#xe63c;',
            'menu' => array(
                '交易记录' => 'Order/orderList',
                //2019-4-11 任梦龙：单独分离自助账号交易记录
                '自助交易记录' => 'UserOrder/userOrderList',
                '交易日志' => 'Order/orderLog',
                '资金变动记录' => 'DataCount/moneyChangeList'
            )
        ),
        '结算管理' => array(
            'icon' => '&#xe63c;',
            'menu' => array(
                '结算设置' => 'Settle/settleSetup',
                '结算记录' => 'Settle/settleList',
                '版本设置' => 'SettleVersion/settleVersion',
            )
        ),
        '工单管理' => array(
            'icon' => '&#xe606;',
            'menu' => array(
                '工单记录' => 'AdminWorkOrder/adminWorkList',
                '帮助文档' => 'AdminWorkOrder/workHelpList'
            )
        ),
        '信息设置' => array(
            'icon' => '&#xe716;',
            'menu' => array(
                '邮箱设置' => 'EmailSeting/EmailSeting',
                '短信设置' => 'SmsSeting/SmsSeting',
            ),
        ),
        //2019-4-8 任梦龙：修改
        '版权管理' => array(
            'icon' => '&#xe608;',
            'menu' => array(
                '版权声明' => 'CopyRight/copyRight',
                '更新日志' => 'CopyRight/updateLog'
            )
        ),
        //2019-3-18 任梦龙：公告发布
        '公告管理' => array(
            'icon' => '&#xe667;',
            'menu' => array(
                '公告列表' => 'NoticeSeting/noticeList'
            )
        ),
    )
);