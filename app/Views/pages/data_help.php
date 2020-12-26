<?php
/**
 * data/create - create a new data object, configure fields
 */

?>

<?php $this->extend('layouts/generic');?>

<?php $this->section('content');?>

This provides help with data objects.  It will have the following setup:
<ul>
    <li>connection will be the master connection</li>
    <ll>The help information will at least be Juliet-ish like and will have some useful formatting</ll>
    <li>it will cover data objects, the concept of global/definer/user settings, the whole array_merge_onto concept;
        also status of CVT and using progressive enhancement with Vue;
        also getting rid of bootstrap entirely;
        also technical challenges of having multiple data objects on a single page
        also templating engine - we really need to define the data architecture (and the $dataGroups) legacy should have some valuable input on what's needed and important since they were developed for real-world applications
    </li>
    <li>
        * ALSO important: I need to separate this information from the generic "this is a help page" in the application, and what table it refers to.  It should also not choke if there is actually no table asset present; and this should be a templating engine conditional such as {if_table_exists:tablename} and {if_relationship_exists:relationname} etc.
    </li>
</ul>
<?php

/*
 * We want to select all tables the user has access to based on admin or not for now, as well as a
 * manage tables and create tables link, and also a help link which will key off the universal
 * help information in the master database
 */

?>
<?php $this->endSection('content');?>
