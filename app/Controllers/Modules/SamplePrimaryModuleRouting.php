<?php

namespace App\Controllers\Modules;


class SamplePrimaryModuleRouting extends \App\Controllers\BaseController implements \CodeIgniter\Module\ModuleRoutingInterface
{

    /**
     * The following 6 properties are currently set in CI4 in Router::checkRoutes()
     * and need to be set in the client's Module Router via the ::isMyUri() method.
     * Note that with Module Routing ANY regex pattern might be grabbed by the module.

    detectedLocale : empty (or set if detected)

    controller : \App\Controllers\Modules\SampleVendorCMS

    method : renderArticle

    params : Array(
        [0] => (array) article from db
        [1] => (int) responseid (say there is a user response we want to highlight)
    )

    matchedRoute: Array(
        [0] => ([0-9]+)   (again, this could be anything with Module Routing)
        [1] => \App\Controllers\Modules\SampleVendorCMS::renderArticle
    )

    matchedRouteOptions : Array()


    The variable $controllerConstructor allows constructor arguments from one or more
    modules to be sent to constructor of the selected controller when instantiated.
     */
    public $detectedLocale = null;

    public $controller = null;

    public $method = null;

    public $params = [];

    public $matchedRoute = null;

    public $matchedRouteOptions = null;

    public $controllerConstructor = null;

    /**
     * @param string $HTTPVerb
     * @param string $uri
     * @param array $module
     * @return bool
     */
    public function isMyUri(string $HTTPVerb, string $uri, array $module = []): bool
    {

        /* This is example coding ONLY, not the way we'd probably handle it with an actual db table */
        // todo: Finish isMyUri() method to an actual CMS using an articles database.

        if($HTTPVerb !== 'get' && $HTTPVerb !== 'post')
        {
            // Why POST? Perhaps there is also a coupon or sign-in feature on the page
            // which responds directly to a POSt
            return false;
        }

        // -- Let's simulate the juliet $thispage for now ---
        if ($uri === 'about-us')
        {
            $this->controller = '\App\Controllers\Juliet';
            $this->method = 'renderPage';
            $this->params = [
                /* // 'thisnode' => */      53,
                // not to be used /* // 'thisfolder' => */    '',
                // not to be used /* // 'aux1' => */          'Romeo',
                // not to be used /* // 'aux2' => */          'Juliet',
            ];
            $this->matchedRoute = [
                '',
                $this->controller . '::renderPage' . (! empty($this->params[0]) ? '/' . $this->params[0] : ''),
            ];

            $this->matchedRouteOptions = null;

            // Just pass the module that accepted this uri for now
            $module['additional'] = 'Comments about the matching method, helper classes, services, etc.';
            $this->controllerConstructor = $module;
            return true;
        }

        return false;
    }

}