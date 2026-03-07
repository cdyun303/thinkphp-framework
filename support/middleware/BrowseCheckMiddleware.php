<?php
declare (strict_types=1);

namespace support\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 浏览器类型中间件
 */
class BrowseCheckMiddleware
{
    /**
     * 处理请求
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 在这里实现浏览器检查逻辑
        return $next($request);
    }
}
