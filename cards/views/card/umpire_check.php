<h3>Are you an official umpire for this match?</h3>

<p>
<em>Official umpires are assigned (typically by an umpiring body) - as opposed to club umpires that are assigned by each team</em>
</p>

<p>
<strong><?= $fixture['competition']." - ".$fixture['home']['team']." v ".$fixture['away']['team'] ?></strong>
</p>

<p>If the match has official umpires:
	<ul>
		<li>Team Captains cannot record penalty (red/yellow) cards</li>
		<li>All players must have shirt number listed on the matchcard</li>
		<li>All players must be added before the match start time</li>
		<li>Changes after an umpire signs the card are unconfirmed</li>
		<li>The umpire must verify the matchcard and sign it</li>
	</ul>
</p>

<p>If you want to just view the card, click 'No'.</p>

<div>
	<a href='<?= urlx()."&official=yes" ?>' class='btn btn-success'>Yes</a>
	<a href='<?= urlx()."&official=no" ?>' class='btn btn-warning'>No</a>
	<a href='<?= url(null,'index', 'card') ?>' class='btn btn-info'>Back</a>
</div>
