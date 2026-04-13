<?php
/**
 * BaseValidate.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/11/5 21:13
 */

declare (strict_types=1);

namespace support\base;

use think\Validate;

class BaseValidate extends Validate
{
    protected $regex = [
        //字母开头，5-16字节，字母数字下划线构成
        'account_rg' => '/^[a-zA-Z][A-Za-z\d_]{4,15}$/',
        //6-18位，字母数字特殊符号构成
        'pwd_rg' => '/^[A-Za-z\d!@#$%&)(_]{6,18}$/',
        //手机号码
        'phone_rg' => '/^1[3-9]\d{9}$/',
        //英文别名，由英文字母开头，小写字母数字下划线构成
        'alias_rg' => '/^[a-z][a-z0-9_]*$/'
    ];

    /**
     * 验证账号由字母开头，5-16位字母数字下划线构成或手机号码
     * @param $value
     * @return bool
     */
    protected function verifyAccount($value): bool
    {
        $account = preg_match($this->regex['account_rg'], (string)$value);
        $phone = preg_match($this->regex['phone_rg'], (string)$value);

        if (!$account && !$phone) return false;
        return true;
    }
}