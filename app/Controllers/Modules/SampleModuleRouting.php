<?php

namespace App\Controllers\Modules;


class SampleModuleRouting implements \CodeIgniter\Module\ModuleRoutingInterface
{

    /**
     * The following 6 properties are currently set in CI4 in Router::checkRoutes()
     * and need to be set in the client's Module Router via the ::isMyUri() method.
     * Note that with Module Routing ANY regex pattern might be grabbed by the module

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

    matchedRoutOptions : Array()

     */
    public $detectedLocale = null;

    public $controller = null;

    public $method = null;

    public $params = [];

    public $matchedRoute = null;

    public $matchedRouteOptions = null;

    /**
     * @var $locals array supported by the CMS
     */
    public $locales = ['en-us' => 'en', 'en-gr' => 'en', 'fr' => 'fr', 'es' => 'es'];



    public function usesDb(): bool
    {
        // TODO: Implement usesDb() method.
        // An example of something a Module Routing system might want to know from this class
        return true;
    }

    /**
     * @param $segment
     * @param $title
     * @return float
     */
    public function titleMatchSortOf($segment, $title)
    {
        // this value would be somewhere between 0.0 and 1.0
        // based on perhaps a levenshtein distance, or most of the words in the general order
        // or maybe we might see several matches, or a series, and pick our controller/method accordingly

        // With this, /how-to-train-dragon or /how-train-your-dragon would match > 0.  An actual
        // usable algorithm would be much more involved
        $lev = levenshtein($segment, $title);
        $changes = 7;
        $matchLevel = 1 - $lev / $changes;
        return $matchLevel > 0 ? $matchLevel : 0.0;
    }

    /**
     * @param $uri
     * @param $record
     * @return array|bool
     */
    public function titleRenderMatchUri($uri, $record)
    {
        $title = $record['title'];
        $title = strtolower($title);
        $title = str_replace(' ', '-', $title);  // Let's assume the db title has been trimmed and cleaned
                                                 // of any double/triple spaces etc.
        $title = str_replace('\'', '', $title);
        $title = str_replace('"', '', $title);   // etc.; perhaps we want to avoid regex
                                                 // or perhaps all the entries have a field for title slug
                                                 // that is available instead

        // We may also have a list of unimportant words like middle-of-title a, an, and the, etc.
        $segments = explode('/', strtolower($uri));
        $ridToken = false;
        $responseid = 0;
        $titleMatch = 0.0;
        foreach($segments as $segment)
        {

            // Handle locale slug if present; note en, en-us, and en-br will all resolve to `en`
            if(isset($this->locales[$segment]))
            {
                $this->detectedLocale = $this->locales[$segment];
            }
            else if(in_array($segment, $this->locales))
            {
                $this->detectedLocale = $segment;
            }

            // We may want to scroll directly to another user's response below
            // The Uri would be something like /how-to-train-your-dragon/responseid/1734/...
            if($ridToken && is_numeric($segment))
            {
                $responseid = $segment;
            }
            else
            {
                //bad uri
                $ridToken = false;
            }
            if(strtolower($segment) === 'responseid')
            {
                $ridToken = true;
            }


            // Handle title match
            if($segment === $title)
            {
                $titleMatch = 1;
            }
            else if($matchLevel = $this->titleMatchSortOf($segment, $title))
            {
                // perhaps we'll accept an approximate match
                $titleMatch = $matchLevel;
            }
        }

        if($titleMatch)
        {
            $this->params = [
                $record = array_merge($record, [ '_titleMatch' => $titleMatch]),
                $responseid
            ];
            return $record;
        }
        return false;
    }

    /**
     * @param string $HTTPVerb
     * @param string $uri
     * @return bool
     */
    public function isMyUri(string $HTTPVerb, string $uri): bool
    {

        /* This is example coding ONLY, not the way we'd probably handle it with an actual db table */
        // todo: Finish isMyUri() method to an actual CMS using an articles database.

        if($HTTPVerb !== 'get' && $HTTPVerb !== 'post')
        {
            // Why POST? Perhaps there is also a coupon or sign-in feature on the page
            // which responds directly to a POSt
            return false;
        }

        // Simulate a sample database table of articles; we would do a Config::connect() here
        $articles = [
            [
                'title' => 'How To Train Your Dragon',
                'create_date' => '2019-03-29 09:08:00',
                'active' => 0,
                'auth_needed' => 0,
            ], [
                'title' => 'Why I\'m Frustrated with Star Wars',
                'create_date' => '2019-09-15 13:59:17',
                'active' => 1,
                'auth_needed' => 1,
            ],
            // etc..
        ];

        foreach($articles as $article)
        {
            if($result = $this->titleRenderMatchUri($uri, $article))
            {
                // Perhaps we want to check for the date also
                // Active may be false (0) but we still want to manage the 404 and let the
                // user know they're not crazy, so we return true and our own 404 controller

                // the controller could even be this::class and be self-contained!
                $this->controller = '\App\Controllers\Modules\SampleVendorCMS';

                $this->method = 'renderArticle';

                $this->matchedRoute = [
                    '', // this Regexp used with matchedRouteOptions in ::isFiltered()
                    $this->controller . '::renderArticle' . (! empty($this->params[0]) ? '/' . $this->params[0] : ''),
                ];

                $this->matchedRouteOptions = null;  //@todo: develop this/call it back somehow

                // We're done here
                return true;
            }
        }
        return false;
    }

}