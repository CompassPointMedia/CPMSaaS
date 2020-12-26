<?php
$compile = __FiLE__;

/**
 * so there are the following processes
 *  processes we choose not to fork off and run-and-wait
 *  processes we fork off, get a receipt for, and close
 *  processes that are going to take a long time on the AO side
 *
 * we submit the following:
 *  who we are (in session)
 *  a self-generated hash as a receipt for this process
 *  the process information
 *
 * my process does the following:
 *  creates a receipt of dbid-yourhash-myhash after entering in the database
 *  does one of the following:
 *      runs the process (shouldFork = never)
 *          ideally we want to close the window and snackbar the result to a system notifier
 *          so, this is a simple AJAX and wait call
 *      hands off the process (shouldFork = always)
 *          places the request in another table besides sync_mgr_changelog (because that's only for actual changes made), and add a token
 *          creates a JSON file with basic status information, the id of the request entry, and that it is callable
 *           
 *
 * todo:
 * -----
 * DONE     bring $delay and $duration over into the system for testing - and have them be outside my spec methods
 * SKIP     improve the json fields - make it an nv pair deal
 * DONE     get the cron running and pick up scheduled jobs
 * DONE     remove sleep(10) in automate - this is just to show process
 * DONE     sync_mgr_automation.title and .comments - flesh these out with title mapping to an array in $operations on the BE
 * DONE     minimal error checking (must select a method, etc.)
 * DONE     need better understand of timestamps between client and server, and we also need to only run the cron EXACTLY once over 15 seconds, so have a writable file last_run that I pull to make it bulletproof
 * DONE     status_message field (from AO, or my system)
 * DONE     transaction_receipt (from AO)
 * DONE     work order number - but that makes sync_mgr_changelog redundant
 * tabbed interface with decision tree on 2nd tab - execute will be on the bottom
 * @minimum - have the decision tree JSON show in a box as to what it "should" look like and have this coming from the back end
 *
 *
 * Bug:
 * ---
 * why is the 'started' status never showing - check for any locks that might be happening

 *
 * DEMO A DECISION TREE LOAD
 * have $data coming from backend (by server-side load using PHP)
 *
 * Documentation:
 * -------------
 * explain tightly integrated system ITO S in SOLID
 * explain relationship between _automation and _changelog tables (one to many)
 * how will we _STORE_ automation tasks? how do we identify templates? do we need a _schedule table? I think yes.
 * saving of scheduled operations elsewhere - so we can have a UI showing what will be, but is not.
 *  i.e. in other words, build out the status="scheduled" part of sync_mgr_automation
 *
 *
 * Future/Soon:
 * -----------
 * prevent double submission
 * have sync_mgr be a subset of this
 * build in the option to call a process synchronously
 * all methods should return something, at least error=0
 * garbage collection on log/automation
 * add a field for when process started, when it ended
 * ability to cancel a process
 *
 *
 * Misc:
 * ----
 * [system] we ought to be able to do an update with unique key value present and primary key value absent being interpreted as, "use the unique key instead"
 * de-globalize the timeout
 * figure out lag time in calling the process (does it matter that much though)
 *
 *
 *
 *
 *
 */
?><style>
#FooterWell{
    visibility: hidden !important;
}
.inputwidthauto
{
    width:auto !important;
}
</style>
<div id="app">
    <div id="panel-execute">
        <h4>Multithread Demonstration</h4>
        <div class="row">
            <div class="col-sm-6" style="border: 1px solid #ccc; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <p>Select a process you would like to perform:
                    <select name="process" v-model="process" class="form-control inputwidthauto">
                        <option value="">&lt; Select.. &gt;</option>
                        <option v-for="p in processes" :value="p.name">{{ p.label }}</option>
                    </select>
                </p>
                <p>Simulate how long the process will take in seconds<br>
                    <select name="duration" v-model="duration" class="form-control inputwidthauto">
                        <option v-for="i in 181" v-if="!((i-1)%10)" :value="i - 1">
                            {{ i - 1 ? 'Take ' + (i - 1) + ' seconds' : 'Process in negligible time' }}
                        </option>
                    </select></p>
                <p>Optional: define a delay of the process to start in seconds<br>
                    <select name="delay" v-model="delay" class="form-control inputwidthauto">
                        <option v-for="i in 241" v-if="!((i-1)%15)" :value="i - 1">
                            {{ i - 1 ? 'Start in ' + (i - 1) + ' seconds' : 'Start immediately' }}
                        </option>
                    </select></p>
                <p>Comment: <input class="form-control" v-model="comments" placeholder="(Optional)" /> </p>
                <div class="row">
                    <div class="col-sm-12">
                        <button class="btn btn-sm btn-info" @click="execute()">Execute..</button>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <textarea style="min-height: 250px; background-color: transparent; color: white;" v-if="JSON.stringify(decisions[process], null, 4)" class="form-control" v-model="JSON.stringify(decisions[process], null, 4)">
                </textarea>
            </div>
        </div>
        <!--
        <p>Paste in any JSON desired:
        <textarea class="form-control" rows="3" v-model="config"></textarea>
        </p>
        -->
    </div>
    <div id="panel-list-processes">
        <table class="cpm-datatable table table-condensed small table-striped table-hover">
            <thead v-if="sizeOf(dataset[0])">
            <tr>
                <th>&nbsp;</th>
                <th v-if="!skip(col)" v-for="(val, col) in dataset[0]">{{ col }}</th>
            </tr>
            </thead>
            <tbody>
                <tr v-for="row in dataset">
                    <td @click="remove(row.id)" style="cursor: pointer; text-decoration: underline;">delete..</td>
                    <td :class="cell_class(val, col)" v-if="!skip(col)" v-for="(val, col) in row" :title="val"> {{ output(val, col) }} </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<script>
var reload = true;
var log = false;
var app = new Vue({
	el: '#app',
    data: function(){
		return {
            processes: [
                {
                	name: 'dbServerStandupOracle',
                    label: 'Stand Up Oracle DB Server',
                },
                {
                	name: 'dbServerStandupMssql',
                    label: 'Stand Up MSSQL DB Server',
                },
                {
                	name: 'dbCopy',
                    label: 'Copy a Database',
                },
                {
                	name: 'systemHealthCheck',
                    label: 'DB Health Check',
                },
                {
                	name: 'dbPackIndexes',
                    label: 'Pack DB Indexes',
                }
            ],
            process: '',
            duration: 0,
            config: '{testmode: true}',
            token: '',
            dataset: [],
            delay: 0,
            comments: '',
            status_class: {
            	'file created': '',
                'scheduled': 'secondary',
                'appropriated': 'primary',
                'started': 'success',
                'responding': 'success',
                'not responding': 'warning',
                'failed': 'danger',
                'completed': '', /* or info but it's used; done stuff doesn't need to call as much attention */
            },
            decisions: {
            	dbServerStandupOracle: {
            		params: {
            			volume_size: {
            				type: 'select',
                            range: [ '256M', '512M', '1G', '2G', '4G' ],
                            decisions: {
            					                                //further parameters here on decision node;
                                                                // can be a shared function which receives this position
                                                                // and outputs by context of range
                            },
                            label: 'Select a volume size (required):',
                            required: true,
                            validation: null,                   //no validation req'd besides <select> values
                        },
                        root_username: {                        //example of minimal field config; text input assumed
            				validation: 'root_username',        //custom validation
                        },
                        root_password: {
            				type: 'password',
                            validation: 'root_password',
                        }
                    }
                },
                dbServerStandupMssql: {
            		params: {
            			volume_size: {
            				type: 'select',
                            range: [ '256M', '512M', '1G', '2G', '4G', '8G' ],
                        },
                        server_name: {
            				type: 'text',
                            validation: {
            					ontype: validServerNameSyntax,
                            }
                        }
                    }
                },
                systemHealthCheck: {
            		params: {
            			server_name: {
            				type: 'text',
                            validation: {
            					ontype: validServerNameSyntax,
                                onblur: validServerName,

                            }
                        },
                        actions: {
            				type: 'multiselect',
                            range: ['optionA', 'optionB', 'optionC', 'optionD'],
                        }
                    }
                }
            },
            decision: {},
        }
    },
    methods: {
		execute: function(){
        	//error checking/validation - can this be submitted

            //generate client-side token
            this.token = rand().substring(0, 12);

            if(!this.process){
            	alert('select something to do!');
            	return false;
            }


            //define what success will look like - pop client-side token into the listened-for items

            //make the submission
			// AJAX request  - for now just send everything
			var params = 'execute=' + encodeURIComponent(JSON.stringify(this.$data));
			var self = this;
			min_ajax({
				uri: '/api/sync/automate',
				params: params,
				before: function(xhr){

				},
				either: function(xhr){

				},
				success: function(xhr){
					if(typeof xhr.response === 'string'){
						json = JSON.parse(xhr.response);
						console.log('recognized response as string');
					}else{
						json = xhr.response;
					}

                    console.log(json);
				},
				error: function(xhr){
					// handle this
					console.log(xhr);
					var error = xhr.response && typeof xhr.response.error !== 'undefined' ? xhr.response.error : 'There was an error submitting your request; see the browser console for more details';
					alert(error);
					throw new Error('Error in your submission; see previous console entry');
				}
			});


        },

        remove: function(id){
			console.log('deleting..')
			// AJAX request  - for now just send everything
			var params = 'delete=' + JSON.stringify({id: id});
			var self = this;
			min_ajax({
				uri: '/api/data/delete/automation',
				params: params,
			});
        },

        skip: function(col){
        	var skip = ['token', 'process', 'editor', 'create_time', 'work_order', 'transaction_receipt', 'status_message'];
        	for(var i in skip){
        		if(skip[i] === col) return true;
            }
            return false;
        },

		cell_class: function(val, col){
            if(col !== 'status') return '';
            if(typeof this.status_class[val] === 'undefined' || this.status_class[val] === '') return '';
            return 'bg-' + this.status_class[val];
        },

        output: function(val, col){
			if(val === null) return '';
        	if(col === 'request'){
        		return '(JSON)';
            }else if(col === 'error'){
        		return val ? 'YES' : '';
            }else if(col === 'scheduled_for' || col === 'last_ping'){
            	return createDate(val, 'ymdhis');
            }
            return val;
        },

    },
    created: function () {

    	var self = this;
    	var timesrun = 0;

    	//start an interval to monitor processes
        setInterval(function(){
        	if(!reload) return;
        	timesrun++;
            min_ajax({
                uri: '/api/data/request/automation',
                params: 'orderBy=|id|DESC',
                success: function(xhr){
                    if(log) console.log('reloading dataset on ' + timesrun);
                    self.dataset = xhr.response.dataset;

                },
            });
        }, 2000);
    }
});
function validServerNameSyntax(e){ }
function validServerName(e){ }
</script>