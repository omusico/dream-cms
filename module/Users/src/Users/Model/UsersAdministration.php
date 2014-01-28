<?php

namespace Users\Model;

use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as DbSelectPaginator;
use Application\Utility\Pagination as PaginationUtility;
use Application\Service\Service as ApplicationService;
use Zend\Db\Sql\Predicate\Like as LikePredicate;
use Application\Model\Acl as AclBase;
use Zend\Db\Sql\Predicate\NotIn as NotInPredicate;
use Exception;

class UsersAdministration extends Base
{
    /**
     * Delete user
     *
     * @param integer $userId
     * @return boolean|string
     */
    public function deleteUser($userId)
    {
        try {
            $this->adapter->getDriver()->getConnection()->beginTransaction();

            $delete = $this->delete()
                ->from('users')
                ->where(array(
                    'user_id' => $userId
                ))
                ->where(array(
                    new NotInPredicate('user_id', array(AclBase::DEFAULT_USER_ID))
                ));

            $statement = $this->prepareStatementForSqlObject($delete);
            $result = $statement->execute();

            // clear a cache
            $this->removeUserCache($userId);

            $this->adapter->getDriver()->getConnection()->commit();
        }
        catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            return $e->getMessage();
        }

        return $result->count() ? true : false;
    }

    /**
     * Get users
     *
     * @param integer $page
     * @param integer $perPage
     * @param string $orderBy
     * @param string $orderType
     * @param array $filters
     *      string nickname
     *      string email
     *      string status
     *      integer role
     * @return object
     */
    public function getUsers($page = 1, $perPage = 0, $orderBy = null, $orderType = null, array $filters = array())
    {
        $orderFields = array(
            'id',
            'nickname',
            'email',
            'registered'
        );

        $orderType = !$orderType || $orderType == 'desc'
            ? 'desc'
            : 'asc';

        $orderBy = $orderBy && in_array($orderBy, $orderFields)
            ? $orderBy
            : 'id';

        $select = $this->select();
        $select->from(array('a' => 'users'))
            ->columns(array(
                'id' => 'user_id',
                'nickname' => 'nick_name',
                'email',
                'status',
                'registered'
            ))
            ->join(
                array('b' => 'acl_roles'),
                'a.role = b.id',
                array(
                    'role' => 'name'
                )
            )
            ->order($orderBy . ' ' . $orderType);

        // filter by nickname
        if (!empty($filters['nickname'])) {
            $select->where(array(
                new LikePredicate('nick_name', $filters['nickname'] . '%')
            ));
        }

        // filter by email
        if (!empty($filters['email'])) {
            $select->where(array(
                'email' => $filters['email']
            ));
        }

        // filter by status
        if (!empty($filters['status'])) {
            $select->where(array(
                'status' => $filters['status']
            ));
        }

        // filter by role
        if (!empty($filters['role'])) {
            $select->where(array(
                'role' => $filters['role']
            ));
        }

        $paginator = new Paginator(new DbSelectPaginator($select, $this->adapter));
        $paginator->setCurrentPageNumber($page);
        $paginator->setItemCountPerPage(PaginationUtility::processPerPage($perPage));
        $paginator->setPageRange(ApplicationService::getSetting('application_page_range'));

        return $paginator;
    }
}