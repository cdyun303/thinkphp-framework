<?php
/**
 * BaseEntity.php
 * @author cdyun(121625706@qq.com)
 * @date 2026/3/22 19:41
 */

declare (strict_types=1);

namespace support\base;

use think\Entity;
use think\Request;

class BaseEntity extends Entity
{
    /**
     * 初始化数据查询
     * @param Request $request
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public function doSearchSelect(Request $request): array
    {
        $field = $request->get('field', $this->getPk());
        $order = $request->get('order', 'desc');
        $order = $order === 'asc' ? 'asc' : 'desc';
        $format = $request->get('format', 'list');
        $limit = (int)$request->get('limit', $format === 'select' ? 1000 : 10);
        $limit = $limit <= 0 ? 10 : $limit;
        $where = $request->get();
        $page = (int)$request->get('page');
        $page = $page > 0 ? $page : 1;

        // 获取数据表字段
        $tableFields = $this->getFieldsType();
        if (!isset($tableFields[$field])) {
            $field = null;
        }
        foreach ($where as $column => $value) {
            if ($value === '' || !isset($tableFields[$column]) || is_array($value) && (empty($value) || !in_array($value[0], ['null', 'not null']) && !isset($value[1]))) {
                unset($where[$column]);
            }
        }
        return [$where, $format, $limit, $field, $order, $page];
    }

    /**
     * 构建查询条件
     * @param array $where - 查询条件
     * @param string|null $field - 排序字段
     * @param string $order - 排序
     * @author cdyun(121625706@qq.com)
     */
    public function doQueryWhere(array $where, ?string $field = null, string $order = 'desc')
    {
        $model = $this;
        foreach ($where as $column => $value) {
            if (is_array($value)) {
                if ($value[0] === 'like' || $value[0] === 'not like') {
                    $model = $model->where($column, $value[0], "%$value[1]%");
                } elseif (in_array($value[0], ['>', '=', '<', '<>'])) {
                    $model = $model->where($column, $value[0], $value[1]);
                } elseif ($value[0] == 'in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereIn($column, $valArr);
                } elseif ($value[0] == 'not in' && !empty($value[1])) {
                    $valArr = $value[1];
                    if (is_string($value[1])) {
                        $valArr = explode(",", trim($value[1]));
                    }
                    $model = $model->whereNotIn($column, $valArr);
                } elseif ($value[0] == 'null') {
                    $model = $model->whereNull($column);
                } elseif ($value[0] == 'not null') {
                    $model = $model->whereNotNull($column);
                } elseif ($value[0] !== '' || $value[1] !== '') {
                    $model = $model->whereBetween($column, $value);
                }
            } else {
                $model = $model->where($column, $value);
            }
        }
        if ($field) {
            $model = $model->order($field, $order);
        }
        return $model;
    }

    public function delete(): bool
    {
        return $this->getModel()->delete();

    }
}