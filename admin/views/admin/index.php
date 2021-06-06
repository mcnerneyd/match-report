<div class='row'>

<div class='col-4'>
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

<div class='col-4'>
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

<div class='col-4'>
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

<div class='col-4'>
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

<div class='col-4'>
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

<div class='col-4'>
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

