<?php
// 全局中间件定义文件

return [
    // 浏览器和操作系统类型检测
    \support\middleware\BrowserCheckMiddleware::class,
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
    // Session初始化
    // \think\middleware\SessionInit::class
];
