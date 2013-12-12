<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\EventManager\EventManagerInterface;
use Users\Service\Service as UsersService;
use Application\Form\Settings as SettingsForm;
use Application\Event\Event as ApplicationEvent;
use Application\Utility\Pagination as PaginationUtility;

class AbstractAdministrationController extends AbstractActionController
{
    /**
     * Translator
     * @var object  
     */
    protected $translator;

    /**
     * Order by value
     * @var string
     */
    protected $orderBy = null;

    /**
     * Order type value
     * @var string
     */
    protected $orderType = null;

    /**
     * Per page value
     * @var integer
     */
    protected $perPage = null;

    /**
     * Page value
     * @var integer
     */
    protected $page = null;

    /**
     * Get page value
     *
     * @return integer
     */
    public function getPage()
    {
        if ($this->page === null) {
            $this->page = $this->params()->fromRoute('page', 1);
        }

        return $this->page; 
    }

    /**
     * Get order by value
     *
     * @return string
     */
    public function getOrderBy()
    {
        if ($this->orderBy === null) {
            $this->orderBy = $this->params()->fromRoute('order_by');
        }

        return $this->orderBy;
    }

    /**
     * Get order type
     *
     * @return string
     */
    public function getOrderType()
    {
        if ($this->orderType === null) {
            $this->orderType = $this->params()->fromRoute('order_type');
        }

        return $this->orderType;
    }

    /**
     * Get per page value
     *
     * @return string
     */
    public function getPerPage()
    {
        if ($this->perPage === null) {
            $this->perPage  = $this->params()->fromRoute('per_page');
        }

        return $this->perPage; 
    }

    /**
     * Get translation
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            $this->translator = $this->getServiceLocator()->get('Translator');
        }

        return $this->translator;
    }

    /**
     * Generate settings form
     *
     * @param string $module
     * @param string $controller
     * @return object
     */
    protected function settingsForm($module, $controller)
    {
        $currentlanguage = UsersService::getCurrentLocalization()['language'];

        // get settings form
        $settingsForm = $this->getServiceLocator()
            ->get('Application\Form\FormManager')
            ->getInstance('Application\Form\Settings');

        // get settngs list
        $settings = $this->getServiceLocator()
            ->get('Application\Model\ModelManager')
            ->getInstance('Application\Model\SettingAdministration');

        // get settings list
        if (false !== ($settingsList = $settings->getSettingsList($module, $currentlanguage))) {
            $settingsForm->addFormElements($settingsList);
            $request  = $this->getRequest();

            // validate form
            if ($request->isPost()) {
                // fill form with received values
                $settingsForm->getForm()->setData($request->getPost(), false);

                // save data
                if ($settingsForm->getForm()->isValid()) {
                    if (true === ($result = $settings->
                            saveSettings($settingsList, $settingsForm->getForm()->getData(), $currentlanguage))) {

                        // fire event
                        $eventDesc = UsersService::isGuest()
                            ? 'Event - Settings change (guest)'
                            : 'Event - Settings change (user)';

                        $eventDescParams = UsersService::isGuest()
                            ? array($module)
                            : array(UsersService::getCurrentUserIdentity()->nick_name, $module);

                        ApplicationEvent::fireEvent(ApplicationEvent::
                            APPLICATION_CHANGE_SETTINGS, 0, UsersService::getCurrentUserIdentity()->user_id, $eventDesc, $eventDescParams);

                        $this->flashMessenger()
                            ->setNamespace('success')
                            ->addMessage($this->getTranslator()->translate('Settings have been saved'));
                    }
                    else {
                        $this->flashMessenger()
                            ->setNamespace('error')
                            ->addMessage($result);
                    }

                    $this->redirect()->toRoute('application', array('controller' => $controller));
                }
            }
        }

        return $settingsForm->getForm();
    }
}
