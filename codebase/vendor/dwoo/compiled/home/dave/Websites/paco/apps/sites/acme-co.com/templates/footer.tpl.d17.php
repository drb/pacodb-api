<?php
/* template head */
/* end template head */ ob_start(); /* template body */ ?>	<div>
<footer>
<?php echo $this->scope["site"]["name"];?>. Paco version <?php echo $this->scope["paco"]["version"];?>

</footer>
</body>
</html>
<?php  /* end template body */
return $this->buffer . ob_get_clean();
?>