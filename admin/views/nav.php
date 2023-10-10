<?php function allowed($key) {
  try {
    return \Auth::has_access($key);
  } catch (Exception $e) {
    Log::error("Access has gone pear shaped again");
    return false;
  }
} ?>
<nav class='navbar navbar-dark bg-dark navbar-expand-lg fixed-top'>
    <div class='navbar-brand'>Leinster Hockey<?php $user = Session::get("user");
      if ($user && $user->section) {
        $sectionTitle = \Config::get('section.title');
        if ($sectionTitle) echo "<span>$sectionTitle</span>";
      } ?></div>
    <button type='button' class='navbar-toggler' data-bs-toggle='collapse' data-target='#navBarDropdown' aria-expanded='false'>
       <span class="navbar-toggler-icon"></span>
    </button>

    <div class='collapse navbar-collapse gap-5' id='navBarDropdown'>
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link' href='/cards/ui/'>Matches</a>
        </li>
        <?php if (allowed('registration.view')) { ?>
        <li class='nav-item dropdown'>
          <a class='nav-link' href="#" class="dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" 
              aria-expanded="false">Registration</a>
          <div class='dropdown-menu'>
            <a class='dropdown-item' href='<?= Uri::create('Registration') ?>'>Registrations</a>
            <a class='dropdown-item' href='<?= Uri::create('Registration/Info') ?>'>Club Info</a>
          </div>
        </li>
        <?php } ?>
        <li class='nav-item dropdown'>
          <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" 
              aria-expanded="false">Reports</a>
          <div class='dropdown-menu'>
            <a class='dropdown-item' href='<?= Uri::create('Report/Scorers') ?>'>Top Scorers</a>
            <a class='dropdown-item' href='<?= Uri::create('Report/Grid') ?>'>Grids</a>
            <?php if (allowed('umpire_reports.view')) { ?>
            <a class='dropdown-item' href='<?= Uri::create('Report/Cards') ?>'>Red/Yellow Cards</a>
            <?php } ?>

            <?php if (allowed('system_reports.view')) { ?>
            <a class='dropdown-item' href='<?= Uri::create('Report/Mismatch') ?>'>Mismatch Results</a>
            <a class='dropdown-item' href='<?= Uri::create('Report/RegSec') ?>'>Anomalies</a>
            <?php } ?>
          </div>
        </li>
      </ul>

      <!-- Search box -->
      <form id='search' class="d-flex flex-grow-1 me-auto">
          <input type="search" class="form-control me-2" placeholder="Search Club, Competition, Date or Card/Fixture ID">
          <button class='btn btn-outline-info' type='submit'><i class="fas fa-search"></i></button>
      </form>

      <!-- Admin Menu -->
      <ul class='navbar-nav'>
        <li class='nav-item'>
          <a class='nav-link'  href="<?= Uri::create('help') ?>" id='help-me'>
            <i class="fas fa-chalkboard-teacher"></i> Help!
          </a>
        </li>
        <?php if (allowed('configuration.view')) { ?>
        <li class='nav-item dropdown'>
          <a href="#" class="nav-link show dropdown-toggle" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
            Admin
          </a>
          <ul class="dropdown-menu">
            <li><a class='dropdown-item' href="<?= Uri::create('competitions') ?>">Competitions</a></li>
            <li><a class='dropdown-item' href="<?= Uri::create('clubs') ?>">Clubs</a></li>
            <li><hr class="dropdown-divider"/></li>
            <li><a class='dropdown-item' href="<?= Uri::create('fixtures') ?>">Fixtures</a></li>
            <li><a class='dropdown-item' href="<?= Uri::create('fines') ?>">Fines</a></li>
            <li><a class='dropdown-item' href="<?= Uri::create('users') ?>">Users</a></li>
            <li><hr class="dropdown-divider"/></li>
            <li><a class='dropdown-item' href="<?= Uri::create('Admin/Config') ?>">Configuration</a></li>
            <li><a class='dropdown-item' href="<?= Uri::create('Admin/Log') ?>">System Log</a></li>
        </ul>
        </li>
        <?php } ?>
        <?php if (\Auth::check()) { ?>
        <li class='nav-item'>
          <a id='logout' class='nav-link' href='<?= Uri::create('/Login') ?>'><i class="fas fa-sign-out-alt"></i> Logout</a>
        <?php } else { ?>
          <a id='login-link' class='nav-link' href='<?= Uri::create('/Login') ?>'><i class='fas fa-sign-in-alt'></i> Login</a>
        <?php } ?>
        </li>
      </ul>
    </div>
</nav>

