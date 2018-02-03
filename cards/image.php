<?php
	ini_set('display_errors',1);
	ini_set('display_startup_errors',1);
	ini_set('max_execution_time', 300); 
	error_reporting(E_ALL);

function debug($msg) { }

require_once('config.php');
require_once('model/connection.php');
require_once('secure.php');

require_once('model/player.php');
require_once('model/card.php');

if (isset($_GET['player'])) {

		securekey("image".$_GET['player'].$_GET['club']);

    $image = Player::getPlayerImage($_GET['player']);

		if (isset($_REQUEST['w'])) {
    	$res = imagecreatefromstring($image);
			$width = imagesx($res);
			$height = imagesy($res);
			$preferredWidth = $_REQUEST['w'];
			$preferredHeight = $preferredWidth * 1.25;
			$newheight = $height * $preferredWidth / $width;

			if ($newheight > $preferredHeight) {
				$newheight = $preferredHeight;
				$height = $preferredHeight * $width / $preferredWidth;
			}

			// Load
			$thumb = imagecreatetruecolor($preferredWidth, $newheight);

			// Resize
			$offset = ($height - $newheight * $width / $preferredWidth) /2;
			imagecopyresized($thumb, $res, 0, 0, 0, $offset, $preferredWidth, $newheight, $width, $height);

			ob_start();
			imagejpeg($thumb);
			$image = ob_get_contents();
			ob_end_clean();
		}

    header("Content-Type: image/jpeg");
		header('Content-Size: '.count($image));
		echo $image;
    return;
}

if (isset($_GET['card'])) {

		//securekey("image".$_GET['card']);

    $image = Card::getImage($_GET['card']);

    header("Content-Type: image/jpeg");
		header('Content-Size: '.count($image));
		echo $image;
    return;
}

echo "Unknown image";

?>
