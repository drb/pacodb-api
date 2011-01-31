<?php
/* template head */
if (class_exists('Dwoo_Plugin_sitemap', false)===false)
	$this->getLoader()->loadPlugin('sitemap');
/* end template head */ ob_start(); /* template body */ ;
echo $this->classCall('sitemap', array("xml", 'sitemap'));?>

<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>