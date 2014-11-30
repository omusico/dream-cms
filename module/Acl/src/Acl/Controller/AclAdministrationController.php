<?php
namespace Acl\Controller;

use Application\Controller\ApplicationAbstractAdministrationController;
use Zend\View\Model\ViewModel;

class AclAdministrationController extends ApplicationAbstractAdministrationController
{
    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Get model
     */
    protected function getModel()
    {
        if (!$this->model) {
            $this->model = $this->getServiceLocator()
                ->get('Application\Model\ModelManager')
                ->getInstance('Acl\Model\AclAdministration');
        }

        return $this->model;
    }

    /**
     * Default action
     */
    public function indexAction()
    {
        // redirect to list action
        return $this->redirectTo('acl-administration', 'list');
    }

    /**
     * Allow selected resources
     */
    public function allowResourcesAction()
    {
        // get the role info
        if (null == ($role = 
                $this->getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($resourcesIds = $request->getPost('resources', null))) {
                // allow recources
                foreach ($resourcesIds as $resourceId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // allow the resource
                    if (true !== ($allowResult = $this->getModel()->allowResource($role['id'], 
                            $resourceId))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($allowResult));

                        break;
                    }
                }

                if (true === $allowResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected resources have been allowed'));                 
                }                
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'browse-resources', [
            'slug' => $role['id']
        ], true);
    }

    /**
     * Disallow selected resources
     */
    public function disallowResourcesAction()
    {
        // get the role info
        if (null == ($role = 
                $this->getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($resourcesIds = $request->getPost('resources', null))) {
                // disallow recources
                foreach ($resourcesIds as $resourceId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // disallow the resource
                    if (true !== ($disallowResult = 
                            $this->getModel()->disallowResource($role['id'], $resourceId))) {

                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate($disallowResult));

                        break;
                    }
                }

                if (true === $disallowResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected resources have been disallowed'));                    
                }
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'browse-resources', [
            'slug' => $role['id']
        ], true);
    }

    /**
     * Delete selected roles
     */
    public function deleteRolesAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            if (null !== ($rolesIds = $request->getPost('roles', null))) {
                // delete selected roles
                foreach ($rolesIds as $roleId) {
                    // check the permission and increase permission's actions track
                    if (true !== ($result = $this->aclCheckPermission(null, true, false))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($this->getTranslator()->translate('Access Denied'));

                        break;
                    }

                    // delete the role
                    if (true !== ($deleteResult = $this->getModel()->deleteRole($roleId))) {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage(($deleteResult ? $this->getTranslator()->translate($deleteResult)
                                : $this->getTranslator()->translate('Error occurred')));

                        break;
                    }
                }

                if (true === $deleteResult) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Selected roles have been deleted'));
                }
            }
        }

        // redirect back
        return $this->redirectTo('acl-administration', 'list', [], true);
    }

    /**
     * Edit a role action
     */
    public function editRoleAction()
    {
        // get the role info
        if (null == ($role = $this->getModel()->getRoleInfo($this->getSlug()))) {
            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get the acl role form
        $aclRoleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclRole')
            ->setModel($this->getModel())
            ->setRoleId($role['id']);

        $aclRoleForm->getForm()->setData($role);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclRoleForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit the role
                if (true == ($result = $this->
                        getModel()->editRole($role['id'], $aclRoleForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'edit-role', [
                    'slug' => $role['id']
                ]);
            }
        }

        return new ViewModel([
            'role' => $role,
            'aclRoleForm' => $aclRoleForm->getForm()
        ]);
    }

    /**
     * Add a new role action
     */
    public function addRoleAction()
    {
        // get an acl role form
        $aclRoleForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclRole')
            ->setModel($this->getModel());

        $request  = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclRoleForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclRoleForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // add a new role
                $result = $this->getModel()->addRole($aclRoleForm->getForm()->getData());

                if (is_numeric($result)) {
                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Role has been added'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'add-role');
            }
        }

        return new ViewModel([
            'aclRoleForm' => $aclRoleForm->getForm()
        ]);
    }

    /**
     * Acl roles list 
     */
    public function listAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclRoleFilter');

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getRoles($this->getPage(),
                $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Acl browse resources
     */
    public function browseResourcesAction()
    {
        // check the permission and increase permission's actions track
        if (true !== ($result = $this->aclCheckPermission())) {
            return $result;
        }

        // get the role info
        if (null == ($role = $this->
                getModel()->getRoleInfo($this->getSlug(), false, true))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        $filters = [];

        // get a filter form
        $filterForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclResourceFilter');

        $filterForm->setModel($this->getModel());

        $request = $this->getRequest();
        $filterForm->getForm()->setData($request->getQuery(), false);

        // check the filter form validation
        if ($filterForm->getForm()->isValid()) {
            $filters = $filterForm->getForm()->getData();
        }

        // get data
        $paginator = $this->getModel()->getResources($role['id'],
                $this->getPage(), $this->getPerPage(), $this->getOrderBy(), $this->getOrderType(), $filters);

        return new ViewModel([
            'slug' => $role['id'],
            'roleInfo' => $role,
            'filter_form' => $filterForm->getForm(),
            'paginator' => $paginator,
            'order_by' => $this->getOrderBy(),
            'order_type' => $this->getOrderType(),
            'per_page' => $this->getPerPage()
        ]);
    }

    /**
     * Acl resource's settings
     */
    public function resourceSettingsAction()
    {
        // get resource's settings info
        if (null == ($settings =
                $this->getModel()->getResourceSettings($this->getSlug()))) {

            return $this->createHttpNotFoundModel($this->getResponse());
        }

        // get an acl resource's settings form
        $aclResourceSettingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Acl\Form\AclResourceSetting');

        // fill the form with default values
        $aclResourceSettingsForm->setActionsLimit($settings['actions_limit'])
            ->setActionsReset($settings['actions_reset'])
            ->setDateStart($settings['date_start'])
            ->setDateEnd($settings['date_end']);

        $request = $this->getRequest();

        // validate the form
        if ($request->isPost()) {
            // fill the form with received values
            $aclResourceSettingsForm->getForm()->setData($request->getPost(), false);

            // save data
            if ($aclResourceSettingsForm->getForm()->isValid()) {
                // check the permission and increase permission's actions track
                if (true !== ($result = $this->aclCheckPermission())) {
                    return $result;
                }

                // edit settings
                if (true == ($result = $this->getModel()->editResourceSettings($settings['connection'], 
                        $settings['resource'], $settings['role'], $aclResourceSettingsForm->getForm()->getData()))) {

                    $this->flashMessenger()
                        ->setNamespace('success')
                        ->addMessage($this->getTranslator()->translate('Resource\'s settings have been edited'));
                }
                else {
                    $this->flashMessenger()
                        ->setNamespace('error')
                        ->addMessage($this->getTranslator()->translate($result));
                }

                return $this->redirectTo('acl-administration', 'resource-settings', [
                    'slug' => $settings['connection']
                ]);
            }
        }

        return new ViewModel([
            'resourceSettings' => $settings,
            'aclResourceSettingsForm' => $aclResourceSettingsForm->getForm()
        ]);
    }
}