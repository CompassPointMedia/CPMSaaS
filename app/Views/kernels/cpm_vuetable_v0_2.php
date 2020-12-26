<?php
/**
 *
 * CPM Vue Table - version 0.2.0
 * 2018-05-09 - Samuel Fullman <sfullman@presidio.com>
 *
 * a JavaScript-only front-end CRUD which integrates with the backend easily and adds functionality as-needed
 *
 */
$compile = __FILE__;
?>
<script>
if (typeof injector === 'undefined') {
	injector = {};
}

if (typeof sys_table_config === 'object') {
	injector = mergeDeep(injector, sys_table_config);
}
</script>

<script src="/asset/js/cpmstd-tracking-calendar.js"></script>
<link rel="stylesheet" href="/asset/css/calendar-default.css" type="text/css" />

<!-- cvt-container -->
<span id="cvt-container"><cvt :depth="0" :injector="'<?php echo $injector ?? 'primary';?>'"></cvt></span>


<?php if(false): ?>
<!-- stringize:cvt -->
<div class="cpm cpm-0-2">
    <!-- insert calendar -->
    <div class="calendar-container" v-if="depth === 0 && settings.implementCalendar">
        <div class="clearfix">
            <div class="pull-right">
                <label class="toggleShowCalendar"><input type="checkbox" class="toggleShowCalendarCheckbox trackable-Show-or-hide-calendar" v-model="settings.showCalendar" /> Show Calendar</label>
            </div>
        </div>
        <div class="cal-container" v-if="settings.showCalendar">
            <cpm-calendar-control v-on:controltoken="controlToken" :dataset="dataset"></cpm-calendar-control>
            <cpm-calendar-control-right v-on:controltoken="controlToken" :events="dataset"></cpm-calendar-control-right>
            <cpmstd-tracking-calendar v-on:loadeventobject="loadRecord" v-on:loadday="controlToken" :events="dataset"></cpmstd-tracking-calendar>
        </div>
    </div>

    <h4 style="font-weight: 400;" v-if="status >= CVT_STAT_INITIAL_LOADED" v-show="depth < 1">{{ datasetCountUI() }}</h4>
    <ul class="cpm-pagination">
        <template v-for="n in datasetPagination()">
            <li v-on:click="paginate(n);" :title="n.title" :class="'trackable-click' + (n.active ? '-current' : '') + '-pagination ' + (n.spacer ? 'ellipsis' : (n.active ? 'active' : ''))">{{ n.spacer ? '...' : n.index }}</li>
        </template>
    </ul>

    <!-- temporary -->
    <div v-if="depth === 0" class="clearfix" style="float: right;">
        <top-right-controls></top-right-controls>
        <button v-if="insertURI" type="button" class="btn btn-default btn-sm trackable-new-record" data-toggle="modal" data-target="#DatasetFocus" v-on:click="loadRecord('dataset', {}, '', true)">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span> New Record
        </button>
        &nbsp;
        <button type="button" class="btn btn-info btn-sm trackable-export-share-modal" data-toggle="modal" data-target="#ShareDataset" v-on:click="share.link=''">
            <span class="glyphicon glyphicon-export" aria-hidden="true"></span> <span class="glyphicon glyphicon-envelope" aria-hidden="true"></span> Export&nbsp;/&nbsp;Share
        </button>
        &nbsp;
        <button type="button" class="btn btn-warning btn-sm trackable-columns-modal" data-toggle="modal" data-target="#SelectColumns">
            <span class="glyphicon glyphicon-barcode" aria-hidden="true"></span> Columns
        </button>
    </div>

    <table class="cpm-datatable table table-condensed table-striped table-hover">
        <caption v-if="depth > 0">{{ dependency.caption }}</caption>
        <thead>
        <tr class="data-filter" v-if="depth === 0">
            <th v-if="Vue.options.components['cvt-selector']&& depth === 0" nowrap><cvt-selector ref="cvt-selector" :dataset="dataset" :selected="selected" :relations="relations" :structure="structure" v-on:controltoken="controlToken"></cvt-selector></th>
            <th v-if="showDeleteDevice()"></th>
            <th v-if="showEditDevice() && depth === 0"></th>
            <th v-for="(_null_, column) in filterColumns(structure ? structure : (dataset[0] ? dataset[0] : {}))">
                <component v-bind:is="columnSearchWidgetSelector(column)" v-on:changetoken="searchHandler" :column="column" :request="request" :structure="structure" :layout="layout"></component>
            </th>
            <!-- right control buttons -->
            <th nowrap="nowrap" v-if="status >= CVT_STAT_INITIAL_LOADED" style="padding-right: 0px;">
                <button title="Update search criteria (normally not needed; results should refresh as you type" type="button" class="btn btn-warning btn-sm" v-on:click="updateRequest()"><span class="glyphicon glyphicon-filter"></span>Update</button>
                &nbsp;
                <button title="Clear search criteria" type="button" class="btn btn-danger btn-sm" v-on:click="updateRequest(true)" >&times;</button>
            </th>

        </tr>
        <tr>
            <th v-if="Vue.options.components['cvt-selector']&& depth === 0">&nbsp;</th>
            <th v-if="showDeleteDevice()"></th>
            <th v-if="showEditDevice() && depth === 0"></th>
            <th :class="sortDatasetClass(column)" :style="columnStyle(column)" v-for="(val, column) in filterColumns(structure ? structure : (dataset[0] ? dataset[0] : {}))" v-on:click="sortDataset(column, $event)" v-on:mousedown="columnWidth($event, column)">{{ columnLabel(column) }}</th>
            <th> </th>
        </tr>
        </thead>
        <tbody>
        <template v-for="(datarow, index) in filterRows(dataset)">
            <tr v-bind:index="index" :class="getRowClass(datarow, index)">
                <td :class="getColumnClass(datarow, index, {component: 'cvt-selector'})" v-if="Vue.options.components['cvt-selector'] && depth === 0" class="text-center"><input type="checkbox" class="" v-model="selected[index]" v-on:click="clickSelected(index)" /></td>
                <td :class="getColumnClass(datarow, index, {component: 'deleteDevice'})" v-if="showDeleteDevice()" class="delete-device" title="Delete this record" v-on:click="deleteRecord('dataset', index)"><span class="glyphicon glyphicon-minus-sign"></span></td>
                <td :class="getColumnClass(datarow, index, {component: 'editDevice'})" v-if="showEditDevice() && depth === 0" :id="'recordid-' + getPrimary(datarow)" class="edit-device" title="Open and view details/edit this request" data-toggle="modal" data-target="#DatasetFocus" v-on:click="loadRecord('dataset', datarow, index)" tabindex="-1"><span class="trackable-open-record glyphicon glyphicon-edit" :data-trackable="getPrimary(datarow)"></span></td>
                <td :class="getColumnClass(datarow, index, field)" v-for="(val, field) in filterColumns(datarow)">
                    <component v-bind:is="cellComponentSelector(field, val)" :datarow="datarow" :index="index" :field="field" :val="val"></component>
                </td>
                <td :class="getColumnClass(datarow, index, {component: 'right-action'})"><right-action :datarow="datarow" :index="index"></right-action></td>
            </tr>
            <tr v-if="settings.rowHeadingRepeat && !((index + 1) % settings.rowHeadingRepeat)">
                <td v-if="Vue.options.components['cvt-selector'] && depth === 0" class="text-center"></td>
                <td v-if="showDeleteDevice()"></td>
                <td v-if="showEditDevice() && depth === 0"></td>
                <th v-for="(val, field) in filterColumns(datarow)" style="vertical-align: middle;">{{ columnLabel(field) }}</th>
                <td> </td>
            </tr>
        </template>
        </tbody>
        <tbody v-if="dataset.length == 0">
        <tr v-if="status >= CVT_STAT_INITIAL_LOADED">
            <td colspan="100%">
                <!--
                we want to say the following things:
                your search didn't return any records
                there ARE no records (factoring in hidden search criteria)

                we eventually want to consider their last query - so the first time I a user go to a CVT UI, I must read and then after get my last query
                -->
                <div v-if="depth === 0">
                    <div v-if="load_status < CVT_STAT_SECONDARY_LOADING && settings.suppressInitialDatasetLoad">
                        <!-- include initial instructions or component for searching here --> &nbsp;
                    </div>
                    <div v-else class="no-records text-center bg-info">
                        There are no records under the current search criteria. <button title="Clear search criteria" type="button" class="btn btn-default btn-sm" v-on:click="updateRequest(true)">Clear</button>
                    </div>
                </div>
                <div v-else="no-records text-center bg-info">
                    There are currently no records.
                </div>
            </td>
        </tr>
        <tr v-else>
            <td colspan="100%"><span class="loading-data">Loading..</span></td>
        </tr>
        </tbody>
    </table>

    <!-- generic modal for focusing/editing a single record -->
    <div v-if="depth === 0" class="modal fade" id="DatasetFocus" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div id="DatasetFocus-header" class="modal-header trackable-Drag-modal" :class="settings.modalHeaderClass">
                    <div class="incremental-navigation">
                        <a href="#" :class="navigateDisabled(-1)" v-on:click="navigateRecord(-1)" title="Navigate to previous record" tabindex="-1">
                            <span class="glyphicon glyphicon-chevron-left"></span>
                        </a>
                        &nbsp;
                        <a href="#" :class="navigateDisabled(1)" v-on:click="navigateRecord(1)" title="Navigate to next record" tabindex="-1">
                            <span class="glyphicon glyphicon-chevron-right"></span>
                        </a>
                        &nbsp;
                        <button type="button" class="close" data-dismiss="modal" v-on:click="cancelRecord(focus)" tabindex="-1" title="Close this record">
                            <span style="font-size: 75%;" class="glyphicon glyphicon-remove"></span>
                        </button>
                    </div>
                    <h4 v-if="typeof Vue.options.components['dataset-focus-header'] === 'undefined' && settings[editMode + 'Title']" v-html="settings[editMode + 'Title']"></h4>
                    <dataset-focus-header :focus="focus" :focusIndex="focusIndex" :editMode="editMode"></dataset-focus-header>
                </div><!-- modal-header -->
                <div class="modal-body">
                    <table class="table table-condensed small table-striped table-hover">
                        <tbody v-for="(groupConfig, group) in structureGroups.meta">
                        <tr v-if="group !== '_generic_'" class="structure-group-heading"><td colspan="100%">
                                <p><label><input type="checkbox" v-model="structureGroups.meta[group].show" v-bind:true-value="1" v-bind:false-value="0" /> {{ groupConfig.label }}</label></p>
                            </td>
                        </tr>
                        <tr v-for="(column_structure, column) in structureGroups.groups[group]" v-if="showForEdit(column, column_structure)" v-show="group === '_generic_' || _true(structureGroups.meta[group].show)">
                            <td class="rowLabel" v-if="!editFullRow(column, column_structure)">{{ columnLabel(column) }}</td>
                            <td :colspan="editFullRow(column) ? '100%' : ''"><component v-bind:is="columnEditSelector(column)" v-on:changetoken="methodFocusChanged" :layout="layout" :column="column" :column_structure="column_structure" :value="editableValue(focus[column], column)" :datarow="focus"></component></td>
                        </tr>
                        </tbody>
                    </table>
                    <!-- one-to-many widget -->
                    <cvt v-for="dependency in dependencies" :depth="1" :dependency="dependency" :datarow="focus" :ref="dependency.name"></cvt>
                    <cvt-one-to-many-widget v-on:controltoken="controlToken" :dataset="dataset" :datarow="focus"></cvt-one-to-many-widget>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-sm-12 text-right">
                            <cvt-focus-additional-controls v-on:controltoken="controlToken" :dataset="dataset" :datarow="focus" :userconfig="userConfig" :settings="settings"></cvt-focus-additional-controls>
                            <span v-if="focusChanged">
                            <button v-on:click="insertOrUpdateRecord()" type="button" class="btn btn-default trackable-submit-record">{{ editMode === 'update' ? 'Update' : 'Add' }}</button>
                        </span>
                            &nbsp;&nbsp;
                            <button type="button" class="btn btn-default" data-dismiss="modal" v-on:click="cancelRecord(focus)">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- single field editing tool -->
    <single-field-edit ref="single-field-edit" :datarow="{}"></single-field-edit>

    <!-- available columns modal -->
    <div v-if="depth === 0" class="modal fade" id="SelectColumns" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div><!-- modal-header -->
                <div class="modal-body clearfix">
                    <div class="clearfix">
                        <div style="float:left;"><a v-on:click="selectColumns($event)" href="javascript:null;" class="trackable-select-all-columns"> Select all</a></div>
                        <div style="float:right;"><a v-on:click="selectColumns($event, [])" href="javascript:null;" class="trackable-select-almost-no-columns"> Select almost none</a></div>
                    </div>
                    <ul class="list-unstyled">
                        <li class="col-sm-4" v-for="(params, column) in filterColumns(structure, true)">
                            <label role="button"><input type="checkbox" :checked="layout.columnsToShow.indexOf(column) !== -1" v-on:click="selectColumns($event, column)" class="trackable-show-or-hide-column" /> {{ columnLabel(column) }}</label>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-sm-12 text-right">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- sharing modal -->
    <div v-if="depth === 0" class="modal fade" id="ShareDataset" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    Export and Share
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div><!-- modal-header -->
                <div class="modal-body clearfix">
                    Export as:
                    &nbsp;&nbsp;&nbsp;
                    <label class="checkbox-parent"><input v-model="share.exportAs" :value="'xlsx'" type="radio"> Excel (XLSX)</label>
                    &nbsp;&nbsp;&nbsp;
                    <label class="checkbox-parent"><input v-model="share.exportAs" :value="'csv'" type="radio"> CSV</label>
                    &nbsp;&nbsp;&nbsp;
                    <!--
                    // 2018-07-29 <sfullman@presidio.com> not developed currently
                    <label class="checkbox-parent"><input v-model="share.exportAs" :value="'pdf'" type="radio"> PDF</label>
                    &nbsp;&nbsp;&nbsp;
                    -->
                    <div class="visible-xs-block  visible-sm-inline-block visible-md-inline-block visible-lg-inline-block">
                        <label class="checkbox-parent"><input v-model="share.exportAs" :value="'link'" type="radio"> Quick-link:</label>

                        <label><input style="display: inline; width: 350px;" type="text" class="form-control" v-model="share.link" placeholder="Click Export/Share below.." /></label>
                    </div>

                    <div v-if="share.exportAs === 'link'">
                        <label class="checkbox-parent"><input v-model="share.report" type="checkbox" v-bind:true-value="1" v-bind:false-value="0" v-on:click="!share.report && document.getElementById('shareReportName') ? document.getElementById('shareReportName').focus() : '';" /> Save as a report/query: </label>
                        <span v-if="share.report">
                        <input id="shareReportName" type="text" v-model="share.reportName" placeholder="Enter report name here.." class="form-control" /><br />
                        <textarea v-model="share.reportDescription" class="form-control" rows="3" placeholder="(Optional description of report)"></textarea>
                        <label class="checkbox-parent"><input v-model="share.public" type="checkbox" v-bind:true-value="1" v-bind:false-value="0" /> report can be seen by other users</label>
                    </span>
                    </div>
                    <br/><br/>

                    <label><input type="checkbox" v-bind:true-value="1" v-bind:false-value="0" v-model="share.exportOnlyColumnsShown" /> Export only columns shown</label><br />
                    <label><input type="checkbox" v-bind:true-value="1" v-bind:false-value="0" v-model="share.sendToUsers" class="sendToSelectedUsers" /> Send to selected users:</label>
                    <div v-if="share.sendToUsers">
                        <textarea class="form-control" v-model="share.selectedUsers" rows="4"></textarea>
                    </div>
                    <div v-else>
                        (no users selected)
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="row">
                        <div class="col-sm-12 text-right">
                            <button type="button" class="btn btn-info trackable-export-share-execute" v-on:click="shareDataset()">Export/Share</button>
                            <button type="button" class="btn btn-default" data-dismiss="modal" >Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /stringize:cvt -->

<!-- stringize:single-field-edit -->
<span class="cpm-layer cpm-layer-default" v-show="show">
    <textarea id="single-field-edit" class="form-control" rows="3" v-model="val"></textarea>
    <div class="control-group" style="margin-top: 7px;">
        <button type="button" class="btn btn-info btn-sm" @click="single_field_submit_cancel(val)">Submit</button>
        &nbsp;
        <button type="button" class="btn btn-default btn-sm" @click="single_field_submit_cancel(val, true)">Cancel</button>
    </div>
</span>
<!-- /stringize:single-field-edit -->
<?php endif;?>

<script language="JavaScript">
//CVT requires global functions

//constants
Object.defineProperties(window, {
	CVT_STAT_NONE: {
		value: 1,
		enumerable: true
	},
	CVT_STAT_CREATED: {
		value: 2,
		enumerable: true
	},
	CVT_STAT_INITIAL_LOADING: {
		value: 4,
		enumerable: true
	},
	CVT_STAT_INITIAL_LOADED: {
		value: 8,
		enumerable: true
	},
	CVT_STAT_SECONDARY_LOADING: {
		value: 16,
		enumerable: true
	},
	CVT_STAT_SECONDARY_LOADED: {
		value: 32,
		enumerable: true
	},

    //LS = load status
	CVT_LS_ERROR: {
		value: 1,
		enumerable: true
	},
	CVT_LS_WARNING: {
		value: 2,
		enumerable: true
	},
    CVT_LS_PRELOAD: {
		value: 3,
		enumerable: true
    },
    CVT_LS_LOADING: {
	    value: 4,
        enumerable: true
    },
	CVT_LS_LOADED: {
		value: 5,
		enumerable: true
	},

    //Others
    GENERIC_SQL_NUMBER_FIELDS: {
		value: 'int|integer|bigint|mediumint|smallint|tinyint|dec|decimal|float|double',
        enumerable: true
    },
	SQL_INT_RANGES: {
		value: {
			tinyint: 255,
			smallint: 65535,
			mediumint: 16777215,
			int: 4294967295,
			integer: 4294967295,
			bigint: 18446744073709551615,
        },
        enumerable: true
	},
    SETTINGS_SHOW_PER_PAGE_DEFAULT: {
		value: 100,
        enumerable: true
    }
});

Vue.use(VeeValidate);

Vue.component('generic_display', {
    /* layout = layout.columns, layout.columnsToShow and layout.orderBy */
	props: [ 'layout', 'column_structure', 'column', 'value' ],
	template: '<span><span v-if="text() && value" v-html="nl2br(value)"></span><span v-else :class="output(value, true) ? \'cpm-blank\' : \'\'">{{ output(value) }}</span></span>',
	methods: {
		output: function(value, isBlank){
			var output;
			if(typeof value === 'undefined' || value === null){
				output = '';
            }else{
				output = value;
            }
            if(isBlank){
				return output.length ? false : true;
			}

			if(!output.length && this.$parent.$data.settings && this.$parent.$data.settings.blankValueReplacementOnEdit){
				output = this.$parent.$data.settings.blankValueReplacementOnEdit;
            }
			return output;
        },
		text: function(){
			if(this.column_structure && this.column_structure.type && this.column_structure.type.match(/^(text|tinytext|mediumtext|longtext)$/)){
				return true;
            }
            return false;
        }
    }
});

Vue.component('generic_input', {
    /* layout = layout.columns, layout.columnsToShow and layout.orderBy */
	props: [ 'layout', 'column_structure', 'column', 'value' ],
	template: '<span><input v-model:string="value" v-on:input="emitChange" :placeholder="placeholder()" v-bind:maxlength="maxlength()" v-on:change="emitChange" class="form-control" v-validate="rules" :name="column" />' +
	'<p class="is-danger" v-show="errors.has(column)" v-if="rules !== {}">{{ errors.first(column) }}</p>' +
	'</span>',
	data: function(){
		return {
			rules: {}
		}
	},
	created: function(){
		this.validate_rules();
	},
	methods: {
		validate_rules: function(){
			var i, rules = {};
			var col = this.column;
			var settings = this.$parent.settings;
			var validation = this.$parent.validation[col] ? this.$parent.validation[col] : {};

			var validateFromStructure = settings.useValidationFromStructure;
			if(this.layout.columns && this.layout.columns[col] && this.layout.columns[col].noValidation){
				return false;
			}

			//validate from backend rules
			if(validation){
				for(i in validation){
					//override is a pseudo attribute
					if(i === 'override') continue;
					rules[i] = validation[i];
				}
			}

			//validate from structure unless we override this from backend
			if(validateFromStructure && this.column_structure && this.column_structure.type && !validation.override){
				//specify which areas of validation will be used; boolean true means "all"
				if(validateFromStructure === true){
					validateFromStructure = 'date,time,moment,string,number';
				}
				//this logic should be moved to VeeValidate

				//requirements such as datatype
				var structure = this.column_structure;
				if(validateFromStructure.match(/string/) && (structure.type === 'char' || structure.type === 'varchar')){
					if(structure.max_length){
						rules['max'] = structure.max_length;
					}
				}
				if(validateFromStructure.match(/number/)){
					var reg = new RegExp(GENERIC_SQL_NUMBER_FIELDS);
					if(this.column_structure.type.match(reg)){
						rules['numeric_general'] = this.column_structure;
					}
					if(this.column_structure.type.match(/^(tinyint|smallint|mediumint|int|bigint)/)){
						if(this.column_structure.unsigned){
							rules['int_min'] = 0;
							rules['int_max'] = SQL_INT_RANGES[this.column_structure.type];
						}else{
							rules['int_min'] = 0 - ((SQL_INT_RANGES[this.column_structure.type] + 1) / 2);
							rules['int_max'] = ((SQL_INT_RANGES[this.column_structure.type] + 1) / 2);
						}
					}
				}
			}
			this.rules = rules;
		},
		emitChange: function(e){
			this.$emit('changetoken', this.column, this.value);
		},
		placeholder: function(){
			if(typeof this.layout.columns !== 'undefined' && typeof this.layout.columns[this.column] !== 'undefined'){
				if(typeof this.layout.columns[this.column].placeholder !== 'undefined')
					return this.layout.columns[this.column].placeholder;
			}
			return '';
		},
		// todo: this is a temp stay-in until we go to a javascript solution.  You should ALWAYS allow the user to type beyond maxlength and give them the chance to edit content down; also, they may have pasted it in from somewhere.
		maxlength: function(){

			return '';

            /*
			if(this.column_structure && (this.column_structure.type === 'char' || this.column_structure.type === 'varchar') && this.column_structure.max_length){
				return this.column_structure.max_length;
			}
			return '';
			*/
		}
	}
});

Vue.component('generic_datetime', {
	props: [ 'layout', 'column_structure', 'column', 'value' ],
	template:
	'<div :class="\'input-group date edit-datetime-\' + column ">' +
        '<input v-model:string="value" :id="\'edit-datetime-\' + column" v-on:input="emitChange" v-on:change="emitChange" class="form-control" type="datetime" />' +
        '<span class="input-group-addon">' +
        '<span class="glyphicon glyphicon-calendar"></span>' +
        '</span>' +
	'</div>',
	methods: {
		emitChange: function(e){
			this.$emit('changetoken', this.column, this.value);
		},
	},
	created: function(){
		try{
			var self = this;
			var field = 'edit-datetime-' + this.column;
			//known bug - does not work without a timeout
			setTimeout(function(){
				//self.value = createDate(self.value, 'standard');
				var settings = {
					keepInvalid: true,
				};
				switch (self.column_structure.type){
					case 'date':
						settings.format = 'L'; break;
					case 'datetime':
                    case 'timestamp':
						// settings.format = 'yyyy-mm-dd hh:ii';
						break;
					case 'time':
						// settings.format = 'hh:ii:ss';
						break;
					default:
						console.log('unable to determine structure.type for assumed datetime column');
				};

				$('.' + field).datetimepicker({
					keepInvalid: true,
					format: 'L',
				});
			}, 500);
			function monitor() {
				var delta = '';
				if(document.getElementById(field)){
					delta = document.getElementById(field).value;
					//console.log(self.value + ':' + delta);
					if(delta !== self.value){
						document.getElementById(field).dispatchEvent(new Event('input'));
						self.value = delta;
					}
				}
				setTimeout(monitor, 1000);
			}
			monitor(field);
        }catch(e){
			console.log(e);
        }
	},
});

Vue.component('generic_textarea', {
	props: [ 'layout', 'column_structure', 'column', 'value' ],
	template: '<textarea v-model:string="value" :placeholder="placeholder()" v-on:change="emitChange" v-on:input="emitChange" :rows="dimension(\'rows\')" class="form-control"></textarea>',
	methods: {
		dimension: function(dimension){
			//clumsy but makes sure path is available
			if(this.layout.columns &&
				this.layout.columns[this.column] &&
				this.layout.columns[this.column].edit &&
				this.layout.columns[this.column].edit[dimension]){
				return this.layout.columns[this.column].edit[dimension];
			}
			return 4;
		},
		placeholder: function(){
			if(typeof this.layout.columns !== 'undefined' && typeof this.layout.columns[this.column] !== 'undefined'){
				if(typeof this.layout.columns[this.column].placeholder !== 'undefined')
					return this.layout.columns[this.column].placeholder;
            }
			return '';
		},
		emitChange: function(e){
			this.$emit('changetoken', this.column, this.value);
		}
	}
});

Vue.component('generic_select', {
    props: [ 'layout', 'column_structure', 'column', 'value' ],
	template: '<select v-model:string="value" v-on:change="emitChange" class="form-control">' +
	'<option value="">&lt;Select&gt;</option>' +
	'<option v-for="(label, option_value) in buildOptions(column)" v-bind:value="option_value">{{ label }}</option>' +
	'</select>',
	methods: {
		emitChange: function(e){
			this.$emit('changetoken', this.column, this.value);
		},
		buildOptions: function(column){
            /*
             see search-widget-dropdown; copied from there (not the relations part)
             */
			if(this.$parent.relations[column]){
				var i, primary, display, range = {}, relation = this.$parent.$data.relations[column];
				//what field to relate to - currently only field is primary key
				primary = this.$parent.db.getPrimary(relation.structure, 'string');

				//what field to display - currently only first text string
				display = this.$parent.db.getLabel(relation.structure);

				for(i in relation.dataset){
					//note string conversion
					range['' + relation.dataset[i][primary] + ''] = relation.dataset[i][display];
				}
				return range;
			}

			if(this.layout.columns && this.layout.columns[column]){
				if(this.layout.columns[column].data_range){
					return this.layout.columns[column].data_range;
				}
			}
			return this.$parent.structure[column].data_range;
		}
    }
});

Vue.component('generic_checkbox', {
	props: [ 'layout', 'column_structure', 'column', 'value' ],
    template: '<input type="checkbox" v-model="value" v-bind:true-value="1" v-bind:false-value="0" v-on:change="emitChange" class="form-control" />',
    methods: {
		emitChange: function(e){
			this.$emit('changetoken', this.column, this.value);
        }
    }
});

Vue.component('search-widget-generic', {
	props: ['column', 'request', 'structure', 'layout'],
	template: '<input v-model.string="request[column]" v-on:input="activeSearch($event)" v-on:keyup.enter="activeSearch($event, \'enter\')" type="text" :name="column" :placeholder="label(column, structure, layout)" class="form-control input-sm" />',
	data: function(){
		return {
			searching: false,
		}
	},
	methods: {
		label: function(column, structure, layout){
			if(layout.columns && layout.columns[column] && layout.columns[column].label){
				return layout.columns[column].label;
			}
			if(structure[column] && structure[column].label){
				return structure[column].label;
			}
			var label = camelCase(snakeCase(column));
			return label;
		},
		activeSearch: function(event, type){
			if(typeof type === 'undefined') type = 'type'
			clearTimeout(this.searching);
			if(type === 'enter'){
				console.log('direct to enter')
				this.$emit('changetoken', this.column, event);
				return;
			}
			//fix for the timeout
			var that = this;
			this.searching = setTimeout(function(){
				console.log('search on rest');
				that.$emit('changetoken', that.column, event);
			}, 500)
		}
	}
});

Vue.component('search-widget-dropdown', {
	props: ['column', 'request', 'structure', 'layout'],
    template: '<select v-model.string="request[column]" v-on:change="activeSearch($event)" class="form-control input-sm">' +
    '<option value="">&lt;Any&gt;</option>' +
    '<option v-for="(label, value) in buildOptions(column)">{{ label }}</option>' +
    '</select>',
    methods: {
        buildOptions: function(column){
        	/*
        	Normally we expect this array in structure definition
        	Otherwise we should have a way of getting the data range for this column, or we _might_ try and get distinct values, but otherwise we're out of luck and should present an input.text field.
        	*/
			if(this.layout.columns && this.layout.columns[column]){
				if(this.layout.columns[column].data_range){
					return this.layout.columns[column].data_range;
				}
			}
        	return this.structure[column].data_range;
        },
        activeSearch: function(event){
			this.$emit('changetoken', this.column, event);
        }
    }
});

Vue.component('search-widget-daterange', {
	props: ['column', 'request', 'structure', 'layout'],
    template: '<div class="daterange"><div class="daterange-control" title="Select a range of dates" data-toggle="modal" v-on:click="loadDateRange" :data-target="\'#manageDateRange-\' + column">' +
    '<span :class="\'input-group-addon \' + (!dateRangeStartTime && !dateRangeEndTime ? \'no-dates\' : \'\')"><span class="glyphicon glyphicon-calendar"></span></span>' +
    '</div>' +
    '<div class="modal fade" :id="\'manageDateRange-\' + column" role="dialog">' +
        '<div class="modal-dialog">' +
            '<div class="modal-content">' +
                '<div class="modal-header">' +
                	'<button type="button" class="close" data-dismiss="modal">&times;</button>' +
                    '<h4 class="modal-title float-left">Select date range for {{ column }}</h4>' +
                '</div>' +
                '<div class="modal-body">' +
                    '<div class="container col-sm-12">' +
                        '<div class="row">' +
                            '<label class="control-label col-sm-3">From</label>' +
                            '<div class="col-sm-9">' +
                                '<div class="form-group">' +
                                    '<div class="input-group date StartTimePicker" id="StartTimePicker">' +
                                        '<input name="StartTime" id="StartTime" placeholder="(optional)" v-model:string="focus.dateRangeStartTime" type="datetime" class="form-control"/>' +
                                        '<span class="input-group-addon">' +
                                            '<span class="glyphicon glyphicon-calendar"></span>' +
                                        '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<!-- {{ focus }} ^ {{dateRangeStartTime}} ^ {{dateRangeEndTime}} -->' +
                        '<div class="row">' +
                            '<label class="control-label col-sm-3">To</label>' +
                            '<div class="col-sm-9">' +
                                '<div class="form-group">' +
                                    '<div class="input-group date EndTimePicker" id="EndTimePicker">' +
                                        '<input name="EndTime" id="EndTime" placeholder="(optional)" v-model:string="focus.dateRangeEndTime" type="datetime" class="form-control"/>' +
                                        '<span class="input-group-addon">' +
                                            '<span class="glyphicon glyphicon-calendar"></span>' +
                                        '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<input type="hidden" name="dbKey" id="dbKey" />' +
                            '<input type="hidden" name="title" id="title" />' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-sm btn-info" v-on:click="updateDateRange()" mode="update">Update</button> ' +
                    '<button type="button" class="btn btn-sm btn-warning" v-on:click="updateDateRange(true)" mode="update">Clear</button> ' +
                    '<button type="button" class="btn btn-sm btn-default" data-dismiss="modal">Cancel</button> ' +
                '</div>' +
            '</div>' +
        '</div>' +
    '</div>' +
    '</div>',
    data: function(){
		return {
			//this is a copy during editing phase till committed.
			focus: {
				dateRangeStartTime: '',
				dateRangeEndTime: '',
            },
			dateRangeStartTime: '',
            dateRangeEndTime: '',
        }
    },
    methods: {
        loadDateRange: function(){
            this.focus.dateRangeStartTime = this.dateRangeStartTime;
            this.focus.dateRangeEndTime = this.dateRangeEndTime;
        },
        updateDateRange: function(clear){

        	if(typeof clear ==='undefined') clear = false;

			this.dateRangeStartTime = (clear ? '' : this.focus.dateRangeStartTime);
			this.dateRangeEndTime = (clear ? '' : this.focus.dateRangeEndTime);
			$('#manageDateRange-' + this.column).modal('hide');
			// NOT WORKING
            // this.$emit('changetoken', this.dateRangeStartTime, this.dateRangeEndTime, this.column);
            if(this.dateRangeStartTime.length || this.dateRangeEndTime.length){
				this.$parent.request[this.column] = '|between|' + this.dateRangeStartTime + '|' + this.dateRangeEndTime;
            }else{
				this.$parent.request[this.column] = '';
            }
            console.log('check on change, updating request')
            this.$parent.updateRequest();
        }
    },
    created: function(){
    	var col = this.column;
    	var fields = '#manageDateRange-' + col + ' .StartTimePicker, #manageDateRange-' + col + ' .EndTimePicker';
    	//known bug - does not work without a timeout
    	setTimeout(function(){
    		$(fields).datetimepicker();
        }, 500);

    	if(typeof this.request[col] === 'string' && this.request[col].substr(0, 1) === '|'){
    		this.request[col] = this.request[col].substr(1).split('|');
        }

        if(typeof this.request[col] !== 'undefined' && typeof this.request[col][1] !== 'undefined'){
            this.dateRangeStartTime = createDate(this.request[col][1], 'standard');
        }else{
        	this.dateRangeStartTime = '';
        }
        if(typeof this.request[col] !== 'undefined' && typeof this.request[col][2] !== 'undefined'){
            this.dateRangeEndTime = createDate(this.request[col][2], 'standard');
        }else{
        	this.dateRangeEndTime = '';
        }
	}
});

Vue.component('generic-cell', {
	props: ['datarow', 'index', 'field', 'val'],
	template: '<span :class="style()" @dblclick="single_field_edit()">{{ format(val) }}</span>',
    methods: {
		//---------- note: format is also found on the backend for exports --------
		format: function(val){
            if(val === null){
            	val = '';
            }

            if(this.$parent.$data.layout.columns[this.field]){
            	var layout = this.$parent.$data.layout.columns[this.field];
            	if(typeof layout.transform === 'function'){
            		return layout.transform(val, this.datarow);
                }
            }

			if(this.$parent.$data.structure[this.field]){
                var structure = this.$parent.$data.structure[this.field];
                if(structure.intent === 'date' || structure.intent === 'datetime' || structure.intent === 'time' || structure.intent === 'timestamp'){
                	val = createDate(val);
                	if(!val.getDate || isNaN(val.getDate())){
                		return arguments[0];
					}

					/* todo: improve this using locale and formatting, and allow for offset if it's a unix timestamp */
					var obj = parseDateToString(val.toString(), true);

					var str = '';
					if(structure.intent !== 'time'){
						str += obj.m + '/' + obj.d + '/' + obj.Y
                    }
                    if(structure.intent === 'datetime' || structure.intent === 'timestamp'){
						str += ' ';
                    }
                    if(structure.intent !== 'date' && val.hasTime){
                    	// todo: this was Mike's request, need a setting for this
                        str += obj.H + ':' + obj.i
                    }
                    return str;
				}
            }

            //translate value based on relationship
            if(this.$parent.$data.relations[this.field]){
				var i, primary, display, relation = this.$parent.$data.relations[this.field];
				//what field to relate to - currently only field is primary key
                primary = this.$parent.db.getPrimary(relation.structure, 'string');

                //what field to display - currently only first text string
                display = this.$parent.db.getLabel(relation.structure);

                for(i in relation.dataset){
                	if(val == relation.dataset[i][primary]){
                		return relation.dataset[i][display]
                    }
                }
            }
            if(this.$parent.$data.settings.maxCharactersPerCell && typeof val.length !== 'undefined' && val.length > this.$parent.$data.settings.maxCharactersPerCell){
				return val.substring(0, this.$parent.$data.settings.maxCharactersPerCell) + '...';
            }
            if(val === ''){
            	if(typeof this.$parent.$data.layout.columns !== 'undefined' &&
					typeof this.$parent.$data.layout.columns[this.field] !== 'undefined'){
					if(this.$parent.$data.layout.columns[this.field].blankReplacementOnRead){
						return this.$parent.$data.layout.columns[this.field].blankReplacementOnRead;
					}
                }
            }
			return val;
        },
        style: function(){
			// todo: allow for user defined function in config
            var styles = [];
            if(this.val === null || this.val === ''){
				if(typeof this.$parent.$data.layout.columns !== 'undefined' &&
					typeof this.$parent.$data.layout.columns[this.field] !== 'undefined'){
					if(this.$parent.$data.layout.columns[this.field].blankReplacementOnRead){
						styles.push('cpm-blank');
					}
				}
            }
            if(this.$parent.settings.singleFieldEdit){
            	styles.push('cpm-cp');
            }
            return styles.join(' ');
        },
        single_field_edit: function(){
        	//only allow if we've specified it
        	if(!_false(this.$parent.settings.singleFieldEdit)) return;

			var el = this.$el.parentNode;
			var tx = this.$parent.$refs['single-field-edit'];
			position(el, tx.$el);

            // todo: imprecise way of thing this, go through props instead
        	tx.show = true;
            tx.datarow = this.datarow;
            tx.index = this.index;
            tx.field = this.field;
            tx.val = this.val;
            tx.structure = this.$parent.structure;
            setTimeout(function(){
				document.getElementById('single-field-edit').focus();
            }, 500);
        }
    }
});

Vue.component('single-field-edit', {
	props: ['datarow'],
    data: function(){
		return {
			show: false,
            val: null,
        }
    },
    watch: {
    	datarow: function(old, _new){
    		/*
            console.log('change!!');
            console.log(old);
            console.log(_new);
            */
        }
    },
    template: <?php echo stringize('single-field-edit', $compile);?>,
    methods: {
		single_field_submit_cancel: function(val, cancel){
			if(typeof cancel === 'undefined'){
				var primary = this.$parent.db.getPrimary(this.structure, 'string');
				var _focus = {};

				_focus[primary] = this.datarow[primary];
				_focus[this.field] = this.val;

				this.$parent.editMode = 'update';
				this.$parent.insertOrUpdateRecord(_focus);

				this.$parent.dataset[this.index][this.field] = this.val;
            }

            this.show = false;
		},
    }
});

Vue.component('cvt', {
	props: ['depth', 'dependency', 'datarow','injector'],
    template: <?php echo stringize('cvt', $compile);?>,
    data: function(){
        console.log('i am data, injector should be present');
    	var native = {
            // ---------- user-added values; feel free to add what you want ------------ //
            userConfig: {},


            // ---------- user-changeable settings; present in injector but you control the values ----------- //
            requestURI:'',
            updateURI: '',
            insertURI: '',
            deleteURI: '',
            shareURI: '',

            // Observer hooks
			observerPostDataLoad: null,
			observerPostDataReload: null,
			observerPostLocalSort: null,
			observerTransformDataset: null,
			observerLoadRecord: null,
			observerPostLoadRecord: null,
			observerInsertOrUpdateParameters: null,
			observerInsertOrUpdateApplicationParameters: null,
			observerBeforeCancelRecord: null,
			observerPostInsertOrUpdateRecord: null,
			observerGridClass: null,
			observerPostResizeColumn: null,
			observerUpdateRequest: null,

            row_descriptor: 'row',

            // Settings.  Normally immutable.
            settings: {},

            // item #3 for presentation: layout includes what columns are visible, locale formatting, etc.; can include user prefs and is mutable.
            // NOTE! layout must be here, as well as .columns and .columnsToShow if possible, even if empty {}
            layout: {
                columns: {},
                columnsToShow: [],
                orderBy: {},
            },


            // ----------- component-specific settings; don't touch these unless you know what you're doing ------------ //

            version: '0.2.0',
            status: CVT_STAT_NONE,
            load_status: CVT_LS_PRELOAD,

            // item #1 for presentation: data (dataset).  From this we can present a basic spreadsheet-like display.  Mutable by API
            dataset: [],
            total_rows: 0,

            // item #2: structure.  This is a compiled table source and meta data object. Everything maps to columns.  Immutable (unless BE structure changed!)
            structure: {},

            // item #3: layout.  Mutable (see above)

            // item #4: focus fields.  As of 2018-04-30 haven't figured out how to not need these for editing
            // they can be gotten at: /api/data/fieldsJSON?db=My_DB&table=my_table
            // we are now depending on focus to be present from the injector
            // focus: injector.focus,
            focusIndex: null,
            focusChanged: false,
            editMode: null,

            // item #5: relational data
            relations: {},

            // item #6: validation rules
            validation: {},

            // item #7: security
            security: {},

            // item #8: dependencies and dependency
            dependencies: [],
            dependency: {},

            // item #9: selected records. Note this is going to change when dataset becomes a computed object
            selected: [],

            //initial request parameters, if not specified otherwise use query string
            init: window.location.search.replace(/^\?+/,'').replace(/^&+/,'').replace(/&+$/, ''),

            request: {},
            original: {},
            page: null,
            share: {
                exportAs: null,
                exportOnlyColumnsShown: 1,
                sendToUsers: 0,
                link: null,
                report: false,
                reportName: '',
                reportDescription: '',
                public: false,
            },
            lazy: '',
        };
        return mergeDeep(native, injector);
    },
    mounted: function() {
        console.log('i am mounted');
    },
    beforeCreate: function() {
        console.log('i am before create');
    },
    created: function () {
    	console.log('i am created');
    	//add common validations as extension of Vee-Validate
		this.$validator.extend('numeric_general', {
			validate: function(value, _args){
				var structure = _args[0], test;

				if(structure.unsigned && value.match('-')){
                    //console.log('negative values not allowed');
                    return false;
                }
				//leading negative sign is OK
				test = value.replace(/^-/, '');

				//generally, currency signs are OK
				test = test.replace(/^[$£€]/, '');

                //commas before 3 numbers are just fine
				test = test.replace(/,([0-9]{3})/g, '$1');

                //trailing zeros or .0 can be removed
				test = test.replace(/\.[0]*$/, '');

				if(structure.type.match(/dec|decimal|float/)){
					if(typeof structure.max_length !== 'undefined' && typeof structure.decimal !== 'undefined'){
						//float or decimal
                        if(!test.match(/^[.0-9]*$/)){
                        	//console.log('only numbers and period allowed');
                            return false;
                        }

						//more than one period
						test = test.split('.');
						if(test.length > 2){
							//console.log('more than one period');
							return false;
						}

						if(!test[1]) test[1] = '';
						test[1] = test[1].replace(/0*$/, '');

						if(test[1].length > structure.decimal){
							//console.log('total decimal size exceeded');
							return false;
						}
						if(test[0].length + test[1].length > structure.max_length){
							//console.log('total number size exceeded');
							return false;
						}
                    }else{
						//we cannot process
                        console.log('unable to process decimal/float field due to lack of structure parameters');
                    }
					return true;
				}else{
					if(test.match(/^[0-9]*$/)){
						return true;
                    }
                    return false;
                }
            },
            getMessage: function(field) {
				//console.log(field);
                return 'This is not a valid number format, or will not go into `' + field + '` without being truncated, rounded or lost.';
            }
        });

		this.$validator.extend('int_min', {
			validate: function(value, _args){
				var negative, n = _args[0];

				negative = (value.substring(0,1) === '-');

				test = value.replace(/^-/, '');

				//generally, currency signs are OK
				test = test.replace(/^[$£€]/, '');

				//replace commas
                test = test.replace(/,/g, '');

                if(!test.match(/^[0-9]+/)){
                	console.log('unable to evaluate');
                	return true;
                }

                test = parseFloat(test) * (negative ? -1 : 1);

                if(test < n) return false;
                return true;
			},
			getMessage: function(field) {
				return 'This value is smaller than the allowable range for this field (' + field + ').';
			}
		});

		this.$validator.extend('int_max', {
			validate: function(value, _args){
				var positive, n = _args[0];

				negative = (value.substring(0,1) === '-');

				test = value.replace(/^-/, '');

				//generally, currency signs are OK
				test = test.replace(/^[$£€]/, '');

				//replace commas
				test = test.replace(/,/g, '');

				if(!test.match(/^[0-9]+/)){
					console.log('unable to evaluate');
					return true;
				}

				test = parseFloat(test) * (negative ? -1 : 1);

				if(test > n) return false;
				return true;
			},
			getMessage: function(field) {
				return 'This value is larger than the allowable range for this field (' + field + ')';
			}
		});

		//rule for testing
		this.$validator.extend('test_validate', {
			validate: function(value, _args){
				console.log('test_validate called');
				return false;
            },
            getMessage: function(field){
				return 'You successfully assigned rule "test_validate" on '+field;
            }
        });

		if(this.lazy){
			window.lazyAttributes(this.$data, this.lazy);
		}

		/**
         * --- Dependency presentation management ---
         * @added: 2018-07-23
         * @author: Sam Fullman <sfullman@presidio.com>
         *
         * If this.dependency is declared it means that <cvt> is actually being called as such.
         * `this.dependency.data` will have all the overrides
         *
         */
		if(sizeOf(this.dependency)){
			var i, j, subj, _subj;
			//overwrite data values - this is clumsy
			for(i in this.dependency.data){
				subj = this.dependency.data[i];
				if(typeof subj === 'object'){
                    for(j in subj){
                    	_subj = subj[j];
                    	this[i][j] = _subj;
                    }
                }else{
					// string or function
                    this[i] = subj;
                }
            }
			//configure normal settings for an embedded dataGroup
            /*
            BUG: I can't seem to separate between the parent dataset and the dependency
			this.settings.displayDatasetCount = _false(this.settings.displayDatasetCount)
			this.settings.displayDataFilter = _false(this.settings.displayDataFilter);
			*/
        }
        // -------------------------------------------

    	this.status = CVT_STAT_CREATED;

    	//instantiate comm object for managing state and storing requests
        this.comm = this._comm(this);
    	this.db = this._db(this);

        //advanced features
        if(!this.share.exportAs) this.share.exportAs = 'xlsx';
        if((typeof this.share.exportOnlyColumnsShown).match(/undefined|object/)) this.share.exportOnlyColumnsShown = 1;

    	//simple trick to allow hiding of any identified column such as contact_id
        if(typeof this.settings.hideColumnNamedId === 'undefined' || this.settings.hideColumnNamedId === true){
        	this.settings.hideColumnNamedId = 'id';
        }else if(this.settings.hideColumnNamedId !== false){
        	this.settings.hideColumnNamedId = this.settings.hideColumnNamedId.toLowerCase();
        }

    	var params, request_obj = {};
    	parse_str(this.init, request_obj);

		// Initial page request should show what user requested, else settings if present.
        // Server will have defaults as well
		if(request_obj.limitRange){
			this.settings.show_per_page = request_obj.limitRange;
        }else if(this.settings.show_per_page){
			request_obj.limitRange = this.settings.show_per_page;
        }else{
            this.settings.show_per_page = SETTINGS_SHOW_PER_PAGE_DEFAULT;
        }

        //pass default sort only if not specified in init
        if(this.layout.orderBy && !request_obj.orderBy){
        	request_obj.orderBy = this.layout.orderBy;
        }

		//STORE request string - this will convert orderBy pipe notation to objects
        //2018-10-25: used to do this with field parameters also but no longer
		this.request = this.parseQueryBuild(request_obj);

		//PASS request string - pipe notation gets sent as-is to API
		if(typeof this.observerUpdateRequest === 'function') {
			params = this.observerUpdateRequest(this, request_obj);
		}else{
			params = object_to_query_string(request_obj);
		}
        var self = this;
        //don't call AJAX on embedded datasets @todo - design logic to do this in context
        if(this.depth < 1) min_ajax({
            uri: this.requestURI,
            params: params,
            before: function(xhr){
                xhr.key = rand();
                self.comm.createXHR({
                    key: xhr.key,
                    uri: self.requestURI,
                    params: params,
                });
                self.status = CVT_STAT_INITIAL_LOADING;
            },
            either: function(xhr){
                self.status = CVT_STAT_INITIAL_LOADED;
            },
            success: function(xhr){
                var i;
                if(typeof xhr.response === 'string'){
                    json = JSON.parse(xhr.response);
                    console.log('recognized response as string');
                }else{
                    json = xhr.response;
                }
				self.load_status = CVT_LS_LOADED;
				self.comm.updateXHR(xhr.key, 'status', 200);

				if(self.depth < 1 && self.settings.suppressInitialDatasetLoad){
					//do not load the data
                }else{
					self.dataset = json.dataset;
                }

                if(typeof json.relations !== 'undefined') self.relations = json.relations;

                if(typeof self.observerTransformDataset === 'function'){
                    self.dataset = self.observerTransformDataset(self, self.dataset);
                }

                if(json.structure) self.structure = json.structure;

                self.total_rows = (typeof json.total_rows !== 'undefined' ? parseInt(json.total_rows) : null);

                self.page = (typeof json.page !== 'undefined' && json.page !== null ? parseInt(json.page) : null);

                // determine columns to show
                if(self.request.columnsToShow){
                    self.layout.columnsToShow = (typeof self.request.columnsToShow === 'string' ? self.request.columnsToShow.split('|') : self.request.columnsToShow);
                }else{
                    //let's get all the columns that are present, if nothing is in columnsToShow
                    if(!sizeOf(self.layout.columnsToShow)){
                        // console.log('no $data.layout.columnsToShow declared');
                        if(!self.layout.columnsToShow) self.layout.columnsToShow = [];
                        var source = (self.dataset[0] ? self.dataset[0] : (self.structure ? self.structure : {}))
                        for(i in source){

                            if(self.settings.hideColumnNamedId && i.toLowerCase() === self.settings.hideColumnNamedId) continue;
                            if(self.settings.hidePrimaryKey && self.structure[i] && self.structure[i].primary_key == 1)continue;

                            self.layout.columnsToShow[self.layout.columnsToShow.length] = i;
                        }
                    }
                }

                if(self.request.orderBy){
                    //transformations done above
                    self.layout.orderBy = self.request.orderBy;
                }else{
                    //currently this.layout.orderBy is not read initially and may not reflect the dataset's sort
                    if(!self.layout.orderBy){
                        self.layout.orderBy = [];
                    }
                }
                if(json.validation){
                    self.validation = json.validation;
                }

				//call any observer for created hook
				if(typeof self.observerPostDataLoad === 'function'){
                	// console.log('calling observerPostDataLoad in new promise location');
					self.observerPostDataLoad(self);
				}
			},
            error: function(xhr){
                // handle this
                self.load_status = CVT_LS_ERROR;
                self.comm.deleteXHR(xhr.key);
                console.log(xhr);
                var error = xhr.response && typeof xhr.response.error !== 'undefined' ? xhr.response.error : 'There was an error submitting your request; see the browser console for more details';
                alert(error);
                throw new Error('Error in your submission; see previous console entry');
            }
        });

		// This is so PHPStorm my IDE shows these methods as used.  Never called :)
		var test = 1;
        if(test === 2){
        	this.parseQueryBuild();
        	this.methodFocusChanged();
        	this.filterRows();
        	this.cellComponentSelector();
        	this.columnSearchWidgetSelector();
        	this.columnEditSelector();
        	this.filterColumns();
        	this.columnLabel();
        	this.orderBy();
        	this.updateRequest();
        	this.loadRecord();
        	this.navigateRecord();
        	this.navigateDisabled();
        	this.insertOrUpdateRecord();
        	this.cancelRecord();
        	this.datasetCountUI();
        	this.datasetPagination();
        	this.paginate();
        	this.columnWidth();
        	this.columnStyle();
        	this.sortDataset();
        	this.sortDatasetClass();
        	this.ascDesc();
			this.dynamicSortMultiple();
        	this.showForEdit();
        	this.editableValue();
        	this.selectColumns();
        	this.showEditDevice();
			this.showDeleteDevice();
			this.deleteRecord();
			//this.recordEditRows();
        	this.controlToken();
        	this.shareDataset();
        	this.searchHandler();
        	this.getRowClass();
        	this.getColumnClass();
        	this.getPrimary();
        	this.clearSelected();
        	this.clickSelected();
        }
    },
    methods: {
    	parseQueryBuild: function(request){
    		var _request = {};
    		for(var i in request){
    			if(i === 'orderBy' && typeof request[i] === 'string'){
					_request[i] = this.orderBy(request[i]);
				} else {
                	_request[i] = request[i];
                }
            }
            return _request;
        },

		methodFocusChanged: function(column, value_new){
			//this should trigger focusChanged
			this.focus[column] = value_new;
			if(!this.updateURI){
				if(!this.methodFocusChangedWarning){
					this.methodFocusChangedWarning = true;
					console.log('CPM Vue Table version ' + this.version + ': You have changed a form value however there is no `updateURI` available; the Update button will not be shown');
                }
				return false;
			}
			this.focusChanged = true;
        },

		filterRows: function (dataset) {
            /**
             * Unused, not scheduled for implementation yet
             */
            return dataset;
            /*
             * this is a means to filter the rows shown over and above any actual hard pushes or splices to the dataset object.  This can also be made dynamic by referencing this.$data elements
             * note that this won't reduce the count shown right now (e.g. 100 rows)
             * */
			return dataset.filter(function (row, rowIndex, rowSource) {
				return true;
			});
		},

        cellComponentSelector: function(field, value) {
			if(this.layout && this.layout.columns && this.layout.columns[field]){
				var layout = this.layout.columns[field];
				if(layout.column_component){
					if(typeof layout.column_component === 'function'){
						return layout.column_component(this, field, value);
                    }
					return layout.column_component;
                }
            }
            return 'generic-cell';
        },

        columnSearchWidgetSelector: function(column) {
        	if(this.layout.columns && this.layout.columns[column]){
        		var layout = this.layout.columns[column];
				if(layout.search_widget){
					return 'search-widget-' + layout.search_widget;
				} else if(layout.data_range){
					return 'search-widget-dropdown';
				}
            }
            if(this.structure && this.structure[column]){
        		if(this.structure[column].data_range){
        			return 'search-widget-dropdown';
				}
            }
        	return 'search-widget-generic';
        },

		columnEditSelector: function(column) {
			//if uneditable overall, we want to do display over input widgets
			var uneditableOverall = (this.settings && this.settings.uneditable);

            if(this.relations[column] && this.relations[column].dataset.length){
            	return 'generic_select';
            }

            if(this.layout.columns && this.layout.columns[column]) {
				var layout = this.layout.columns[column];
				if(layout.uneditable) {
					// `uneditable` allows a temporary override of any other setting
					return 'generic_display';
				}else if(layout.editWidget) {
					return layout.editWidget;
				}else if(uneditableOverall){
					return 'generic_display';
				}else if(layout.editWidget) {
					// custom edit widget
					return layout.editWidget;
				}else if(layout.edit) {
					if(layout.edit.type) {
						return 'generic_' + layout.edit.type;
					}
				}
			}

			if(uneditableOverall){
            	return 'generic_display';
            }

            if(this.structure && this.structure[column]){
            	var structure = this.structure[column];
            	if(structure.type === 'enum'){
            		return 'generic_select';
                }else if(structure.type.match(/^(text|tinytext|mediumtext|longtext)$/i)){
            		return 'generic_textarea';
                }else if(structure.type.match(/^(date|time|datetime|timestamp)$/i)){
                	if(structure.type === 'timestamp' && this.settings.uneditableTimestampOnUpdate !== false && this.editMode === 'update'){
                		return 'generic_display';
                    }
                	return 'generic_datetime';
                }
            }

            //default
            return 'generic_input';
		},

        filterColumns: function(columns, available) {
			//todo: this only needs to run once unless I update columns requested
			var _columns = {};
			var hideColumnNamedId = this.settings.hideColumnNamedId;
			var hidePrimaryKey = (this.settings.hidePrimaryKey === false);
			if(typeof available === 'undefined') available = false;
            //there must be at least one column specified in columnsToShow - UI must prevent hiding last column.
            for(var n in columns){

				if(hideColumnNamedId && n.toLowerCase() === hideColumnNamedId) continue;
				if(hidePrimaryKey && this.structure[n] && this.structure[n].primary_key == 1)continue;

				if(this.layout.columns && this.layout.columns[n] && this.layout.columns[n].hideColumn) continue;

				//show *available* columns after previous criteria
				if(available){
					_columns[n] = columns[n];
					continue;
                }
                for(var i in this.layout.columnsToShow){
                    if(this.layout.columnsToShow[i].toLowerCase() === n.toLowerCase()){
                        _columns[n] = columns[n]
                        break;
                    }
                }
            }

            if(!sizeOf(_columns)){
                _columns = columns;
            }

			return _columns;
        },

        columnLabel: function(column){
			if(this.layout && this.layout.columns && this.layout.columns[column]){
				if(this.layout.columns[column].label){
				    return this.layout.columns[column].label;
                }
            }
            if(this.settings.rewriteColumnLabels === true || typeof this.settings.rewriteColumnLabels === 'undefined'){
				column = camelCase(snakeCase(column));
            }
			return column;
        },

        orderBy: function(str){
			var j, hash = Math.random().toString(), obj;
			var out = {}, order = 'ASC', previous_key = null;
			str = str.replace(/\\\|/, hash);
			//remove leading and trailing pipes, they are not necessary
			obj = str.replace(/^\|+/,'').replace(/\|+$/, hash).split('|');

			for (j in obj){
				if(!obj[j].length) continue;
				if(obj[j].toUpperCase().match(/^(BIN)*(ASC|DESC)$/)){
					if(typeof out[previous_key] !== 'undefined'){
						//set previous field
						order = obj[j].toUpperCase();
						out[previous_key] = order;
					}
					//reset to default
					order = 'ASC';
					continue;
				}
				previous_key = obj[j].replace(hash, '|');
				out[previous_key] = order;
			}
			return out;
        },

        updateRequest: function(clear, updates){
            var i, name, subj, updated = {}, params = '', request = {}, orderBy;
            if(updates){
				//back-update this.request
            	for(i in updates){
            		if(i === 'orderBy' && typeof updates[i] === 'string'){
            			this.request[i] = this.orderBy(updates[i]);
            			continue;
                    }
                    this.request[i] = updates[i];
                }
            }

            //break association with request
			if(typeof updates === 'undefined') updates = {};
            for(i in this.request){
				if(this.layout.columns[i] && this.layout.columns[i].searchAlias){
					name = this.layout.columns[i].searchAlias;
				}else{
					name = i;
				}
				if(typeof updates[i] !== 'undefined'){
					subj = updated[i] = updates[i];
				}else{
					subj = this.request[i];
				}
				request[name] = subj;
			}

            if(clear){
				//@todo: use a definition of "meta" requests on clear, and keep these
                orderBy = updates.orderBy ? updates.orderBy : (this.request.orderBy ? this.request.orderBy : orderBy);
                //Clear the request, but preserve orderBy and pass it to params also
                //We still want to pass any observerUpdateRequest parameters transparently
                this.request = {};
				if(typeof this.observerUpdateRequest === 'function') {
					params = this.observerUpdateRequest(this, request, updates);
				}else{
					params = object_to_query_string(request);
				}
                if(orderBy){
                	if(typeof orderBy === 'object'){
                		params += (params ? '&' : '') + subserialize('orderBy', orderBy);
                    }else{
                        //not developed
                    }
                	this.request.orderBy = orderBy;
				}
            }else{
				if(typeof this.observerUpdateRequest === 'function') {
					params = this.observerUpdateRequest(this, request, updates);
				}else{
					params = object_to_query_string(request);
                }

                //add new updates that weren't in the request object
                for(i in updates){
                	if(typeof updated[i] === 'undefined'){
                		if(typeof updates[i] === 'object'){
							params += (params ? '&' : '') + subserialize(i, updates[i]);
                			continue;
                        }
                		params += (params ? '&' : '') + i + '=' + encodeURIComponent(updates[i]);
                    }
                }
            }

			var self = this;
			min_ajax({
				uri: this.requestURI,
				params: params,
				before: function(xhr){
					xhr.key = rand();
					self.comm.createXHR({
						key: xhr.key,
						uri: self.requestURI,
						params: params,
					});
					self.status = CVT_STAT_SECONDARY_LOADING;
					self.load_status = CVT_LS_LOADING;
				},
				success: function(xhr){
					self.status = CVT_STAT_SECONDARY_LOADED;
					if (xhr.status === 200) {
						self.load_status = CVT_LS_LOADED;
						console.log('data reloaded');
						self.comm.updateXHR(xhr.key, 'status', 200);

						var json;
						if(typeof xhr.response === 'string'){
							json = JSON.parse(xhr.response);
							console.log('recognized response as string');
						}else{
							json = xhr.response;
						}

						//necessarily clear user's selected values
                        //todo: if we could correlate selected[] to primary keys, we could preserve user selection
						this.selected = [];

						self.dataset = json.dataset;
						//structure is a server-side element, update it
                        if(json.structure) self.structure = json.structure;

                        if(typeof self.observerPostDataReload === 'function'){
                        	self.observerPostDataReload(self);
                        }

						if(typeof self.observerTransformDataset === 'function'){
							self.observerTransformDataset(self, json.dataset);
						}

						self.total_rows = (typeof json.total_rows !== 'undefined' ? parseInt(json.total_rows) : null);
						self.page = (typeof json.page !== 'undefined' && json.page !== null ? parseInt(json.page) : null);
					} else {
						// handle this
						self.load_status = CVT_LS_ERROR;
						self.comm.deleteXHR(xhr.key);
					}
				},
				error: function(xhr){

				}
			});
        },

        loadRecord: function(editSource, request, index, insert){
			var i;
        	//allow modification of request before proceeding with method
			if(typeof this.observerLoadRecord === 'function'){
				this.observerLoadRecord(this, request, index, insert);
			}

			if(window.event && window.event.type === 'click') window.event.preventDefault();

			//index for navigating dataset from modal
			if(typeof index !== 'undefined' && index > -1){
				this.focusIndex = index;
            }else{
				this.focusIndex = null;
            }

        	this.editMode = (insert ? 'insert' : 'update');

			//debug
			console.log('with ' + this.editMode);

        	if(editSource === 'calendar'){
        		$('#DatasetFocus').modal('show');
            }
            if(insert){
        		//todo: we should account for default values based on: previous values, user preference, and structure defaults
				for(i in this.focus){
					if(typeof this.structure[i] !== 'undefined' && typeof this.structure[i].default !== 'undefined' && this.structure[i].default !== null && this.structure[i].default !== 'CURRENT_TIMESTAMP'){
						this.original[i] = this.structure[i].default;
                    }else {
						this.original[i] = '';
					}
				}
				//override with any actual passed values
                for(i in request){
                	if(typeof this.original[i] === 'undefined') continue;
                	this.original[i] = request[i];
                }
            }else{
				//pass by reference
				if(this.focusIndex !== null){
					//Note: errors not handled if index doesn't exist
					this.original = this.dataset[this.focusIndex];
                }else{
					this.original = request;
                }
            }

            //pass by value
            for(i in this.focus){
            	this.focus[i] = this.original[i];
            }
            // by implication modal is now open..
            setTimeout(function(){
				var elems = document.getElementById('DatasetFocus').querySelectorAll('input,textarea,select');
				for(var i in elems){
					try{
						elems[i].focus();
						elems[i].select();
						break;
					}catch(e){
						// tired of seeing this..
						// console.log(e);
						continue;
					}
				}
            }, 250);

			if(typeof this.observerPostLoadRecord === 'function'){
				this.observerPostLoadRecord(this, this.focus, index);
			}
        },

        navigateRecord: function(move){
			if(window.event && window.event.type === 'click'){
				window.event.preventDefault();
			}
			if(typeof this.dataset[this.focusIndex + move] === 'undefined') return false;
            this.loadRecord('dataset', {}, this.focusIndex + move);
        },

        navigateDisabled: function(move){
        	if(this.focusIndex === null || !this.dataset.length) return 'nav-disabled';
            if(move === -1){
            	if(this.focusIndex < 1) return 'nav-disabled';
            }else{
            	if(this.focusIndex + 1 >= this.dataset.length) return 'nav-disabled';
            }
            return '';
        },

        insertOrUpdateRecord: function(_focus){
        	if(typeof _focus === 'undefined') _focus = this.focus;

        	//error checking on values as needed

            //debug
            console.log('submitting with ' + this.editMode);

            if(this.editMode === 'insert' && !this.insertURI){
				console.log('CPM Vue Table version ' + this.version + ': no insertURI value present; record cannot be added');
				return false;
            }

            // AJAX request
            var xhr = new XMLHttpRequest();
            xhr.open('POST', this[this.editMode + 'URI']);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.responseType = 'json';
            var params, self = this;
            xhr.onload = function() {
				var json;
				if(typeof xhr.response === 'string'){
					json = JSON.parse(xhr.response);
					console.log('recognized response as string');
				}else{
					json = xhr.response;
				}

                if (xhr.status === 200) {
                    //update values back to reference
                    if(self.editMode === 'update'){
                        for(var i in _focus){
                        	//2018-07-29 NOTE if _focus is smaller, other fields remain the same
                            self.original[i] = _focus[i];
                        }
                    }else{
                        // todo: adjust dataset count, pagination, etc. based on filters, desire for viewability of
                        // just-added record, etc (even if it doesn't match the current filter)
                        self.dataset.unshift(json.dataset);
                        console.log('record added');
                    }

					if(typeof self.observerPostInsertOrUpdateRecord === 'function'){
						var bin = self.observerPostInsertOrUpdateRecord(self, _focus);
						if(typeof bin === 'boolean'){
							return bin;
						}
					}

					$('#DatasetFocus').modal('hide');
                } else {
                    // handle this
					console.log(xhr);
					var error = json && typeof json.error !== 'undefined' ? json.error : 'There was an error submitting your request; see the browser console for more details';
					alert(error);
					throw new Error('Error in your submission; see previous console entry');
                }
            };

            params = this.editMode + '=' + this.insertOrUpdateParameters(this, _focus);

            // todo: handle user locale insertion in _system parameter

            if(this.observerInsertOrUpdateApplicationParameters){
				//must return object
                var appParams;
            	if(appParams = this.observerInsertOrUpdateApplicationParameters(this, _focus)){
            		params += '&_application=' + JSON.stringify(appParams);
                }
            }
            xhr.send(params);
            // end AJAX request
        },

        cancelRecord: function(focus){
        	if(typeof this.observerBeforeCancelRecord === 'function'){
				var bin = this.observerBeforeCancelRecord(this, this.focus);
				if(typeof bin === 'boolean'){
					return bin;
                }
            }
			this.focusChanged = false;
        },

        insertOrUpdateParameters: function(that, focus){
        	/* allow for custom modification or filtering of passed variables to update/insert */
			if(this.observerInsertOrUpdateParameters){
				return this.observerInsertOrUpdateParameters(that, focus);
            }
			return encodeURIComponent(JSON.stringify(focus))
        },

		datasetCountUI: function(){
            // 1 row
            // 2 rows
            // 35 rows
            // 1-100 of 516 rows
            var row_descriptor = this.row_descriptor, page = this.page, show_per_page = this.settings.show_per_page;
            var present = this.dataset.length, total_rows = this.total_rows;
            if(this.dataset.length === 0){
            	return '';
            }else if(total_rows === null || total_rows <= present){
            	// we don't have any information about total recordset
                return present + ' ' + row_descriptor + (present != 1 ? 's' : '');
            }else{
            	// making the assumption here that paging is equal
                var remainder = parseInt(total_rows % show_per_page);
                var whole_pages = parseInt((total_rows - remainder) / show_per_page);

                return (page * show_per_page + 1) + '-' + (page * show_per_page + (page === whole_pages ? remainder : present)) + ' of ' + number_format(total_rows) + ' ' + row_descriptor + 's';
            }
		},

        datasetPagination: function(){
			var row_descriptor = this.row_descriptor, human_page = this.page + 1, show_per_page = this.settings.show_per_page;
			var present = this.dataset.length, total_rows = this.total_rows;
			var i, last, title, pagination = [];
			if(!this.dataset.length ) return pagination; //unable to process without knowledge of total rows

            //get pages
			var remainder = parseInt(total_rows % show_per_page);
			var whole_pages = parseInt((total_rows - remainder) / show_per_page);
			var pages = whole_pages + (remainder ? 1 : 0);

			if(pages < 2) return {};


			for(i=1; i<=pages; i++){
				if(
					i <= Math.min(3, pages) ||
                    Math.abs(human_page - i) < 2 ||
                    i > pages - 3
                ){
					if(i - last > 1){
						pagination.push({
							spacer: true,
                            title: 'spacer',
                        })
                    }
                    title = ((i-1) * show_per_page + 1) + '-' + ((i-1) * show_per_page + ((i-1) === whole_pages ? remainder : present)) +
                        ' of ' + total_rows + ' ' + row_descriptor + 's';
					pagination.push({
						index: i,
                        active: (i === human_page),
                        title: title,
                        limitStart: (i-1) * show_per_page,
                        limitRange: show_per_page,
                    });
					last = i;
                }

            }
            return pagination;
        },

        paginate: function(node){
            if(node.spacer) return false;
        	var obj = {
        		limitStart: node.limitStart,
                limitRange: node.limitRange,
            };
        	if(this.layout.orderBy){
        		var a = '|', i, j;
        		for(i in this.layout.orderBy){
        			if(this.layout.columns[i] && this.layout.columns[i].sortOn){
        				for(j in this.layout.columns[i].sortOn){
        					a += this.layout.columns[i].sortOn[j] + '|' + this.layout.orderBy[i] + '|';
                        }
                    }else{
        				a += i + '|' + this.layout.orderBy[i] + '|';
                    }
                }
				a = a.replace(/\|$/,'');
                obj.orderBy = a;
            }
        	this.updateRequest(false, obj);
			// todo: this is a hack, really need a callback on the 200 response
        	this.page = node.index - 1;
        },

        sortDataset: function(field, event){
            // todo: handle both string and numeric comparisons ideally based on this.structure

			event.preventDefault();

			// todo: preserve selected records on sort provided we don't need to reload dataset
			this.selected = [];

            if(!this.layout.orderBy){
            	this.layout.orderBy = {};
            }
			//this works OK from Mac and PC
            var build = event.shiftKey;
            //console.log('ctrl:alt:shift = ' + this.ctrlKey + ':' + this.altKey + ':' + this.shiftKey);
            var maxOrderByExpressions = 3;
			var i, j=1, newOrderBy = {}, firstField = '', firstSort = '';

			//clumsy but works
			for(i in this.layout.orderBy){
				firstField = i;
				firstSort = this.layout.orderBy[i];
				break;
			}
			if(build){
				newOrderBy[field] = (firstField === field ? this.ascDesc(firstSort) : 'ASC');
                delete this.layout.orderBy[field];
				for(i in this.layout.orderBy){
					j++;
					if(j > maxOrderByExpressions) break;
					newOrderBy[i] = this.layout.orderBy[i];
                }
                this.layout.orderBy = newOrderBy;
			}else{
				this.layout.orderBy = {};
				this.layout.orderBy[field] = (firstField === field ? this.ascDesc(firstSort) : 'ASC');
			}
            //more clumsiness, but..
            var col, a = [], b = [];
            for(i in this.layout.orderBy){
            	if(this.layout.columns[i] && this.layout.columns[i].sortOn){
            		for(j in this.layout.columns[i].sortOn){
						col = this.layout.columns[i].sortOn[j];
            			a.push((this.layout.orderBy[i].match(/(BIN)*DESC/) ? '-' : '') + col);
            			b.push(typeof this.structure[col] !== 'undefined' ? this.structure[col].type : 'char');
                    }
                }else {
					a.push((this.layout.orderBy[i].match(/(BIN)*DESC/) ? '-' : '') + i);
					b.push(typeof this.structure[i] !== 'undefined' ? this.structure[i].type : 'char');
                }
            }
            //do this initially even if we load a completely different dataset (below).  It gives a visible cue to user.
			this.dataset.sort(this.dynamicSortMultiple(a, b));

			var orderBy = '|';
			for(i in a){
				orderBy += a[i].replace(/^-/,'') + '|' + (a[i].match(/^-/) ? 'DESC' : 'ASC') + '|';
			}
			orderBy = orderBy.replace(/\|+$/,'');

            if(this.total_rows > this.dataset.length){
				console.log('partial dataset shown; we need to reload');
            	this.updateRequest(false, {orderBy: orderBy});
            }else{
            	this.request.orderBy = this.orderBy(orderBy);
				// todo: I needed this for reorganizing another component view; consider promoting this to `observerPostSort(local|reload)` instead
            	if(typeof this.observerPostLocalSort === 'function'){
					this.observerPostLocalSort(this);
                }
            }
        },

        columnWidth: function(e, column){
        	// https://stackoverflow.com/questions/7478336/only-detect-click-event-on-pseudo-element
			if(e.offsetX < e.target.offsetWidth - 4){
                return; //nothing to do
			}
			console.log('init ' + column);
			e.target.mouseIsDown = true;
			e.target.column = column;

			document.body.onmousemove = columnMouseMove(e.target);
			document.body.onmouseup = columnMouseUp(e.target);
			document.body.style.setProperty('cursor', 'col-resize', 'important');

        	if(!e.target.resizeSet){
				console.log('column set for resize, but document.body needed reset');
				e.target.resizeSet = true;
            }

			var self = this;
            function columnMouseMove(target){
        		return function(){
					if(!target.mouseIsDown) return;
					arguments[0].preventDefault();
        			var w = arguments[0].pageX - target.offsetLeft;
        			target.style.wordWrap = 'normal';
					target.style.width= w + 'px';
					target.style.maxWidth= w + 'px';
					target.style.minWidth= w + 'px';
                    return false;
                }
            }
            function columnMouseUp(target){
            	return function(){
            		if(typeof self.observerPostResizeColumn === 'function'){
                        self.observerPostResizeColumn(self, target);
                    }
					document.body.style.setProperty('cursor', 'inherit');
					target.mouseIsDown = false;
					return false;
                }
            }
        },

        columnStyle: function(column){
            if(this.layout.columns[column] && this.layout.columns[column].width){
            	var w = this.layout.columns[column].width;
            	return 'word-wrap: normal; width: ' + w + 'px; min-width: ' + w + 'px; max-width: ' + w + 'px;';
            }
        },

		sortDatasetClass: function(column){
			var i, j = 0, ident = {};
            /**
             * 2018-09-06 SF:
             * note this is this.layout.orderBy, not this.request.orderBy.  The request may not change from sort to sort
            */
            if(typeof this.layout.orderBy === 'string'){
            	ident = this.parseQueryBuild({'orderBy': this.layout.orderBy}).orderBy;
				console.log('pipe string converted')
            }else{
            	ident = this.layout.orderBy;
            }

			for(i in ident){
				j++;
				if(i.toLowerCase() === column.toLowerCase()){
					return 'column-headings column-sortable-' + column + ' column-sorted-'+ ident[i].toLowerCase() + '-' + j;
				}
			}
			return 'column-headings column-sortable-' + column;
		},

        ascDesc: function(current){
			/* this preserves BINASC and BINDESC, keywords to sort binary */
			if(!current) return 'ASC';
			switch(current.toLowerCase()){
                case 'asc':     return 'DESC';
                case 'binasc':  return 'BINDESC';
                case 'desc':    return 'ASC';
                case 'bindesc': return 'BINASC';
            }
        },

        dynamicSortMultiple: function(props, datatypes) {
        	// https://stackoverflow.com/a/11379791/4127646
			// https://stackoverflow.com/questions/1129216/sort-array-of-objects-by-string-property-value-in-javascript/4760279#4760279
            /* save the arguments object as it will be overwritten
             * note that arguments object is an array-like object
             * consisting of the names of the properties to sort by
             */
            //var props = arguments;
            return function(obj1, obj2) {
                var i = 0, result = 0, numberOfProperties = props.length;
                /* try getting a different result from 0 (equal)
                 * as long as we have extra properties to compare
                 */
                while(result === 0 && i < numberOfProperties) {
                    result = dynamicSort(props[i], datatypes[i])(obj1, obj2);
                    i++;
                }
                return result;
            }
        },

		showForEdit: function(column, column_structure){
        	if(this.settings.hidePrimaryKey && column_structure.primary_key) return false;
        	if(this.settings.hideColumnNamedId && column.toLowerCase() === this.settings.hideColumnNamedId) return false;

        	if(this.layout.columns && this.layout.columns[column] && typeof this.layout.columns[column].hideFromEdit !== 'undefined'){
        		if(typeof this.layout.columns[column].hideFromEdit === 'boolean'){
        			return !this.layout.columns[column].hideFromEdit;
                }else{
        			if(this.editMode === this.layout.columns[column].hideFromEdit) return false;
                }
            }
            if(this.editMode === 'insert' && this.settings.hideTimestampOnInsert !== false  && column_structure.type === 'timestamp'){
        		return false;
            }
            return true;
        },

        editFullRow: function(column){
			return (this.layout.columns && this.layout.columns[column] && this.layout.columns[column].editFullRow)
        },

        editableValue: function(value, column){
        	//Note: these values are in flux and subject to change until we have a coherent system
            /**
             * @todo: depending on the element we may need to return an object (such as id, label from relationship, source, range of options, etc.)  SEE HOW THIS AND columnEditSelector() are going to work on the same level.  It may take that function to tell this function what to do.
             */
			if(this.structure && this.structure[column] && this.structure[column].intent === 'datetime'){
				return createDate(value, 'ymdhis');
            }else if(this.structure && this.structure[column] && this.structure[column].type){
				var type = this.structure[column].type;
				if(type === 'date' && typeof value === 'string' && value.length){
					//we assume the value is valid but MySQL values can be like 2017-08-00 in some cases
                    return createDate(value, 'm/d/Y');
                }
            }
		    return value;
        },

		selectColumns: function(event, column){
        	var n = [], available = true, i, j=0;
        	if(typeof column === 'undefined'){
        		for(i in this.filterColumns(this.structure, available)){
        			n.push(i);
                }
                this.layout.columnsToShow = n;
                event.preventDefault();
                return;
            } else if(typeof column === 'object' && sizeOf(column) === 0){
        		for(i in this.filterColumns(this.structure, available)){
                    n.push(i);
					j++;
                    if(j>=3) break;
                }
				this.layout.columnsToShow = n;
				event.preventDefault();
                return;
            }
        	if(!event.target.checked && sizeOf(this.layout.columnsToShow) < 2){
				event.preventDefault();
                alert('You must have at least one column selected to show');
				return;
            }

            var idx = this.layout.columnsToShow.indexOf(column);
        	if(event.target.checked){
				this.layout.columnsToShow.push(column);
            }else{
				this.layout.columnsToShow.splice(idx, 1);
            }
        },

        showEditDevice: function(){
		    var i, showEditDevice = _true(this.settings.showEditDevice);
		    for(i in this.focus){
		    	if(showEditDevice) return true;
            }
            if(showEditDevice && !this.showEditDeviceWarning){
				this.showEditDeviceWarning = true;
				console.log('CPM Vue Table version ' + this.version + ': settings dictate you wish to `showEditDevice` but you have not configured the `focus` object in data.  See documentation for further information');
            }
            return false;
        },

        showDeleteDevice: function(){
        	if(this.settings.showDeleteDevice && !this.deleteURI){
        		if(!this.showDeleteDeviceWarning){
        			this.showDeleteDeviceWarning = true;
        			console.log('CPM Vue Table version ' + this.version + ': settings dictate you wish to `showDeleteDevice` but you have not specified a `deleteURI`.  See documentation for further information');
                }
                return false;
			}
        	return this.settings.showDeleteDevice;
        },

        deleteRecord: function(editSource, index){
			if(!confirm('Are you sure you want to delete this record?')) return false;
			{
                var self = this;
                min_ajax({
                	uri: self.deleteURI,
                    params: 'delete=' + encodeURIComponent(JSON.stringify(self.dataset[index])),
                    success: function(xhr){
						//update values back to reference
						console.log(xhr.response);
						self.dataset.splice(index, 1);
                    }
                });
			}
        },

        /* 2018-07-11 removed; we have structure coming from BE for all UIs *
        recordEditRows: function(){
        	for(var i in this.structure) return this.structure;
        	var structure = {};
        	for(i in focus){
        		structure[i] = { type: 'unknown' }
            }
            return structure;
        },
        */

        controlToken: function(){
			// simple flexible pass-through function;
			var fn = arguments[0];
			var args = [];
			for(var i in arguments){
				if(i === 0) continue;
				args[i-1] = arguments[i];
				i++;
            }

            if(typeof this[fn] === 'function'){
				this[fn].apply(this, args);
            }else if(typeof this.userConfig[fn] === 'function'){
            	//user config method
                this.userConfig[fn].apply(this, args);
            }else if(typeof window[fn] === 'function'){
            	window[fn].apply(window, args);
            }else{
            	//ideally return or throw error
				console.log('CPM Vue Table version ' + this.version + ': You called pass-through function `' + fn + '` which does not exist in any available scope');
            }
        },

        shareDataset: function(){
            if(!(lastRequest = this.comm.readXHR('last-good'))){
            	alert('Error: unable to retrieve last XHR request');
            	return false;
            }
            if(!this.shareURI){
            	console.log('Error: shareUI has not been defined for this CVT instance');
            	return false
            }
			{
                //add local config values to params for the share link
				var params = lastRequest.params;
				params += '&request=' + encodeURIComponent(lastRequest.uri);
				params += '&share=' + encodeURIComponent(JSON.stringify(this.share));
				params += '&columnsToShow=' + encodeURIComponent(JSON.stringify(this.layout.columnsToShow));
				params += '&orderBy=' + encodeURIComponent(JSON.stringify(this.layout.orderBy));
				params += '&_application=' + JSON.stringify(observerInsertOrUpdateApplicationParameters());
				params = params.replace(/^&+/,'');

				var uri = lastRequest.uri;
				var self = this;
                min_ajax({
                	uri: this.shareURI + '/export',
                    params: params,
                    before: function(xhr){
						xhr.key = rand();
						self.comm.createXHR({
							key: xhr.key,
							uri: uri,
							params: params,
						});
                    },
                    success: function(xhr){
                        var json;
                        if(typeof xhr.response === 'string'){
                            json = JSON.parse(xhr.response);
                            console.log('recognized response as string');
                        }else{
                            json = xhr.response;
                        }
                        console.log('share request returned success');
                        if(json.exportAs === 'xlsx' || json.exportAs === 'csv'){
                            var href = self.shareURI + '/read';
                            href += '?key=' + json.key;
                            href += '&exportAs=' + json.exportAs;
                            href += '&filename=' + json.filename;
                            window.location.href = href;
                        }else if(json.exportAs === 'link'){
                            self.share.link = json.link;
                        }
                    }
                });
            }
        },

        searchHandler: function(){
        	//this needs to be defined more for advanced UI widgets esp. multi-selects
            this.updateRequest();
        },

        getRowClass: function(dataset, index){
        	var str = 'cpm-datarow';
            if(typeof this.observerGridClass === 'function'){
            	//null indicates this is the call for the row
            	str += this.observerGridClass(this, dataset, index, null);
            }
            return str;
        },

        getColumnClass: function(dataset, index, column){
        	var str = '';
			var reg = new RegExp(GENERIC_SQL_NUMBER_FIELDS);
        	//start with system classes; as of 2018-11-01 we have only right-alignment for numbers
            if(typeof column === 'string' && typeof this.structure[column] !== 'undefined' && this.structure[column].type){
                if(this.structure[column].type.match(reg)){
                	str += 'text-right';
                }
            }
			if(typeof this.observerGridClass === 'function'){
				str += (str ? ' ' : '') + this.observerGridClass(this, dataset, index, column);
			}
			return str;
        },

		getPrimary: function(dataset){
			var primary = this.db.getPrimary(this.structure);
			if(!primary || primary.match(',')) return '';

			if(typeof dataset[primary] === 'undefined') return '';
			return dataset[primary];
		},

        clearSelected: function(){
			for(var i in this.selected) this.selected[i] = false;
			this.$forceUpdate();
        },

        clickSelected: function(index){
            if(this.$refs['cvt-selector'] && typeof this.$refs['cvt-selector'].clickListener === 'function'){
				this.$refs['cvt-selector'].clickListener();
            }
        },

        _comm: function(parent) {
            return {
            	XHR: [],
            	createXHR: function(obj){
            		this.XHR.push(obj);
                },
                readXHR: function(method, key){
            		if(method === 'last-good'){
            			for(var i=this.XHR.length - 1; i>=0; i--){
            				if(this.XHR[i].status && this.XHR[i].status === 200){
            					return this.XHR[i];
                            }
                        }
                    }
                    return false;
                },
                updateXHR: function(key, attrib, value){
                    for(var i in this.XHR){
                    	if(this.XHR[i].key === key){
                    		this.XHR[i][attrib] = value;
                        }
                    }
                },
                deleteXHR: function(key){
                	for(var i in this.XHR){
                		if(this.XHR[i].key === key){

                        }
                    }
                }
            }
        },

        _db: function(parent){
        	return{
        		getPrimary: function(structure, string){
        			var i, primary = (typeof string === 'undefined' || string === 'string' ? '' : []);
        			for(i in structure){
        				if(structure[i].primary_key){
        					if(typeof primary === 'string'){
        						primary = (primary ? '|' : '') + structure[i].name;
                            }else {
								primary.push(structure[i].name);
							}
                        }
                    }
                    return primary;
                },
                getLabel: function(structure){
        			var i, label = null;
        			for(var i in structure){
        				if(label = null) label = structure[i].name;
        				if(structure[i].type.match(/char/)){
        					return structure[i].name;
                        }
                    }
                    return label;
                }
            }
        }
	},
    computed: {
    	structureGroups: function(){
    		var i, meta = {}, structureGroups = {}, group = '_generic_', layout;
    		for(i in this.structure){
    			layout = this.layout.columns;
    			if(layout[i] && layout[i].stop){
    				if(layout[i].stop.group){
    					group = layout[i].stop.group;
                    }else{
    					group = i;
                    }
                    structureGroups[group] = {};
                    meta[group] = layout[i].stop;
                }
                if(typeof structureGroups[group] === 'undefined'){
    				structureGroups[group] = {};
					meta[group] = (layout[i] && layout[i].stop ? layout[i].stop : {});
                }
                structureGroups[group][i] = this.structure[i];
            }
            return {
    			groups: structureGroups,
                meta: meta,
            }
        }
    }
});

var cvt = new Vue({
	el: '#cvt-container',
});

try{
	window.dragElement(document.getElementById('DatasetFocus'));
}catch(e){
	console.log('error setting draggable for DatasetFocus')
}

if(false){
	/*

	idea: we still have a class settable-{global|user} etc. but IF there's a function associated with the target by that exact name, it does the params, removes itself, etc.
	@todo: still need to map out all that analytics-settables does, as well as how the hierarchy will display - we have global settings which I don't even have a place for and those could be by page but also by admin type, then user settings, then hard-coded settings.  Eventually too, we'll want to store these settings in a db where CVT could be completely elastic.  The other feature desirable is where an admin could say "I've got this the way I want it, now I want to make these settings the default that everyone else sees.'
	@todo: see analytics-settables.js - perhaps the event type (click, scroll, change) should be dictated by the class as well which means we need to get the elements first and then assign the event listener.

	 */
    var Broadscope = {
        // for more object-like variable pairs and to avoid clogging up the global window.varname ecosystem
        settings_initialized: false,
        new_settable: {
        	components: {
        		cvt: { /* but there may be more than one on a page */
                    default: {
                    	/* --- matching container --- */
						layout: {
							columns: {
								Assigned: {
									width: 154,
								}
							}
						}
						//node = forget it, not needed & data = {this object above} & connector = everything up to and including default

						/* -------------------------- */
                    }
                }
            }
        },
        remote_settable: {"page":{"colwidth_Description":"277","colwidth_Assigned":"154","colwidth_RiskLevel":"101","colwidth_TemplateName":"239","colwidth_Status":"176","colwidth_CRQ":"152","toggleShowCalendarCheckbox":true},"global":[],"error":"false","record_last_edited":"2018-09-14 04:12:02"},
        serverTime: 'September 13, 2018 21:21:49',
    }
}
</script>
