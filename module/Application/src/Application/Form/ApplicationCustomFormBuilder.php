<?php

/**
 * EXHIBIT A. Common Public Attribution License Version 1.0
 * The contents of this file are subject to the Common Public Attribution License Version 1.0 (the “License”);
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at
 * http://www.dream-cms.kg/en/license. The License is based on the Mozilla Public License Version 1.1
 * but Sections 14 and 15 have been added to cover use of software over a computer network and provide for
 * limited attribution for the Original Developer. In addition, Exhibit A has been modified to be consistent
 * with Exhibit B. Software distributed under the License is distributed on an “AS IS” basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the specific language
 * governing rights and limitations under the License. The Original Code is Dream CMS software.
 * The Initial Developer of the Original Code is Dream CMS (http://www.dream-cms.kg).
 * All portions of the code written by Dream CMS are Copyright (c) 2014. All Rights Reserved.
 * EXHIBIT B. Attribution Information
 * Attribution Copyright Notice: Copyright 2014 Dream CMS. All rights reserved.
 * Attribution Phrase (not exceeding 10 words): Powered by Dream CMS software
 * Attribution URL: http://www.dream-cms.kg/
 * Graphic Image as provided in the Covered Code.
 * Display of Attribution Information is required in Larger Works which are defined in the CPAL as a work
 * which combines Covered Code or portions thereof with code not governed by the terms of the CPAL.
 */
namespace Application\Form;
 
use Acl\Service\Acl as AclService;
use Application\Service\ApplicationSetting as SettingService;
use Application\Service\ApplicationCaptcha as CaptchaService;
use Localization\Utility\LocalizationLocale as LocaleUtility;
use Zend\Captcha\Image as CaptchaImage;
use Zend\Form\Form;
use Zend\Form\FormInterface;
use Zend\Mvc\I18n\Translator;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\Form\Exception\InvalidArgumentException;
use IntlDateFormatter;

class ApplicationCustomFormBuilder extends Form 
{
    /**
     * Slug type
     */
    const FIELD_SLUG = 'slug';

    /**
     * Slug pattern
     */
    const FIELD_SLUG_PATTERN = '0-9a-z-';

    /**
     * Text type
     */
    const FIELD_TEXT = 'text';

    /**
     * Integer type
     */
    const FIELD_INTEGER = 'integer';

    /**
     * Float type
     */
    const FIELD_FLOAT = 'float';

    /**
     * Email type
     */
    const FIELD_EMAIL = 'email';

    /**
     * Hidden type
     */
    const FIELD_HIDDEN  = 'hidden';

    /**
     * Text area type
     */
    const FIELD_TEXT_AREA = 'textarea';

    /**
     * Password type
     */
    const FIELD_PASSWORD = 'password';

    /**
     * Radio type
     */
    const FIELD_RADIO = 'radio';

    /**
     * Select type
     */
    const FIELD_SELECT = 'select';

    /**
     * Multi select type
     */
    const FIELD_MULTI_SELECT = 'multiselect';

    /**
     * Checkbox type
     */
    const FIELD_CHECKBOX = 'checkbox';

    /**
     * Multi checkbox type
     */
    const FIELD_MULTI_CHECKBOX = 'multicheckbox';

    /**
     * Url type
     */
    const FIELD_URL = 'url';

    /**
     * Submit type
     */
    const FIELD_SUBMIT = 'submit';

    /**
     * CSRF type
     */
    const FIELD_CSRF = 'csrf';

    /**
     * Image type
     */
    const FIELD_IMAGE = 'image';

    /**
     * File type
     */
    const FIELD_FILE = 'file';

    /**
     * CSRF timeout
     */
    const CSRF_TIMEOUT = 1200;

    /**
     * Captcha type
     */
    const FIELD_CAPTCHA = 'captcha';

    /**
     * Date type
     */
    const FIELD_DATE = 'date';

    /**
     * Date unix time type
     */
    const FIELD_DATE_UNIXTIME = 'date_unixtime';

    /**
     * Html area type
     */
    const FIELD_HTML_AREA = 'htmlarea';

    /**
     * Notification message
     */
    const FIELD_NOTIFICATION_MESSAGE = 'notification_message';

    /**
     * Notification title
     */
    const FIELD_NOTIFICATION_TITLE = 'notification_title';

    /**
     * Form custom elements
     *
     * @var array
     */
    protected $customElements;

    /**
     * Translator
     *
     * @var \Zend\Mvc\I18n\Translator
     */
    protected $translator;

    /**
     * Input filter
     *
     * @var \Zend\InputFilter\InputFilter
     */
    protected $inputFilter;

    /**
     * Input factory
     *
     * @var \Zend\InputFilter\Factor
     */
    protected $inputFactory;

    /**
     * List of ignored fields
     *
     * @var array
     */
    protected $ignoredElements = [];

    /**
     * List of not validate fields
     *
     * @var array
     */
    protected $notValidatedElements = [];

    /**
     * Default filters
     *
     * @var array
     */
    protected $defaultFilters = [
        [
            'name' => 'StripTags'
        ],
        [
            'name' => 'StringTrim'
        ]
    ];

    /**
     * Class constructor
     *
     * @param string $formName
     * @param array $formElements
     *      string name required
     *      string type required
     *      string label optional
     *      string description optional
     *      array description_params optional
     *      string category label optional
     *      integer max_length optional
     *      integer min_length optional
     *      boolean|integer required optional
     *      string value optional
     *      array values required for radios, multi checkboxes and selects
     *      string values_provider (PHP function that returns the list of values)
     *      array attributes optional
     *      array filters optional
     *      array validators optional
     *      array extra_options optional
     * @param \Zend\Mvc\I18n\Translator $translator
     * @param string $method
     * @param array $ignoredElements
     * @param array $notValidatedElements
     * @throws \Zend\Form\Exception\InvalidArgumentException
     */
    public function __construct($formName, array $formElements,
        Translator $translator, array $ignoredElements = [], array $notValidatedElements = [], $method = 'post') 
    {
        parent::__construct($formName);

        $useFilters = true;
        $this->setAttribute('method', ($method == 'post' ? $method : 'get'));

        // ignored elements
        $this->ignoredElements = array_merge(['csrf', 'submit'], $ignoredElements);

        // not validated elements
        $this->notValidatedElements = $notValidatedElements;

        $this->translator = $translator;
        $this->inputFilter = new InputFilter();
        $this->inputFactory = new InputFactory();

        // add elements
        foreach ($formElements as $element) {
            $elementType     = isset($element['type']) ? $element['type'] : null;
            $elementName     = isset($element['name']) ? $element['name'] : null;
            $elementRequired = !empty($element['required']) ? true : false;
            $elementValue    = isset($element['value']) ? $element['value'] : null;
            $elementValues   = isset($element['values']) ? $element['values'] : [];
            $elementAttrs    = isset($element['attributes']) && is_array($element['attributes']) ? $element['attributes'] : [];

            $elementAttrs = array_merge(['class' => 'form-control'], $elementAttrs);

            if (!empty($element['values_provider'])) {
               $valuesProvider =  eval($element['values_provider']);
                if (!is_array($valuesProvider)) {
                    throw new InvalidArgumentException('Values provider should return only an array');
                }

                $elementValues = $elementValues + $valuesProvider; 
            }

            if (!$elementType || !$elementName) {
                throw new InvalidArgumentException('Type and name are required');
            }

            // remember all elements
            $this->customElements[$elementName] = $elementType;

            // list of default element validators
            $elementValidators = [];
            $extraOptions = [];
            $applyLengthValidator = false;

            switch ($elementType) {
                case self::FIELD_NOTIFICATION_MESSAGE :
                case self::FIELD_HTML_AREA :
                    $applyLengthValidator = true;

                    // add custom filters
                    $element['filters'] = array_merge((isset($element['filters']) ? $element['filters'] : []), [
                        ['name' => 'StringTrim'],
                        [
                            'name' => 'callback',
                            'options' => [
                                'callback' => function($value) {
                                    $config = \HTMLPurifier_Config::createDefault();
                                    $config->set('Cache.DefinitionImpl', null);
                                    $config->set('HTML.SafeObject', true);
                                    $config->set('Output.FlashCompat', true);
                                    $purifier = new \HTMLPurifier($config);

                                    // clear js
                                    return AclService::checkPermission('application_use_js') ? $value : $purifier->purify($value);
                                }
                            ]
                        ]
                    ]);

                    $elementAttrs = array_merge($elementAttrs, ['class' => 'htmlarea', 'required' => false]);
                    $elementType  = 'Textarea';
                    break;

                case self::FIELD_DATE :
                case self::FIELD_DATE_UNIXTIME :
                    $elementValidators[] = [
                        'name' => 'dateTime',
                        'options' => [
                            'dateType' => IntlDateFormatter::MEDIUM //input format
                        ]
                    ];

                    $elementAttrs = array_merge($elementAttrs, ['class' => 'date form-control']);
                    $elementValue = LocaleUtility::convertToLocalizedValue($elementValue, $elementType);
                    $elementType  = 'Text';
                    break;

                case self::FIELD_SELECT :
                case self::FIELD_RADIO  :    
                    $elementValidators[] = [
                        'name' => 'inArray',
                        'options' => [
                            'haystack' => array_keys($elementValues)
                        ]
                    ];

                    // add an empty value
                    if ($elementType == self::FIELD_SELECT) {
                        $elementValues = ['' => ''] + $elementValues;
                    }

                    if ($elementType == self::FIELD_RADIO) {
                        $elementAttrs = array_merge($elementAttrs, ['class' => '']);
                    }

                    $elementType  = $elementType == self::FIELD_SELECT
                        ? 'Select'
                        : 'Radio';
                    break;

                case self::FIELD_MULTI_SELECT   :
                case self::FIELD_MULTI_CHECKBOX :
                    if ($elementType == self::FIELD_MULTI_SELECT) {
                        $elementAttrs = array_merge($elementAttrs, ['multiple' => true]);
                    }

                    $elementValidators[] = [
                        'name' => 'callback',
                        'options' => [
                            'message' => 'The input was not found in the haystack',
                            'callback' => function($values) use ($elementValues) {
                                if (!is_array($values)) {
                                    return false;
                                }

                                foreach ($values as $value) {
                                    if (!array_key_exists($value, $elementValues)) {
                                        return false;
                                    }
                                }

                                return true;
                            }
                        ]
                    ];

                    $useFilters = false;
                    if ($elementType == self::FIELD_MULTI_CHECKBOX) {
                        $extraOptions = [
                            'unchecked_value' => '',
                            'use_hidden_element' => true
                        ];

                        $elementAttrs = array_merge($elementAttrs, ['class' => '']);
                        $elementAttrs = array_merge(['required' => false], $elementAttrs);
                        $elementType  = 'MultiCheckbox';                        
                    }
                    else {
                        $elementType  = 'Select';
                    }
                    break;

                case self::FIELD_CHECKBOX :
                    $extraOptions = [
                        'checked_value' => 1,
                        'unchecked_value' => '',
                        'use_hidden_element' => true
                    ];

                    $elementValidators[] = [
                        'name' => 'inArray',
                        'options' => [
                            'haystack' => [1]
                        ]
                    ];

                    if ($elementRequired) {
                        $elementValidators[] = [
                            'name' => 'callback',
                            'options' => [
                                'message' => 'You need to select the checkbox',
                                'callback' => function($value) {
                                    return (int) $value >= 1;
                                }
                            ]
                        ];
                    }

                    $elementAttrs = array_merge($elementAttrs, ['class' => '']);
                    $elementType  = 'Checkbox';
                    break;

                case self::FIELD_HIDDEN :
                    $applyLengthValidator = true;
                    $elementType  = 'Hidden';
                    break;

                case self::FIELD_FILE :
                    $elementAttrs = array_merge($elementAttrs, ['class' => '']);
                    $elementType  = 'File';
                    $useFilters   = false;
                    break;

                case self::FIELD_IMAGE :
                    $validExtensions = [
                        'gif',
                        'png',
                        'jpeg',
                        'jpg'
                    ];

                    $elementValidators[] = [
                        'name' => 'fileExtension',
                        'options' => [
                            'message' => sprintf($this->translator->
                                    translate('Allowed images extensions: %s'), implode(',', $validExtensions)),

                            'extension' => $validExtensions
                        ]
                    ];

                    $elementAttrs = array_merge($elementAttrs, ['class' => '']);
                    $elementType  = 'File';
                    $useFilters   = false;
                    break;

                case self::FIELD_INTEGER :
                    $elementValidators[] = [
                        'name' => 'int'
                    ];

                    $elementType = 'Text';
                    break;

                case self::FIELD_FLOAT :
                    $elementValue = LocaleUtility::convertToLocalizedValue($elementValue, $elementType);
                    $elementValidators[] = [
                        'name' => 'float'
                    ];

                    $elementType  = 'Text';
                    break;

                case self::FIELD_URL :
                    $applyLengthValidator = true;
                    $elementValidators[] = [
                        'name' => 'uri',
                        'options' => [
                            'allowRelative' => false
                        ]
                    ];

                    $elementType  = 'Url';
                    break;

                case self::FIELD_EMAIL :
                    $applyLengthValidator = true;
                    $elementValidators[] = [
                        'name' => 'emailAddress'
                    ];

                    $elementType  = 'Email';
                    break;

                case self::FIELD_TEXT_AREA :
                    $applyLengthValidator = true;
                    $elementType  = 'Textarea';
                    break;

                case self::FIELD_PASSWORD :
                    $applyLengthValidator = true;
                    $elementType  = 'Password';
                    break;

                case self::FIELD_CSRF :
                    $this->addCsrf($elementName);
                    continue(2);

                case self::FIELD_SUBMIT :
                    $this->addSubmit($elementName, (!empty($element['label']) ? $element['label'] : null));
                    continue(2);

                case self::FIELD_CAPTCHA :
                    $this->addCaptcha($elementName, (!empty($element['label'])
                            ? $element['label'] : null), (!empty($element['category']) ? $element['category'] : null));
                    continue(2);

                case self::FIELD_SLUG :
                    $applyLengthValidator = true;
                    $element['filters'] = array_merge((isset($element['filters']) ? $element['filters'] : []), [
                        ['name' => 'stringToLower']
                    ]);

                    $elementValidators[] = [
                        'name' => 'regex',
                        'options' => [
                            'pattern' => '/^[' . self::FIELD_SLUG_PATTERN . ']+$/i',
                            'message' => 'You can use only latin, numeric or dashes characters'
                        ]
                    ];

                    $elementType  = 'Text';
                    break;

                case self::FIELD_TEXT :
                case self::FIELD_NOTIFICATION_TITLE :
                default :
                    $applyLengthValidator = true;
                    $elementType = 'Text';
            }

            $this->add([
                'type' => 'Zend\Form\Element\\' . $elementType,
                'name' => $elementName,
                'attributes' => array_merge([
                    'id'   => $elementName,
                    'required' => $elementRequired,
                    'value' => '' !== $elementValue ? $elementValue : null,
                ], $elementAttrs),
                'options' => array_merge($extraOptions, [
                    'category' =>  !empty($element['category']) ? $element['category'] : null,
                    'extra_options' =>  !empty($element['extra_options']) ? $element['extra_options'] : null,
                    'value_options' => $elementValues,
                    'label' => !empty($element['label'])
                        ? ($elementRequired
                                ? '*' . $this->translator->translate($element['label'])
                                : $element['label'])
                        : null,
                    'description' => !empty($element['description'])
                        ? !empty($element['description_params'])
                            ? vsprintf($this->translator->translate($element['description']), $element['description_params'])
                            : $this->translator->translate($element['description'])
                        : null
                ])
            ]);

            // define element filters
            $filters = [];

            if ($useFilters) {
                $filters = isset($element['filters']) ? $element['filters'] : $this->defaultFilters;
            }

            // add a string max length validator
            if (!empty($element['max_length']) && $applyLengthValidator) {
                $elementValidators[] = [
                    'name' => 'StringLength',
                    'options' => [
                        'max' => (int) $element['max_length']
                    ]
                ];
            }

            // add a string min length validator
            if (!empty($element['min_length']) && $applyLengthValidator) {
                $elementValidators[] = [
                    'name' => 'StringLength',
                    'options' => [
                        'min' => (int) $element['min_length']
                    ]
                ];
            }

            // add validators
            $this->inputFilter->add($this->inputFactory->createInput([
                'name' => $elementName,
                'required' => $elementRequired,
                'filters' => $filters,
                'validators' => !empty($element['validators'])
                    ? array_merge($elementValidators, $element['validators'])
                    : $elementValidators                
            ]));
        }

        $this->setInputFilter($this->inputFilter);
    }

    /**
     * Retrieve the validated data
     *
     * By default, retrieves normalized values; pass one of the
     * FormInterface::VALUES_* constants to shape the behavior.
     *
     * @param boolean $localizeData
     * @param  int $flag
     * @throws \Zend\Form\Exception\DomainException
     * @return array|object
     */
    public function getData($localizeData = true, $flag = FormInterface::VALUES_NORMALIZED)
    {
        $formData = parent::getData($flag);

        // process form data
        $processedData = [];
        foreach ($formData as $fieldName => $fieldValue) {
            // skip all ignored elements
            if (in_array($fieldName, $this->ignoredElements)) {
                continue;
            }

            // convert from localized data
            $processedData[$fieldName] = $localizeData
                ? LocaleUtility::convertFromLocalizedValue($fieldValue, $this->customElements[$fieldName])
                : $fieldValue;
        }

        return $processedData;
    }

    /**
     * Set data to validate and/or populate elements
     *
     * Typically, also passes data on to the composed input filter.
     *
     * @param  array|\ArrayAccess|Traversable $data
     * @param boolean $convertValues
     * @return Form|FormInterface
     * @throws \Zend\Form\Exception\InvalidArgumentException
     */
    public function setData($data, $convertValues = true)
    {
        // unset not validated fields
        foreach($this->notValidatedElements as $name) {
            if (isset($data[$name])) {
                unset($data[$name]);
            }
        }

        // convert localized values
        if ($convertValues) {
            foreach ($data as $fieldName => $fieldValue) {
                if (!isset($this->customElements[$fieldName])) {
                    continue;
                }

                $data[$fieldName] = LocaleUtility::convertToLocalizedValue($fieldValue, $this->customElements[$fieldName]);
            }
        }

        return parent::setData($data);
    }

    /**
     * Add CSRF
     *
     * @param string $name
     * @return void
     */
    protected function addCsrf($name)
    {
        $this->add([
            'name' => $name, 
            'type' => self::FIELD_CSRF,
            'options' => [
                'csrf_options' => [
                    'timeout' => self::CSRF_TIMEOUT
                ]
            ]
        ]);
    }

    /**
     * Add captcha
     *
     * @param string $name
     * @param string $label
     * @param string $category
     * @return void
     */
    protected function addCaptcha($name, $label = null, $category = null)
    {
        // pass captcha image options
        $captchaImage = new CaptchaImage([
            'font' => CaptchaService::getCaptchaFontPath(),
            'width' => SettingService::getSetting('application_captcha_width'),
            'height' => SettingService::getSetting('application_captcha_height'),
            'dotNoiseLevel' => SettingService::getSetting('application_captcha_dot_noise'),
            'lineNoiseLevel' => SettingService::getSetting('application_captcha_line_noise')
        ]);

        $captchaImage->setImgDir(CaptchaService::getCaptchaPath());
        $captchaImage->setImgUrl(CaptchaService::getCaptchaUrl());

        $this->add([
            'type' => self::FIELD_CAPTCHA,
            'name' => $name,
            'options' => [
                'label' => '*' . $this->translator->translate(($label ? $label : 'Please verify you are human')),
                'captcha' => $captchaImage,
                'category' =>  $category ? $category : null,
            ],
            'attributes' => [
                'id' => 'captcha',
                'class' => 'form-control',
                'required' => 'required'
            ]
        ]);
    }

    /**
     * Add submit button
     *
     * @param string $name
     * @param string $label
     * @return void
     */
    protected function addSubmit($name, $label = null)
    {
        $this->add([
            'type' => self::FIELD_SUBMIT,
            'name' => $name,
            'attributes' => [
                'id' => $name,
                'value' => ($label ? $label : 'Submit'),
                'class' => 'btn btn-default btn-submit'
            ],
            'options' => [
                'label' => ' ',
            ]
        ]);
    }

    /**
     * Get an element type
     *
     * @param string $elementName
     * @return string
     */
    public function getElementType($elementName)
    {
        return isset($this->customElements[$elementName])
            ? $this->customElements[$elementName]
            : null;
    }
}