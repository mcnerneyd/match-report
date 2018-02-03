<h1>Top Scorers</h1>

<form id='form' method='get'>
	<input type='hidden' name='site' value='<?= site() ?>'/>
	<input type='hidden' name='controller' value='report'/>
	<input type='hidden' name='action' value='scorers'/>

	<label>Club
	<select class='form-control' name='club' onchange='changePage(0);'>
		<option value=''>All clubs</option>
	<?php foreach ($clubs as $club) { ?>
		<option <?= (isset($_REQUEST['club']) and ($club->name == $_REQUEST['club'])) ? 'selected' : '' ?>><?= $club['name'] ?></option>	
	<?php } ?>
	</select>
	</label>

	<label>Competition
	<select class='form-control' name='competition' onchange='changePage(0);'>
		<option value=''>All competitions</option>
	<?php foreach ($competitions as $competition) { ?>
		<option <?= (isset($_REQUEST['competition']) and ($competition->name == $_REQUEST['competition'])) ? 'selected' : '' ?>><?= $competition['name'] ?></option>	
	<?php } ?>
	</select>
	</label>

	<table class='table'>
		<thead>
			<tr>
				<th>Rank</th>
				<th>Player</th>
				<th>Club</th>
				<th>Competition</th>
				<th>Goals</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($scorers as $scorer) { ?>
			<tr>
				<td><?= $scorer['rank'] ?></td>
				<td><?= $scorer['player'] ?></td>
				<td><?= $scorer['club'] ?></td>
				<td><?= $scorer['competition'] ?></td>
				<td><?= $scorer['score'] ?></td>
			</tr>
			<?php } ?>
		</tbody>
	</table>

	<script>
	function changePage(v) {
		var f = document.getElementById('form');
		f.page.value = v;
		f.submit();
	}
	</script>

	<nav>
		<input type='hidden' name='page' value='<?= $page ?>'/>

		<ul class="pager">
			<?php if ($page > 0) { ?>
			<li class="previous"><a href='#' onclick='changePage(<?= $page -1 ?>)'><span aria-hidden="true">&larr;</span> Previous</a></li>
			<?php } ?>
			<li class="next"><a href='#' onclick='changePage(<?= $page +1 ?>)'>Next <span aria-hidden="true">&rarr;</span></a></li>
		</ul>
	</nav>
</form>
