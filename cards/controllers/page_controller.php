<?php
  class PageController {
    public function home() {
			if (!user()) {
				require_once('views/page/home.php');
			} else {
				call('cards','index');
			}
    }

    public function error() {
      require_once('views/page/error.php');
    }
  }
?>
