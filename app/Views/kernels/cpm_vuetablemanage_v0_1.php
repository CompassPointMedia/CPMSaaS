<?php
$compile = __FILE__;
?>
<script>
/**
 *
 * CPM Vue Table Manager - version 0.1.0
 * @author Samuel Fullman
 * @created 2020-12-20
 *
 * A Vue-based table structure creator and manager
 *
 * todo:
 *  DONE    style out the table with the same as the CVT table
 *  DONE    delete becomes null icon
 *  DONE    have a table name
 *  DONE    have a general table comment
 *  DONE    state that type will be innodb - have it be a variable but don't give user the option
 *  DONE    write the creation code
 *  have entry to the new table value to be a popup that closes, or refreshes to "table successfully created - click here to view, or close"
 *  have a "show create table SQL" button - use a modal
 *  explain that id is automatically created:
 *      if you don't specify it
 *      unless your indexes indicate that you want a compound primary key
 *  DONE    explain about the indexes
 *      Y means basic index
 *      PRI means a primary key (you should have two of these ore more)
 *      U means unique value
 *
 *  blur from Field field with something_id and no settings yet, automatically set INT(11) UNSIGNED NULL
 *  have a ^ icon for "add row above this field", title attribute for this and delete row
 *  confirm deletion of non-trivial row
 *  automatically create the creator_id, create_time, editor_id, edit_time with settings like these
 *      [x] track creation - checked by default, on uncheck, alert user "only recommended for very large tables"
 *      [x] track last edit - same; on uncheck, alert user "this is only recommended for historical records or audit values that will likely never be changed, or of course large tables
 *      [ ] include auditing
 *  arrow up or down in fields
 *  tab to new row on final field
 *  ctrl-i = insert a row after the present
 *  ctrl-d = delete current row with confirm()
 *
 * ERROR CHECKING
 *  1. field value legal
 *  2. not blank with other columns non-blank (ignore completely blank rows)
 *  3. type and length out of sync
 *  4. enum/set and no values
 *  5. current_timestamp or other select values on a field that can't be that
 *  6. indexes that violate the rules
 *  what would really be nice is to do a "trial" create table in actual SQL flavor and get back the message that it would give
 *  but alas, that would only show one..
 *
 * ADVANCED
 *  we need to consider the following in creating
 *      are we creating a new table or is there data involved, or are we creating a table /from/ data (in that case we would, say, ignore the column)
 *      what privileges are involved - a user cannot creeate an admin table
 *      on alter, there are things a user should not be able to do
 *      ties in to other fields need to respect layers
 *
 * LATER
 *  *  access to administrator [] checkbox
 *  improve the table_group field to be all names that have been created plus admin plus (common)
 */

/* --------------------------------------
// Temp to finish this out.. this is a sample what would be injected
var injector = {
		fields: [{"Field":"ClientName","Kind":"CHAR","Length":"100","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"PrimaryFirstName","Kind":"CHAR","Length":"50","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"PrimaryLastName","Kind":"CHAR","Length":"50","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"Address","Kind":"CHAR","Length":"75","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"City","Kind":"CHAR","Length":"75","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"State","Kind":"CHAR","Length":"3","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"Country","Kind":"CHAR","Length":"20","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"PrimaryPhone","Kind":"CHAR","Length":"35","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"PhoneType","Kind":"CHAR","Length":"35","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"Comments","Kind":"TEXT","Length":"","Unsigned":false,"Null":false,"Key":"","Default":"","Extra":"","Comment":""},{"Field":"creator_id","Kind":"INT","Length":"11","Unsigned":true,"Null":true,"Key":"","Default":"NULL","Extra":"","Comment":"Sam's CF manually created"},{"Field":"create_time","Kind":"DATETIME","Length":"","Unsigned":false,"Null":true,"Key":"","Default":"CURRENT_TIMESTAMP","Extra":"","Comment":"Sam's CF manually created"},{"Field":"editor_id","Kind":"INT","Length":"11","Unsigned":true,"Null":true,"Key":"","Default":"NULL","Extra":"","Comment":"Sam's CF manually created"},{"Field":"edit_time","Kind":"TIMESTAMP","Length":"","Unsigned":false,"Null":true,"Key":"","Default":"NULL","Extra":"ON UPDATE CURRENT_TIMESTAMP","Comment":"Sam's CF manually created"}],
        tableName: 'todo',
        tableGroup: 'common',
        title: 'Project Todo List',
        description: 'For organizing the multitude of todos that I come up with (which is great) but never get done by category, project and page',
    };
*/

/**
 * so the rules for props are:
 * 1. if you pass a :injector="injector" then it must be declared, or you'll get ReferenceError: injector is not defined
 * 2. the prop is defined as this.$props['injector'] before the data object is returned
 * 3. if you don't pass :injector="injector" to the component, then this.$props['injector'] will be undefined
 */
if (typeof injector === 'undefined') {
	injector = {};
}
</script>
<!-- cvtm-container -->
<span id="cvtm-container"><cvtm :injector="injector"></cvtm></span>

<?php if(false): ?>
<!-- stringize:cvtm -->
<div class="cpm cpm-0-2 container">
    <p>This is a basic table creator and shows how easy creation with VueJS is.  Documentation is at bottom.  This will successfully create a form</p>
    <div>Table group: <input type="text" v-model="tableGroup" class="form-control input" placeholder="See notes below" /></div>
    <div>Table name: <input type="text" v-model="tableName" class="form-control input" size="50" :placeholder="'Technical ' + db + ' name'" /> </div>
    <div>Title: <input type="text" v-model="title" class="form-control input" placeholder="Two to three words" /></div>
    <div>Description:<br />
        <textarea v-model="description" class="form-control" rows="4" placeholder="Instruction for table usage, etc."></textarea></div>
    <p v-if="db==='mysql'">Type will be {{tableEngine}}</p>
    <table class="cpm-datatable table table-condensed table-striped table-hover">
        <thead>
        <tr>
            <th>&nbsp;</th>
            <th>Field</th>
            <th>Type</th>
            <th>Length</th>
            <th>+</th>
            <th>ø</th>
            <th>Key</th>
            <th>Default</th>
            <th>Extra</th>
            <th>Comment</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="(field, i) in fields" :key="i">
            <td class="delete-device" title="Delete this row" v-on:click="removeFieldRow(i)">
                <span class="glyphicon glyphicon-minus-sign"></span>
            </td>
            <td>
                <input type="text" v-model="field.Field" class="form-control input-sm" />
            </td>
            <td>
                <select v-model="field.Kind" class="form-control input-sm" style="width: 150px;">
                    <option value="">&lt;select&gt;</option>
                    <optgroup :label="optgroup" v-for="(types, optgroup) in fieldTypes.mysql">
                        <option v-for="(config, option) in types">{{option}}</option>
                    </optgroup>
                </select>
            </td>
            <td>
                <input type="text" v-model="field.Length" class="form-control input-sm" size="10" />
            </td>
            <td style="text-align: center;">
                <input type="checkbox" v-model="field.Unsigned" />
            </td>
            <td style="text-align: center;">
                <input type="checkbox" v-model="field.Null" />
            </td>
            <td>
                <input type="text" v-model="field.Key" class="form-control input-sm" size="10" />
            </td>
            <td>
                <input type="text" v-model="field.Default" class="form-control input-sm" />
            </td>
            <td>
                <select v-model="field.Extra" class="form-control input-sm" style="width: 150px;">
                    <option value=""></option>
                    <option value="auto_increment">auto_increment</option>
                    <option value="ON UPDATE CURRENT_TIMESTAMP">ON UPDATE CURRENT_TIMESTAMP</option>
                    <option value="DEFAULT SERIAL VALUE">DEFAULT SERIAL VALUE</option>
                </select>
            </td>
            <td><input type="text" v-model="field.Comment" class="form-control input-sm" /></td>
        </tr>
        <tr>
            <td colspan="100%">
                <div class="bottom-toolbar">
                    <button v-on:click="addFieldRow()">Add field</button>
                    <div style="float: right;">
                        <button v-on:click="createTable()">Create</button>
                    <button v-on:click="createTable(1)" title="Only show SQL creation syntax">Show SQL</button>
                    </div>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
    <div>
        <h3>Indexes</h3>
        <p>The <code>Key</code> column is for creating indexes.  Following rules apply:</p>
        <ul>
            <li>You can have multiple indexes on a field; separate multiple indexes by commas.<br />
                (Note, just as in good DB Administration, keep your indexes sensible and few)</li>
            <li><code>Y</code> means create an index on that field</li>
            <li><code>U</code> means create a unique index on that field</li>
            <li><code>myindexname</code> means create an index on that field with that name - the index will contain all fields with the same name</li>
            <li><code>u-myindexname</code> is the same as above, but for a unique index</li>
            <li><code>P</code> or <code>PRI</code> or <code>PRIMARY</code> mean create a primary key; if specified for more than one field a compound primary key will be created</li>
            <li>If you do not specify a primary key, one will be created first named <code>`id` INT(14) AUTO_INCREMENT NOT NULL</code></li>
        </ul>
        <h3>Table group</h3>
        <p>Table group can be any short identifier (30 characters max).  The default value is "common".</p>
    </div>
</div>
<!-- /stringize:cvtm -->
<?php endif;?>
<script language="JavaScript">
	var fieldTypes = {
		// Source: Sequel Pro 1.1.2 - MySQL version unknown
        // range of values and description etc. TBC; for now just use true
		mysql: {
			Numeric: {
				TINYINT : true,
				SMALLINT : true,
				MEDIUMINT : true,
				INT : true,
				BIGINT : true,
				FLOAT : true,
				DOUBLE : true,
				"DOUBLE PRECISION" : true,
				REAL : true,
				DECIMAL : true,
				BIT : true,
				SERIAL : true,
				BOOL : true,
				BOOLEAN : true,
				DEC : true,
				FIXED : true,
				NUMERIC : true,
			},
			Character: {
				CHAR : true,
				VARCHAR : true,
				TINYTEXT : true,
				TEXT : true,
				MEDIUMTEXT : true,
				LONGTEXT : true,
				TINYBLOB : true,
				MEDIUMBLOB : true,
				BLOB : true,
				LONGBLOB : true,
				BINARY : true,
				VARBINARY : true,
				ENUM : true,
				SET : true,
			},
			"Date and Time": {
				DATE : true,
				DATETIME : true,
				TIMESTAMP : true,
				TIME : true,
				YEAR : true,
			},
			Geometry: {
				GEOMETRY : true,
				POINT : true,
				LINESTRING : true,
				POLYGON : true,
				MULTIPOINT : true,
				MULTILINESTRING : true,
				MULTIPOLYGON : true,
				GEOMETRYCOLLECTION : true,
			}
		}
	};

	// Main CVT Manager
	Vue.component('cvtm', {
		props: ['injector'],
		template: <?= stringize('cvtm', $compile)?>,
		data: function() {
			console.log('i am data');
			console.log(this.$props['injector'])
			return {
				db: 'mysql',                    // Flavor of database
                version: null,                  // Version
				tableEngine: 'InnoDB',

				fieldTypes: fieldTypes,
                tableName: this.IM('tableName') || '',
                tableGroup: this.IM('tableGroup') || '',
                title: this.IM('title') || '',
                description: this.IM('description') || '',
				fields: this.IM('fields') || [],
				fieldTemplate: {
					Field: '',
                    // NOTE: this field "Kind" is used because Type in MYSQL `EXPLAIN` is used for all fields Type | Length | Unsigned | Zerofill | Binary
                    // Even though the column header says Type
					Kind: '',
					Length: '',
					Unsigned: false,
					Null: false,
					Key: '',
					Default: '',
					Extra: '',
					Comment: '',
				},

				observerPostTableCreate: null,
			}
		},
		mounted: function() {
			console.log('i am mounted')
		},
		beforeCreate: function() {
			console.log('i am before create')
            this.IM = function(node) {
				if (typeof this.$props['injector'] === 'undefined') return null;
				if (typeof this.$props['injector'][node] === 'undefined') return null;
				return this.$props['injector'][node];
            }
		},
		created: function () {
			console.log('i am created');

			// default to three visible fields
			if (!this.IM('fields')) {
				this.addFieldRow(3);
            }

			var test = 1;
			if (test === 2) {
				this.addFieldRow();
				this.removeFieldRow();
				this.createTable();
				this.IM();
			}
		},
		methods: {
			addFieldRow: function(value) {
				var count = 1;
				if (typeof value === 'number') {
					count = value;
                }
				for (var i=1; i <= count; i++) {
					this.fields.push(typeof value === 'object' ? value : {
                        Field: '',
                        Kind: '',
                        Length: '',
                        Unsigned: false,
                        Null: false,
                        Key: '',
                        Default: '',
                        Extra: '',
                        Comment: '',
                    });
                }
			},
			removeFieldRow: function(i) {
				console.log(i);
				this.fields.splice(i,1);
			},
            createTable: function(trial) {
                if (typeof trial === 'undefined') trial = 0;

				// some level of error checking - see todo above

                params = object_to_query_string({
                	trial: trial,
                	tableGroup: this.tableGroup,
                	tableName: this.tableName,
                    title: this.title,
                    description: this.description,
                    db: this.db,
                    tableEngine: this.tableEngine,
                    fields: this.fields,
                });

                // send the data
				var self = this;
				min_ajax({
					uri: '/api/data/cvt_create',
					params: params,
                    /* --- from CVT, may be useful for this also ---
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
					*/
					success: function(xhr){
						if (xhr.status >= 200 && xhr.status <= 299) {
							// self.load_status = CVT_LS_LOADED;
							// self.comm.updateXHR(xhr.key, 'status', 200);

							console.log('I haz success! table create response');

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

							if(typeof self.observerPostTableCreate === 'function'){
								self.observerPostTableCreate(self);
							}
                        } else {
							// handle this
                            /*
							self.load_status = CVT_LS_ERROR;
							self.comm.deleteXHR(xhr.key);
							*/
						}
					},
					error: function(xhr){
						var json;
						if(typeof xhr.response === 'string'){
							json = JSON.parse(xhr.response);
							console.log('recognized response as string');
						}else{
							json = xhr.response;
						}
						alert('Error' + (json.status_header ? ' ' + json.status_header : '') + ': ' + (json.status_message ? json.status_message : '(unspecified error)'));
					}
				});
            },
		},
		computed: {

		}
	});

	// Kick off the UI
	var cvtm = new Vue({
		el: '#cvtm-container',
	});
</script>
