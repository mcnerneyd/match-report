<!-- <?php print_r($registration) ?> -->

<script>
$(document).ready(function() {
  const club = "<?= $club ?>";
  const section = "<?= $section ?>";

	var table = $('#registration-table').DataTable({
		paging: false,
		ordering: false,
	});
	$('#registration-table tbody').show();
	$('#change-date').click(function() {
		if ($('#date-select').is(':visible')) {
			$('#date-select').hide();
		} else {
			$('#date-select').show();
		}
	});
	$('#date-select').datepicker({
		dateFormat: "yy-mm-dd",
		showOtherMonths: true,
		selectOtherMonths: true,
		onSelect: function(d, i) {
				window.location = `./Registration/Registration?c=${club}&s=${section}&d=${d}`;
			}
		});

  $('#registration-table tbody').on('click', 'tr', function() {
    var tr = $(this)
    var row = table.row(tr)
    if (row.child.isShown()) {
      row.child.hide()
    } else {
      row.child(format(tr.data('history'))).show()
    }
  })
});

const format = (d) => {
  return "<table>" + d.map(x => "<tr><td>" + x.name + "</td><td>" + x.date + "</td><td>" + x.opposition + "</td></tr>").join("") + "</table>";
}



</script>
<div id='registration'>

  <p>Players eligible on or after <?= $ts->format("%A %e, %B %G") ?> for <?= $club ?></p>
  <button id='change-date' class='btn btn-primary'>Select Date <i class="far fa-calendar-alt"></i></button>
  <div id='date-select'></div>

  <table id='registration-table' class='table'>
    <thead>
      <th></th>
      <th></th>
      <th>Player</th>
      <th>Team</th>
      <?= ($section === 'lha-men' ? '<th></th>' : '') ?>
    </thead>
    <tbody display='none'>
  <?php
  $ct=1;
  foreach ($registration as $player) 
  {
    $class = "player";
    if (isset($player['status'])) $class .= " ${player['status']}";

    echo "<tr data-history='".json_encode($player['history'])."'>
      <td>$ct</td>
      <td>";
      if (isset($player['membershipid']) && $player['membershipid']) {
        echo \Asset::img('hockeyireland-icon.png', array('class'=>'membership'));
      }
    echo "</td>
    <td class='$class'>${player['name']}</td>";

    /*
    echo "<td>";
    foreach ($player['history'] as $match) {
      $date = date('d.n', strtotime($match['date']));
      echo "<a class='match-pill' href='#'><span>${match['code']}</span><span>$date</span></a>";
    }

    echo "</td>";
    */

    echo "<td>${player['team']}</td>";
    if ($section === 'lha-men') echo "<td>".($player['score'] != 99 ? $player['score'] : "")."</td>";
    echo "</tr>";

    $ct++;
  }
  ?>
    </tbody>
  </table>

  <p class='subinfo'>Valid from <?= Date::forge($info['initial'])->format('%Y-%m-%d') ?>
    to <?= Date::forge($info['current'])->format('%Y-%m-%d') ?></p>
  <table class='subinfo'>
  <?php 
  function ordinal($n) {
    if ($n == 1) return "1st";
    if ($n == 2) return "2nd";
    if ($n == 3) return "3rd";
    return $n."th";
  }
  
  foreach ($info['teamSizes'] as $k => $v) { ?>
    <tr><td><?= ordinal($k+1)." team" ?></td>
    <td><?= "$v players" ?></td></tr>
  <?php } ?>
  </table>

</div>
