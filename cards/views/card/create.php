	<h1><?= $competition ?></h1>
	<table class='table' data-club='<?= $club ?>' data-cardid='<?= $matchcardId ?: 0 ?>'>
		<thead>
			<tr><td class='row'>
				<input type='text' class='form-control' placeholder='Unlisted Player / Search' onkeyup='filter(event);'/>
				<span class='input-group-btn'>
					<a onclick='addPlayer(event);' class='btn btn-warning'>Add</a>
				</span>
			</td></tr>
		</thead>

		<tbody>

	<?php
	// --------------------------------------------------------------------
	// List Players

	foreach ($card['players'] as $playerName => $detail) { ?>

		<tr ".($class != null ? " class='{$class}'":"").">
			<td data-card='<?= $card ?>'>
				<span class='number'><?= $detail['number'] ?></span>
				<?= $playerName ?>
				<span class='score'><?= $detail['score'] ?></span>
				<button class='score btn btn-default'><?= $detail['score'] ?></button>
				<button class='card btn btn-default'><span class='glyphicon glyphicon-bookmark'></span></button>
			</td>
		</tr>
	<?php } ?>
		</tbody>
	</table>
</div>
