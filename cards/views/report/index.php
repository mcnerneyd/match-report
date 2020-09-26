<h1>Reports</h1>

<ul>
	<li><a href='<?= url(null, 'scorers', 'report') ?>'>Top Scorers</a></li>
	<?php if (user('umpire') or user('admin')) { ?>
	<li><a href='<?= url(null, 'cards', 'report') ?>'>Red/Yellow Card Report</a></li>
	<li><a href='<?= url(null, 'resultsMismatch', 'report') ?>'>Mismatch Results</a></li>
	<?php } ?>
</ul>
