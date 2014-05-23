<?php

namespace Membership\Model;

use Application\Utility\ErrorLogger;
use Exception;
use Application\Model\AbstractBase;
use Application\Utility\FileSystem as FileSystemUtility;
use Zend\Db\ResultSet\ResultSet;

class Base extends AbstractBase
{
    /**
     * Images directory
     * @var string
     */
    protected static $imagesDir = 'membership/';

    /**
     * Get images directory name
     *
     * @return string
     */
    public static function getImagesDir()
    {
        return self::$imagesDir;
    }

    /**
     * Delete the role
     *
     * @param array $roleInfo
     *      integer id required
     *      string image required
     * @return boolean|string
     */
    public function deleteRole($roleInfo)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('membership_level')
                ->where(array(
                    'id' => $roleInfo['id']
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // delete the image
            if ($roleInfo['image']) {
                if (true !== ($imageDeleteResult = $this->deleteImage($roleInfo['image']))) {
                    throw new Exception('Image deleting failed');
                }
            }

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            ErrorLogger::log($e);

            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Delete an membership's image
     *
     * @param string $imageName
     * @return boolean
     */
    protected function deleteImage($imageName)
    {
        return FileSystemUtility::deleteResourceFile($imageName, self::$imagesDir);
    }

    /**
     * Get all memberhip levels
     *
     * @param integer $roleId
     * @return object
     */
    public function getAllMembershipLevels($roleId)
    {
        $select = $this->select();
        $select->from('membership_level')
            ->columns(array(
                'id',
                'image'
            ))
            ->where(array(
                'role_id' => $roleId
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $resultSet = new ResultSet;
        return $resultSet->initialize($statement->execute());
    }

    /**
     * Get the role info
     *
     * @param integer $id
     * @return array
     */
    public function getRoleInfo($id)
    {
        $select = $this->select();
        $select->from('membership_level')
            ->columns(array(
                'id',
                'role_id',
                'cost',
                'lifetime',
                'description',
                'language',
                'image',
            ))
            ->where(array(
                'id' => $id
            ));

        $statement = $this->prepareStatementForSqlObject($select);
        $result = $statement->execute();

        return $result->current();
    }
}