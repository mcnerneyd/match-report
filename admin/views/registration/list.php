<script>
$(document).ready(function() {
  const club = "<?= $club ?>";
  const section = "<?= $section ?>";

	$('#registration-table').DataTable({
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
});
</script>
<div id='registration'>

  <p>Players eligible on or after <?= $ts->format("%A %e, %B %G") ?> for <?= $club ?></p>
  <button id='change-date' class='btn btn-primary'>Select Date <i class="far fa-calendar-alt"></i></button>
  <div id='date-select'></div>

  <table id='registration-table' class='table'>
    <thead>
      <th/>
      <th/>
      <th>Player</th>
      <th>Team</th>
      <th></th>
    </thead>
    <tbody display='none'>
  <?php
  $ct=1;
  foreach ($registration as $player) 
  {
    $class = "player";
    if (isset($player['status'])) $class .= " ${player['status']}";

    echo "<tr>
      <td>$ct</td>
      <td>";
      if (isset($player['membershipid']) && $player['membershipid']) {
        echo "<img class='membership' src='http://cards.leinsterhockey.ie/public/assets/img/hockeyireland-icon.png'/>";
      }
    echo "</td>
    <td class='$class'>${player['name']}</td>";

    /*
    echo "<td>";
    foreach ($player['history'] as $match) {
      $date = date('d.n', strtotime($match['date']));
      if ($match['code'][0] == 'D') $cls = 'match-pill-league';
      else $cls = 'match-pill-cup';
      echo "<a class='match-pill $cls' href='#'><span>${match['code']}</span><span>$date</span></a>";
    }

    echo "</td>";
    */

    echo "<td>${player['team']}</td>";
    echo "<td>${player['score']}</td>";
    echo "</tr>";

    $ct++;
  }
  ?>
    </tbody>
  </table>
</div>
