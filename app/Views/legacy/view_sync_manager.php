<link rel="stylesheet" type="text/css" href="/request_tracking/legacy/css/main.css?r=<?php echo $commitToken;?>"/>
<script>
    /*
    todo: create and document the submission
    todo: have this transfer actually work
    todo: execute button disables, pending spinner till done
    todo: handle the changelog
    todo: poll what's happening and put it in the status window
    todo: reset the UI after completed
    todo: cancel button sends a signal - but this would involve multithreading

    * (move this js function up) integrate the min_ajax function globally - will cut down on lines
    *

    todo:
    todo: when we select a server we need info on that server, esp. my accesses and operations listed in changelog
    todo: when we select a database I need type (myisam), #tables
    todo: differentiate between a table and a view - very important
    todo: show stored procedures - use an <optgroup> for this

     */
</script>
<div class="appsm">

    <h3>Automation and Sync Manager</h3>

    <div class="col-md-6">
        <table class="table table-striped">
            <tr>
                <td class="rowLabel">
                    Work Order (WO#):
                </td>
                <td>
                    <input type="text" v-model="workOrderNumber" class="form-control" placeholder="(optional)" />
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Comments:
                </td>
                <td>
                    <input type="text" v-model="comment" class="form-control" placeholder="(required)" maxlength="100" />
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Source server:
                </td>
                <td>
                    <select v-model="sourceServer.identifier" v-on:change="cascadeFromServer('source', sourceServer.identifier)" class="form-control">
                        <option value="">&lt;Select source server..&gt;</option>
                        <option v-for="node, index in connections">{{ node.identifier }}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Source database:
                </td>
                <td>
                    <select v-model="sourceServer.database" class="form-control" v-on:change="cascadeFromDatabase('source', sourceServer.database)">
                        <option v-if="!sourceServer.identifier" value="">(Select a source server first)</option>
                        <option v-else value="">&lt;Select database from {{sourceServer.identifier}}..&gt;</option>
                        <optgroup v-for="label in groupDatabases('source')" :label="label">
                            <option v-for="db in groupDatabases('source', label)">{{ db }}</option>
                        </optgroup>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Source table(s):
                </td>
                <td>
                    <select v-model="sourceServer.table" class="form-control" multiple>
                        <option v-if="!sourceServer.database" value="">(Select a database first)</option>
                        <option v-else value="">&lt;Select tables from {{ sourceServer.database }}..&gt;</option>
                        <optgroup v-if="in_array('Views', groupTables(sourceServer.database, 'source'))" v-for="label in groupTables(sourceServer.database, 'source')" :label="label">
                            <option v-for="table in groupTables(sourceServer.database, 'source', label)">{{ table }}</option>
                        </optgroup>
                        <option v-else v-for="table in filterTables(sourceServer.tables)">{{ table }}</option>
                    </select>
                </td>
            </tr>
            <tr><td colspan="100%">&nbsp;</td> </tr>
            <tr>
                <td class="rowLabel">
                    Target server:
                </td>
                <td>
                    <select v-model="targetServer.identifier" v-on:change="cascadeFromServer('target', targetServer.identifier)" class="form-control">
                        <option value="">&lt;Select target server..&gt;</option>
                        <option v-for="node, index in connections">{{ node.identifier }}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Target database:
                </td>
                <td>
                    <select v-model="targetServer.database" class="form-control" v-on:change="cascadeFromDatabase('target', targetServer.database)">
                        <option v-if="!targetServer.identifier" value="">(Select a target server first)</option>
                        <option v-else value="">&lt;Select database from {{targetServer.identifier}}..&gt;</option>
                        <optgroup v-for="label in groupDatabases('target')" :label="label">
                            <option v-for="db in groupDatabases('target', label)">{{ db }}</option>
                        </optgroup>
                        <option value="{RBADDNEW}">&lt;New Database..&gt;</option>
                    </select>
                    <div v-if="targetServer.database==='{RBADDNEW}'">
                        <input type="text" class="form-control" v-model="targetServer.newDatabase" placeholder="(Enter new database name)" />
                    </div>
                </td>
            </tr>
            <tr>
                <td class="rowLabel">
                    Target table(s) (optional):
                </td>
                <td>
                    <select v-model="targetServer.table" class="form-control" multiple>
                        <option v-if="!targetServer.database" value="">(Select a database first)</option>
                        <option v-else-if="targetServer.database === '{RBADDNEW}'" value="" :disabled="true">(Adding new DB)</option>
                        <option v-else value="">&lt;Select tables from {{ targetServer.database }}..&gt;</option>
                        <optgroup v-if="in_array('Views', groupTables(targetServer.database, 'target'))" v-for="label in groupTables(targetServer.database, 'target')" :label="label">
                            <option v-for="table in groupTables(targetServer.database, 'target', label)">{{ table }}</option>
                        </optgroup>
                        <option v-else v-for="table in filterTables(targetServer.tables)">{{ table }}</option>
                    </select>

                    <!--
                    <select v-model="targetServer.table" class="form-control" multiple>
                        <option v-if="!targetServer.database" value="">(Select a database first)</option>
                        <option v-else-if="targetServer.database === '{RBADDNEW}'" value="">(Adding new DB)</option>
                        <option v-else value="">&lt;Select table from {{ targetServer.database }}..&gt;</option>
                        <option v-if="targetServer.database !== '{RBADDNEW}'" v-for="table in filterTables(targetServer.tables)">{{ table }}</option>
                    </select>
                    -->
                </td>
            </tr>
            <tr>
                <td class="rowLabel" colspan="100%">
                    <h4>Action:</h4>
                    <p>
                        <label class="checkbox-parent text-normal"><input type="radio" v-model="targetAction" :value="'drop'"  /> Remove target table(s) and replace with source table(s)</label><br />
                        <label class="checkbox-parent text-normal"><input type="radio" v-model="targetAction" :value="'keep'"  /> Keep target table(s) and: </label><br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<label class="checkbox-parent text-normal"><input type="radio" :disabled="targetAction !== 'keep'" v-model="integrate" :value="'data'" /> Integrate data only (match by primary key)</label><br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<label class="checkbox-parent text-normal"><input type="radio" :disabled="targetAction !== 'keep'" v-model="integrate" :value="'structure_data'" /> Integrate structure and data (match by primary key)</label><br />
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label class="checkbox-parent text-normal"><input type="radio" :disabled="targetAction !== 'keep' || !integrate.match('data')" v-model="prevailingData" :value="'source'" /> Source data prevails</label><br />
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<label class="checkbox-parent text-normal"><input type="radio" :disabled="targetAction !== 'keep' || !integrate.match('data')" v-model="prevailingData" :value="'target'" /> Target data prevails</label><br />
                        &nbsp;&nbsp;&nbsp;&nbsp;<label class="checkbox-parent text-normal"><input type="radio" :disabled="targetAction !== 'keep'" v-model="integrate" :value="'structure'" /> Integrate structure only (see documentation) </label><br />
                    </p>
                    <h4>If target table exists:</h4>
                    <p>
                        <label class="checkbox-parent text-normal"><input type="checkbox" v-model="makeBackup" v-bind:true-value="1" v-bind:false-value="0" /> Make a backup named: </label> <input class="form-control" type="text" v-model="backupName" placeholder="{table}_bk{YmdHis}_SM{key}" style="width:50%;" />
                    </p>
                </td>
            </tr>
            <tr>
                <td class="rowLabel" colspan="100%">
                    <label class="checkbox-parent text-normal"><input type="checkbox" v-model="changelogEntry" v-bind:true-value="1" v-bind:false-value="0" /> Notate in changelog </label>
                    <br />
                    <br />
                    <button  class="btn btn-default" v-on:click="executeMoveTableData">Execute..</button>
                </td>
            </tr>
        </table>
    </div>
</div>
<script>
var appsm = new Vue({
    el: '.appsm',
    data: {
    	workOrderNumber: '',
        comment: '',
    	makeBackup: 1,
        backupName: '',
        targetAction: 'drop',
        integrate: 'structure_data',
        prevailingData: 'source',
        changelogEntry: 1,
		connections: [],
        sourceServer: {
        	identifier: '',
            database: '',
            table: [],
            structure: {},
            tables: [],
        },
        targetServer: {
        	identifier: '',
            database: '',
			newDatabase: '',
            table: [],
            structure: {},
            tables: [],
        },
        sys_dbs: ['information_schema', 'performance_schema', 'mysql', 'phpmyadmin', 'sys', 'sync_mgr'],
    },
	created: function(){
		var self = this;
		min_ajax({
			uri: '/api/data/request/sync-manager-servers',
			success: function(xhr){
				self.connections = xhr.response.dataset;
			}
		});

		var test = 16;
		if(test === 17){
			this.cascadeFromServer();
			this.cascadeFromDatabase();
			this.executeMoveTableData();
			this.groupDatabases();
			this.groupTables();
			this.filterTables();
		}
	},
    methods: {
		cascadeFromServer: function(src, identifier){
			this[src + 'Server'].database = '';
			this[src + 'Server'].table = '';
			min_ajax({
				self: this,
				uri: '/api/sync/get_databases/' + identifier,
				success: function(xhr){
					console.log('loaded ' + src + ' structure');
					this.self[src + 'Server'].structure = xhr.response;
				}
			});
        },
		cascadeFromDatabase: function(src, db){
        	//reset selected values, release from newly-built array
        	this[src + 'Server'].table = '';
        	var i, j, a = new Array;
        	for(i in this[src + 'Server'].structure){
        		if(i !== db) continue;
        		for(j in this[src + 'Server'].structure[i].tables){
					a.push(j)
                }
                this[src + 'Server'].tables = a;
        		break;
            }
        },
        executeMoveTableData: function(){
			/**
             * if bad we need to feed (optionally) into an injected notification class
             * if good we need to store the previous XHR - so we need to sinter the _comm object into a common injectable class in this case for history and playbooks
             * we want to get a running poll of what's happening, kind of like a ticker/feed
			 */
			if(this.comment.replace(/\s*/g, '').length < 4){
				alert('Please enter a comment on what you are doing');
				return;
            }
            if(!confirm('Are you sure you want to continue?')){
				return;
            }

			var submit_data = {}, i, j;
			for(i in this.$data){
				if(i === 'connections') continue;
				if(i === 'sourceServer' || i === 'targetServer'){
					submit_data[i] = {};
					for(j in this.$data[i]){
						if(j === 'tables' || j === 'structure') continue;
						submit_data[i][j] = this.$data[i][j];
                    }
                    continue;
                }
				submit_data[i] = this.$data[i];
            }
			var self = this;
			min_ajax({
				uri: '/api/sync/execute_move_table_data',
                params: 'execute=' + JSON.stringify(submit_data),
                success: function(xhr){
					/**
                     * use the returned transaction key to initiate a new poller
                     */
					console.log(xhr);
					if(typeof xhr.response.success === 'string'){
						alert(xhr.response.success);
                    }
                },
                error: function(xhr){
                	console.log(xhr);
                	var error = xhr.response && typeof xhr.response.error !== 'undefined' ? xhr.response.error : 'There was an error submitting your request; see the browser console for more details';
                	alert(error);
					throw new Error('Error in your submission; see previous console entry');
                }
            });
		},
		groupDatabases: function(source, label){
        	var i, a = new Array;
        	if(typeof label === 'undefined'){
        		return ['User DBs', 'System DBs (reserved)'];
            }
            for(i in this[source + 'Server'].structure){
        		if(label === 'User DBs'){
                    if(!in_array(i, this.sys_dbs)){
                    	a.push(i);
                    }
                }else{
                    if(in_array(i, this.sys_dbs)){
                    	a.push(i);
                    }
                }
            }
            return a.sort(function (a, b) {
				return a.toLowerCase().localeCompare(b.toLowerCase());
			});

        },
        groupTables: function(database, source, label){
			if(!database) return;
			var i, a = new Array, hasTables = false, hasViews = false;
			var types = [];
			//as would be for `RBADDNEW` database
			if(typeof this[source + 'Server'].structure[database] === 'undefined') return types;

			if(typeof label === 'undefined'){
				for(i in this[source + 'Server'].structure[database].tables){
					if(!this.filterTables(this[source + 'Server'].structure[database].tables[i].name)) continue;
					if(this[source + 'Server'].structure[database].tables[i].view){
						hasViews = true;
                    }else{
						hasTables = true;
                    }
                }
                if(hasTables || hasViews){
					if(hasTables) types.push('Tables');
					if(hasViews) types.push('Views');
                }
                return types;
            }
			for(i in this[source + 'Server'].structure[database].tables){
				if(!this.filterTables(this[source + 'Server'].structure[database].tables[i].name)) continue;
				if(label === 'Tables' && !this[source + 'Server'].structure[database].tables[i].view){
					a.push(this[source + 'Server'].structure[database].tables[i].name)
				}else if(label === 'Views' && this[source + 'Server'].structure[database].tables[i].view){
					a.push(this[source + 'Server'].structure[database].tables[i].name)
				}
			}
			return a.sort(function (a, b) {
				return a.toLowerCase().localeCompare(b.toLowerCase());
			});
        },
        filterTables: function(tables){
        	if(typeof tables === 'string'){
        		return !tables.match(/_bk[0-9]{8}_SM[a-f0-9]+/);
            }
			var t = [];
			for(var i in tables){
				if(tables[i].match(/_bk[0-9]{8}_SM[a-f0-9]+/)) continue;
				t.push(tables[i]);
            }
			return t;
        },
    }
})
</script>