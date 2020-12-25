<?php
/** Sample use of CVT.
 *
 * This is for CodeIgniter (3.x)
 * Based on a (MySQL) table with the following structure:
 *
    CREATE DATABASE IF NOT EXISTS cvt_example;
    USE cvt_example;
    CREATE TABLE `contact_requests` (
        `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
        `FirstName` char(30) DEFAULT NULL,
        `MiddleName` char(30) DEFAULT NULL,
        `LastName` char(30) DEFAULT NULL,
        `Email` char(85) DEFAULT NULL,
        `Phone` char(85) DEFAULT NULL,
        `Subject` char(255) DEFAULT NULL,
        `Request` text,
        `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
        `edit_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `Status` enum('New','Read','Deleted','Junk') DEFAULT NULL,
        `Important` tinyint(1) unsigned NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
    -- add records as desired or use this UI to create them!!
 *
 * The application/config/datagroups.php file needs to contain the following declaration for a basic CRUD usage:
 *
    $config['datagroups'] = [
        'contact-requests' => [
            'root_table' => 'cvt_example.contact_requests',
            'updatable' => true,
            'deletable' => true,
            'insertable' => true,
            'changelog' => false,
        ],
    ];
 *
 *
 */
?><html>
<head>
    <link rel="stylesheet" type="text/css" href="/juliet-ci-supplement/public/css/main.css" />
    <link rel="stylesheet" type="text/css" href="/juliet-ci-supplement/public/bootstrap/3.3.7/css/bootstrap.min.css" />


    <script language="JavaScript" src="/juliet-ci-supplement/public/js/jquery.min.js"></script>     <!-- currently 2.2.4 -->
    <script language="JavaScript" src="/juliet-ci-supplement/public/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script language="JavaScript" src="/juliet-ci-supplement/public/bootstrap/3.3.6/js/moment.min.js"></script>
    <script language="JavaScript" src="/juliet-ci-supplement/public/bootstrap/3.3.6/js/bootstrap-datetimepicker.min.js"></script>
    <script language="JavaScript" src="/juliet-ci-supplement/public/js/vue.min.js"></script>
    <script language="JavaScript" src="/juliet-ci-supplement/public/js/vee-validate.js"></script>
    <script language="JavaScript" src="/juliet-ci-supplement/public/js/tools.js"></script>
</head>
<body>
<script>
	/**
     * The following two variables are all that is needed to present the data coming from the Data_model
	 */
	var requestURI = '/api/data/request/contact-requests',
		focus = {
			id: "",
			FirstName: "",
			MiddleName: "",
			LastName: "",
			Email: "",
			Phone: "",
			Subject: "",
			Request: "",
            Important: "",
			create_time: "",
			edit_time: "",
			Status: "",
		};
	/**
     * From here we want to do the following things:
     * 0. Allow edits and deletes
     * 1. Default sort DESC (newest on top of oldest)
     * 2. Hide the middle name column 
	 */
	var updateURI = '/api/data/update/contact-requests',
        deleteURI = '/api/data/delete/contact-requests',
        insertURI = '/api/data/insert/contact-requests';
	var settings = {
		showDeleteDevice: true,
    };

	// These get attached to the layout object.  This hides them
    var orderBy = {create_time: 'DESC'};
    var columnsToShow = ['FirstName', 'LastName', 'Email', 'Phone', 'Request', 'create_time', 'Status'];

    var columns = {
		create_time:{
			label: 'Sent',
			search_widget: 'daterange',
			hideFromEdit: 'insert',
			uneditable: true,
		},
        edit_time: {
			hideFromEdit: 'update',
        }
    };

</script>
<?php
require APPPATH . '../public/cpm_vuetable_v0.2.php';
?>
</body>
</html>


