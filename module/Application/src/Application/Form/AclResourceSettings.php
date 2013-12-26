<?php

namespace Application\Form;

use Application\Model\Acl as AclModel;

class AclResourceSettings extends AbstractCustomForm 
{
    /**
     * Form name
     * @var string
     */
    protected $formName = 'acl-resource-settings';

    /**
     * Actions limit
     * @var integer
     */
    protected $actionsLimit;

    /**
     * Actions reset
     * @var integer
     */
    protected $actionsReset;

    /**
     * Date start
     * @var integer
     */
    protected $dateStart;

    /**
     * Date end
     * @var integer
     */
    protected $dateEnd;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'actions_limit' => array(
            'name' => 'actions_limit',
            'type' => 'integer',
            'label' => 'Number of allowed actions',
            'required' => false
        ),
        'actions_reset' => array(
            'name' => 'actions_reset',
            'type' => 'integer',
            'label' => 'Number of actions is reset every N seconds',
            'required' => false
        ),
        'date_start' => array(
            'name' => 'date_start',
            'type' => 'date_unixtime',
            'label' => 'This action is available since',
            'required' => false
        ),
        'date_end' => array(
            'name' => 'date_end',
            'type' => 'date_unixtime',
            'label' => 'This action is available until',
            'required' => false
        ),
        'csrf' => array(
            'name' => 'csrf',
            'type' => 'csrf'
        ),
        'submit' => array(
            'name' => 'submit',
            'type' => 'submit',
            'label' => 'Submit',
        )
    );

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get form builder
        if (!$this->form) {
            // file the form with default values
            $this->formElements['actions_limit']['value'] = $this->actionsLimit;
            $this->formElements['actions_reset']['value'] = $this->actionsReset;
            $this->formElements['date_start']['value'] = $this->dateStart;
            $this->formElements['date_end']['value'] = $this->dateEnd;

            $this->form = new CustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set actions limit
     *
     * @param integer $actionsLimit
     * @return object fluent interface
     */
    public function setActionsLimit($actionsLimit)
    {
        if ((int) $actionsLimit) {
            $this->actionsLimit = $actionsLimit;
        }

        return $this;
    }

    /**
     * Set actions reset
     *
     * @param integer $actionsReset
     * @return object fluent interface
     */
    public function setActionsReset($actionsReset)
    {
        if ((int) $actionsReset) {
            $this->actionsReset = $actionsReset;
        }

        return $this;
    }

    /**
     * Set date start
     *
     * @param integer $dateStart
     * @return object fluent interface
     */
    public function setDateStart($dateStart)
    {
        if ((int) $dateStart) {
            $this->dateStart = $dateStart;
        }

        return $this;
    }

    /**
     * Set date end
     *
     * @param integer $dateEnd
     * @return object fluent interface
     */
    public function setDateEnd($dateEnd)
    {
        if ((int) $dateEnd) {
            $this->dateEnd = $dateEnd;
        }

        return $this;
    }
}