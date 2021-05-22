<style>
.month-marker th { margin: 10px 0px; padding: 20px 2px 5px 2px; border-bottom: 2px solid black; }
.result { background: #efe; }
.late { background: #faa; }
.locked { 
	background-image: linear-gradient(135deg, #ddffdd 8.33%, #ffffff 8.33%, #ffffff 50%, #ddffdd 50%, #ddffdd 58.33%, #ffffff 58.33%, #ffffff 100%);
	background-size: 8.49px 8.49px;
}
.signed { background: #dfd; }
.title th { font-size: 150%; padding-top: 20px; }
.title:first-child { margin-top: 0; }
.time { font-size: 80%; color: #aaa; }
.score { font-weight: bold; padding: 0px 8px; }
body { position: absolute; top: 0; bottom: 0; left: 0; right: 0; }
table { width: 100%; }
table tr td { border-top: 1px solid #ddd; padding: 5px 0px; }
tr td:nth-child(1) { width: 0; }
tr td:nth-child(4) { width: 0; padding: 2px 5px; }
tr td:nth-child(4) .label { width: 0; padding: 2px 5px; display: inline !important; }
#fixtures-container {
	position:fixed;
	overflow-y: auto;
	bottom: 10px;
	top: 130px !important;
}
#fixtures tr:first-child {
	margin-top: 0;
	padding-top: 0;
}
.scrollrow {
	text-align: center;
	border: none;
}
</style>

<script>
function loadPage(row) {
	var page = row.data('page');
	if (typeof page === 'undefined') return;
	console.log("Loading page "+ page);
	row.removeData('page');
	row.find("i:first").hide();
	row.find("i.fa-sync-alt").show();

	// results are page<0, fixtures>=0

	$.get('<?= Uri::create('fixtureapi.json') ?>?p=' + page, function(data) {
		if (data) {
			var anchorRow;
			var anchorTop = 0;
			var anchorScrollTopBefore = $('#fixtures-container').scrollTop();

			if (page < 0) {
				anchorRow = row.next();
				data.reverse();	// Items are going in backwards
			} else {
				anchorRow = row.prev();
			}
			var ofs = anchorRow.offset();
			if (typeof ofs != 'undefined') {
				anchorTop = ofs.top;
			}
			console.log(`Loaded page: ${page} ${data.length} entry/s.`);
			for (var i=0;i<data.length;i++) {
				var item = data[i];

				if (item['state'] == 'invalid') continue;

				var fixtureID = item['fixtureID'];
				if ($('#' + fixtureID).length > 0) return;

				var dt = moment(item['datetimeZ']);
				var dtFormat = dt.format('MMMM YYYY');
				var title = `#${fixtureID}:${item['competition']} - ${item['home']} v ${item['away']}`;
				
				 //+ "/" + item['cardId'] 

				if (page < 0) {
					row.after(`<tr id="${fixtureID}" title="${title}"></tr>`);
				} else {
					row.before(`<tr id="${fixtureID}" title="${title}"></tr>`);
				}

				var current = $('#' + fixtureID);
				current.addClass('fixture');
				if (item['state']) {
					current.addClass(item['state']);
				}

				current.data('competition', item['competition']);
				current.data('home', item['home_club']);
				current.data('away', item['away_club']);

				filter(current);

				var tds = `<td data-value="${dt.format()}" class="date">${dt.format('D')}</td>
					<td class="d-none d-md-table-cell time">${dt.format('h:mm')}</td>
					<td class="d-none d-md-table-cell"><span class="badge label-league">${item['competition']}</span></td>
					<td class="d-table-cell d-md-none"><span class="badge label-league">${item['competition-code']}</span></td>
					<td class="d-none d-md-table-cell">${item['home']}`;

				if (item['home_info']['signed']) tds += ' <i class="fas fa-check-square"></i>';
				else if (item['home_info']['locked']) tds += ' <i class="fas fa-lock"></i>';

				tds += '</td>';

				tds += "<td class='d-none d-md-table-cell'>";
				if (item['played'] === 'yes') tds += item['home_score'] + " - " + item['away_score'];
				tds += "</td>";

				tds += '<td class="d-none d-md-table-cell">' + item['away'];
				if (item['away_info']['signed']) tds += ' <i class="fas fa-check-square"></i>';
				else if (item['away_info']['locked']) tds += ' <i class="fas fa-lock"></i>';
				tds += `</td>
				<td class='d-none d-md-table-cell mail-btn'><i class='fa fa-envelope'></i></td>
				<td class="d-md-none">${item['home']} `;

				if (item['played'] === 'yes') tds += "<span class='score'>" + item['home_score'] + "-" + item['away_score'] + "</span> ";
				else tds += "v ";
				tds += item['away'] + '</td>';

				current.append(tds);
				current.data('time', dt);
				current.data('id', fixtureID);

				if (page > 0) {
					var prevDate = null;
					if (current.prev()) {
						prevDate = current.prev().data('time');
					}
					if (!prevDate || dt.format('MMMM YYYY') != prevDate.format('MMMM YYYY')) {
						addMonthYear(current);
					}
				} else {
					if (current.next()) {
						var nextDate = current.next().data('time');
						if (nextDate && dt.format('MMMM YYYY') != nextDate.format('MMMM YYYY')) {
							addMonthYear(current.next());
						}
					}
				}

				if (page < 0) {
					$(window).scrollTop(row.position().top + row.height() + 5);
					$(window).scroll(triggerLoad);
				}
			}

			if (data.length > 0) {
				row.find("i:first").hide();
				row.find("i.fa-sync-alt").show();
				$(".scrollrow").show();
				console.log("Repage: " + page);
				if (page<0) {
					row.data('page', page-1);
				} else {
					row.data('page', page+1);
				}
			} else {
				row.remove();
			}
		} else {
			console.log(`Loaded page: ${page} empty`);
			row.remove();
			if (page < 0) {
				addMonthYear($('#fixtures tr.fixture:first'));
			}
		}

		if (typeof anchorRow !== 'undefined') {
			ofs = anchorRow.offset();
			if (typeof ofs !== 'undefined') {
				var t = anchorTop;
				anchorTop = (ofs.top - anchorTop) + anchorScrollTopBefore;
			}
			$('#fixtures-container').scrollTop(anchorTop);
		}

		//$('#fixtures tr').show();
		triggerLoad();
	});
}

function filter(fixtureRow) {
	var competition = $('#pills-competition').val();
	var club = $('#pills-club').val();
	var show = true;
	if (competition !== "" && competition !== fixtureRow.data('competition')) {
		show = false;
	}
	if (club !== "" && club !== fixtureRow.data('home') && club !== fixtureRow.data('away')) {
		show = false;
	}
	if (show) fixtureRow.show();
	else fixtureRow.hide();
}

function addMonthYear(firstRow) {
	var dt = firstRow.data('time');
	firstRow.before("<tr class='month-marker'><th colspan='20'>" + dt.format('MMMM YYYY') + "</th></tr>");
}

function triggerLoad() {
	$('#fixtures tr.scrollrow:last, #fixtures tr.scrollrow:first').each(function() {
		if (typeof $(this).data('page') === 'undefined') return true;
		var elementTop = $(this)[0].offsetTop;
		var elementBottom = elementTop + $(this).outerHeight();
		var viewportTop = $('#fixtures-container').scrollTop();
		var viewportBottom = viewportTop + $('#fixtures-container').height();
		if (elementBottom > viewportTop && elementTop < viewportBottom) {
			loadPage($(this));
		}
	});
}

function sizeFixtures() {
	var ofs = $('#fixtures-tab').offset();
	$('#fixtures-container').css("left", ofs.left);
	$('#fixtures-container').css("width", $('#fixtures-tab').width());
	$('#fixtures-container').css("top", ofs.top + $('#fixtures-tab').height());
}

$(document).ready(function() {
	// Load initial dataset
	$("#fixtures tr:first").data('page', -1).hide();
	$(".scrollrow i.fa-sync-alt").hide();
	$("#fixtures tr:last").data('page', 0);
	loadPage($("#fixtures tr:last"));
	$('#fixtures-container').scroll(triggerLoad);
	sizeFixtures();
	$('#fixtures-container').focus();

	$('.filter').change(function(evt) {
		$('.fixture').each(function(index) {
			filter($(this));
		});
	});

  $("#fixtures").on("click", ".mail-btn", function(evt) {
		var tr = $(this).closest("tr.fixture");
    window.document.location = "<?= Uri::create("/Card/Sendmail") ?>?id="+tr.attr("id");
		evt.stopPropagation();
	});

  $("#fixtures").on("click", "tr.fixture", function() {
    window.document.location = "<?= url(null, 'get','card') ?>&fid="+$(this).attr("id");
	});
});
</script>

<nav>
	<form class=form-inline' id='fixtures-tab'>
		<label class='col-sm-auto'>Filters:</label>
		<select id='pills-club' class='filter custom-select custom-select-sm col-sm-4'>
			<option selected value="">All Clubs</option>
			<?php foreach ($clubs as $club) echo "<option>$club</option>\n" ?>
		</select>
		<!--select id='pills-team' class='filter custom-select custom-select-sm col-sm-3'>
			<option selected value="">All Teams</option>
		</select-->
		<select id='pills-competition' class='filter custom-select custom-select-sm col-sm-4'>
			<option selected value="">All Competitions</option>
			<?php foreach ($competitions as $competition) echo "<option>$competition</option>\n" ?>
		</select>
	</form>
</nav>

<div id='fixtures-container'>
	<table id='fixtures'>
		<tbody>
			<tr class='scrollrow'>
				<td colspan='100'><i class="fas fa-chevron-up"></i><i class='fas fa-sync-alt fa-spin'></i></td>
			</tr>
			<tr class='scrollrow'>
				<td colspan='100'><i class="fas fa-chevron-down"></i><i class='fas fa-sync-alt fa-spin'></i></td>
			</tr>
		</tbody>
	</table>
</div>
