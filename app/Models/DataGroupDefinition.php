<?php
namespace App\Models;

/**
 * Class Data Group Definition
 * @author Samuel Fullman
 *
 * @todo: I want a generalized toolage extension for manipulating SAAS-type array and XML objects
 *
 * See Jira CPMSAAS-12 for documentation as well as diagrams from 2021-01-02; this works toward an Application Definition Language (ADL) for
 * User Defined Applications (UDA) in SAAS.  This class represents a dataGroup application and its ancestors.  A rootDataObject is optional.
 *
 */


class DataGroupDefinition extends \App\Libraries\Tooling
{

    public $dataGroup = null;

    public $inheritance = null;

    public $rootDataObject = null;

    /**
     * Data constructor.
     */
    public function __construct() {}

}
