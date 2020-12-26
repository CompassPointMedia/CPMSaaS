<style>
    div.sample-view, .sample-view pre{
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-weight: lighter;
    }
    .sample-view h3 {
        font-weight: lighter;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    }
    .sample-view pre {
        color: lightblue;
    }
    .sample-view {
        margin: 10px 50px;
        padding: 20px;
        border: 1px solid #CCC;
    }
</style>
<div class="sample-view" style="">

    <h3>Hello! I am a test view</h3>

    I think I am: <?php echo get_class($this);?> (i.e. $this -- see last thing for full definition)<br />
    <br />
    My defined variables are (any of which I can display just using ${var_name}):
    <?php pre(get_defined_vars());?><br />

    Example of $renderVars:<br />
    <?php pre($renderVars); ?><br />

    I should now have the \App\Libraries\Store connections:<br />
    <?php pre(\App\Libraries\ConnectionStore::$dbMaster);?><br />
    I should be able to use the connections as I wish:<br />
    <?php

    $query = $dbMaster->query("SELECT * FROM sys_login order by create_time DESC LIMIT 10");
    $results = $query->getResultArray();
    pre($results);
    ?><br />


    Finally (this is a long one), my class has the following definition for `$this`:<br />
    <?php pre($this);?>

</div>
