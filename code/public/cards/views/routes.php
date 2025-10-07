<?php
  function call($controller, $action) {

    // require the file that matches the controller name
    require_once('controllers/' . $controller . '_controller.php');
		require_once('model/card.php');
		require_once('model/player.php');
		require_once('model/competition.php');
		require_once('model/club.php');
		require_once('model/incident.php');

    // create a new instance of the needed controller
    switch($controller) {
      case 'card':
        $controllerObj = new CardController();
				break;
      case 'player':
        $controllerObj = new PlayerController();
				break;
      case 'page':
        $controllerObj = new PageController();
				break;
    }

		try {
			Log::info("--- Execute: $controller/$action");
			Log::debug("Session: ".json_encode($_SESSION));

			// call the action
			$controllerObj->{ $action }();
			return;
		} catch (RedirectException $e) {
			throw $e;
		} catch (Exception $e) {
			Log::warn("Routing exception:".$e->getMessage());
			if (strstr($e->getMessage(), 'You are not logged in')) {
				call('club','login');
			} else if ($action != 'error') {
				$_REQUEST['error'] = $e->getMessage();
				$_REQUEST['error_full'] = $e;
				call('page','error');
			} else {
				echo "<pre>Unhandled controller error:\n".print_r($e,true)."</pre>";
			}
		}
  }

  // just a list of the controllers we have and their actions
  // we consider those "allowed" values
  $controllers = array(
		'card' => array('index', 'get',  'search', 'searchAJAX', 'lock'),
		'page' => array('home', 'error'),
		'player' => array('profile', 'image', 'number', 'update', 'unplay'),
	);

	if (!isset($controller)) { $keys = array_keys($controllers); $controller = $keys[0]; }
	if (!isset($action)) $action = $controllers[$controller][0];

  // check that the requested controller and action are both allowed
  // if someone tries to access something else he will be redirected to the error action of the pages controller
  if (array_key_exists($controller, $controllers)) {
    if (in_array($action, $controllers[$controller])) {
      call($controller, $action);
    } else {
			$_REQUEST['error'] = "Action ($action) does not exist on controller ($controller)";
      call('page', 'error');
    }
  } else {
		$_REQUEST['error'] = "Controller does not exist";
    call('page', 'error');
  }
?>
