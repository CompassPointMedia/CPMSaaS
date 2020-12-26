<link rel="stylesheet" type="text/css" href="/request_tracking/legacy/css/main.css?r=<?php echo $commitToken;?>"/>
<script>
var focus = {
    id: "",
    identifier: "",
    label: "",
    platform: "",
    hostname: "",
    username: "",
    password: "",
    database_default: "",
    description: "",
    create_time: "",
    edit_time: "",
},
requestURI = '/api/data/request/sync-manager-servers',
updateURI = '/api/data/update/sync-manager-servers',
insertURI = '/api/data/insert/sync-manager-servers',
deleteURI = '/api/data/delete/sync-manager-servers',
settings = {
    maxCharactersPerCell: 228,
    rowHeadingRepeat: 25,
    showDeleteDevice: true,
    blankValueReplacementOnEdit: 'N/A',
},
columns = {
	identifier: {
		label: 'Identifier',
    },
    hostname: {
		label: 'Host',
    },
    username: {
		label: 'User',
    },
    password: {
		label: 'Pass',
    },
	create_time: {
		search_widget: 'daterange',
		hideFromEdit: 'insert',
		uneditable: true,
	},
	edit_time: {
		search_widget: 'daterange',
		hideFromEdit: 'insert',
		uneditable: true,
	}
};
</script>
<?php
require dirname(__FILE__) . '/cpm_vuetable_v0.2.php';
