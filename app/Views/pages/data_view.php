<?php
/**
 * @author      Samuel Fullman
 * @created     2020-12-15
 *
 * data/view - show data objects (tables) in all their glory with CVT
 */
?>

<?php $this->extend('layouts/generic');?>

<?php $this->section('content');?>

<style type="text/css">
    <?php echo $table['css_config_main'];?>
</style>
<script language="JavaScript" type="text/javascript">
    <?php echo $javascript;?>
</script>
<script language="JavaScript" type="text/javascript">
    <?php echo $config;?>
</script>
<h3><?php echo $table['title'];?></h3>
<p class="gray"><?php echo $table['description'];?></p>
<?php echo $this->include('kernels/cpm_vuetable_v0_2'); ?>

<?php $this->endSection('content');?>
