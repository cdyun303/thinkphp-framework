<?php
/**
 * BaseModel.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/10/29 22:48
 */

declare (strict_types=1);

namespace support\base;

use think\Model;

class BaseModel extends Model
{
    /**
     * 定义软删除字段
     * @var  string
     */
    protected string $deleteTime = 'delete_time';

    /**
     * 定义软删除字段的默认值
     * @var int
     */
    protected int $defaultSoftDelete = 0;
}