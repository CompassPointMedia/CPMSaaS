<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the frameworks
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @link: https://codeigniter4.github.io/CodeIgniter4/
 */

use Config\Services;
use Config\View;

if (! function_exists('view'))
{
    /**
     * See the system Common::view() function for comparison
     * I will probably always be curious if I could have used the "third-party extensions" option, but this works
     * Allows for `$dbMaster` and `$dbAccounts` connections to be accessible in the view.
     * They (at least dbMaster) must be present first; $dbAccounts will be empty unless it's been added to.
     *
     * @param string $name
     * @param array  $data
     * @param array  $options Unused - reserved for third-party extensions.
     *
     * @return string
     */
    function view(string $name, array $data = [], array $options = []): string
    {
        /**
         * @var CodeIgniter\View\View $renderer
         */
        $renderer = Services::renderer();

        $saveData = config(View::class)->saveData;

        if (array_key_exists('saveData', $options))
        {
            $saveData = (bool) $options['saveData'];
            unset($options['saveData']);
        }

        // ----------------------------------------------------------------------------
        // Add the Connection Store assets as they are when the view
        if (\App\Libraries\ConnectionStore::$dbMaster instanceof \CodeIgniter\Database\BaseConnection) {
            $renderer->setVar('dbMaster', \App\Libraries\ConnectionStore::$dbMaster);
            $renderer->setVar('dbAccounts', \App\Libraries\ConnectionStore::$dbAccounts);
        }
        // ----------------------------------------------------------------------------

        return $renderer->setData($data, 'raw')
            ->render($name, $options, $saveData);
    }
}
