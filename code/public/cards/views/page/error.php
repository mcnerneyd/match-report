<div class="alert alert-danger" role="alert">
<i class="fas fa-exclamation-circle"></i>
  <span><?php
		if (isset($_REQUEST['error'])) {
			echo $_REQUEST['error'];

			if (isset($_REQUEST['error_full'])) {
				$e = $_REQUEST['error_full'];
				echo "<!--\n".$e->getTraceAsString()."\n-->\n";
			}
		}
		else echo "Unknown Error";
	?></span>
</div>
