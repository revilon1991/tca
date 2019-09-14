<?php

declare(strict_types=1);

namespace App\Component\Manager\Executer;

use App\Component\IdGenerator\IdGenerator;
use App\Component\Manager\Driver\MysqlDriver;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\ORM\EntityManagerInterface;

class RowManager
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MysqlDriver
     */
    private $driver;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    /**
     * @param EntityManagerInterface $entityManager
     * @param IdGenerator $idGenerator
     * @param MysqlDriver $driver
     */
    public function __construct(EntityManagerInterface $entityManager, IdGenerator $idGenerator, MysqlDriver $driver)
    {
        $this->connection = $entityManager->getConnection();
        $this->driver = $driver;
        $this->idGenerator = $idGenerator;
    }

    /**
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function generateUniqueId(): string
    {
        return $this->idGenerator->generateUniqueId();
    }

    /**
     * @param string $tableName
     * @param array $params
     * @param bool $isIgnore
     *
     * @return int
     *
     * @throws DBALException
     */
    public function insert(string $tableName, array $params, bool $isIgnore = false): int
    {
        return $this->insertBulk($tableName, [$params], $isIgnore);
    }

    /**
     * @param string $tableName
     * @param array $paramsList
     * @param bool $isIgnore
     *
     * @return int
     *
     * @throws DBALException
     */
    public function insertBulk(string $tableName, array $paramsList, bool $isIgnore = false): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        $paramsList = $this->prepareInsertParamsList($paramsList);

        $sql = $this->driver->getInsertBulkSql($tableName, $paramsList, $isIgnore);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param array $params
     * @param array $where
     *
     * @return int
     *
     * @throws DBALException
     */
    public function update(string $tableName, array $params, array $where = []): int
    {
        $params = $this->prepareUpdateParams($params);

        $sql = $this->driver->getUpdateSql($tableName, $params, $where);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param array $paramsList
     *
     * @return int
     *
     * @throws DBALException
     */
    public function updateBulk(string $tableName, array $paramsList): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        $paramsList = $this->prepareUpdateParamsList($paramsList);

        $sql = $this->driver->getUpdateBulkSql($tableName, $paramsList);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param array $params
     * @param array $replaceFields
     *
     * @return int
     *
     * @throws DBALException
     */
    public function upsert(string $tableName, array $params, array $replaceFields): int
    {
        return $this->upsertBulk($tableName, [$params], $replaceFields);
    }

    /**
     * @param string $tableName
     * @param array $paramsList
     * @param array $replaceFields
     *
     * @return int
     *
     * @throws DBALException
     */
    public function upsertBulk(string $tableName, array $paramsList, array $replaceFields): int
    {
        if (empty($paramsList)) {
            return 0;
        }

        $paramsList = $this->prepareInsertParamsList($paramsList);
        $replaceFields = $this->updateReplaceFields($replaceFields);

        $sql = $this->driver->getUpsertBulkSql($tableName, $paramsList, $replaceFields);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param string $id
     *
     * @return int
     *
     * @throws DBALException
     */
    public function delete(string $tableName, string $id): int
    {
        $sql = $this->driver->getDeleteSql($tableName, $id);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param array $idList
     *
     * @return int
     *
     * @throws DBALException
     */
    public function deleteBulk(string $tableName, array $idList): int
    {
        if (empty($idList)) {
            return 0;
        }

        $sql = $this->driver->getDeleteBulkSql($tableName, $idList);

        return $this->executeSql($sql);
    }

    /**
     * @param string $tableName
     * @param string $id
     *
     * @return int
     *
     * @throws DBALException
     */
    public function deleteSoft(string $tableName, string $id): int
    {
        return $this->update($tableName, ['id' => $id, 'deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * @param string $tableName
     * @param array $idList
     *
     * @return int
     *
     * @throws DBALException
     */
    public function deleteBulkSoft(string $tableName, array $idList): int
    {
        if (empty($idList)) {
            return 0;
        }

        $updateParamList = [];

        foreach ($idList as $id) {
            $updateParamList[] = ['id' => $id, 'deleted_at' => date('Y-m-d H:i:s')];
        }

        return $this->updateBulk($tableName, $updateParamList);
    }

    /**
     * @param ResultStatement $stmt
     * @param string $indexBy
     * @param string $indexStackBy
     *
     * @return array
     */
    public function getResultStackList(ResultStatement $stmt, string $indexBy, string $indexStackBy = null): array
    {
        $result = [];

        while ($row = $stmt->fetch()) {
            if ($indexStackBy) {
                $result[$row[$indexBy]][$row[$indexStackBy]] = $row;
            } else {
                $result[$row[$indexBy]][] = $row;
            }
        }

        $stmt->closeCursor();

        return $result;
    }

    /**
     * @param ResultStatement $stmt
     * @param string $indexBy
     * @param string $indexValue
     *
     * @return array
     */
    public function getResultPairList(ResultStatement $stmt, string $indexBy, string $indexValue = null): array
    {
        $indexValue = $indexValue ?? $indexBy;

        $result = [];

        while ($row = $stmt->fetch()) {
            $result[$row[$indexBy]] = $row[$indexValue];
        }

        $stmt->closeCursor();

        return $result;
    }

    /**
     * @param string $sql
     *
     * @return int
     *
     * @throws DBALException
     */
    private function executeSql(string $sql): int
    {
        return $this->connection->executeUpdate($sql);
    }

    /**
     * @param array $paramsList
     *
     * @return array
     */
    private function prepareInsertParamsList(array $paramsList): array
    {
        $date = date('Y-m-d H:i:s');

        foreach ($paramsList as &$params) {
            $params = $this->prepareId($params);
            $params = $this->prepareCreatedAt($params, $date);
            $params = $this->prepareUpdatedAt($params, $date);
        }

        return $paramsList;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function prepareUpdateParams(array $params): array
    {
        $result = $this->prepareUpdateParamsList([$params]);

        return reset($result);
    }

    /**
     * @param array $paramsList
     *
     * @return array
     */
    private function prepareUpdateParamsList(array $paramsList): array
    {
        $date = date('Y-m-d H:i:s');

        foreach ($paramsList as &$params) {
            $params = $this->prepareUpdatedAt($params, $date);
        }

        return $paramsList;
    }

    /**
     * @param array $replaceFields
     *
     * @return array
     */
    private function updateReplaceFields(array $replaceFields): array
    {
        if (!in_array('updated_at', $replaceFields, true)) {
            $replaceFields[] = 'updated_at';
        }

        return $replaceFields;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    private function prepareId(array $params): array
    {
        if (empty($params['id'])) {
            $params['id'] = [$this->generateUniqueId()];
        }

        return $params;
    }

    /**
     * @param array $params
     * @param string $date
     *
     * @return array
     */
    private function prepareCreatedAt(array $params, string $date): array
    {
        if (empty($params['created_at'])) {
            $params['created_at'] = [$date];
        }

        return $params;
    }

    /**
     * @param array $params
     * @param string $date
     *
     * @return array
     */
    private function prepareUpdatedAt(array $params, string $date): array
    {
        $params['updated_at'] = [$date];

        return $params;
    }
}
