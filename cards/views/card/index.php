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
	overflow-y: auto;
}
#fixtures tr:first-child {
	margin-top: 0;
	padding-top: 0;
}
.scrollrow {
	text-align: center;
	border: none;
}
#toggle-buttons .btn-block {
	margin-top: 0;
}
</style>

<script>
function loadPage(row) {
	var page = row.data('page');
	console.log("Loading page", page, $('#pills-club').val());
	if (page === undefined) return;
	row.removeData('page');
	row.find("i:first").hide();
	row.find("i.fa-sync-alt").show();

	// results are page<0, fixtures>=0

	$.get('<?= Uri::create('fixtureapi.json') ?>?c='+$('#pills-club').val()+'&p=' + page +'&n=50', function(datax) {
		if (!datax) {
		console.log("No results");
		}
		if (datax && datax['fixtures'].length > 0) {
      		const data = datax['fixtures'];
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
			if (ofs) {
				anchorTop = ofs.top;
			}
			console.log(`Loaded page: ${page} ${data.length} entry/s.`);
			currentDate = moment();

			for (var i=0;i<data.length;i++) {
				var item = data[i];

				var fixtureID = item['id'];
				if ($('#' + fixtureID).length > 0) return;

				var dt = moment(item['datetimeZ']);
				var title = `#${fixtureID}:${item['competition']} - ${item['home']['name']} v ${item['away']['name']}`;
				var key = `${item['section']}.${item['competition']}.${item['home']['name']}.${item['away']['name']}`;
        		if (key) key = key.replace(/ /g, "").toLowerCase();

				const noPlayers = (teamItem) => {
					if (teamItem === undefined) return true;
					if (teamItem['players'] === undefined) return true;
					return teamItem['players'] === 0;
				} 

				const homeError = item['home']['score'] != item['home']['match_score']
				const awayError = item['away']['score'] != item['away']['match_score']
				const lateError = (noPlayers(item['home']) || noPlayers(item['away'])) && currentDate.isAfter(dt.endOf("day"))
				const errorClass = (homeError || awayError || lateError) ? "error" : ""

				const rowStr = `<tr id="${fixtureID}" class="${errorClass}" title="${title}" data-key='${key}' data-result='${item['played']}' data-page='${page}'></tr>`
				if (page < 0) {
					row.after(rowStr);
				} else {
					row.before(rowStr);
				}

				var current = $('#' + fixtureID);
				current.addClass('fixture');

				current.data('competition', item['competition']);
				current.data('home', item['home']['club']);
				current.data('away', item['away']['club']);

				filter(current);

				var tds = `<td data-value="${dt.format()}" class="date">${dt.format('D')}</td>
					<td class="d-none d-md-table-cell time">${dt.format('h:mm')}</td>";

					<?php if (!$_SESSION['section']) { ?> tds += "<td class="d-none d-md-table-cell"><span>${item['section']}</span></td>"; <?php } ?>

					tds += "<td class="d-none d-md-table-cell"><span class="badge label-league">${item['competition']}</span></td>
					<td class="d-table-cell d-md-none"><span class="badge label-league">${item['competition-code']}</span></td>
					<td class="d-none d-md-table-cell">${item['home']['name']}`;

				tds += '</td>';

				tds += "<td class='d-none d-md-table-cell'>";
				if (item['played'] === 'yes') tds += item['home']['score'] + " - " + item['away']['score'];
				tds += "</td>";

				tds += '<td class="d-none d-md-table-cell">' + item['away']['name'];
				tds += `</td>
				<td class='d-none d-md-table-cell mail-btn'><i class='fa fa-envelope'></i></td>
				<td class="d-md-none">${item['home']['name']} `;

				if (item['played'] === 'yes') tds += "<span class='score'>" + item['home']['score'] + "-" + item['away']['score'] + "</span> ";
				else tds += "v ";
				tds += item['away']['name'] + '</td>';

				current.append(tds);
				current.data('time', dt);
				current.data('id', fixtureID);

				if (page < 0) {
					if (current.next()) {
						var nextDate = current.next().data('time');
						if (nextDate && dt.format('MMMM YYYY') != nextDate.format('MMMM YYYY')) {
							addMonthYear(current.next());
						}
					}

					$(window).scrollTop(row.position().top + row.height() + 5);
					$(window).scroll(triggerLoad);
				} else {
					var prevDate = null;
					if (current.prev()) {
						prevDate = current.prev().data('time');
					}
					if (!prevDate || dt.format('MMMM YYYY') != prevDate.format('MMMM YYYY')) {
						addMonthYear(current);
					}
				}
			}

			if (data.length > 0) {
				row.find("i:first").hide();
				row.find("i.fa-sync-alt").show();
				$(".scrollrow").show();
				if (page < 0) {
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

		if (anchorRow) {
			ofs = anchorRow.offset();
			if (typeof ofs !== 'undefined') {
				var t = anchorTop;
				anchorTop = (ofs.top - anchorTop) + anchorScrollTopBefore;
			}
			$('#fixtures-container').scrollTop(anchorTop);
		}

		triggerLoad();
	});
}

function filter(fixtureRow) {
	var competition = $('#pills-competition').val();
	//var club = $('#pills-club').val();
	var show = true;
	if (competition !== "" && competition !== fixtureRow.data('competition')) {
		show = false;
	}
	//if (club !== "" && club !== fixtureRow.data('home') && club !== fixtureRow.data('away')) {
	//	show = false;
	//}
	if (show) fixtureRow.show();
	else fixtureRow.hide();
}

function addMonthYear(firstRow) {
	var dt = firstRow.data('time');
	firstRow.before("<tr class='month-marker'><th colspan='20'>" + (dt ? dt.format('MMMM YYYY') : "-") + "</th></tr>");
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


const resetTable = () => {
  $('#fixtures tr').remove();
  $('#fixtures tbody').append($(`<tr class='scrollrow'>
      <td colspan='100'><i class='fas fa-chevron-up'></i><i class='fas fa-sync-alt fa-spin'></i></td> 
    </tr> 
    <tr class='scrollrow'> 
      <td colspan='100'><i class='fas fa-chevron-down'></i><i class='fas fa-sync-alt fa-spin'></i></td> 
    </tr>`));
	$("#fixtures tr:first").data('page', -1).hide();
	$(".scrollrow i.fa-sync-alt").hide();
	$("#fixtures tr:last").data('page', 0);
	loadPage($("#fixtures tr:last"));
}

$(document).ready(function() {
  $("#pills-club").val('<?= addslashes(\Arr::get($_SESSION, 'club', '')) ?>');

	// Load initial dataset
  resetTable();
	$('#fixtures-container').scroll(triggerLoad);
	sizeFixtures();
	$('#fixtures-container').focus();

  $('#pills-club').change(function(evt) {
    resetTable();
  });

	$('.filter').change(function(evt) {
		$('.fixture').each(function(index) {
			filter($(this));
		});
	});

  $("#fixtures").on("click", ".mail-btn", function(evt) {
		evt.stopPropagation();
		const BASE_URL = "<?= \Config::get('base_url') ?>";
		var tr = $(this).closest("tr.fixture");
		fetch(BASE_URL + "fixtureapi/contact?id=" + tr.attr('id'))
		.then((response) => response.json())
		.then((data) => {
				body = "Link to card: " + BASE_URL + "/card/" + tr.attr('id');
	    	window.open("mailto:" + data.to.join() + "?cc="+data.cc.join()
				+"&subject="+ encodeURIComponent(data.subject)
				+"&body="+ encodeURIComponent(body),
				"_blank");
		});
	});

  $("#fixtures").on("click", "tr.fixture", function() {
    window.document.location = "<?= url(null, 'get','card') ?>&fid="+$(this).attr("id");
	});

	$("#toggle-buttons").on("change", "input", function(evt) {
		if ($("#toggle-buttons input[name='view-results']").is(':checked')) {
			$("#fixtures tr[data-result='yes']").show();
		} else {
			$("#fixtures tr[data-result='yes']").hide();
		}

		if ($("#toggle-buttons input[name='view-fixtures']").is(':checked')) {
			$("#fixtures tr[data-result='no']").show();
		} else {
			$("#fixtures tr[data-result='no']").hide();
		}
	})
});

const entryMap = [
<?php foreach ($entries as $i => $entry) {
	echo "(".$entry[0].",".$entry[1]."),"; 
	if ($i%20 == 19) echo "\n";
	}?>
]

</script>

<form id='fixtures-tab'>
    <select id='pills-club' class='custom-select'>
      <option selected value=''>All Clubs</option>
      <?php foreach ($clubs as $i => $club) echo "<option data-index='{$club['id']}' value=\"{$club['name']}\">{$club['name']}</option>\n" ?>
    </select>
    <select id='pills-competition' class='filter custom-select'>
      <option selected value="">All Competitions</option>
      <?php foreach ($competitions as $i => $competition) {
				$name = $competition['name'];
				if (!$_SESSION['section']) $name .= " (".$competition['section'].")";
				echo "<option data-index='{$competition['id']}' value='".$competition['name']."'>$name</option>\n";
			 } ?>
    </select>
    <div class="btn-group btn-group-toggle" data-toggle="buttons">
      <label class="btn btn-secondary active btn-block">
        <input type="checkbox" name='view-results' checked autocomplete="off"> Results
      </label>
      <label class="btn btn-secondary active btn-block">
        <input type="checkbox" name='view-fixtures' checked autocomplete="off"> Fixtures
      </label>
    </div>
</form>

<div id='fixtures-container' style='position: fixed; bottom: 10px; left: 0; right: 0; top: 95px; margin-top: 20px; border-top: 1px solid lightgray; border-bottom: 1px solid lightgray;'>
  <table id='fixtures'>
    <tbody>
    </tbody>
  </table>
</div>

