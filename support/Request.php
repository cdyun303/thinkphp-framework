<?php

namespace support;

// 应用请求对象类
class Request extends \think\Request
{
    /**
     * 系统类型
     * @var string
     */
    public string $os = '';

    /**
     * 浏览器类型
     * @var string
     */
    public string $browser = '';

}
