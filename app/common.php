<?php
// 全局公共函数文件

use Cdyun\ThinkphpResponse\ResponseEnforcer;
use support\exception\AppException;
use think\response\Redirect;

/**
 * 成功响应
 * @param array|string $msg
 * @param mixed|null $data
 * @author cdyun(121625706@qq.com)
 */
function success(array|string $msg = '操作成功', mixed $data = null): void
{
    ResponseEnforcer::success($msg, $data);
}

/**
 * 失败响应
 * @param array|string $msg
 * @param mixed|null $data
 * @author cdyun(121625706@qq.com)
 */
function error(array|string $msg = '操作失败', mixed $data = null): void
{
    ResponseEnforcer::error($msg, $data);
}

/**
 * 程序终止并返回错误信息
 * @param string $msg
 * @param int|null $code
 * @author cdyun(121625706@qq.com)
 */
function abort(string $msg = '服务器内部错误', ?int $code = null): void
{
    ResponseEnforcer::abort($msg, $code);
}

/**
 * MISS页面
 * @param int $type
 * @param string $msg
 * @param string $url
 * @return Redirect
 * @author cdyun(121625706@qq.com)
 */
function miss(int $type = 404, string $msg = '', string $url = 'admin/miss/index'): Redirect
{
    return redirect((string)url($url, ['type' => $type, 'msg' => $msg]));
}

/**
 * 分页响应
 * @param array $data
 * @param int $totalCount
 * @param string $msg
 * @author cdyun(121625706@qq.com)
 */
function paginate(array $data = [], int $totalCount = 0, string $msg = '加载完成'): void
{
    ResponseEnforcer::paginate($data, $totalCount, $msg);
}

/**
 * 数据验证
 * @param array $data
 * @param $validate
 * @param $scene
 * @return bool
 * @author cdyun(121625706@qq.com)
 */
function validate_data(array $data, $validate, $scene = null): bool
{
    try {
        $v = new $validate();
        if ($scene) {
            $v->scene($scene);
        }
        if (!$v->check($data)) {
            throw new AppException($v->getError());
        }
        return true;
    } catch (AppException $e) {
        throw new AppException($e->getMessage());
    }
}


/**
 * 获取IP
 */
function get_ip(): string|null
{
    $xRealIp = request()->header('x-real-ip');
    return $xRealIp ?: request()->ip();
}

/**
 * 获取浏览器类型
 * @param $user_agent
 * @return string
 * @author cdyun(121625706@qq.com)
 */
function browser($user_agent): string
{
    if (empty($user_agent)) {
        return '';
    }

    $browserMap = [
        '/micromessenger/i' => 'WeChat',
        '/alipay/i' => 'Alipay',
        '/MSIE|Trident/i' => 'IE',
        '/Firefox/i' => 'Firefox',
        '/Chrome/i' => 'Chrome',
        '/Safari/i' => 'Safari',
        '/Opera|OPR/i' => 'Opera',
    ];

    foreach ($browserMap as $pattern => $name) {
        if (preg_match($pattern, $user_agent)) {
            return $name;
        }
    }

    return 'Other';
}

/**
 * 获取操作系统类型
 * @param $user_agent
 * @return string
 * @author cdyun(121625706@qq.com)
 */
function os($user_agent): string
{
    if (empty($user_agent)) {
        return '';
    }

    $osMap = [
        '/win/i' => 'Windows',
        '/mac/i' => 'Mac',
        '/linux/i' => 'Linux',
    ];

    foreach ($osMap as $pattern => $name) {
        if (preg_match($pattern, $user_agent)) {
            return $name;
        }
    }

    return 'Other';
}

/**
 * 应用内链添加或删除域名
 * @param string $url - 链接地址
 * @param bool $set - 是否添加域名,true:添加域名,false:删除域名
 * @param string $domain - 域名
 * @return string
 * @author cdyun(121625706@qq.com)
 */
function app_domain_url(string $url, bool $set = true, string $domain = ''): string
{
    // 添加域名
    if ($set) {
        if (str_contains($url, '://') || $url === '') {
            return $url;
        }
        $currentDomain = $domain ?: request()->domain();
        if (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }
        return $currentDomain ? $currentDomain . $url : $url;
    }

    // 删除域名
    if (!str_contains($url, '://')) {
        return $url;
    }
    $currentDomain = $domain ?: request()->domain();
    if (!str_contains($currentDomain, '://')) {
        throw new AppException('提供的域名格式错误');
    }
    if (!str_starts_with($url, $currentDomain)) {
        return $url;
    }
    $url = str_replace($currentDomain, '', $url);
    if (!str_starts_with($url, '/')) {
        $url = '/' . $url;
    }

    return $url;
}

/**
 * 应用资源签名
 * @param string $url - 资源地址
 * @return string
 * @author cdyun(121625706@qq.com)
 */
function app_sign_url(string $url): string
{
    return app_domain_url($url, true);
}