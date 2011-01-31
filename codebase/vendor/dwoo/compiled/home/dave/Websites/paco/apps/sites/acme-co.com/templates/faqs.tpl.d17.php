<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
if (class_exists('Dwoo_Plugin_bucket', false)===false)
	$this->getLoader()->loadPlugin('bucket');
if (class_exists('Dwoo_Plugin_form', false)===false)
	$this->getLoader()->loadPlugin('form');
/* end template head */ ob_start(); /* template body */ ;
echo Dwoo_Plugin_include($this, 'header.tpl', null, null, null, '_root', null);?>


<h2>FAQs (bucket)</h2>

<?php $this->addStack("bucket", array("faqs", null, "".(isset($this->scope["request"]["limit"]) ? $this->scope["request"]["limit"]:null)."", null, "date_create{:}1"));?>

<?php 
$_fh0_data = (isset($this->scope["faqs"]) ? $this->scope["faqs"] : null);
if ($this->isArray($_fh0_data) === true)
{
	foreach ($_fh0_data as $this->scope['faq'])
	{
/* -- foreach start output */
?>
	<h3><?php echo $this->scope["faq"]["title"];?></h3>
	<p><?php echo $this->scope["faq"]["body"];?></p>
	<p><?php echo $this->scope["faq"]["date_create"];?></p>
<?php 
/* -- foreach end output */
	}
}?>


<div style="padding:10px;border:1px solid gray;">
	<?php echo $this->classCall('form', array("faqs"));?>

</div>

<?php echo Dwoo_Plugin_include($this, 'footer.tpl', null, null, null, '_root', null);?>

<?php $this->delStack();
 /* end template body */
return $this->buffer . ob_get_clean();
?>