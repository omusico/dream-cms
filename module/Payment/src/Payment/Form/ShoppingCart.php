<?php

namespace Payment\Form;

use Application\Form\CustomFormBuilder;
use Application\Form\AbstractCustomForm;
use Application\Service\Service as ApplicationService;
use Zend\Form\Exception\InvalidArgumentException;

class ShoppingCart extends AbstractCustomForm 
{
    /**
     * Count string length
     */
    const COUNT_MAX_LENGTH = 4;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'shopping-cart';

    /**
     * Object Id
     * @var integer
     */
    protected $objectId;

    /**
     * Module name
     * @var string
     */
    protected $moduleName;

    /**
     * Hide count field
     * @var boolean
     */
    protected $hideCountField;

    /**
     * Discount
     * @var integer
     */
    protected $discount;

    /**
     * Tariffs
     * @var array
     */
    protected $tariffs;

    /**
     * Count limit
     * @var integer
     */
    protected $countLimit;

    /**
     * Form elements
     * @var array
     */
    protected $formElements = array(
        'count' => array(
            'name' => 'count',
            'type' => CustomFormBuilder::FIELD_INTEGER,
            'label' => 'Item count',
            'required' => true,
            'description' => '',
            'description_params' => array(),
            'max_length' => self::COUNT_MAX_LENGTH
        ),
        'cost' => array(
            'name' => 'cost',
            'type' => CustomFormBuilder::FIELD_SELECT,
            'label' => 'Choose the tariff',
            'required' => true,
        ),
        'discount' => array(
            'name' => 'discount',
            'type' => CustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Use discount',
            'description' => 'Item discount info'
        ),
        'object_id' => array (
            'name' => 'object_id',
            'type' => CustomFormBuilder::FIELD_HIDDEN,
            'required' => true
        ),
        'module' => array (
            'name' => 'module',
            'type' => CustomFormBuilder::FIELD_HIDDEN,
            'required' => true
        ),
        'validate' => array (
            'name' => 'validate',
            'type' => CustomFormBuilder::FIELD_HIDDEN,
            'value' => 1
        )
    );

    /**
     * Extra form elements
     * @var array
     */
    protected $extraFormElements = array();

    /**
     * Get extra options
     *
     * @param array $formData
     * @param boolean $skipEmptyValues
     * @return array
     */
    public function getExtraOptions(array $formData, $skipEmptyValues = true)
    {
        $extraData = array();
        foreach ($formData as $name => $value) {
            if (array_key_exists($name, $this->formElements) || ($skipEmptyValues && !$value)) {
                continue;
            }

            $extraData[$name] = $value;
        }

        return $extraData;
    }

    /**
     * Get form instance
     *
     * @return object
     */
    public function getForm()
    {
        // get the form builder
        if (!$this->form) {
            // add extra settings for "cost" field
            if ($this->tariffs) {
                $this->formElements['cost']['values'] = $this->tariffs;
            }
            else {
                unset($this->formElements['cost']);
            }

            // add extra validators for "count" field
            if (!$this->hideCountField) {
                if ($this->countLimit) {
                    $this->formElements['count']['description'] = 'Max items count description';
                    $this->formElements['count']['description_params'] = array(
                        $this->countLimit
                    );
                }

                $this->formElements['count']['validators'] = array(
                    array (
                        'name' => 'callback',
                        'options' => array(
                            'callback' => array($this, 'validateItemCount'),
                            'message' => 'Value should be greater than 0'
                        )
                    ),
                    array(
                        'name' => 'callback',
                        'options' => array(
                            'callback' => array($this, 'validateItemMaxCount'),
                            'message' => sprintf($this->translator->translate('Item count must be less or equal %d'), $this->countLimit)
                        )
                    )
                );

            }
            else {
                unset($this->formElements['count']);
            }

            // add extra settings for "discount" field
            if ($this->discount) {
                $this->formElements['discount']['description_params'] = array(
                    ApplicationService::getServiceManager()->
                            get('viewHelperManager')->get('processCost')->__invoke($this->discount)
                );
            }
            else {
                unset($this->formElements['discount']);
            }

            $formElements = $this->formElements;

            if ($this->extraFormElements) {
                $formElements = array_merge($formElements, $this->extraFormElements);
            }

            $this->form = new CustomFormBuilder($this->formName, $formElements,
                    $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }

    /**
     * Set count limit
     *
     * @param integer $limit
     * @return object fluent interface
     */
    public function setCountLimit($limit)
    {
        $this->countLimit = $limit;
        return $this;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     * @return object fluent interface
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;
        return $this;
    }

    /**
     * Set tariffs
     *
     * @param array $tariffs
     * @return object fluent interface
     */
    public function setTariffs(array $tariffs)
    {
        if (null == ($this->tariffs = $tariffs)) {
            throw new InvalidArgumentException('Tariffs list must not be empty');    
        }

        return $this;
    }

    /**
     * Set extra options
     *
     * @param array $extraOptions
     * @return object fluent interface
     */
    public function setExtraOptions(array $extraOptions)
    {
        $this->extraFormElements = $extraOptions;
        return $this;
    }

    /**
     * Hide count field
     *
     * @param boolean $hide
     * @return object fluent interface
     */
    public function hideCountField($hide)
    {
        $this->hideCountField = $hide;
        return $this;
    }

    /**
     * Validate the item's count
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateItemCount($value, array $context = array())
    {
        return (int) $value > 0;
    }

    /**
     * Validate the item's max count
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateItemMaxCount($value, array $context = array())
    {
        return (int) $value <= $this->countLimit || !$this->countLimit;
    }
}