<?php

namespace Application\Test\Controller;

use Application\Test\ApplicationBootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Application\Controller\IndexController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit_Framework_TestCase;

class IndexControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Index controller
     * @var object
     */
    protected $controller;

    /**
     * Request
     * @var object
     */
    protected $request;

    /**
     * Response
     * @var object
     */
    protected $response;

    /**
     * Route match
     * @var object
     */
    protected $routeMatch;

    /**
     * Service manager
     * @var object
     */
    protected $serviceManager;

    /**
     * Event
     * @var object
     */
    protected $event;

    protected function setUp()
    {
        $this->serviceManager = ApplicationBootstrap::getServiceManager();
        $config = $this->serviceManager->get('Config');

        $this->controller = new IndexController();
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'index'));
        $this->event      = new MvcEvent();
        
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($this->serviceManager);
    }

    /**
     * Test index action
     */
    public function testIndexActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'index');

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test not exists action
     */
    public function testNotExistsAction()
    {
        $this->routeMatch->setParam('action', 'index-not-exsist');

        $result   = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * Test localization
     */ 
    public function testLocalization()
    {
        $this->serviceManager->get('EventManager')
            ->trigger('onBootstrap');
        
        // get all localizations
       /* $localization = $this->serviceManager
            ->get('Application\Model\Builder')
            ->getInstance('Application\Model\Localization');

        $localizations = $localization->getAllLocalizations();
echo 'here';
$translator = $this->serviceManager->get('translator');
echo $translator->getLocale();
        foreach($localizations as $language) {
            
        }*/
    }
}