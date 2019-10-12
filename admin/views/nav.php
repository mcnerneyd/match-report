	<?php if (Session::get('site')) { ?>
	<nav class='navbar navbar-dark bg-dark navbar-expand-lg fixed-top'>
			<div class='navbar-brand'><?= \Config::get('config.title') ?></div>
			<button type='button' class='navbar-toggler' data-toggle='collapse' data-target='#navBarDropdown' aria-expanded='false'>
				 <span class="navbar-toggler-icon"></span>
			</button>

			<div class='collapse navbar-collapse' id='navBarDropdown'>
				<ul class='navbar-nav'>
					<?php if (\Auth::check()) { ?>
					<li class='nav-item'>
						<a class='nav-link' href='http://cards.leinsterhockey.ie/card/index.php?site=<?= Session::get('site') ?>&controller=card&action=index'>Matches</a>
					</li>
					<?php if (\Auth::has_access('registration.view')) { ?>
					<li class='nav-item dropdown'>
						<a class='nav-link' href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							Registration
						</a>
						<div class='dropdown-menu'>
							<a class='dropdown-item' href='<?= Uri::create('Registration') ?>'>Registrations</a>
							<a class='dropdown-item' href='<?= Uri::create('Registration/Info') ?>'>Club Info</a>
						</div>
					</li>
					<?php } ?>
					<?php } ?>
					<li class='nav-item dropdown'>
						<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							Reports
						</a>
						<div class='dropdown-menu'>
							<a class='dropdown-item' href='<?= Uri::create('Report/Scorers') ?>'>Top Scorers</a>
							<?php if (\Auth::has_access('umpire_reports.view')) { ?>
							<a class='dropdown-item' href='<?= Uri::create('Report/Cards') ?>'>Red/Yellow Cards</a>
							<?php } ?>

							<?php if (\Auth::has_access('system_reports.view')) { ?>
							<a class='dropdown-item' href='<?= Uri::create('Report/Mismatch') ?>'>Mismatch Results</a>
							<a class='dropdown-item' href='<?= Uri::create('Report/RegSec') ?>'>Anomalies</a>
							<?php } ?>
						</div>
					</li>
				</ul>

				<!-- Search box -->
				<form id='search' class="form-inline mr-auto">
						<input type="search" class="form-control mr-sm-2" placeholder="Search Club, Competition, Date or Card/Fixture ID">
						<button class='btn btn-outline-info my-2 my-sm-0' type='submit'><i class="fas fa-search"></i></button>
				</form>

				<!-- Admin Menu -->
				<ul class='navbar-nav'>
					<li class='nav-item'>
						<a class='nav-link disabled' id='help-me'>
							<i class="fas fa-chalkboard-teacher"></i> Help!
						</a>
					</li>
					<?php if (\Auth::has_access('configuration.view')) { ?>
					<li class='nav-item dropdown'>
						<a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
							Admin
						</a>
						<div class="dropdown-menu">
							<a class='dropdown-item' href="<?= Uri::create('competitions') ?>">Competitions</a>
							<a class='dropdown-item' href="<?= Uri::create('clubs') ?>">Clubs</a>
							<div class="dropdown-divider"></div>
							<a class='dropdown-item' href="<?= Uri::create('fixtures') ?>">Fixtures</a>
							<a class='dropdown-item' href="<?= Uri::create('fines') ?>">Fines</a>
							<a class='dropdown-item' href="<?= Uri::create('users') ?>">Users</a>
							<div class="dropdown-divider"></div>
							<a class='dropdown-item' href="<?= Uri::create('Admin/Config') ?>">Configuration</a>
							<a class='dropdown-item' href="<?= Uri::create('Admin/Log') ?>">System Log</a>
						</div>
					</li>
					<?php } ?>
					<?php if (\Auth::check()) { ?>
					<li class='nav-item'>
						<a class='nav-link' href='<?= Uri::create('/Login') ?>'><i class="fas fa-sign-out-alt"></i> Logout</a>
					<?php } else { ?>
						<a class='nav-link' href='<?= Uri::create('/Login') ?>'><i class='fas fa-sign-in-alt'></i> Login</a>
					<?php } ?>
					</li>
				</ul>
			</div>
	</nav>
	<?php } /* $site */ ?>
