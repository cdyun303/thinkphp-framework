<?php
/**
 * AppException.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/29 22:54
 */

namespace support\exception;

/**
 * 自定义异常类
 */
class AppException extends \RuntimeException
{
    public function __construct($message, $code = null)
    {
        if ($code === null) {
            $code = env('code.error_code', -1);
        }
        parent::__construct($message, $code);
    }
}