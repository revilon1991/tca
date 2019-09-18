<?php

declare(strict_types=1);

namespace App\Component\Manager\Driver;

use Doctrine\DBAL\Driver\Connection;
use function array_keys;
use function implode;
use function is_array;
use function sprintf;

class MysqlDriver
{
    public const UPSERT_INCREMENT = 'increment';
    public const UPSERT_DECREMENT = 'decrement';
    public const UPSERT_CONDITION = 'condition';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getInsertSql(string $tableName, array $params, bool $isIgnore = false): string
    {
        return $this->getInsertBulkSql($tableName, [$params], $isIgnore);
    }

    /**
     * {@inheritdoc}
     */
    public function getInsertBulkSql(string $tableName, array $paramsList, bool $isIgnore = false): string
    {
        $fields = implode(', ', array_keys($paramsList[0]));

        $sql = $isIgnore ? 'INSERT IGNORE' : 'INSERT';
        $sql = sprintf('%s INTO `%s` (%s) VALUES ', $sql, $tableName, $fields);

        $sqlValuesList = [];

        foreach ($paramsList as $params) {
            $values = $this->getValues($params);
            $sqlValuesList[] = sprintf('(%s)', implode(', ', $values));
        }

        $sql .= ' ' . implode(', ', $sqlValuesList);

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateSql(string $tableName, array $params, array $where = []): string
    {
        if (!$where) {
            $where = ['id' => $params['id']];
        }

        unset($params['id']);

        $subSqlSetList = [];

        foreach ($params as $field => $data) {
            $value = $this->getValue($data);
            $subSqlSetList[$field] = sprintf('%s = %s', $field, $value);
        }

        $subSqlWhereList = [];

        foreach ($where as $whereField => $whereData) {
            $whereValue = $this->getValue($whereData);
            $subSqlWhereList[$whereField] = sprintf('%s = %s', $whereField, $whereValue);
        }

        $sql = sprintf('UPDATE `%s`', $tableName);

        return sprintf('%s SET %s WHERE %s', $sql, implode(', ', $subSqlSetList), implode(' AND ', $subSqlWhereList));
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdateBulkSql(string $tableName, array $paramsList): string
    {
        $idList = [];
        $subSqlWhenList = [];

        foreach ($paramsList as $params) {
            $id = $this->quote($params['id']);
            $idList[] = $id;

            foreach ($params as $field => $data) {
                if ($field === 'id') {
                    continue;
                }

                $value = $this->getValue($data);

                $subSqlWhenList[$field][] = sprintf('WHEN id=%s THEN %s', $id, $value);
            }
        }

        $subSqlCaseList = [];

        foreach ($subSqlWhenList as $field => $data) {
            $subSqlCaseList[] = sprintf('%s = CASE %s ELSE %s END', $field, implode(' ', $data), $field);
        }

        $sql = sprintf('UPDATE `%s`', $tableName);

        return sprintf('%s SET %s WHERE id IN (%s)', $sql, implode(', ', $subSqlCaseList), implode(', ', $idList));
    }

    /**
     * {@inheritdoc}
     */
    public function getUpsertSql(string $tableName, array $params, array $replaceFields): string
    {
        return $this->getUpsertBulkSql($tableName, [$params], $replaceFields);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpsertBulkSql(string $tableName, array $paramsList, array $replaceFields): string
    {
        $insertSql = $this->getInsertBulkSql($tableName, $paramsList);

        $sql = $insertSql . ' ON DUPLICATE KEY UPDATE ';

        $sqlReplaceList = [];

        foreach ($replaceFields as $replaceField) {
            if (!is_array($replaceField)) {
                $sqlReplaceList[] = sprintf('%s = VALUES(%s)', $replaceField, $replaceField);

                continue;
            }

            [$field, $replaceType] = $replaceField;
            $condition = $replaceField[2] ?? '';

            if ($replaceType === self::UPSERT_INCREMENT) {
                $sqlReplaceList[] = sprintf('%s = %s + VALUES(%s)', $field, $field, $field);
            } elseif ($replaceType === self::UPSERT_DECREMENT) {
                $sqlReplaceList[] = sprintf('%s = %s - VALUES(%s)', $field, $field, $field);
            } elseif ($replaceType === self::UPSERT_CONDITION) {
                $sqlReplaceList[] = sprintf('%s = %s', $field, $condition);
            }
        }

        $sql .= implode(', ', $sqlReplaceList);

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteSql(string $tableName, string $id): string
    {
        $id = $this->quote($id);

        return sprintf('DELETE FROM `%s` WHERE id = %s', $tableName, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDeleteBulkSql(string $tableName, array $idList): string
    {
        foreach ($idList as $key => $id) {
            $idList[$key] = $this->quote($id);
        }

        return sprintf('DELETE FROM `%s` WHERE id IN (%s)', $tableName, implode(', ', $idList));
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * @param string|array $data
     *
     * @return string
     */
    private function getValue($data): string
    {
        if (is_array($data)) {
            return $this->quote($data[0], $data[1] ?? null);
        }

        return $this->quote($data);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function getValues(array $params): array
    {
        $values = [];

        foreach ($params as $key => $data) {
            $values[] = $this->getValue($data);
        }

        return $values;
    }

    /**
     * @param $value
     * @param int|null $type
     *
     * @return string
     */
    private function quote($value, ?int $type = null): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return $this->connection->quote($value, $type);
    }
}
