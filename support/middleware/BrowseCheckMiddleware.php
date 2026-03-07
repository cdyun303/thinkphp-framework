<?php
declare (strict_types=1);

namespace support\middleware;

/**
 * 浏览器和操作系统类型中间件
 */
class BrowseCheckMiddleware
{
    public function handle($request, \Closure $next)
    {
        $ua = $request->header('user-agent');
        $request->os = get_os($ua);
        $request->browser = get_browser($ua);
        return $next($request);
    }
}
