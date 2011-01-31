<?php
/**
 * Extracts bucket data
 *
 */
 
function Dwoo_Plugin_html(Dwoo $dwoo, $block_id)
{
	$value = $dwoo->readVar('html.' . $block_id);	
	return $value;
}
?>
