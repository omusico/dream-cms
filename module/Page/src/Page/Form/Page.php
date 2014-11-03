<?php
namespace Page\Form;

use Application\Service\ApplicationServiceLocator as ServiceLocatorService;
use Application\Form\ApplicationAbstractCustomForm;
use Application\Form\ApplicationCustomFormBuilder;
use Acl\Service\Acl as AclService;
use Page\Model\PageBase as PageBaseModel;

class Page extends ApplicationAbstractCustomForm 
{
    /**
     * Title max string length
     */
    const TITLE_MAX_LENGTH = 50;

    /**
     * Slug max string length
     */
    const SLUG_MAX_LENGTH = 100;

    /**
     * Meta keywords max string length
     */
    const META_KEYWORDS_MAX_LENGTH = 150;

    /**
     * Meta description max string length
     */
    const META_DESCRIPTION_MAX_LENGTH = 150;

    /**
     * Redirect url max string length
     */
    const REDIRECT_URL_MAX_LENGTH = 255;

    /**
     * Form name
     * @var string
     */
    protected $formName = 'page';

    /**
     * Is system page
     * @var boolean
     */
    protected $isSystemPage = false;

    /**
     * Show main menu
     * @var boolean
     */
    protected $showMainMenu = true;

    /**
     * Show site map
     * @var boolean
     */
    protected $showSiteMap = true;

    /**
     * Show footer menu
     * @var boolean
     */
    protected $showFooterMenu = true;

    /**
     * Show user menu
     * @var boolean
     */
    protected $showUserMenu = true;

    /**
     * Show visibility settings
     * @var boolean
     */
    protected $showVisibilitySettings = true;

    /**
     * Show seo
     * @var boolean
     */
    protected $showSeo = true;

    /**
     * Model instance
     * @var object  
     */
    protected $model;

    /**
     * Page info
     * @var array
     */
    protected $pageInfo = [];

    /**
     * Page parent
     * @var array
     */
    protected $pageParent = [];

    /**
     * Form elements
     * @var array
     */
    protected $formElements = [
        'custom_validate' => [
            'name' => 'custom_validate',
            'type' => ApplicationCustomFormBuilder::FIELD_HIDDEN,
            'value' => 1,
            'required' => true,
            'category' => 'General info'
        ],
        'title' => [
            'name' => 'title',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Title',
            'required' => true,
            'max_length' => self::TITLE_MAX_LENGTH,
            'category' => 'General info'
        ],
        'slug' => [
            'name' => 'slug',
            'type' => ApplicationCustomFormBuilder::FIELD_SLUG,
            'label' => 'Display name',
            'required' => false,
            'max_length' => self::SLUG_MAX_LENGTH,
            'category' => 'General info',
            'description' => 'The display name will be displayed in the browser bar'
        ],
        'active' => [
            'name' => 'active',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Page is active',
            'required' => false,
            'category' => 'General info'
        ],
        'layout' => [
            'name' => 'layout',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page layout',
            'required' => true,
            'values' => [],
            'category' => 'General info',
            'description' => 'Page layout description'
        ],
        'menu' => [
            'name' => 'menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the main menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'site_map' => [
            'name' => 'site_map',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the site map',
            'required' => false,
            'category' => 'Navigation'
        ],
        'footer_menu' => [
            'name' => 'footer_menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the footer menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'footer_menu_order' => [
            'name' => 'footer_menu_order',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Order in the footer menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'user_menu' => [
            'name' => 'user_menu',
            'type' => ApplicationCustomFormBuilder::FIELD_CHECKBOX,
            'label' => 'Show in the user menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'user_menu_order' => [
            'name' => 'user_menu_order',
            'type' => ApplicationCustomFormBuilder::FIELD_INTEGER,
            'label' => 'Order in the user menu',
            'required' => false,
            'category' => 'Navigation'
        ],
        'redirect_url' => [
            'name' => 'redirect_url',
            'type' => ApplicationCustomFormBuilder::FIELD_URL,
            'label' => 'Redirect url',
            'required' => false,
            'max_length' => self::REDIRECT_URL_MAX_LENGTH,
            'category' => 'Navigation'
        ],
        'meta_keywords' => [
            'name' => 'meta_keywords',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT,
            'label' => 'Meta keywords',
            'required' => false,
            'max_length' => self::META_KEYWORDS_MAX_LENGTH,
            'category' => 'SEO',
            'description' => 'Meta keywords should be separated by comma',
        ],
        'meta_description' => [
            'name' => 'meta_description',
            'type' => ApplicationCustomFormBuilder::FIELD_TEXT_AREA,
            'label' => 'Meta description',
            'required' => false,
            'max_length' => self::META_DESCRIPTION_MAX_LENGTH,
            'category' => 'SEO'
        ],
        'visibility_settings' => [
            'name' => 'visibility_settings',
            'type' => ApplicationCustomFormBuilder::FIELD_MULTI_CHECKBOX,
            'label' => 'Page is hidden for',
            'required' => false,
            'values' => [],
            'category' => 'Visibility settings'
        ],
        'page_direction' => [
            'name' => 'page_direction',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Direction',
            'required' => true,
            'value' => 'after',
            'values' => [
                'before' => 'Before',
                'after' => 'After'
            ],
            'category' => 'Page position'
        ],
        'page' => [
            'name' => 'page',
            'type' => ApplicationCustomFormBuilder::FIELD_SELECT,
            'label' => 'Page',
            'required' => true,
            'values' => [],
            'category' => 'Page position'
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
            // remove some fields
            if ($this->isSystemPage) {
                unset($this->formElements['title']);
                unset($this->formElements['slug']);
            }

            if (!$this->showMainMenu) {
                unset($this->formElements['menu']);
            }

            if (!$this->showSiteMap) {
                unset($this->formElements['site_map']);
            }

            if (!$this->showFooterMenu) {
                unset($this->formElements['footer_menu']);
                unset($this->formElements['footer_menu_order']);
            }

            if (!$this->showUserMenu) {
                unset($this->formElements['user_menu']);
                unset($this->formElements['user_menu_order']);
            }

            if (!$this->showVisibilitySettings) {
                unset($this->formElements['visibility_settings']);
            }

            if (!$this->showSeo) {
                unset($this->formElements['meta_keywords']);
                unset($this->formElements['meta_description']);
            }

            if (!$this->isSystemPage) {
                // add extra validators
                $this->formElements['slug']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validateSlug'],
                            'message' => 'Display name already used'
                        ]
                    ]
                ];
            }

            if ($this->pageInfo) {
                // add extra validators
                $this->formElements['custom_validate']['validators'] = [
                    [
                        'name' => 'callback',
                        'options' => [
                            'callback' => [$this, 'validatePage'],
                            'message' => 'You cannot move the page into self or into its child pages'
                        ]
                    ]
                ];
            }

            // fill the form with default values
            $this->formElements['layout']['values'] = $this->model->getPageLayouts();

            if ($this->showVisibilitySettings) {
                $this->formElements['visibility_settings']['values'] = AclService::getAclRoles(false, true);
            }

            if (null != ($pages = $this->getPages())) {
                $this->formElements['page']['values'] = $pages;
            }
            else {
                unset($this->formElements['page']);
                unset($this->formElements['page_direction']);
            }

            $this->form = new ApplicationCustomFormBuilder($this->formName,
                    $this->formElements, $this->translator, $this->ignoredElements, $this->notValidatedElements, $this->method);    
        }

        return $this->form;
    }
  
    /**
     * Get pages
     *
     * @return array
     */
    protected function getPages()
    {
       $pages = [];

       if ($this->pageParent) {
            if (false !== ($childrenPages =
                    $this->model->getAllPageStructureChildren($this->pageParent['id']))) {

                $activePageId = null;
                $currentDefined = false;

                foreach ($childrenPages as $children) {
                    // don't draw current page
                    if (!empty($this->pageInfo) && $this->pageInfo['id'] == $children['id']) {
                        $currentDefined = true;
                        continue;
                    }

                    $pageOptions = [
                        'title' => $children['title'],
                        'system_title' => $children['system_title'],
                        'type' => $children['type']
                    ];

                    if (!$currentDefined) {
                        $activePageId = $children['id'];
                    }
                    else if ($currentDefined && !$activePageId) {
                        $activePageId = $children['id'];
                        $this->formElements['page_direction']['value'] = 'before';
                    }

                    $pages[$children['id']] =  ServiceLocatorService::
                            getServiceLocator()->get('viewHelperManager')->get('pageTitle')->__invoke($pageOptions);
                }

                // set dfault value
                if ($activePageId) {
                    $this->formElements['page']['value'] = $activePageId;
                }
            }
       }

       return $pages;
    }

    /**
     * Set a model
     *
     * @param object $model
     * @return object fluent interface
     */
    public function setModel(PageBaseModel $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set system page
     *
     * @param boolean $system
     * @return object fluent interface
     */
    public function setSystemPage($system)
    {
        $this->isSystemPage = $system;
        return $this;
    }

    /**
     * Show main menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showMainMenu($show)
    {
        $this->showMainMenu = $show;
        return $this;
    }

    /**
     * Show site map
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showSiteMap($show)
    {
        $this->showSiteMap = $show;
        return $this;
    }

    /**
     * Show footer menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showFooterMenu($show)
    {
        $this->showFooterMenu = $show;
        return $this;
    }

    /**
     * Show user menu
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showUserMenu($show)
    {
        $this->showUserMenu = $show;
        return $this;
    }

    /**
     * Show visibility settings
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showVisibilitySettings($show)
    {
        $this->showVisibilitySettings = $show;
        return $this;
    }

    /**
     * Show SEO
     *
     * @param boolean $show
     * @return object fluent interface
     */
    public function showSeo($show)
    {
        $this->showSeo = $show;
        return $this;
    }

    /**
     * Set page info
     *
     * @param array $pageInfo
     * @return object fluent interface
     */
    public function setPageInfo(array $pageInfo)
    {
        $this->pageInfo = $pageInfo;
        return $this;
    }

    /**
     * Set page parent
     *
     * @param array $pageParent
     * @return object fluent interface
     */
    public function setPageParent(array $pageParent)
    {
        $this->pageParent = $pageParent;
        return $this;
    }

    /**
     * Validate page
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validatePage($value, array $context = [])
    {
        if (!$this->pageInfo || !$this->pageParent) {
            return true;
        }

        return $this->model->isPageMovable($this->pageInfo['left_key'],
                $this->pageInfo['right_key'], $this->pageInfo['level'], $this->pageParent['left_key']);
    }

    /**
     * Validate slug
     *
     * @param $value
     * @param array $context
     * @return boolean
     */
    public function validateSlug($value, array $context = [])
    {
        return $this->model->isSlugFree($value, $this->pageInfo['id']);
    }
}