<?php
if (isset($_GET['alternate'])) {
	require_once("index2.php");
	return;
}
?>
<style>
  table { width: 100%; }
  table th:first-child, table td:first-child { width: 3em; padding-left: 6px; }
  table tr td { border-top: 1px solid #ddd; padding: 5px 0px; }
	table tr td:last-child { text-align: right; padding-right: 6px; }
	a[data-toggle='confirmation'] { margin-top: -1px; }
  table tr th { padding: 20px 0 5px 0; border-bottom: 2px solid black; }
	table tr.unprocessed { background: repeating-linear-gradient( 45deg, #ffbbbb, #ffbbbb 5px, white 5px, white 20px); }
	table tr.incomplete-warned { background: #E42217 !important; color: white; }
	table tr.incomplete { background: #ffa500; }
	table tr.incomplete.partial { background: #ffa50050; }
	table tr.incomplete td { border-top: 1px solid black; }
  .form-inline select { margin-left: 5px; }
  select[name=competition] { font-weight: normal; }
	.form-inline .form-control { width:auto; display:inline-block; }
	.nav-tabs { padding-top: 20px; }
	td.nonlha { color:#aa8; font-style:italic; }
</style>

<script>
$(document).ready(function () {
  $("table tr[data-id]").click(function() {
    window.document.location = "<?= url(null, 'get','card') ?>&fid="+$(this).data("id")+
					"&x="+$(this).data("idx")+
					"&status="+$(this).attr('class');
  });

	var selectedTab = window.location.hash.substr(0);

	if (!selectedTab) {
		selectedTab = $('#tabs a:first').attr('href');
	}

	$(".nav a[href='" + selectedTab + "']").closest('li').addClass('active');
	filterCards();

  function filterCards() {
    $('tr').hide();

    if ($(".nav li.active a[href='#fixtures']").length) {
      $('tr.fixture').show();
      $('tr.incomplete').show();
    }

    if ($(".nav li.active a[href='#results']").length) {
      $('tr.result').show();
    }

    if ($("#competition-selector").val()!='') {
      $('tr:not([data-competition="'+ $('#competition-selector').val() +'"])').hide();
    }

    $('table th:first-child').each(function() {
      var tr = $(this).parent();
      var trNext = tr.nextUntil("tr:has(th)", "tr:visible");
      
      if (trNext.length==0) tr.hide();
      else tr.show();
    });
  }

  $('#competition-selector').on('change', filterCards);

  $('.nav li a').on('click', function() {
    $('.nav li').removeClass('active');
    $(this).parent().addClass('active');

    filterCards();
	});

	$('#fixtures-table .fa-envelope').on('click', function(event) {
		event.preventDefault();
		var cardId = $(this).closest('tr').data('id');
		location.href = 'http://cards.leinsterhockey.ie/cards/fuel/public/card/sendmail?id='+cardId;
		return false;
	});

  filterCards();
});
</script>

<form class='form-inline'>
       <div class='form-group col-md-5'>
               <label for='competition-selector'>Competition</label>
               <select id='competition-selector' class='form-control' name='competition'>
                       <option value=''>All competitions</option>
               <?php foreach ($competitions as $competition) { ?>
                       <option <?= (isset($_REQUEST['competition']) and ($competition->name == $_REQUEST['competition'])) ? 'selected' : '' ?>><?= $competition ?></option> 
               <?php } ?>
               </select>
       </div>  <!-- .form-group -->
</form>

<ul class="nav nav-tabs" id='tabs'>
  <li><a href="#fixtures">Fixtures <span class='badge'><?= dd($counts, 'fixture', 0) + dd($counts, 'incomplete', 0) ?></span></a></li>
  <li><a href="#results">Results <span class='badge'><?= dd($counts, 'result', 0) ?></span></a></li>
</ul>

<table id='fixtures-table'>
  <tbody>
<?php $month = "";
  foreach ($cards as $card) { 
		if (user('user') and isset($card['card'])) {
			$mycard = $card['card'][$card[user()]];
		} else $mycard = null;

    if (!isset($card['home']['valid']) && !isset($card['away']['valid'])) continue;

    if ($month != date('F Y', $card['date'])) {
        $month = date('F Y', $card['date']); ?>
      <tr>
        <th colspan='6'><?= $month ?></th>
      </tr>
<?php  } 
			
			$rowClass = $card['status'];
			if (isset($card['warned'])) $rowClass .= " incomplete-warned";
			if ($rowClass == 'incomplete' and $card['submitted']) $rowClass .= " partial";

			$cardKey = dd($card,'competition-code'); 
			if (isset($card['home'])) $cardKey .= $card['home']['team']; else $cardKey .= "XH0";
			if (isset($card['away'])) $cardKey .= $card['away']['team']; else $cardKey .= "XA0";
			$cardKey = str_replace(" ", "", $cardKey);

			$label = 'label-league';
			//if (isset($card['competition-code']) && strlen($card['competition-code']) == 3) $label = 'label-cup';
			if (stripos($card['competition'], 'div') === FALSE) $label = 'label-cup';

			?>
      <tr class='<?= $rowClass ?>' data-id='<?= dd($card,'id',0) ?>' data-idx='<?= createsecurekey('card'.dd($card,'id',0)) ?>' data-competition='<?= dd($card,'competition') ?>' title='<?= $card['id'] ?>' data-key='<?= $cardKey ?>'>
        <td><?= date('j', $card['date']) ?></td>
        <td>
					<span class='hidden-xs'><span class='label <?= $label ?>'><?= dd($card,'competition') ?></span></span>
					<span class='visible-xs'><span class='label <?= $label ?>'><?= dd($card,'competition-code') ?></span></span>
				</td>
        <td <?= !isset($card['home']['valid'])?"class='nonlha'":"" ?> >
				<?php 
					if (isset($card['card']['home']['closed'])) {
						//echo "<img width='16' src='img/tick.png'/> ";
						echo "<i class='fa fa-check' aria-hidden='true'></i> ";
					} else if (isset($card['card']['home']['locked'])) {
						//echo "<img width='16' src='img/lock.png'/> ";
						echo "<i class='fa fa-lock' aria-hidden='true'></i> ";
					}

					if (isset($card['home'])) {
						echo $card['home']['team'];
					}
					?></td>
        <td <?= !isset($card['away']['valid'])?"class='nonlha'":"" ?> >
				<?php 
					if (isset($card['card']['away']['closed'])) {
						//echo "<img width='16' src='img/tick.png'/> ";
						echo "<i class='fa fa-check' aria-hidden='true'></i> ";
					} else if (isset($card['card']['away']['locked'])) {
						//echo "<img width='16' src='img/lock.png'/> ";
						echo "<i class='fa fa-lock' aria-hidden='true'></i> ";
					}
					
					if (isset($card['away'])) {
						echo $card['away']['team'];
					}
					?></td>
				<td class='hidden-xs'><?php
					if (isset($card['late'])) {
					if ($card['late'] === true) {
						echo "Fined";
					} else {
						echo $card['late']." days ";

						if (user('admin') && isset($card['warned'])) {
							echo "<a class='btn btn-xs btn-danger' data-toggle='confirmation' data-title='Fine issued?' href='".url(null,'fine','card')."&fixtureid=".$card['id']."'><i class='fa fa-eur' aria-hidden='true'></i></a>";
						}
					}
					}
?>
						<i class='fa fa-envelope'></i>
					</td>
      </tr>
<?php } ?>
  </tbody>
</table>

