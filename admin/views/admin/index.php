<div class='row'>

<div class='col-md-4 col-12'>
<div class='card bg-warning'>
  <div class='card-header'>Impersonate</div>
  <div class='card-body'>
    <p>Impersonate allows you to use the system as if you were the impersonated user.</p>

    <form action='<?= Uri::create('User/switch') ?>'>
      <div class='row'>
        <div class='input-group col'>
          <select class='form-control' name='u'>
          <?php foreach ($users as $user) {
    echo "<option value='".$user['username']."'>".$user->getName()."</option>";
} ?>
          </select>
        </div>
      </div>

      <div class='row'>
        <div class='col'>
          <button class='btn btn-primary btn-block'>Impersonate</button>
        </div>
      </div>
    </form>
  </div>
</div>
</div>

<div class='col-md-8 col-12'>
<div class='card'>
  <div class='card-header'>Create Incident</div>
  <div class='card-body'>
    <div class='row'>
      <div class='col'>
      <form method='POST' action='<?= Uri::create('api/1.0/cards') ?>'>

          <div class="form-group row">
            <label for="matchcardid" class="col-4 col-form-label">Matchcard ID</label>
            <div class="col-8">
              <input id="matchcardid" name="matchcardid" type="number" class="form-control">
            </div>
          </div>

          <div class="form-group row">
            <label for="player" class="col-4 col-form-label">Player Name</label>
            <div class="col-8">
              <div class="input-group">
                <div class="input-group-prepend">
                  <div class="input-group-text">
                  <i class="fas fa-running"></i>
                  </div>
                </div>
                <input id="player" name="player" type="text" class="form-control">
              </div>
            </div>
          </div>
          <div class="form-group row">
            <label for="club" class="col-4 col-form-label">Club</label>
            <div class="col-8">
              <input id="club" name="club" type="text" class="form-control">
            </div>
          </div>
          <div class="form-group row">
            <label for="type" class="col-4 col-form-label">Type</label>
            <div class="col-8">
              <select id="type" name="t" required="required" class="custom-select">
                <?php foreach (['Played','Red Card','Yellow Card','Ineligible','Scored','Missing','Postponed','Other','Locked','Reversed','Signed','Number','Late'] as $t) {
                    echo "<option name='key' value='".strtolower($t)."'>$t</option>";
                }?>
              </select>
            </div>
          </div>
          <div class="form-group row">
            <div class="offset-4 col-8">
              <button name="submit" type="submit" class="btn btn-primary">Create</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</div>



<div class='col-md-4 col-12'>
<div class='card'>
  <div class='card-header'>View Logs</div>
  <div class='card-body'>
    <p>Show the system logs.</p>

    <div class='row'>
      <div class='col'>
				  <a href='<?= Uri::create('Admin/Log') ?>' class='btn btn-primary btn-block'>Show Logs</a>
      </div>
    </div>
  </div>
</div>
</div>

<div class='col-md-4 col-12'>
<div class='card'>
  <div class='card-header'>User Management</div>
  <div class='card-body'>
    <p>Create, update or delete users of the matchcard system.</p>

    <div class='row'>
      <div class='col'>
          <a href='<?= Uri::create('User') ?>' class='btn btn-primary btn-block'>Users</a>
      </div>
    </div>
  </div>
</div>
</div>

<!--div class='col-4'>
<div class='card'>
  <div class='card-header'>Touch Registration</div>
  <div class='card-body'>
    <p>Makes sure all the registration files have the correct date assigned to them. This ensures
    that they are processed correctly.</p>

    <div class='row'>
      <div class='col'>
					<a href='<?= Uri::create('Admin/Touch?f=/') ?>' class='btn btn-primary btn-block'>Touch</a>
      </div>
    </div>
  </div>
</div>
</div-->

<div class='col-md-4 col-12'>
<div class='card'>
  <div class='card-header'>Archive Data Files</div>
  <div class='card-body'>
    <p>Archives all incidents, matchcards and registrations into a single zip file. This file is then
    downloaded to your local system.</p>

    <div class='row'>
      <div class='col'>
					<a href='<?= Uri::create('Admin/Archive') ?>' class='btn btn-primary btn-block'>Archive</a>
      </div>
    </div>
  </div>
</div>
</div>

<div class='col-md-4 col-12'>
<div class='card bg-warning'>
  <div class='card-header'>Clean Data Files</div>
  <div class='card-body'>
    <p>Delete all incidents and matchcards from before <?= currentSeasonStart() ?>.
      Also deletes all registration files that are older that this date (leaving
      at least one for each club).
      <i>Please note this is not easily reversed. You are strongly recommended
      to run Archive first.</i>
    </p>

    <div class='row'>
      <div class='col'>
				  <a href='<?= Uri::create('Admin/Clean?d='.currentSeasonStart()->get_timestamp()) ?>' class='btn btn-danger btn-block'>Clean</a>
      </div>
    </div>
  </div>
</div>
</div>

<div class='col-md-4 col-12'>
<div class='card bg-warning'>
  <div class='card-header'>Import</div>
  <div class='card-body'>
    <p>Import a JSON/CSV file to populate core data</p>

    <form action='<?= Uri::create('Admin/Import') ?>' method='POST' enctype="multipart/form-data">
      <div class='row'>
        <div class='input-group col'>
          <input type='file' name='source' id='source'/>
        </div>
      </div>

      <div class='row'>
        <div class='col'>
          <button class='btn btn-primary btn-block'>Import</button>
        </div>
      </div>
    </form>
  </div>
</div>
</div>

</div>   <!-- row -->
