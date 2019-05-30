<style>
.month-marker th { margin: 10px 0px; padding: 20px 2px 5px 2px; border-bottom: 2px solid black; }
.result { background: #efe; }
.late { background: #faa; }
.title th { font-size: 150%; padding-top: 20px; }
.title:first-child { margin-top: 0; }
.time { font-size: 80%; color: #aaa; }
.score { font-weight: bold; padding: 0px 8px; }
table { width: 100%; }
table tr td { border-top: 1px solid #ddd; padding: 5px 0px; }
tr td:nth-child(1) { width: 0; }
tr td:nth-child(4) { width: 0; padding: 2px 5px; }
tr td:nth-child(4) .label { width: 0; padding: 2px 5px; display: inline !important; }
</style>

<script>
function loadPage(row) {
	if ($('#fixtures-table').data('processing')) return;

	var page = row.data('page');
	$('#fixtures-table').data('processing', true);
	$.get('<?= Uri::create('fixtureapi.json') ?>?p=' + page, function(data) {
		if (data) {
			console.log("Loaded page: " + page + " " + data.length + " entry/s");
			if (page < 0) data = data.reverse();
			for (var i=0;i<data.length;i++) {
				var item = data[i];

				if (item['state'] == 'invalid') continue;

				var dt = moment(item['datetimeZ']);
				var fixtureID = item['fixtureID'];

				if (page >= 0) {
					row.before('<tr id="' + fixtureID + '"></tr>');
				} else {
					row.after('<tr id="' + fixtureID + '"></tr>');
				}

				var current = $('#' + fixtureID);

				if (item['state']) {
					current.addClass(item['state']);
				}
				current.addClass('fixture');

				var tds = '<td class="date">' + dt.format('D') + '</td><td class="hidden-xs time">' + dt.format('h:mm') + '</td>';
				tds += '<td class="hidden-xs"><span class="label label-league">' + item['competition'] + '</span></td>';
				tds += '<td class="visible-xs"><span class="label label-league">' + item['competition-code'] + '</span></td>';

				tds += '<td class="hidden-xs">' + item['home'] + '</td>';

				tds += "<td class='hidden-xs'>";
				if (item['played'] === 'yes') tds += item['home_score'] + " - " + item['away_score'];
				tds += "</td>";
				tds += '<td class="hidden-xs">' + item['away'] + '</td>';
				tds += "<td class='hidden-xs'><i class='fa fa-envelope'></i></td>";
				tds += '<td class="visible-xs">' + item['home'] + " ";
				if (item['played'] === 'yes') tds += "<span class='score'>" + item['home_score'] + "-" + item['away_score'] + "</span> ";
				else tds += "v ";
				tds += item['away'] + '</td>';

				current.append(tds);
				current.data('time', dt);
				current.data('id', fixtureID);

				if (page >= 0) {
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
				if (page<0) {
					row.data('page', page-1);
				} else {
					row.data('page', page+1);
				}
			} else {
				row.remove();
			}
		} else {
			console.log("Loaded page: " + page + " empty");
			row.remove();
			if (page < 0) {
				addMonthYear($('#fixtures-table tr.fixture:first'));
			}
		}

		if (page == -1) {
			$(window).scrollTop($('#fixtures-title').position().top - 30);
		}
		$('#fixtures-table').data('processing', false);

		$('#fixtures-table tr').show();
		triggerLoad();
	});
}

function addMonthYear(firstRow) {
	var dt = firstRow.data('time');
	firstRow.before("<tr class='month-marker'><th colspan='20'>" + dt.format('MMMM YYYY') + "</th></tr>");
}

function triggerLoad() {
	$('#fixtures-table tr[data-page]').each(function() {
		var elementTop = $(this).offset().top;
		var elementBottom = elementTop + $(this).outerHeight();
		var viewportTop = $(window).scrollTop();
		var viewportBottom = viewportTop + $(window).height();
		if (elementBottom > viewportTop && elementTop < viewportBottom) {
			loadPage($(this));
		}
	});
}

$(document).ready(function() {
	// Load initial dataset
	$('#fixtures-table tr').hide();
	$('#fixtures-table tr[data-page=0]').show();
	loadPage($('#fixtures-table tr[data-page=0]'));

  $("table").on("click", "tr.fixture", function() {
    window.document.location = "<?= url(null, 'get','card') ?>&fid="+$(this).attr("id");
	});
});
</script>

<table id='fixtures-table'>
	<tbody>
		<tr class='title'><th colspan="20">Results</th></tr>
		<tr data-page='-1'><td colspan="20">Loading... <i class="fas fa-sync fa-spin"></i></td></tr>
		<tr class='title' id='fixtures-title'><th colspan="20">Fixtures</th></tr>
		<tr data-page='0'><td colspan="20">Loading... <i class="fas fa-sync fa-spin"></i></td></tr>
  </tbody>
</table>

