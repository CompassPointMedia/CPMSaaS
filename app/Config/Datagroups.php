<?php namespace Config;

class Datagroups {
    /**
     * Datagroups.php used in conjunction with Models/Data.php
     * to be in keeping with the Config folder, this needs to be a class
     */

    public $dataGroups = [
        'contact-requests' => [
            'root_table' => 'cpm156.contact_requests',
            'updatable' => true,
            'deletable' => true,
            'insertable' => true,
            'changelog' => false,
        ],
    ];
}
