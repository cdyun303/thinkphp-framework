<?php
// 文件系统配置

return [
    // 默认磁盘
    'default' => 'local',
    // 磁盘列表
    'disks'   => [
        // 本地
        'local'  => [
            // 磁盘类型
            'type'       => 'local',
            // 磁盘路径
            'root'       => app()->getRootPath() . 'public/bucket',
            // 磁盘路径对应的外部URL路径
            'url'        => '/bucket',
            // 可见性
            'visibility' => 'public',
        ],
        // 阿里云
        'oss' => [
            // 磁盘类型，不要修改直接使用Local驱动
            'type'       => 'local',
            // 磁盘路径，改为存储桶
            'root'       => 'bucket',
            // 磁盘路径对应的外部URL路径，改为存储桶的域名，结尾不要带斜杠
            'url'        => '',
            // 可见性
            'visibility' => 'public',
        ],
        // 更多的磁盘配置信息
    ],
];
