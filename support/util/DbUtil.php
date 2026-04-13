<?php
/**
 * 数据库工具类
 * @author cdyun(121625706@qq.com)
 * @date 2026/4/1 17:21
 */

declare (strict_types=1);

namespace support\util;

use think\facade\Db;

class DbUtil
{
    /**
     * 获取数据库表列表
     * @param array $options - 参数
     * @return array
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function listTables(array $options): array
    {
        $tableName = $options['table_name'] ?? '';
        $columns = 'TABLE_NAME,TABLE_COMMENT,ENGINE,TABLE_ROWS,CREATE_TIME,UPDATE_TIME,TABLE_COLLATION';
        $field = self::verify($options['field'] ?? 'TABLE_NAME', 'alphaDash');
        if (!in_array($field, explode(',', $columns))) {
            $field = 'TABLE_NAME';
        }
        $order = ($options['order'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
        $database = self::getDbConfig('database');
        $sql = "SELECT $columns FROM information_schema.`TABLES` WHERE TABLE_SCHEMA='$database'";
        if ($tableName) {
            $sql .= " AND TABLE_NAME like '%{$tableName}%'";
        }
        $sql .= " order by $field $order";
        if (!empty($options['limit'])) {
            $limit = (int)$options['limit'];
            $page = ($options['page'] ?? 1) - 1;
            $offset = $limit * $page;
            $sql .= " limit $offset,$limit";
        }
        $tables = Db::query($sql);
        if ($tables) {
            $table_names = array_column($tables, 'TABLE_NAME');
            $table_rows_count = [];
            foreach ($table_names as $table_name) {
                $table_rows_count[$table_name] = Db::table($table_name)->count();
            }
            foreach ($tables as $key => $table) {
                $tables[$key]['TABLE_ROWS'] = $table_rows_count[$table['TABLE_NAME']] ?? $table['TABLE_ROWS'];
            }
        }
        return $tables;
    }

    /**
     * 验证
     * @param string $value - 值
     * @param string $ruleName - 规则名称
     * @return string|true
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function verify(string $value = '', string $ruleName = ''): bool|string
    {
        if (!$ruleName) {
            throw new \Exception('未指定规则名称');
        }
        if (!$value) {
            throw new \Exception('未指定值');
        }
        switch ($ruleName) {
            case 'alphaDash': // 字母数字下划线
                if (!preg_match('/^[a-zA-Z_0-9]+$/', $value)) {
                    throw new \Exception($value . '不符合' . $ruleName . '规则');
                }
                break;
            case 'alphaNum': // 字母数字
                if (!preg_match('/^[0-9]+$/', $value)) {
                    throw new \Exception($value . '不符合' . $ruleName . '规则');
                }
                break;
            default :
                throw new \Exception('未匹配到验证规则');
        }
        return $value;
    }

    /**
     * 获取数据库配置
     * @param string $name - 数据库配置名称
     * @param string $default - 默认值
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public static function getDbConfig(string $name = '', string $default = ''): mixed
    {
        $defaultConnection = config('database.default');
        if (empty($name)) {
            return config('database.connections.' . $defaultConnection);
        }
        return config('database.connections.' . $defaultConnection . '.' . $name, $default);
    }

    /**
     * 获取数据库表数量
     * @param array $options
     * @return int
     * @author cdyun(121625706@qq.com)
     */
    public static function countTables(array $options): int
    {
        $tableName = $options['table_name'] ?? '';
        $database = self::getDbConfig('database');
        $sql = "SELECT count(*)total FROM information_schema.`TABLES` WHERE TABLE_SCHEMA='$database'";
        if ($tableName) {
            $sql .= " AND TABLE_NAME like '%{$tableName}%'";
        }
        return Db::query($sql)[0]['total'] ?? 0;
    }

    /**
     * 生成表结构SQL
     * @param string $tableName - 表名
     * @param array $columns - 字段组
     * @param array $keys - 索引组
     * @param array $options - 参数
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function buildTableSql(string $tableName, array $columns, array $keys, array $options): string
    {
        $tableComment = isset($options['table_comment']) ? addslashes($options['table_comment']) : '';
        $tableCharset = self::verifyTableCharset($options['table_charset'] ?: 'utf8mb4');
        $tableCollation = self::verifyTableCollation($tableCharset, $options['table_collation'] ?: 'utf8mb4_unicode_ci');
        $tableEngine = self::verifyTableEngine($options['table_engine'] ?: 'InnoDB');

        [$columns, $primaryKey] = self::prepareColumns($columns);
        $keys = self::prepareKeys($keys);
        $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (\n";
        // 构建字段
        if (!empty($columns)) {
            $types = self::columnType();
            $columnSql = [];
            foreach ($columns as $column) {
                if (!isset($column['type'])) {
                    throw new \Exception("请为{$column['field']}选择类型");
                }
                if (!in_array($column['type'], $types)) {
                    throw new \Exception("不支持的类型{$column['type']}");
                }
                $columnSql[] = self::createColumnSql($column);
            }
            $sql .= implode(",\n", $columnSql);
        }
        // 构建主键
        if ($primaryKey) {
            $sql .= ",\n";
            $sql .= "PRIMARY KEY (`$primaryKey`)";
        }
        // 构建索引
        if (!empty($keys)) {
            $keysSql = [];
            foreach ($keys as $key) {
                $keyName = $key['name'];
                $keyColumns = $key['columns'];
                $key = ($key['type'] ?? '') === 'unique';
                $keysSql[] = ($key ? 'UNIQUE KEY' : 'KEY') . " `$keyName` (`$keyColumns`)";
            }
            $sql .= ",\n";
            $sql .= implode(",\n", $keysSql);
        }
        $sql .= "\n) ENGINE = $tableEngine CHARACTER SET = $tableCharset COLLATE = $tableCollation COMMENT = '$tableComment' ROW_FORMAT = Dynamic;";
        return $sql;
    }

    /**
     * 验证数据表字符集
     * @param string $charset - 字符集
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function verifyTableCharset(string $charset): string
    {
        $default = ['utf8mb4', 'utf8mb3', 'gb2312', 'gbk', 'ascii'];
        if (!in_array($charset, $default)) {
            throw new \Exception('建议使用常用的字符集，如使用特殊字符集请联系管理员');
        }
        return $charset;
    }

    /**
     * 验证数据表排序
     * @param string $charset - 字符集
     * @param string $collation - 排序规则
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function verifyTableCollation(string $charset, string $collation): string
    {
        switch ($charset) {
            case 'utf8mb4':
                $default = ['utf8mb4_general_ci', 'utf8mb4_bin', 'utf8mb4_unicode_ci', 'utf8mb4_0900_ai_ci', 'utf8mb4_0900_as_cs'];
                break;
            case 'utf8mb3':
                $default = ['utf8mb3_general_ci', 'utf8mb3_unicode_ci', 'utf8mb3_bin'];
                break;
            case 'gb2312':
                $default = ['gb2312_chinese_ci', 'gb2312_bin'];
                break;
            case 'gbk':
                $default = ['gbk_chinese_ci', 'gbk_bin'];
                break;
            case 'ascii':
                $default = ['ascii_general_ci', 'ascii_bin'];
                break;
            default:
                return '建议使用常用的字符集，如使用特殊字符集请联系管理员';
        }
        if (!in_array($collation, $default)) {
            throw new \Exception('建议使用常用的字符集排序规则，如使用特殊字符集排序规则请联系管理员');
        }
        return $collation;
    }

    /**
     * 验证数据表引擎
     * @param string $engine
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function verifyTableEngine(string $engine): string
    {
        $default = ['InnoDB', 'MyISAM', 'MEMORY'];
        if (!in_array($engine, $default)) {
            throw new \Exception('建议使用常用的引擎，如使用特殊引擎请联系管理员');
        }
        return $engine;
    }

    /**
     * 预处理字段
     * @param array $columns - 字段
     * @return array
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function prepareColumns(array $columns): array
    {
        $primaryKey = '';
        $primaryKeyRows = 0;
        foreach ($columns as $index => $item) {
            // 字段格式验证
            self::verify($item['field'], 'alphaDash');
            $columns[$index]['field'] = trim($item['field']);
            if (!$item['field']) {
                unset($columns[$index]);
                continue;
            }
            $columns[$index]['primary_key'] = !empty($item['primary_key']);
            if ($columns[$index]['primary_key']) {
                $primaryKey = $columns[$index]['field'];
                $primaryKeyRows++;
            }
            $columns[$index]['auto_increment'] = !empty($item['auto_increment']);
            $columns[$index]['nullable'] = !empty($item['nullable']);
            if ($item['default'] === '') {
                $columns[$index]['default'] = null;
            } else if ($item['default'] === "''") {
                $columns[$index]['default'] = '';
            }
        }
        if ($primaryKeyRows > 1) {
            throw new \Exception('不支持复合主键');
        }
        if (empty($primaryKey)) {
            throw new \Exception('请设置主键');
        }
        return [$columns, $primaryKey];
    }

    /**
     * 预处理索引
     * @param array $keys - 索引
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    public static function prepareKeys(array $keys): array
    {
        foreach ($keys as $index => $item) {
            if (!$item['name'] || !$item['columns']) {
                unset($keys[$index]);
            }
        }
        return $keys;
    }

    /**
     * 获取字段类型
     * @return string[]
     * @author cdyun(121625706@qq.com)
     */
    public static function columnType(): array
    {
        return [
            'integer',
            'string',
            'text',
            'date',
            'enum',
            'float',
            'tinyInteger',
            'smallInteger',
            'mediumInteger',
            'bigInteger',
            'unsignedInteger',
            'unsignedTinyInteger',
            'unsignedSmallInteger',
            'unsignedMediumInteger',
            'unsignedBigInteger',
            'decimal',
            'double',
            'mediumText',
            'longText',
            'dateTime',
            'time',
            'timestamp',
            'char',
            'binary',
            'json'
        ];
    }

    /**
     * 构建字段sql
     * @param array $column - 字段
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function createColumnSql(array $column): string
    {
        $auto_increment = $column['auto_increment'] ?? '';
        $comment = $column['comment'] ?? '';
        $length = (int)$column['length'] ?? 0;
        $nullable = $column['nullable'];
        $default = $column['default'] !== null ? $column['default'] : null;
        $method = self::verify($column['type'], 'alphaDash');
        $fieldName = self::verify($column['field'], 'alphaDash');
        $sql = "`$fieldName` ";

        if (stripos($method, 'integer') !== false) {
            $type = str_ireplace('integer', 'int', $method);
            if (stripos($method, 'unsigned') !== false) {
                $type = str_ireplace('unsigned', '', $type);
                $sql .= "$type ";
                $sql .= 'unsigned ';
            } else {
                $sql .= "$type ";
            }
            if ($auto_increment) {
                $nullable = false;
                $default = null;
                $sql .= 'AUTO_INCREMENT ';
            }
        } else {
            switch ($method) {
                case 'dateTime':
                    $sql .= "DATETIME ";
                    break;
                case 'string':
                    $length = $length ?: 255;
                    $sql .= "varchar($length) ";
                    break;
                case 'char':
                case 'time':
                    $sql .= $length ? "$method($length) " : "$method ";
                    break;
                case 'enum':
                    $args = array_map('trim', explode(',', (string)$column['length']));
                    foreach ($args as $key => $value) {
                        $args[$key] = $value;
                    }
                    $sql .= 'enum(' . implode(',', $args) . ') ';
                    break;
                case 'double':
                case 'float':
                case 'decimal':
                    if (trim($column['length'])) {
                        $args = array_map('intval', explode(',', $column['length']));
                        $args[1] = $args[1] ?? $args[0];
                        $sql .= "$method($args[0], $args[1]) ";
                        break;
                    }
                    $sql .= "$method ";
                    break;
                default :
                    $sql .= "$method ";

            }
        }

        // 特殊字段存储大量或复杂数据必须允许为空
        if (stripos($method, 'text') !== false || $method == 'json' || $method == 'blob') {
            $nullable = true;
            $default = null;
        }
        if (!$nullable) {
            $sql .= 'NOT NULL ';
        }

        if ($method != 'text' && $default !== null) {
            if ($default === '') {
                $default = "''";
            }
            $sql .= "DEFAULT $default ";
        }

        if ($comment !== null) {
            $sql .= "COMMENT '$comment'";
        }

        return "$sql";
    }

    /**
     * 修改表结构
     * @param string $oldTableName - 旧表名
     * @param array $columns - 字段数据
     * @param array $keys - 索引
     * @param array $table - 表数据
     * @return true
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function modifyTable(string $oldTableName, array $columns, array $keys, array $table): bool
    {
        $oldSchema = self::getSchema($oldTableName);
        $oldColumns = $oldSchema['columns'];
        $oldKeys = $oldSchema['keys'];
        $oldTable = $oldSchema['table'];
        $newTableName = $table['table_name'];

        $tableComment = isset($table['table_comment']) ? addslashes($table['table_comment']) : '';
        $tableCharset = self::verifyTableCharset($table['table_charset'] ?: 'utf8mb4');
        $tableCollation = self::verifyTableCollation($tableCharset, $table['table_collation'] ?: 'utf8mb4_unicode_ci');
        $tableEngine = self::verifyTableEngine($table['table_engine'] ?: 'InnoDB');

        // 数据表待修改的属性
        $alterParts = [];
        // 表注释修改
        if ($oldTable['table_comment'] != $tableComment) {
            $alterParts[] = "COMMENT = '$tableComment'";
        }
        // 表编码修改
        if ($oldTable['table_charset'] != $tableCharset) {
            $alterParts[] = "CONVERT TO CHARACTER SET $tableCharset";
        }
        // 表字符集修改
        if ($oldTable['table_collation'] != $tableCollation) {
            $alterParts[] = "COLLATE $tableCollation";
        }
        // 表引擎修改
        if ($oldTable['table_engine'] != $tableEngine) {
            $alterParts[] = "ENGINE = $tableEngine";
        }
        // 执行所有属性修改（单条SQL）
        if (!empty($alterParts)) {
            $sql = "ALTER TABLE `$oldTableName` " . implode(', ', $alterParts);
            Db::query($sql);
        }

        // 表名修改，放在最后处理
        if ($oldTableName != $newTableName) {
            Db::query("ALTER TABLE `$oldTableName` RENAME TO `$newTableName`;");
        }

        $oldPrimaryKey = $oldTable['primary_key'][0] ?? null;
        [$columns, $primaryKey] = self::prepareColumns($columns);
        $keys = self::prepareKeys($keys);

        // 列更新
        $updateColumns = [];
        $types = self::columnType();
        foreach ($columns as $column) {
            if (!in_array($column['type'], $types)) {
                throw new \Exception("不支持的类型{$column['type']}");
            }
            $field = $column['old_field'] ?? $column['field'];
            $old_column = $oldColumns[$field] ?? [];
            // 遍历原始列，是否存在更新
            foreach ($old_column as $key => $value) {
                if (key_exists($key, $column) && ($column[$key] != $value || ($key === 'default' && $column[$key] !== $value))) {
                    $updateColumns[] = self::modifyColumn($column);
                    break;
                }
            }
        }
        // 执行所有列修改（单条SQL）
        if (!empty($updateColumns)) {
            $sql = "ALTER TABLE `$newTableName` " . implode(', ', $updateColumns);
            Db::query($sql);
        }

        // 列新增
        $addColumns = [];
        $tableSchema = self::getSchema($newTableName); // 获取最新表结构
        $tableColumns = $tableSchema['columns'];
        foreach ($columns as $column) {
            $field = $column['field'];
            // 新字段
            if (!isset($tableColumns[$field])) {
                //新增字段
                $sql = self::createColumnSql($column);
                $addColumns[] = "ADD COLUMN " . $sql;
            }
        }
        // 执行所有新增列（单条SQL）
        if (!empty($addColumns)) {
            $sql = "ALTER TABLE `$newTableName` " . implode(', ', $addColumns);
            Db::query($sql);
        }

        // 列删除
        $dropColumns = [];
        $tableSchema = self::getSchema($newTableName); // 获取最新表结构
        $tableColumnsFields = array_column($tableSchema['columns'], 'field');
        $existsColumnFields = array_column($columns, 'field');
        $dropColumnFields = array_diff($tableColumnsFields, $existsColumnFields);
        foreach ($dropColumnFields as $field) {
            $dropColumns[] = "DROP COLUMN `$field`";
        }
        // 执行所有新增列（单条SQL）
        if (!empty($dropColumns)) {
            $sql = "ALTER TABLE `$newTableName` " . implode(', ', $dropColumns);
            Db::query($sql);
        }

        // 数据表待修改的索引
        $addKeys = [];
        $dropKeys = [];
        foreach ($keys as $key) {
            $keyName = $key['name'];
            $oldKey = $oldKeys[$keyName] ?? null;
            // 如果索引不存在或已改变，需要重新创建
            $needRecreate = false;
            if (!$oldKey) {
                $needRecreate = true;
            } else {
                if ($key['type'] != $oldKey['type'] || $key['columns'] != implode(',', $oldKey['columns'])) {
                    // 索引定义已改变，需要先删除再创建
                    $dropKeys[] = "DROP INDEX `$keyName`";
                    unset($oldKeys[$keyName]);
                    $needRecreate = true;
                } else {
                    // 索引未变化，保留
                    unset($oldKeys[$keyName]);
                }
            }
            // 重新建立索引
            if ($needRecreate) {
                $columns = is_array($key['columns']) ? implode('`,`', $key['columns']) : $key['columns'];
                $type = $key['type'];

                if ($type == 'unique') {
                    $addKeys[] = "ADD UNIQUE INDEX $keyName ($columns)";
                } else {
                    $addKeys[] = "ADD INDEX $keyName ($columns)";
                }
            }
        }

        // 删除在新配置中不存在的旧索引
        foreach ($oldKeys as $oldKeyName => $oldKeyData) {
            $dropKeys[] = "DROP INDEX `$oldKeyName`";
        }
        // 执行所有索引修改（单条SQL）
        if (!empty($dropKeys) || !empty($addKeys)) {
            $keyParts = array_merge($dropKeys, $addKeys);
            $sql = "ALTER TABLE `$newTableName` " . implode(', ', $keyParts);
            Db::query($sql);
        }

        // 变更主键
        if ($oldPrimaryKey != $primaryKey) {
            if ($oldPrimaryKey) {
                Db::query("ALTER TABLE `$newTableName` DROP PRIMARY KEY");
            }
            if ($primaryKey) {
                Db::query("ALTER TABLE `$newTableName` ADD PRIMARY KEY (`$primaryKey`)");
            }
        }
        return true;

    }

    /**
     * 获取表结构
     * @param string $table - 表名
     * @return array
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function getSchema(string $table): array
    {
        self::verify($table, 'alphaDash');
        $database = DbUtil::getDbConfig('database');
        $schema_raw = Db::query("select * from information_schema.COLUMNS where TABLE_SCHEMA = '$database' and table_name = '$table' order by ORDINAL_POSITION");
        $columns = [];
        foreach ($schema_raw as $item) {
            $field = $item['COLUMN_NAME'];
            $columns[$field] = [
                'field' => $field,
                'type' => self::typeToMethod($item['DATA_TYPE'], (bool)strpos($item['COLUMN_TYPE'], 'unsigned')),
                'comment' => $item['COLUMN_COMMENT'],
                'default' => $item['COLUMN_DEFAULT'],
                'length' => self::getLengthValue($item),
                'nullable' => $item['IS_NULLABLE'] !== 'NO',
                'primary_key' => $item['COLUMN_KEY'] === 'PRI',
                'auto_increment' => str_contains($item['EXTRA'], 'auto_increment')
            ];
        }
        $table_schema = Db::query("SELECT TABLE_COMMENT,ENGINE,TABLE_COLLATION FROM  information_schema.`TABLES` WHERE  TABLE_SCHEMA='$database' and TABLE_NAME='$table'");

        $indexes = Db::query("SHOW INDEX FROM `$table`");
        $keys = [];
        $primary_key = [];
        foreach ($indexes as $index) {
            $key_name = $index['Key_name'];
            if ($key_name == 'PRIMARY') {
                $primary_key[] = $index['Column_name'];
                continue;
            }
            if (!isset($keys[$key_name])) {
                $keys[$key_name] = [
                    'name' => $key_name,
                    'columns' => [],
                    'type' => $index['Non_unique'] == 0 ? 'unique' : 'normal'
                ];
            }
            $keys[$key_name]['columns'][] = $index['Column_name'];
        }

        $table_collation = $table_schema[0]['TABLE_COLLATION'] ?? '';
        $charset = strstr($table_collation, '_', true);

        return [
            'table' => [
                'name' => $table,
                'table_comment' => $table_schema[0]['TABLE_COMMENT'] ?? '',
                'table_charset' => $charset,
                'table_collation' => $table_collation,
                'table_engine' => $table_schema[0]['ENGINE'] ?? '',
                'primary_key' => $primary_key
            ],
            'columns' => $columns,
            'keys' => array_reverse($keys, true)
        ];

    }

    /**
     * 类型转换
     * @param $type - 数据库类型
     * @param bool $unsigned - 是否为无符号
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    public static function typeToMethod($type, bool $unsigned = false): string
    {
        if (stripos($type, 'int') !== false) {
            $type = str_replace('int', 'Integer', $type);
            return $unsigned ? "unsigned" . ucfirst($type) : lcfirst($type);
        }
        $map = [
            'int' => 'integer',
            'varchar' => 'string',
            'mediumtext' => 'mediumText',
            'longtext' => 'longText',
            'datetime' => 'dateTime',
        ];
        return $map[$type] ?? $type;
    }

    /**
     * 获取字段长度值
     * @param $schema - 字段信息
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public static function getLengthValue($schema): mixed
    {
        $type = $schema['DATA_TYPE'];
        if (in_array($type, ['float', 'decimal', 'double'])) {
            return "{$schema['NUMERIC_PRECISION']},{$schema['NUMERIC_SCALE']}";
        }
        if ($type === 'enum') {
            return implode(',', array_map(function ($item) {
                return trim($item, "'");
            }, explode(',', substr($schema['COLUMN_TYPE'], 5, -1))));
        }
        if (in_array($type, ['varchar', 'text', 'char'])) {
            return $schema['CHARACTER_MAXIMUM_LENGTH'];
        }
        if (in_array($type, ['time', 'datetime', 'timestamp'])) {
            return $schema['CHARACTER_MAXIMUM_LENGTH'];
        }
        return '';
    }

    /**
     * 更新数据表字段
     * @param array $column - 字段信息
     * @return string
     * @throws \Exception
     * @author cdyun(121625706@qq.com)
     */
    public static function modifyColumn(array $column): string
    {
        $auto_increment = $column['auto_increment'] ?? '';
        $comment = $column['comment'] ?? '';
        $length = (int)$column['length'] ?? 0;
        $nullable = $column['nullable'];
        $default = $column['default'] !== null ? $column['default'] : null;
        $method = self::verify($column['type'], 'alphaDash');
        $fieldName = self::verify($column['field'], 'alphaDash');
        $oldField = !empty($column['old_field']) ? self::verify($column['old_field'], 'alphaDash') : null;

        if ($oldField && $oldField !== $fieldName) {
            $sql = "CHANGE COLUMN `$oldField` `$fieldName` ";
        } else {
            $sql = "MODIFY `$fieldName` ";
        }

        if (stripos($method, 'integer') !== false) {
            $type = str_ireplace('integer', 'int', $method);
            if (stripos($method, 'unsigned') !== false) {
                $type = str_ireplace('unsigned', '', $type);
                $sql .= "$type ";
                $sql .= 'unsigned ';
            } else {
                $sql .= "$type ";
            }
            if ($auto_increment) {
                $nullable = false;
                $default = null;
                $sql .= 'AUTO_INCREMENT ';
            }
        } else {
            switch ($method) {
                case 'dateTime':
                    $sql .= "DATETIME ";
                    break;
                case 'string':
                    $length = $length ?: 255;
                    $sql .= "varchar($length) ";
                    break;
                case 'char':
                case 'time':
                    $sql .= $length ? "$method($length) " : "$method ";
                    break;
                case 'enum':
                    $args = array_map('trim', explode(',', (string)$column['length']));
                    foreach ($args as $key => $value) {
                        $args[$key] = $value;
                    }
                    $sql .= 'enum(' . implode(',', $args) . ') ';
                    break;
                case 'double':
                case 'float':
                case 'decimal':
                    if (trim($column['length'])) {
                        $args = array_map('intval', explode(',', $column['length']));
                        $args[1] = $args[1] ?? $args[0];
                        $sql .= "$method($args[0], $args[1]) ";
                        break;
                    }
                    $sql .= "$method ";
                    break;
                default :
                    $sql .= "$method ";

            }
        }

        // 特殊字段存储大量或复杂数据必须允许为空
        if (stripos($method, 'text') !== false || $method == 'json' || $method == 'blob') {
            $nullable = true;
            $default = null;
        }
        if (!$nullable) {
            $sql .= 'NOT NULL ';
        }

        if ($method != 'text' && $default !== null) {
            if ($default === '') {
                $default = "''";
            }
            $sql .= "DEFAULT $default ";
        }

        if ($comment !== null) {
            $sql .= "COMMENT '$comment'";
        }

        return "$sql";
    }

    /**
     * 删除数据表
     * @param $table
     * @return true
     * @author cdyun(121625706@qq.com)
     */
    public static function dropTable($table): bool
    {
        $tables = is_array($table) ? implode(',', $table) : $table;
        $sql = "DROP TABLE IF EXISTS " . $tables;
        Db::query($sql);
        return true;
    }
}