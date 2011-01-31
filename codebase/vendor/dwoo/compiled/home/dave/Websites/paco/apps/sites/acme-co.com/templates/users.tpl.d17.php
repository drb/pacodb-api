<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
if (class_exists('Dwoo_Plugin_bucket', false)===false)
	$this->getLoader()->loadPlugin('bucket');
/* end template head */ ob_start(); /* template body */ ;
echo Dwoo_Plugin_include($this, "header.tpl", null, null, null, '_root', null);?>


<h2><?php echo $this->scope["page"]["title"];?></h2>

<h3>Our staff</h3>

<?php $this->addStack("bucket", array("names", null, "".(isset($this->scope["request"]["ages"]) ? $this->scope["request"]["ages"]:null)."", null, "age:asc, name:desc"));?>

<?php 
$_fh0_data = (isset($this->scope["names"]) ? $this->scope["names"] : null);
if ($this->isArray($_fh0_data) === true)
{
	foreach ($_fh0_data as $this->scope['name'])
	{
/* -- foreach start output */
?>
	<h4>Name: <?php echo $this->scope["name"]["name"];?></h4>
	<li>Age: <?php echo $this->scope["name"]["age"];?></li>
	<?php if ((isset($this->scope["name"]["sex"]) ? $this->scope["name"]["sex"]:null)) {
?>
		<li>Sex: <?php echo $this->scope["name"]["sex"];?></li>
	<?php 
}?>

<?php 
/* -- foreach end output */
	}
}?>


<?php echo Dwoo_Plugin_include($this, "footer.tpl", null, null, null, '_root', null);?>

<?php $this->delStack();
 /* end template body */
return $this->buffer . ob_get_clean();
?>