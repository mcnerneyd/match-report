<!--
<?php print_r($player); ?>
-->
<script>
function capture_file(e) {
	var player = '<?= $player['name'] ?>';
	var club = '<?= $player['club'] ?>';
	var row = e.target.parentElement;
	var inputs = row.getElementsByTagName("INPUT");

	var url = URL.createObjectURL(inputs[0].files[0]);

	$("#userprofile .image img").attr("src", url);
	$("#userprofile .image img").attr("data-changed", true);

	return false;
}

function save(e, player, club) {
	var row = e.target.parentElement.parentElement;
	var inputs = row.getElementsByTagName("INPUT");
	var file = null;

	if ($("#userprofile .image img").attr("data-changed")) {
		file = inputs[0].files[0];
	}

	var formData = new FormData();
	formData.append("site", "<?= site() ?>");
	formData.append("action", "update");
	formData.append("controller", "player");
	formData.append("p", '<?= $player['name'] ?>');
	formData.append("c", '<?= $player['club'] ?>');
	formData.append("n", inputs[1].value);
	formData.append("file", file);

	$("#userprofile .image button").hide();
	$("#userprofile .image progress").show();

	$.ajax({
		type: 'POST',
		url: '<?= 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ?>',
		data: formData,
		processData: false,
		contentType: false,
		success: close,
		error: function (request, status, error) {
			        alert(request.responseText);
							    }
	});

	return true;
}

function capture_click(e) {
	$("#userprofile .image input").val(null).click();
	return false;
}

function close() {
	window.location.href = '<?= $back ?>';
}
</script>

<style>
#userprofile {
	position: relative;
}
#userprofile .image {
	border:1px solid #bbbbbb;
	padding:10px;
	position:relative;
	display:inline-block;
	box-shadow:5px 5px 5px #aaaaaa;
	margin-bottom:20px;
	width:222px;
	height:272px;
}

#userprofile .image>img {
	width:200px;
	height: 250px;
}

#userprofile .image button {
	position:absolute;
	top:15px;
	right:15px;
	width:30px;
	height:30px;
}

#userprofile .image input {
	display:none;
}

#userprofile .image .progress {
	position:absolute;
	top:225px;
	margin:5px;
	border:1px solid white;
	width:190px;
	display:none;
}

#userprofile .details tr td:first-child {
	width:7em;
	height: 2em;
	font-weight: bold;
}

#userprofile .btn-actions {
	margin: 40px 0px;
}

#userprofile .btn-actions .btn {
	width: 8em;
}

#userprofile .fa-times-circle {
	color: red;
	font-size: 120%;
}
</style>

<div id='userprofile'>
	<h1>User Profile</h1>

	<div class='image'>
		<img src='image.php?site=<?= site() ?>&w=200&player=<?= $player['name'] ?>&club=<?= $player['club'] ?>&x=<?= createsecurekey("image".$player['name'].$player['club']) ?>'/>
		<input type="file" accept="image/*;capture=camera" onchange="capture_file(event)"/>
		<button onclick='capture_click(event)'><i class="fa fa-camera" aria-hidden="true"></i></button>
		<div class='progress'>
			<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;"></div>
		</div>
	</div>

	<table class='details'>
		<tr>
			<td>Name</td>
			<td><?= $player['name'] ?></td>
		</tr>
		<tr>
			<td>Club</td>
			<td><?= $player['club'] ?></td>
		</tr>
		<tr>
			<td>Number</td>
			<td><input class='form-control' type='number' value='<?= $player['number'] ?>' size='3'/></td>
		</tr>
	</table>

	<div class='btn-actions'>
		<button class='btn btn-success' onclick="return save(event);">Save</button>
		<a href='<?= $back ?>' class='btn btn-danger'>Cancel</a>
	</div>

<style>
svg {
	shape-rendering: crispEdges;
}
.rule line {
	stroke: #eee;
}
#history {
	position:absolute;
	top: 25px;
	left:300px;
}
#chart {
	font-size:80%;
}
</style>
<div id='history' class='pull-right'>
<h2>Match History</h2>
<div id='chart'></div>

<script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
<script>
var data = [
<?php

	$firstMonth = date('M Y', strtotime($dateRange["start"]));
	$firstDate = date('Y-m-d', strtotime("first day of $firstMonth"));
	$lastMonth = date('M Y', strtotime($dateRange["finish"]));
	$lastDate = date('Y-m-d', strtotime("last day of $lastMonth"));
	foreach ($player['matches'] as $match) {
		echo "{x:'".date('Y-m-d',strtotime($match['date']))."',y:".$match['team']."},";
	}
?>
];

var w = 400, h = 300,
		x = d3.time.scale().domain([new Date('<?= $firstDate ?>'), new Date('<?= $lastDate ?>')]).range([0, w]),
		y = d3.scale.linear().domain([8, 0]).range([h, 0]);

var data2 = [
<?php
	foreach ($player['rank'] as $match) {
		echo "{x:'".date('Y-m-d',strtotime($match['date']))."',y:".$match['top']."},";
	}
?>
];

var data3 = [
<?php
	foreach ($player['rank'] as $match) {
		echo "{x:'".date('Y-m-d',strtotime($match['date']))."',y:".$match['value']."},";
	}
?>
];

var vis = d3.select("#chart")
		.data(data)
	.append("svg:svg")
		.attr("width", w + 80)
		.attr("height", h + 80)
	.append("svg:g")
		.attr("transform","translate(20,20)");

var rules = vis.selectAll("g.rule")
		.data(x.ticks(d3.time.month))
	.enter().append("svg:g")
		.attr("class","rule");

   rules.append("svg:line")
    .attr("x1", x).attr("y1", 0)
    .attr("x2", x).attr("y2", h - 1);

   rules.append("svg:line")
    .attr("class", function(d) { return d ? null : "axis"; })
    .data(y.ticks(8))
    .attr("x1", 0).attr("y1", y)
    .attr("x2", w + 10).attr("y2", y);

   rules.append("svg:text")
    .attr("x", x)
    .attr("y", h + 15)
    .attr("dy", ".71em")
    .attr("text-anchor", "middle")
    .text(d3.time.format("%b"));

   rules.append("svg:text")
    .data(y.ticks(8))
    .attr("y", y)
    .attr("x", -10)
    .attr("dy", ".35em")
    .attr("text-anchor", "end")
    .text(y.tickFormat(5));

var vl = d3.svg.line()
		.x(function(d) { return x(new Date(d.x)); })
		.y(function(d) { return y(d.y); })
		.interpolate("step-before");

	vis.selectAll("circle.line")
		 .data(data)
	 .enter().append("svg:rect")
		 .attr("class", "line")
		 .attr("x", function(d) { return x(new Date(d.x))-3; })
		 .attr("y", function(d) { return y(d.y)-3; })
		 .attr("width", 6)
		 .attr("height", 6)
		 .attr("fill", "#eef")
		 .attr("stroke", "#88d")

	vis.selectAll("g")
		.append("path")
			.data(data2)
			.attr("fill", "none")
			.attr("stroke", "#d88")
			.attr("stroke-width", "2")
			.attr("d", vl(data2));
</script>

<style>
.rating { font-size: 30pt; font-weight: bold; float: left; padding-right: 10px; margin-top: -10px; margin-bottom: -10px; }
</style>

<div id='rating'>
	<h4>Rating</h4>
	<span class='rating'><?= $player['current'] ?></span>
	<p><strong>Explanation</strong> <?= $player['explain'] ?></p>
</div>

<!-- <?php print_r($player); ?> -->

		<table class='table'>
			<tr>
				<th>Date</th><th>Team</th><th></th><th>Opposition</th><th></th>
			<tr>
			<?php
				foreach ($player['matches'] as $match) {
					$cup = false;
					if (isset($match['leaguematch']) && $match['leaguematch'] == 0) $cup = true;
					echo "<tr data-fixture='${match['fixtureid']}' data-incident='${match['incidentid']}'><td>".date('Y-m-d',strtotime($match['date']))."</td>
								<td>".$match['team']."</td>
								<td><span class='label label-success'>${match['venue']}</span></td>
								<td>${match['opposition']}&nbsp;<span class='label label-".($cup?'danger':'warning')."'>${match['competition_code']}</span></td>";

					if (user('admin')) echo	"<td><a href='".url("i=${match['incidentid']}", "unplay", "player")."'><i class='fa fa-times-circle' aria-hidden='true'></i></a></td>";

					echo "</tr>\n";
				}
			?>
		</table>
		<p><span class='label label-danger'>Cup</span> matches are not included in the rating.</p>

</div>	<!-- #history -->
</div>	<!-- #userprofile -->

