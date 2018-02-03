<?php
function trace($msg) {
	try {
	$root = dirname(__FILE__);
	file_put_contents($root.'/service.log', $msg."\n", FILE_APPEND); 
	//echo "<pre>$msg</pre>";
	} catch (Exception $e) {
		error_log($e->Message);
	}
}

try {

trace("Test log:".print_r($_REQUEST,true)." ".print_r($_FILES,true));

require_once('config.php');
require_once('model/connection.php');

trace(print_r($_POST, true));

if (isset($_POST['p'])) {

		$club = $_POST['c'];
		$name = $_POST['p'];

		if (count($_FILES) > 0) {
			$file = $_FILES['file'];
			trace(filesize($_FILES['file']['tmp_name']));
			trace(print_r(getimagesize($file['tmp_name']), true));
			$filename = $file['tmp_name'];
			$data = file_get_contents($filename);
			//trace(print_r($data, true));
			$res = imagecreatefromstring($data);
			$width = imagesx($res);
			$height = imagesy($res);
			trace(print_r($res,true));
			$preferredWidth = 100;
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

			trace("size:$width x $height -> $preferredWidth x $newheight ".imagesx($thumb)." x ".imagesy($thumb));

			ob_start();
			imagejpeg($thumb);
			$d = ob_get_contents();
			ob_end_clean();

			trace("d=".$d);

			$db = Db::getInstance();
			$stmt = $db->prepare("insert into image_player (club_id, name, image) 
					select id, '{$name}', ?
					from club where name = '{$club}'");
			$stmt->bindParam(1, $d, PDO::PARAM_LOB);
			$stmt->execute();
			trace(print_r($stmt->errorInfo(),true));

			trace("Image upload complete");
		}

	return;
}


if (isset($_GET['id'])) {
	if (false && !hashCheck($_GET['id'], $_GET['access'])) {
		header("HTTP/1.0 403 Forbidden");
		echo "Image: Access restricted\n";
		return;
	}

    $image = $controller->loadImage($_GET['id']);
	
	if (isset($_GET['r'])) {
		$rotation = ($_GET['r'] + 4)%4;

    	$res = imagecreatefromstring($image['data']);
		$res = imagerotate($res, 90 * $rotation, 0);
		ob_start();
		imagejpeg($res);
		$image['data'] = ob_get_contents();
		ob_end_clean();

		$image['type'] = 'image/jpeg';
		$controller->saveImage($image);
	}

    header("Content-Type: {$image['type']}");
	header('Content-Size: '.count($image['data']));
	echo $image['data'];
    return;
}

if (isset($_GET['p'])) {
    $image = $controller->loadPlayerImage($_GET['p'], "");
    header("Content-Type: image/jpeg");
	header('Content-Size: '.count($image['data']));
	echo $image['data'];
    return;
}

trace("No image can be found");

	} catch (Exception $e) {
		trace("Exception\n".print_r($e, true));
	}
?>

<form method='POST' action='index.php' enctype='multipart/form-data'>
	<input type='hidden' name='action' value='update'/>
	<input type='hidden' name='controller' value='player'/>
	Image: <input type='file' name='file'/><br>
	Player: <input type='text' name='p'/><br>
	Club: <input type='text' name='c'/><br>
	Site: <input type='text' name='site' value='test'/><br>

	<button type='submit'>Upload</button>
</form>


