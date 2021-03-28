<?php

namespace Config;

use CodeIgniter\Modules\Modules as BaseModules;

class Modules extends BaseModules
{
	/**
	 * --------------------------------------------------------------------------
	 * Enable Auto-Discovery?
	 * --------------------------------------------------------------------------
	 *
	 * If true, then auto-discovery will happen across all elements listed in
	 * $aliases below. If false, no auto-discovery will happen at all,
	 * giving a slight performance boost.
	 *
	 * @var boolean
	 */
	public $enabled = true;

	/**
	 * --------------------------------------------------------------------------
	 * Enable Auto-Discovery Within Composer Packages?
	 * --------------------------------------------------------------------------
	 *
	 * If true, then auto-discovery will happen across all namespaces loaded
	 * by Composer, as well as the namespaces configured locally.
	 *
	 * @var boolean
	 */
	public $discoverInComposer = true;

	/**
	 * --------------------------------------------------------------------------
	 * Auto-Discovery Rules
	 * --------------------------------------------------------------------------
	 *
	 * Aliases list of all discovery classes that will be active and used during
	 * the current application request.
	 *
	 * If it is not listed, only the base application elements will be used.
	 *
	 * @var string[]
	 */
	public function shouldDiscover(string $alias) : bool
	{
		if (! $this->enabled)
		{
			return false;
		}

		$alias = strtolower($alias);

		return in_array($alias, $this->activeExplorers ?? []);
	}

    /*
    |--------------------------------------------------------------------------
    | Modules with Routing Authority (Module Routing Engine)
    |--------------------------------------------------------------------------
    |
    | This system, instead of taking routes and naming controller/method,
    | allows the modules listed below to inspect and "claim" a URI as its own.
    | For example, a site may have a standard page controller as well as an
    | extensive CMS/article plugin.  Pages might include /about-us, /contact-us,
    | etc. while articles might include /how-to-train-your-dragon/2019/08 or
    | /why-i-am-frustrated-with-star-wars.  The site developer may NOT want to
    | preface the articles with /articles/ and so Module Routing allows the module
    | that produced the URI (the articles) to claim it.
    | Now it's no longer a matter of regex routes but simply the module claiming
    | the $uri as its own.
    |
    | Obviously you can't have an article title and a page resolve to the same
    | value, but a little preventative coding on the creation end would avoid
    | the prefix `/articles` being needed - why have part of a url you don't
    | really need?
    */
    /**
     * Whether or not to utilize the Module Routing feature, false turns it off.
     *
     * @var bool $useModuleRouting
     */
    public $useModuleRouting = true;

    public $routingModules = [
        // modules get to review URIs in the order they are declared here
        [
            'active' => true,
            'name' => 'SaaS Portal Controller',
            'class' => \App\Controllers\Modules\SaaSController::class,

        ],[
            'active' => false,
            'name' => 'Sample Module Routing temp-stored in Controllers/Modules',
            'class' => \App\Controllers\Modules\SamplePrimaryModuleRouting::class,

        ],[
            'active' => false,
            'name' => 'CMS/Article Module, handles articles stored in database',
            'class' => \App\Controllers\Modules\SampleVendorCMS::class,
        ]
    ];

    /**
     * --------------------------------------------------------------------------
     * Auto-Discovery Rules
     * --------------------------------------------------------------------------
     *
     * Aliases list of all discovery classes that will be active and used during
     * the current application request.
     *
     * If it is not listed, only the base application elements will be used.
     *
     * @var string[]
     */
    public $aliases = [
		'events',
		'filters',
		'registrars',
		'routes',
		'services',
	];
}
