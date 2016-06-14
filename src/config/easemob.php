<?php
/**
 * 环信即时消息配置文件
 * Date: 16/6/7
 * Author: eric <eric@winhu.com>
 */

return [
    'easemob_url'   => env('EASEMOB_URL', 'https://a1.easemob.com/'),     //环信域名
    'org_name'      => env('ORG_NAME', ''),        //企业的唯一标识，开发者在环信开发者管理后台注册账号时填写的企业 ID
    'app_name'      => env('APP_NAME', ''),        //同一”企业”下“APP”唯一标识，开发者在环信开发者管理后台创建应用时填写的“应用名称”
    'client_id'     => env('CLIENT_ID', ''),  //APP 的 client_id
    'client_secret' => env('CLIENT_SECRET', ''), //APP 的 client_secret
    'app_key'       => env('APP_KEY',''),          //一个 APP 的唯一标识，规则是 ${org_name}#${app_name}
    'token_cache'    => env('TOKEN_CACHE','redis'),  //token储存的位置
    'cache_set' => [
        'redis' => [
            'prefix' => 'xuetianxia',
            'time' => 518400,
        ],
        'file' => [
            'path' => 'easemob.token',
        ],
    ],
    'download_file_path' => storage_path('files'),
//    'status_code' => [
//        400	=> '（错误请求）服务器不理解请求的语法',
//        401	=> '（未授权）请求要求身份验证。对于需要token的接口，服务器可能返回此响应',
//        403	=> '（禁止）服务器拒绝请求',
//        404	=> '（未找到）服务器找不到请求的接口',
//        408	=> '（请求超时）服务器等候请求时发生超时',
//        413	=> '（请求体过大）请求体超过了5kb，拆成更小的请求体重试即可',
//        429	=> '（服务不可用）请求接口超过调用频率限制，即接口被限流',
//        500	=> '（服务器内部错误）服务器遇到错误，无法完成请求',
//        501	=> '（尚未实施）服务器不具备完成请求的功能。例如，服务器无法识别请求方法时可能会返回此代码',
//        502	=> '（错误网关）服务器作为网关或代理，从上游服务器收到无效响应',
//        503	=> '（服务不可用）请求接口超过调用频率限制，即接口被限流',
//        504	=> '（网关超时）服务器作为网关或代理，但是没有及时从上游服务器收到请求',
//    ],
    'error_message' => [
        'invalid_grant' => '用户名或者密码输入错误',
        'organization_application_not_found' => '找不到对应的APP，可能是URL写错了',
        'illegal_argument' => [
            'username' => '创建用户请求体未提供”username”',
            'password' => '创建用户请求体未提供”password”',
            'newpassword' => '修改用户密码的请求体没提供newpassword属性',
            'group' => '批量添加群组时预加入群组的新成员username不存在',
            ''
        ],
        'json_parse' => '发送请求时请求体不符合标准的JSON格式,服务器无法正确解析',
        'duplicate_unique_property_exists' => '用户名已存在',
        'unauthorized' => 'APP的用户注册模式为授权注册,但是注册用户时请求头没带token',
        'auth_bad_access_token' => [
            'corrupt access token' => '发送请求时使用的token错误',
            'Unable to authenticate' => '无效token',
        ],
        'service_resource_not_found' => 'URL指定的资源不存在',
        'Request Entity Too Large' => '请求体过大',
        'reach_limit' => '超过接口每秒调用次数',
        'no_full_text_index' => 'username不支持全文索引',
        'unsupported_service_operation' => '请求方式不被发送请求的URL支持',
        'web_application' => '错误的请求，给一个未提供的API发送了请求',
    ],

];
