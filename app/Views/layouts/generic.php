<?php
/**
 * OK, a plethora of things that need to be done here:
 * 1. delete old versions of bootstrap and jquery
 */
// Page-level variables
$account = $account ?? $subdomain ?? '';
$tableTitle = $table['title'] ?? '';
$title = $title ?? 'Compasspoint SAAS' . ($account ? ' - ' . $account : '') . ($tableTitle ? ' - ' . $tableTitle : '');
?>
<!doctype html>
<html>
<head>
    <title><?php echo $title?></title>

    <link rel="icon" type="image/x-Icon" href="/asset/img/favicon.ico" />
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="stylesheet" href="/asset/bootstrap/3.3.6/css/themes/slate/bootstrap.css" type="text/css" />
    <link rel="stylesheet" href="/asset/bootstrap/3.3.6/css/bootstrap-datetimepicker.min.css" type="text/css" />
    <link rel="stylesheet" type="text/css" href="/asset/css/cvt-main.css" />

    <script src="/asset/jquery/2.2.4/jquery.min.js"></script>
    <script src="/asset/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="/asset/bootstrap/3.3.6/js/moment.min.js"></script>
    <script src="/asset/bootstrap/3.3.6/js/bootstrap-datetimepicker.min.js"></script>
    <script src="/asset/js/vue.min.js" type="text/javascript" language="JavaScript"></script>
    <script src="/asset/js/vee-validate.js" type="text/javascript" language="JavaScript"></script>

    <link rel="stylesheet" href="/asset/css/standard.css" type="text/css" media="screen"/>
    <link rel="stylesheet" href="/asset/css/printstyles.css" type="text/css" media="print" />

    <script src="/asset/js/standard.js?r=<?php echo time();?>"></script>
    <script src="/asset/js/tools.js?r=<?php echo time();?>"></script>

</head>
<body>

<?php echo $this->include('partials/menu') ?>

<?php echo $this->renderSection('content') ?>

</body>
</html>