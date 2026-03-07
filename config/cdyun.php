<?php
// cdyun 扩展插件配置

return [
    //  模板配置
    'template' => [
        //  主题路径
        'theme_path' => 'theme/',
        //  后台主题
        'admin_theme' => 'admin_default',
        //  前端默认主题
        'portal_theme' => 'default',
    ],
    //  响应信息
    'response' => [
        //  返回码
        'code' => [
            //  成功返回码
            'success' => (int)env('code.success_code', 0),
            //  失败返回码
            'error' => (int)env('code.error_code', -1),
        ],
        //  是否开启加密
        'enable' => false,
        //  不需要加密的url，上传URL不需要加密
        'url' => ['/web_api/core/ocr/scan', '/web_api/core/upload/direct'],
        //  RSA私钥
        'rsa_private' => '',
    ],
    //  缓存配置
    'cache' => [
        //  全局缓存标签
        'tag' => 'sys',
        //  缓存有效期，默认4小时
        'expire' => 14400,
    ],
    // 上传配置
    'upload' => [
        //上传文件大小100*1024KB
        'fileSize' => 204800,
        //上传图片大小
        'imgSize' => 1024,
        //上传文件后缀类型
        'fileExt' => 'gif,jpg,jpeg,png,mp4,doc,docx,txt,pdf,xls,xlsx,ppt,pptx,mp3,wma,wav,zip',
        //上传图片类型
        'imgExt' => 'gif,jpg,jpeg,png',
        //上传路径,默认为files
        'path' => 'files',
        //驱动模式配置信息
        'stores' => [
            //本地上传配置
            'local' => [],
            //七牛云上传配置
            'qiniu' => [],
            //oss上传配置
            'oss' => [
                'aki' => '',
                'aks' => '',
                'endpoint' => '',
                'region' => "",
            ],
            //cos上传配置
            'cos' => [],
        ]
    ]
];
