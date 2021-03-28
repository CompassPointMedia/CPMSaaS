<?php namespace Config;

class Datagroups {
    /**
     * Datagroups.php used in conjunction with App\Controllers\Modules\Saas\Data.php
     * to be in keeping with the Config folder, this needs to be a class
     */




    // NOTE: these are sample datagroups representing declarations that the Data class consumes

    /**
     * @var array $dataGroups   configuration for all recognized datasets (tables, joins, grouped data, totals/subtotals etc.)
     *
     * by being here, a data group is considered "selectable";
     * insertable, updatable and deletable are always assumed false unless specified as true
     *
     * forego_limit: this means 'do not use a limit clause UNLESS [limitStart and] limitRange is/are passed'
     */
    /**
     * @todo: move this into the readme.md in CodeIgniter root folder
     *
     * 2020-12-19
     *      update to no longer use _SESSION['UserName'] but the injected session management class
     *
     *
     * 2018-06-17 Sam Fullman <sfullman@presidio.com> How I'd like to see this implemented: "Once you declare `specifically_limited_fields` for a profile, they must be specifically listed or won't be in the input/output.  You can declare `restricted_fields` to just restrict that field.  Neither of these apply to primary key"
     *
     * error checking
     * first of all, it's important to note that we have the user's input and the interpretable/interpreted input.  So the user might enter 6/30/2018 but the actual value is 2018-06-30 in MySQL.
     * we have implicit and explicit error checking.  We can know via structure a lot about what inputs are allowed, for example:
     *      alpha cannot go in int, float, etc.
     *      we know when the input is too long
     *      we know from enum or set if a value or values is not valid
     * given <this primary key> and a unique field, we can know if a field entry or update would be valid
     *      [this should have a live keypress and green check mark UI]
     * we do not know if a field is required or not, or if it must be a valid email (and we may also want Samuel Fullman <sfullman@presidio.com> which is much more complex than the standard email regexp string)
     *
     *
     * security
     * Before dataGroups the only implemented security was on the front end; calls to AO could be made by anyone, of any group, with the right parameters.  There was/is a field master.switch.LockDownEdit which was a comma-separated list of which groups could edit for that UI, but this only disabled buttons on the FE; there was no way to coordinate this with the backend.  Also, "edit" in this context meant anything, including CUD-ing a record, or making an AO call that did not touch the record.
     *
     * The security node in dataGroups allows me to say "for these modes, users assigned to these groups may perform these action(s)".  The equivalent from the previous system would be:
     *
     *  security => [
     *      access => [
     *          'create,update,delete,execute:*' => 'front_end_inheritance'
     *      ]
     *  ]
     *
     * But this can obviously be more fine-grained with this system.  This translates to an FE output of:
     *
     *  security: [
     *      create: true,
     *      update: true,
     *      delete: true,
     *      'execute:*': true,
     *  ]
     *
     * where if any value is not present, it's assumed true.  Note that CVT only uses the CUD parts.
     *
     */
    public $dataGroups = [
        'contact-requests' => [
            'root_table' => 'cpm156.contact_requests',
            'updatable' => true,
            'deletable' => true,
            'insertable' => true,
            'changelog' => false,
        ],
        'request-tracking' => [
            'updatable' => true,
            'insertable' => false,
            'deletable' => false,
            'root_table' => 'cpmsaas_T018532GF90.pub_common_request_tracking_ec8301qzm605',
            'datetime' => ['itsm_create_time'],
            'aliases' => [
                //hopefully you see what I did there with first-last-first with the comma added..
                'customer' => "CONCAT(customer_first_name, ' ', customer_last_name, ', ', customer_first_name, ' ', customer_phone_number)",
            ],
            /*
             * in this test we override any structure-based validation on test_2, require 3 fields, and add that test_1 must be in range 50-55
            'validate' => [
                'number_test_1' => [
                    'in' => [50, 51, 52, 53, 54, 55]
                ],
            ],
            'validate_by_rule' => [
                'required' => [
                    'itsm_priority', 'itsm_status', 'number_test_1',
                ],
                'override' => [
                    //override means override any natural
                    'number_test_2'
                ],
            ],
             */
        ],
        'requests-distinct-assignee_group' => [
            'root_table' => 'Request_Tracking.srq_request',
            'distinct' => true,
            'lookup_select' => 'IF(assignee_group IS NULL OR assignee_group="", "", assignee_group) AS assignee_group',
            'base_order_by' => 'IF(assignee_group IS NULL OR assignee_group="", "", assignee_group)',
        ],
        'changes' => [
            'slug' => 'change-tracking',
            'updatable' => true,
            'insertable' => false,
            'deletable' => false,
            'root_table' => 'Request_Tracking.crq_schedule',
            'datetime' => ['Received', 'ScheduledStartDate', 'ScheduledEndDate', 'Inserted', 'Updated'],
        ],
        'users' => [
            'root_table' => 'master.users',
            'base_where' => "Status = 'ACTIVE'",
            'lookup_select' => 'LastName, FirstName, UserName',
            'base_order_by' => 'LastName, FirstName, UserName',
            'limit_start' => 0,
            'limit_range' => 1000000,
        ],
        'crq-risks' => [
            'root_table' => 'Request_Tracking.crq_risk',
        ],
        'broadscope-project-manager' => [
            'updatable' => true,
            'insertable' => true,
            'deletable' => true,
            'root_table' => 'master.todo',
            'datetime' => ['create_time', 'edit_time'],
            'aggregate_changelog' => true,
            'security' => [
                'access' => [
                    'create,update,delete,execute:*' => 'front_end_inheritance',
                ]
            ],
            //@todo: only method node implemented; need a way to say whether this overrides or can be overridden - default is that this overrides
            'defaults' => [
                'creator' => [
                    'create' => [
                        'method' => 'Security_model:get_user'
                    ],
                ],
                'editor' => [
                    'update' => [
                        'method' => 'Security_model:get_user',
                    ],
                ],
            ],
        ],
        'pages' => [
            'slug' => 'broadscope-page-manager',
            'root_table' => 'master.switch',
            'updatable' => true,
            'insertable' => true,
            'deletable' => false,
            'aggregate_changelog' => true,
        ],
        'itsm_groups' => [
            'updatable' => false,
            'insertable' => false,
            'deletable' => false,
            'root_table' => 'Request_Tracking.itsm_groups',
            'base_where' => "Active = 'Y'",
            'base_order_by' => 'itsm_source, group_name',
            'lookup_select' => 'ID, group_name',
        ],
        'menuitems' => [
            'updatable' => true,
            'insertable' => true,
            'deletable' => true,
            'root_table' => 'master.MenuItems',
            'forego_limit' => true,
            'aggregate_changelog' => true,
        ],
        'provision-tracking' => [
            'updatable' => true,
            'insertable' => false,
            'deletable' => false,
            'root_table' => 'Infrastructure.ProvisionTracking',
            'pre_process' => [
                'create,update,delete' => 'Remedy_model:provision_tracking'
            ],
            'aggregate_changelog' => true,
            'relations' => [
                'Provision_Status_Options_ID' => [
                    'identifier' => 'provision-status-options'
                ]
            ],
            'security' => [
                'access' => [
                    'create,update,delete,execute:*' => 'front_end_inheritance',
                ]
            ],
        ],
        // 2018-07-24 <sfullman@presidio.com> first use of a view
        'provision-tracking-oldest-newest' => [
            'slug' => 'provision-tracking',
            'updatable' => false,
            'insertable' => false,
            'deletable' => false,
            'root_table' => 'Infrastructure._provision_tracking_oldest_newest',
            'relations' => [
                'Provision_Status_Options_ID' => [
                    'identifier' => 'provision-status-options'
                ]
            ],
            'security' => [
                'access' => [
                    'create,update,delete,execute:*' => 'front_end_inheritance',
                ]
            ],
            /*
             * This forces the primary key in structure, as a view doesn't show the primary key
             */
            'structure' => [
                'ID' => [
                    'primary_key' => 1,
                ],
            ],
        ],
        'provision-status-options' => [
            'root_table' => 'Infrastructure.ProvisionStatusOptions',
        ],
        'provision-tracking-tasks' => [
            'root_table' => 'Infrastructure.ProvisionTasksTracking',
        ],
        'analytics' => [
            'updatable' => false,
            'insertable' => true,
            'deletable' => false,
            'root_table' => 'master.analytics',
        ],
        'user' => [
            'updatable' => true,
            'insertable' => false,
            'root_table' => 'master.users',
            'specifically_limited_fields' => ['settings' => true],
        ],
        'schedule-important-dates' => [
            'updatable' => true,
            'insertable' => true,
            'deletable' => true,
            'root_table' => 'master.schedule_important_dates',
        ],
        'sync-manager-servers' => [
            'root_table' => 'sync_mgr.sync_mgr_servers',
            'updatable' => true,
            'insertable' => true,
            'deletable' => true,
            'aggregate_changelog' => true,
        ],
        'broadridge-acronyms' => [
            'root_table' => 'master.acronyms',
            'updatable' => true,
            'insertable' => true,
            'deletable' => true,
        ],
        'changelog' => [
            'root_table' => 'sys_changelog',
        ],
        'changelog-users' => [
            'root_table' => 'master._changelog_with_users',
        ],
        'automation' => [
            'root_table' => 'sync_mgr.sync_mgr_automation',
            'deletable' => true,
            //todo: get rid of this - CRON simulation
            'pre_process' => [
                'read' => 'sync\Sync_model:simulate_cron_call'
            ],
        ],
        '_analytics_by_page_week' => [
            'root_table' => 'master._analytics_by_page_week',
        ],
        '_analytics_by_week_external_links' => [
            'root_table' => 'master._analytics_by_week_external_links',
        ],
        '_analytics_by_week_user_page' => [
            'root_table' => 'master._analytics_by_week_user_page',
        ],
        '_cmdb_main' => [
            'root_table' => 'Infrastructure._cmdb_main',
            'structure' => [
                'ID' => [
                    'primary_key' => 1,
                ],
                'OSType' => [
                    'type' => 'bigint',
                ],
                'TotalPhysicalMemory' => [
                    'type' => 'bigint',
                ],
            ],
            'aliases' => [
                // https://stackoverflow.com/questions/2147824/mysql-select-from-table-where-col-in-null-possible-without-or
                'ITSM_Site' => 'COALESCE(r.ITSM_Site, \'\')',
                '_GRID' => 'CONCAT(r._CI_Status, r._Primary_Capability)',
            ],
        ],
        'cmdb_software' => [
            'root_table' => 'Infrastructure.cmdb_software',
        ],
    ];
}
