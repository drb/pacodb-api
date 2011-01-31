<?php
/* template head */
if (function_exists('Dwoo_Plugin_include')===false)
	$this->getLoader()->loadPlugin('include');
if (class_exists('Dwoo_Plugin_sitemap', false)===false)
	$this->getLoader()->loadPlugin('sitemap');
/* end template head */ ob_start(); /* template body */ ;
echo Dwoo_Plugin_include($this, 'header.tpl', null, null, null, '_root', null);?>


<h2>Sitemap</h2>

<?php echo $this->classCall('sitemap', array('html', 'sitemap'));?>


<?php echo Dwoo_Plugin_include($this, 'footer.tpl', null, null, null, '_root', null);?>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>