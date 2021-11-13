<?php
// vim: et:ts=2:sw=2
$club = Arr::get($_SESSION,'club',null);

if (isset($fixture['card'])) {
    $card = $fixture['card'];
} else {
    $card = null;
}

global $strictProcessing;
$strictProcessing = false;
if (isset($fixture['competition-strict']) && $fixture['competition-strict'] == 'yes') {
    $strictProcessing = true;
}
if (isset($_REQUEST['strict'])) {
    $strictProcessing = true;
}

$cardIsOpen = false;

$whoami = "";
$mycard = null;
if ($club and isset($fixture[$club])) {
    $whoami = $fixture[$club];
    $mycard = $card[$whoami];
    if (!isset($mycard['closed'])) {
        $cardIsOpen = true;
    }
}

// Card is open for umpires if either side has not closed
if (user('Umpires')) {
    if (!isset($card['home']['closed'])) {
        $cardIsOpen = true;
    }
    if (!isset($card['away']['closed'])) {
        $cardIsOpen = true;
    }
}

$date = strftime("%Y-%m-%d", $fixture['date']);
$time = strftime("%H:%M", $fixture['date']);
?>
<!-- <?= "WhoAmI:$whoami Open?:$cardIsOpen" ?> -->

<script src='js/matchcard.js' type='text/javascript'></script>
<script>
<?php
if ($card) {
    $card['away']['suggested-score'] = emptyValue($card['home']['oscore'], 0);
    $card['home']['suggested-score'] = emptyValue($card['away']['oscore'], 0);
    $baseUrl = substr(url(), 0, -11) . "&cid=${card['id']}&x=" . createsecurekey("card${card['id']}"); ?>
  var baseUrl = '<?= $baseUrl ?>';
<?php
} ?>
  var restUrl = '<?= Uri::create('api/1.0/') ?>';
  var card = <?= json_encode($card, JSON_PRETTY_PRINT) ?>;

  $(document).ready(function() {
    flashSubmit();

  <?php
if ($mycard) {
        ?>
  $.getJSON(restUrl + 'registration/list.json?s=<?= $fixture['section'] ?>&t=<?= $mycard['teamx'] ?>&g=<?= join(",", $fixture['groups']) ?>',
    function(json) {
      var ct = 0;
      if (typeof json !== 'undefined') {
        for (var i=0;i<json.length;i++) {
          var p = json[i];
          $('#player-name').append("<option>" + p['name'] + "</option>");
          ++ct;
        }
        console.log('Add player list:' + ct + ' player(s)');
      } else {
        console.log('No player list for team');
      }

      $('#player-name').selectize({
        create: true,
        sortField: 'text',
        persist: false,
        createOnBlur: true,
      });
    });
  <?php
    }
?>
});
</script>

<?php
if ($card) {
    if ($card['official'] || $strictProcessing) {
        ?>
<div class='alert alert-warning alert-small'>
  This matchcard has officially appointed umpires. Tap here for more details.
  <div class='alert-detail'>
    <ul>
      <li>Only umpires can assign penalty cards (Red/Yellow)</li>
      <li>Every player must have a shirt number</li>
      <li>Players must be assigned to card before match</li>
      <li>Matchcards will be closed once the umpire signs the card</li>
    </ul>
  </div>
</div>
<script>
$(document).ready(function() {
  if ($('#matchcard-<?= $whoami ?> .numberless').length > 0) {
    $('#submit-button').attr('disabled','disabled').addClass('disabled');
    $('#match-card').before("<div class='alert alert-danger' data-help='adding-shirt-numbers'>Submit Card button is disabled</strong> because there are players without assigned shirt numbers</div>");
  }
});
</script>
<?php
    }
}
?>

<div id='match-card' <?php
$class = "";
if ($cardIsOpen) {
    $class .= "open ";
}
if ($card && $card['official']) {
    $class .= "official ";
}
if ($class) {
    echo "class='" . trim($class) . "' data-fixtureid='${fixture['id']}' ";
}
if ($card) {
    echo "data-cardid='${card['id']}' data-starttime='${card['date']}'";
}
?>>

<h1 id='competition' data-code='<?= $fixture['competition-code'] ?>' data-format='<?= $card ? $card['format'] : 'Any' ?>'><?= $fixture['competition'] ?></h1>

		<?php /*
if ($fixture['groups']) {
?>
        <h2><?php
    echo join(', ', $fixture['groups']);
?></h2>
        <?php
} */ ?>


  <detail data-timestamp='<?= $fixture['date'] ?>'>

    <dl id='fixtureid'>
      <dt>Fixture ID</dt>        
      <dd><a href='<?= URI::create("Report/Card/${fixture['id']}") ?>'><?= $fixture['id'] ?></a></dd>
    </dl>

    <?php if ($card) { ?>
    <dl id='cardid'>
      <dt>Card ID</dt>        
      <dd><?= $card['id'] ?></dd>
    </dl>
    <?php } ?>

    <dl id='date'>
      <dt>Date</dt>        
      <dd><?= $date ?></dd>
    </dl>

    <dl id='time'>
      <dt>Time</dt>        
      <dd><?= $time ?></dd>
    </dl>
  </detail>

  <div id='teams'>
    <div id='matchcard-home' class='team <?= $whoami == 'home' ? 'ours' : 'theirs' ?>' data-side='home'>
      <?php
      render_team($fixture['home'], $card ? $card['home'] : null);
      ?>
    </div>

    <div id='matchcard-away' class='team <?= $whoami == 'away' ? 'ours' : 'theirs' ?>' data-side='away'>
      <?php
      render_team($fixture['away'], $card ? $card['away'] : null);
      ?>
    </div>

    <div style='clear:both'/>

  </div>

  <form id='submit-card'>
  <?php
if ($cardIsOpen) {
          ?>
      <a id='submit-button' class='btn btn-success' tabindex='10'><i class="fas fa-check"></i> Submit<span class='d-none d-md-inline'> Card</span></a>
  <?php
      }
?>
      <a class='btn btn-info float-right' data-toggle='modal' data-target='#add-note' tabindex='21'>
        <i class="far fa-sticky-note"></i><span class='d-none d-md-inline'> Add Note</span>
      </a>
      <a class='add-player btn btn-danger float-right' data-toggle='modal' data-target='#add-player-modal' tabindex='20'><i class="fas fa-user-plus"></i> Add Player</a>
  <?php
if (!$cardIsOpen) {
    ?>
      <a class='btn btn-success sign-card' data-toggle='modal' data-target='#submit-matchcard' tabindex='2'>
        <i class="fas fa-signature"></i> Add Signature</a>
  <?php
}
?>
    </div>
  </form>
</div>

<?php
// -------------------------------------------------------------------
//     Dialog Box: Submit Matchcard
// -------------------------------------------------------------------
?>
<div id="submit-matchcard" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Submit Card</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body" id='submit-form-detail'>

        <?php
if ($cardIsOpen) {
    ?>

        <form class='needs-validation' novalidation>
          <div class='form-group'>
            <label for='opposition-score'>Opposition Score</label>
            <input class='form-control' type='number' name='opposition-score' required/>
            <div class='invalid-feedback'>You must provide the opposition score</div>
          </div>

          <div class='form-group'>
            <label for='umpire'>Umpire</label>
            <input class='form-control' type='text' name='umpire' required/>
            <div class='invalid-feedback'>You must provide your umpire&apos;s name</div>
          </div>

          <div class='form-group'>
            <label for='receipt-email'>Email for receipt (Optional)</label>
            <input class='form-control' type='email' name='receipt-email' title="If you wish to receive an acknowledgement of submission of this card provide an email address here"/>
          </div>

        </form>
        <?php
}
?>
      </div>

      <div class="modal-body" id='submit-form-signature'>
        <div class='form-group'>
          <label>Signature</label>
          <canvas class='form-control'></canvas>
          <button id='clear-button' class='btn btn-sm btn-danger pull-right'>Clear</button>
        </div>

      </div>

      <div class="modal-footer">
        <?php
if (false and $cardIsOpen) {
    ?>
        <div class='alert alert-danger alert-small md-col-12'>
          <strong>Don't forget</strong> Make sure you have added goals, red/yellow cards to
          your players <u>before</u> submitting the matchcard
        </div>
        <?php
}
?>
        <button type="submit" class="btn btn-success" data-dismiss="modal">
          Submit Matchcard
        </button>
        <a class="btn btn-success" tabindex='1'>
          <i class="fas fa-signature"></i> Sign <i class="fas fa-chevron-right"></i>
        </a>
        <button type="button" class="btn btn-danger" tabindex="2" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<script src='js/signature_pad.min.js' type='text/javascript'></script>

<?php
if (isset($card['notes'])) {
    ?>
<div id='Notes'>
<h4>Notes</h4>
  <table id='notes'>
    <?php
    foreach ($card['notes'] as $note) {
        ?>
    <tr>
      <th><i class="far fa-sticky-note"></i>&nbsp;<?= $note['user'] ?></th>
      <td><?= $note['note'] ?></td>
    </tr>
    <?php
    } ?>
  </table>
</div>
<?php
}
?>

<div id='signatures'>
  <h4>Signatures</h4>
  <span class='progress'>Loading Signatures...</span>
</div>

<div class='spacer'></div>

<?php
// ------------------------------------------------------------------------
//     Signature Pad
// ------------------------------------------------------------------------
?>
<div id='signature'>
  <canvas></canvas>
  <h5>Please sign here</h5>
  <div class='button-box'>
    <button class='btn btn-success' type='submit'>Sign</button>
    <button class='btn btn-warning' type='reset'>Clear</button>
    <a id='cancel-signature' href='#' class='btn btn-danger'>Cancel</a>
  </div>
</div>

<?php
// ------------------------------------------------------------------------
//     Context Menu
// ------------------------------------------------------------------------
if ($cardIsOpen || \Auth::has_access('card.addcards')) {
    ?>
<div id='context-menu' class='dropdown-menu'>
    <div class="modal-header">
      <h4 class="modal-title">Player Name</h4>
      <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>

    <?php
    if (!user('Umpires')) {
        ?>
    <div class='dropdown-item'>
      <button id='remove-player' class='btn btn-block btn-sm btn-danger'>Remove Player</button>
    </div>
    <div id='set-number' class='row'>
      <label>Shirt Number</label>
      <div class='input-group'>
        <input type='number' name='shirt-number' class='form-control'/>
        <button class='btn btn-success'>
          <i class="fas fa-check"></i>
        </button>
      </div>
    </div>
    <div class='dropdown-divider'></div>
    <div id='select-role' class='dropdown-item'>
      <label class='btn btn-xs role-captain'>
        <input type='checkbox' data-role='C'> Capt
      </label>
      <label class='btn btn-xs role-goalkeeper'>
        <input type='checkbox' data-role='G'> GK
      </label>
      <label class='btn btn-xs role-manager'>
        <input type='checkbox' data-role='M'> Mgr
      </label>
      <label class='btn btn-xs role-physio'>
        <input type='checkbox' data-role='P'> Phys
      </label>
    </div>
    <div class='dropdown-divider'></div>
    <?php
    } ?>

    <?php
    if (!$card['official'] || \Auth::has_access('card.addcards')) {
        ?>

    <div class='form-group dropdown-item' id='card-addx'>
      <label><img class='card' src='img/green-card.png'/><img class='card' src='img/yellow-card.png'/><img class='card' src='img/red-card.png'/>Add Penalty Card</label>
      <select class='form-control' id='card-add'>
        <option>Select card to add</option>
        <option class='card-green' data-pcard='green'><img src='img/green-card.png'/> Green Card</option>
        <optgroup label='Yellow Card'>
          <option class='card-yellow' data-pcard='yellow'>Technical - Breakdown</option>
          <option class='card-yellow' data-pcard='yellow'>Technical - Delay/Time Wasting</option>
          <option class='card-yellow' data-pcard='yellow'>Technical - Dissent</option>
          <option class='card-yellow' data-pcard='yellow'>Technical - Foul/Abusive Language</option>
          <option class='card-yellow' data-pcard='yellow'>Technical - Bench/Coach/Team Foul</option>
          <option class='card-yellow' data-pcard='yellow'>Physical - Tackle</option>
          <option class='card-yellow' data-pcard='yellow'>Physical - Dangerous/Reckless Play</option>
        </optgroup>
        <option class='card-red' data-pcard='red'>Red Card</option>
      </select>
    </div>
    <a class='dropdown-item card-clear'>Clear Cards</a>
    <div class='dropdown-divider'></div>
    <?php
    } ?>

    <?php
    if (!user('Umpires')) {
        ?>
    <div class='dropdown-item btn-group'>
      <button id='add-goal' class='btn btn-block btn-success'><i class="fas fa-plus"></i> Add Goal</button>
      <button id='clear-goal' style='margin-top:0' class='btn btn-block btn-warn'><i class="fas fa-ban"></i> Clear Goals</button>
    </div>

    <?php
    } ?>

</div>
<?php
}
?>

<?php
// ------------------------------------------------------------------------
//     Dialog Box: Add New Player
// ------------------------------------------------------------------------
?>
<div id="add-player-modal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-body">
        <label for='player-name'>Player Name</label>
        <select id='player-name'>
          <option>Select from list or type name...</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Add Player</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<?php
// ------------------------------------------------------------------------
//     Dialog Box: Add Note
// ------------------------------------------------------------------------
?>
<div id="add-note" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Add Note</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <label>Note</label>
        <textarea class='form-control' rows='4' cols='50'></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-dismiss="modal">Save</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
      </div>
    </div>

  </div>
</div>

<?php //--------------------------------------------------------------
function render_team($fixture, $team)
{
    global $strictProcessing;
    
    echo "<table class='team-table' data-club='${fixture['club']}' data-team='${fixture['teamnumber']}' data-score='${fixture['score']}'>
  <thead><tr><th colspan='100'>" . $fixture['team'] . " <div class='scores'><span>${fixture['score']}</span>";
    if ($team && $team['suggested-score'] != $fixture['score']) {
        echo "<span>" . $team['suggested-score'] . "</span>";
    }
    echo "</div></th></tr></thead>
      <tbody>\n";
    
    $ct = 0;
    if ($team) {
        foreach ($team['players'] as $player => $detail) {
            $names = cleanName($player, "[Fn][LN]");
        
            $class = "player";
            if (isset($detail['deleted'])) {
                $class .= " deleted";
            } else {
                if (isset($detail['ineligible'])) {
                    $class .= " ineligible";
                }
                if (isset($detail['late'])) {
                    $class .= " late";
                }
                if ($strictProcessing) {
                    if (!isset($detail['number']) or !$detail['number']) {
                        $class .= " numberless";
                    }
                }
            }
        
            $imagekey = createsecurekey("image$player${team['club']}");
            $url      = "image.php?site=" . site() . "&player=$player&w=200&club=${team['club']}&x=$imagekey";
            echo "    <tr class='$class' data-timestamp='${detail['datetime']}' data-imageurl='$url' data-name='$player'>
      <th>" . (isset($detail['number']) ? $detail['number'] : "") . "</th>
      <td>${names['Fn']}</td>
      <td>${names['LN']} ";
        
            echo "<div class='player-annotations'";
            if (isset($detail['detail'])) {
                $d = $detail['detail'];
                echo " data-player='" . htmlspecialchars(json_encode($d), ENT_QUOTES, 'UTF-8') . "'";
            }
            echo ">";
        
            if ($detail['score'] != 0) {
                echo "<span class='score'>${detail['score']}</span>";
            }
            if (isset($detail['cards'])) {
                foreach ($detail['cards'] as $rycard) {
                    $type = "yellow";
                    if ($rycard['type'] == 'Red Card') {
                        $type = "red";
                    }
                    echo "<span class='card-penalty card-$type'>${rycard['detail']}</span>";
                }
            }
            if (isset($detail['detail'])) {
                $d = $detail['detail'];
                if ($d) {
                    $roles = $d->roles;
                    if ($roles) {
                        if (in_array('G', $roles)) {
                            echo "<span class='role role-goalkeeper'>GK</span>";
                        }
                        if (in_array('C', $roles)) {
                            echo "<span class='role role-captain'>C</span>";
                        }
                        if (in_array('M', $roles)) {
                            echo "<span class='role role-manager'>M</span>";
                        }
                        if (in_array('P', $roles)) {
                            echo "<span class='role role-physio'>P</span>";
                        }
                    }
                }
            }
            echo "</div>";
        
            echo "</td>
    </tr>\n";
            $ct++;
        }
    }
    
    for (; $ct < 16; $ct++) {
        echo "    <tr class='filler hidden-xs'><td colspan='4'>&nbsp;</td></tr>\n";
    }
    
    echo "  </tbody>

    </table>\n";
    
    if (isset($team['umpire'])) {
        echo "<dl><dt>Umpire</dt><dd>" . $team['umpire'] . "</dd></dl>";
    }
}
