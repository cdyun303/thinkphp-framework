<?php
// 服务提供器配置
use support\ExceptionHandle;
use support\Request;

return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,
];