<?php
/* template head */
if (class_exists('Dwoo_Plugin_menu', false)===false)
	$this->getLoader()->loadPlugin('menu');
if (function_exists('Dwoo_Plugin_breadcrumb')===false)
	$this->getLoader()->loadPlugin('breadcrumb');
/* end template head */ ob_start(); /* template body */ ?><!DOCTYPE html>
<html>
<head>
<title><?php echo $this->scope["site"]["name"];?> - <?php echo $this->scope["page"]["title"];?></title>
<meta name="paco-version" content="<?php echo $this->scope["paco"]["version"];?>" />
<meta name="paco-generator" content="<?php echo $this->scope["paco"]["homepage"];?>" />
<link rel="stylesheet" href="/sites/acme-co.com/assets/styles.css" />
</head>
<body>
	<header>
		<nav>
			<?php echo $this->classCall('menu', array("/", "nav"));?>

		</nav>
		<p><?php echo Dwoo_Plugin_breadcrumb($this);?></p>
	</header>
	<div>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>