<?php
namespace Acl\Form;

use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Acl\Model\AclAdministration as AclAdministrationModel;

class AclRole extends ApplicationAbstractCustomForm 
{
    /**
     * ACL role name max string length
     */
    const ACL_NAME_MAX_LENGTH = 50;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-role';

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Role id
     * @var integer
     */
    protected $roleId;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'name' => [
            'name' => 'name',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Name',
            'required' => true,
            'max_length' => self::ACL_NAME_MAX_LENGTH
        ],
        'submit' => [
            'name' => 'submit',
            'type' => ApplicationCustomFormBuilder::FIELD_SUBMIT,
            'label' => 'Submit'
        ]
    ];

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // add extra validators
            $this->formElements['name']['validators'] = [
                [
                    'name' => 'callback',
                    'options' => [
                        'callback' => [$this, 'validateRoleName'],
                        'message' => 'Role already used'
                    ]
                ]
            ];

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
    
    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(AclAdministrationModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set a role id
     *
     * @param integer $roleId
     * @return object fluent interface
     */
    public function setRoleId($roleId)
    {
        $this->roleId = $roleId;
        return $this;
    }

    /**
     * Validate a role name
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateRoleName($value, array $context = [])
    {
        return $this->model->isRoleNameFree($value, $this->roleId);
    }
}