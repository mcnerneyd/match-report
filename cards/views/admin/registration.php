<style>
.table>tbody>tr>th { border-top: none; }
.btn .glyphicon { vertical-align: -1px; }
</style>
<h1>Adminstration</h1>
<h2>Registrations</h2>

<div class='tab' id='registrations'>

	<table class='table'>
		<tr>
			<th>Batch</th>
			<th>Date</th>
			<th>Club</th>
			<th>Changes</th>
			<th>Removed</th>
			<th></th>
		</tr>
	<?php 
		$deleteButtons = array();
		foreach (array_reverse($registrations) as $registration) { ?>
		<tr>
			<td><?= $registration['batch'] ?></td>
			<td><?= $registration['date'] ?></td>
			<td><?= $registration['name'] ?></td>
			<td><?= $registration['registers'] ?></td>
			<td><?= $registration['deregisters'] ?></td>
			<td>
		<?php if (!in_array($registration['name'], $deleteButtons)) { ?>
			<a class='btn btn-xs btn-danger' data-toggle='confirmation' data-title='Delete registration?' href='<?= url('bx='.$registration['batch'], '', '') ?>'>Delete</a>
		<?php 
		$deleteButtons[] = $registration['name']; 
		} else { ?>
			<a class='btn btn-xs btn-danger disabled'>Delete</a>
		<?php } ?>
			</td>
		</tr>
	<?php } ?>
	</table>
</div>	<!-- #registrations -->
