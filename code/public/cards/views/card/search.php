<h1>Fixture Search</h1>

<style>
.selection-block { display: block; padding: 5px 0px; }
.selection-block h4 { display: inline; margin-right: 10px; }
.selection-block .btn { margin-bottom: 4px; }
#competitions a.selected { border: 1px solid red; color: red; background: #fee; }
#clubs a.selected { border: 1px solid blue; color: blue; background: #eef; }
.card-image { position: relative; }
</style>
<script>
function updateResult(data, status) {
	var items = JSON.parse(data);
	$('#results tbody').empty();

	for (k in items) {
		var x = items[k];
		$('#results tbody').append("<tr><td>" + x['date'] + "</td><td>" + x['competition'] + "</td><td>" + x['home']['team'] + "</td><td>" + x['away']['team'] + "</td></tr>");
	}
}

$(document).ready(function () {
	teamMap = [
	<?php foreach ($teams as $team) {
		echo "['${team['club']}',${team['team']},'${team['name']}'],";
	} ?>
	];

	$('.selection-block .btn').click(function() {
		if ($(this).hasClass('selected')) {
			$(this).parent().find('.btn').each(function() {
				$(this).removeClass('selected');
			});

			return;
		}

		$(this).parent().find('.btn').each(function() {
			$(this).removeClass('selected');
		});
		$(this).addClass('selected');

		var url = "";

		$('#clubs a.selected').each(function() {
			url += "&" + $(this).attr('data-key') + "=" + $(this).text();
		});

		var comp = $('#competition-selector').val();
		if (comp != '') url += "&competition=" + comp;

		if (url.length > 0) {
			url = '<?= url('layout=raw', 'searchAJAX') ?>' + url;
			//alert(url);
			$.get(url, updateResult);
		}
	});
});
</script>

<label>Competition
<select id='competition-selector' class='form-control' name='competition'>
	<option value=''>All competitions</option>
<?php foreach ($competitions as $competition) { ?>
	<option <?= (isset($_REQUEST['competition']) and ($competition->name == $_REQUEST['competition'])) ? 'selected' : '' ?>><?= $competition['name'] ?></option>  
<?php } ?>
</select>
</label>

<div class='selection-block' id='clubs'>
	<?php foreach ($clubs as $club) {
		echo "<a class='btn btn-default btn-xs' data-key='club'>${club['name']}</a> ";
	} ?>
</div>

<table class='table' id='results'>
	<thead>
		<tr><th>Date</th><th>Competition</th><th>Home</th><th>Away</th></tr>
	</thead>
	<tbody>
	</tbody>
</table>

<div id='matchcard'>
	<?php if (isset($card['matchcard']['images'])) { ?>
	<ul class='pagination'>
	<?php $ctr=0; foreach ($card['matchcard']['images'] as $imageId) {
		echo "<li><a href='$imageId'>".(++$ctr)."</a></li>\n";	
	} ?>
	</ul>
	<?php } ?>
	<div class='card-image'>
		<img width='350px' src='/cards/image.php?card=2000'/>
		<a><span class='mirror glyphicon glyphicon-repeat'/></a>
		<a><span class='glyphicon glyphicon-repeat'/></a>
	</div>
</div>
