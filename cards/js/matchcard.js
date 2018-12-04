// vim: et:ts=4:sw=4:
$(document).ready(function() {

    // initializations
    $('span.card-red').html("<img class='card card-red' src='img/red-card.png'/>");
    $('span.card-yellow').html("<img class='card card-yellow' src='img/yellow-card.png'/>");
    $('#context-menu li.card-red').prepend("<img src='img/red-card.png'/>");
    $('#context-menu li.card-yellow').prepend("<img src='img/yellow-card.png'/>");
    $('#context-menu li.card-clear').prepend("<img src='img/no-card.png'/>");

    if ($('#match-card').hasClass('open')) {
        $('.ours caption:first-child').append(
        "<a class='add-player' data-toggle='modal' data-target='#add-player-modal'>Add Player</a>");
        $('.ours table').append(
        "<button class='add-player btn btn-danger' data-toggle='modal' data-target='#add-player-modal'>Add Player</button>");
        $('a.add-player').html("<img src='img/add-user.png' title='Add Player...'/>");
    }

    $('.alert-detail').hide();
    $('.alert').click(function() {
        $(this).find('.alert-detail').toggle();
    });

    // add headshots
    $('#match-card').prepend("<div id='headshot' class='btn-group btn-group-toggle'>"+
        "<button class='btn btn-sm btn-primary active' value='list'><i class='glyphicon glyphicon-th-list'></i></button>"+
        "<button class='btn btn-sm btn-primary' value='headshot'><i class='glyphicon glyphicon-th-large'></i></button>"+
"</div>");

    $('#headshot button').click(function() { setHeadshot($(this).attr('value')=='list'); });

    $('div.team table').append("<div class='figures'></div>");
    $('div.figures').hide();
    $('tr.player').each(function(index) { createHeadshot($(this)); });
    $('a.unlock').click(function() {
        var side = $(this).closest('[data-side]').data('side');
        window.location= baseUrl + "&action=unlock&" + side;
    });

    // Submit Matchcard Dialog Box

    $('#submit-button').click(function() {
        if ($('#match-card').hasClass('official')) {
            var unnumberedPlayers = 0;
            $('.ours .player th:empty').each(function(index) {
                if ($(this).closest(".player").hasClass('deleted')) return; 
                
                unnumberedPlayers++;
            });

            if (unnumberedPlayers > 0) {
                doAlert("All players must have a shirt number");
                return;
            }
        }
        
        $myScore = getText($('#match-card .ours caption>.score'));
        $theirGuess = getText($('#match-card .ours caption>.score>.score'));

        $('#submit-matchcard').modal('show');
    
        if ($myScore < $theirGuess) {
            if ($myScore == 0) {
                doAlert("Your opposition thinks you scored " + $theirGuess + ". Have you forgotten to add goal scorers?");
            } else {
                doAlert("Your opposition thinks you scored " + $theirGuess + ". Do you need to add more goals?");
            }
        }
    });

    $('#submit-matchcard').on('shown.bs.modal', function() {
        $('#submit-matchcard .modal-footer .btn-success').hide();
        $('#submit-form-signature').hide();
        $('#submit-form-detail').show();
        $('#submit-matchcard .modal-footer a').show();
        $('#submit-matchcard .modal-body').height(260)
    
        if (!$('#submit-form-detail .form-group').length) {
            $('#submit-matchcard a.btn-success').click();
        }

        var receipt = localStorage.getItem("receipt_email");

        if (receipt) $('#submit-matchcard input[name=receipt-email]').val(receipt);
    });

    // Click the Sign button 
    $('#submit-matchcard a.btn-success').click(function(e) {
        var score = $('#submit-matchcard input[name=opposition-score]').val();
        if (score == "") {
            doAlert("Opposition score is a required field");
            return;
        }

        $('#submit-form-detail').hide();
        $('#submit-form-signature').show();
        $('#submit-matchcard .modal-footer .btn-success').show();
        $('#submit-matchcard .modal-footer a').hide();

        var c = $("#submit-form-signature canvas");
        var ctx = c[0].getContext("2d");
        ctx.canvas.width = c.parent().width();
        ctx.canvas.height = c.parent().height();
        new SignaturePad(c[0]);
    });

    $('#submit-matchcard button.btn-success').click(function(e) {
        var score = $('#submit-matchcard input[name=opposition-score]').val();
        var umpire = $('#submit-matchcard input[name=umpire]').val();
        var receipt = $('#submit-matchcard input[name=receipt-email]').val();
        var myscore = getText($('#teams .ours caption>.score'));
        var club = $('#teams .ours>table').data('club');
        var canvas = $("#submit-form-signature canvas").get(0);
        var cardId = $('#match-card').data('cardid');
        var dataUrl = cropSignatureCanvas(canvas);

        if (receipt) localStorage.setItem("receipt_email", receipt);

        $.post(restUrl + "/Signature",
            {
                "card_id":cardId,
                "umpire":umpire,
                "myscore":myscore,
                "score":score,
                "receipt":receipt,
                "signature":dataUrl,
                "c":club
            })
            .done(function() { location.reload(); });
    });

    $('.sign-card').click(function() {
        $('#submit-matchcard').data('sign-only', true);
        $('#submit-form-detail').hide();
        $('#submit-form-signature').show();
        $('#submit-matchcard .modal-footer .btn-success').show();
        $('#submit-matchcard .modal-footer a').hide();
        $('#submit-matchcard .modal-title').text("Sign Matchcard");
        $('#submit-matchcard .modal-footer button[type=submit]').text("Sign Matchcard");

        var c = $("#submit-form-signature canvas");
        var ctx = c[0].getContext("2d");
        ctx.canvas.width = c.parent().width();
        ctx.canvas.height = c.parent().height();
        new SignaturePad(c[0]);
    });

    $('#add-player-modal .btn-success').on('click', function(e) {
        $("body").addClass('waiting');
        var playerName = $('#player-name').val();
        console.log("Adding player: "+ playerName);

        $.post(baseUrl + "&action=player&ineligible="+playerName)
            .done( function() { location.reload(); });
    });


    $('#context-close').click(function() {
        $('#context-menu').hide();
    });

    $('#signature [type=reset]').click(function() {
        signaturePad.clear();
    });

    $('#signature [type=submit]').click(function() {
        var dataUrl = cropSignatureCanvas(canvas);
        var cardId = $('#match-card').data('cardid');
        var playerName = $('#signature').data('name');
        var club = $('#teams .ours>table').data('club');
        $.post('http://cards.leinsterhockey.ie/cards/fuel/public/CardApi/Signature',
            {'player':playerName, 'signature':dataUrl, 'card_id':cardId, 'c':club})
            .done(function() { location.reload(); });
        signaturePad.clear();
        $('#signature').hide();
        $('#mysig').attr('src',dataUrl);
    });

    $('#cancel-signature').click(function() {
        signaturePad.clear();
        $('#signature').hide();
    });

    function addNote(msg) {
        var cardId = $('#match-card').data('cardid');
        $.post('http://cards.leinsterhockey.ie/cards/fuel/public/CardApi/Note',
            {'card_id':cardId, 'msg':msg})
            .done(function() { location.reload(); });
    }

    $('#add-note .btn-success').click(function() {
        addNote($('#add-note textarea').val());
    });

    $('#postpone').click(function() {
        addNote('Match Postponed');
    });

    $('#set-number').click(function() {
        var playerRow = getPlayerRow();
        var playerName = playerRow.data('name');
        var club = playerRow.closest('table').data('club');
        var number = $(this).closest('.input-group').find('[name=shirt-number]').val();
        if (number) {
            $.post('http://cards.leinsterhockey.ie/cards/fuel/public/Registration/Number',
            {'c':club,'p':playerName,'n':number})
            .done(function() { location.reload(); });
        }
    });

    var cardId = $('#match-card').data('cardid');
    $.get('http://cards.leinsterhockey.ie/cards/fuel/public/CardApi/Signatures.json?card_id=' + cardId,
        function(data) {
            if (data !== undefined) {
                for (var i=0;i<data.length;i++) {
                    var sig = data[i];
                    var name = sig['player'];
                    if (sig['club']) name += "<br>" + sig['club'];
                    $('#signatures').append("<div><span>" + name + "</span><img src='data:"+ sig['signature'] + "'/></div>");
                }
                if (!data) $('#signatures').hide();
            } else {
                $('#signatures').hide();
            }
            $('#signatures .progress').hide();
        });

    // ------------------------------------------------------
    // Context Menu Functions
    
    // Open Context Menu
    $('div.team .player').click(function() {
        if ($(this).hasClass('deleted')) return;

        var contextMenu = $('#context-menu');

        if (contextMenu.length == 0) return;

        var playerName = $(this).data('name');

        contextMenu.css("top", "1em");
        contextMenu.css("left", "1em");

        contextMenu.find('.dropdown-menu').show();
        contextMenu.find('input[name=shirt-number]').val($(this).find('th').text());
        setText(contextMenu.find('.dropdown-title').get(0), playerName);
        contextMenu.data('player', playerName);
        contextMenu.data('club', $(this).closest('table').data('club'));
        contextMenu.data('tr', $(this));
        contextMenu.show();
    });

    $('li.card-yellow').click(function() {
        incident('yellow',$(this).text(), function() {
            getPlayerRow().find('.player-annotations')
                .append("<span class='card card-yellow'><img class='card card-yellow' src='img/yellow-card.png'/></span>");
            $('#context-menu').hide();
        });
    });

    $('li.card-red').click(function() {
        incident('red',$(this).text(), function() {
            getPlayerRow().find('.player-annotations')
                .append("<span class='card card-red'><img class='card card-red' src='img/red-card.png'/></span>");
            $('#context-menu').hide();
        });
    });

    $('#add-goal').click(function() {
        var goals=getPlayerRow().find('.score');
        if (goals.length) goals = 1+parseInt(goals.text());
        else goals = 1;
        incident('goal',goals, function() {
            var holder=getPlayerRow().find('.score');
            if (holder.length == 0) {
                getPlayerRow().find(".player-annotations")
                    .prepend("<span class='score'/>");
                holder=getPlayerRow().find('.score');
            }
            holder.text(goals);
            updateGoals(holder);
        });
        $('#context-menu').hide();
    });
    
    $('#clear-goal').click(function() {
        incident('goal',0, function() {
            var holder=getPlayerRow();
            holder.find('.score').remove();
            updateGoals(holder);
        });
        $('#context-menu').hide();
    });

    $('#remove-player').click(function() {
        incident('remove',null, function() {
            var holder=getPlayerRow();
            var now = new Date();
            var starttime = $('#match-card').data('starttime');
            if (now.getTime() > starttime) {
                holder.addClass('deleted');
                holder.find('.player-annotations').remove();
            } else {
                holder.remove();
            }
        });
        $('#context-menu').hide();
    });

    $('li.card-clear').click(function() {
        incident('clearcards','', function() {
            getPlayerRow().find('.player-annotations .card').remove();
        });
        $('#context-menu').hide();
    });
    
    resize();

    $(window).resize(resize);
});

// Helper functions
function updateGoals(holder) {
    var totalGoals = 0;
    holder.closest('table')
        .find('.player-annotations .score')
        .each(function() { totalGoals += parseInt($(this).text()); });
    setText(holder.closest('table').find('caption>.score').get(0), totalGoals);
}

function getPlayerRow(name) {
    return $('#context-menu').data('tr');
}

function incident(type, value, onSuccess) {
    var url = baseUrl + "&action=player&player=" + $('#context-menu').data('player')
        +"&club=" + $('#context-menu').data('club')
        +"&" + type;
        
    if (value) url += "=" + value;

    $.post(url).done(onSuccess);
}

function resize() {
    var hs = $('figure.player:first');
    var w0 = hs.parent().width();
    var w;
    for (i=1;i<10;i++) {
        w = w0/i-17;
        if (w < 90) break;
    }
    $('figure.player').width(w).height(w*105/70);
    $('figure.player img').each(function() {
        var imgPadding = $(this).width()*88/70 - $(this).height();
        $(this).css('padding-bottom', imgPadding); 
    });
}

function setHeadshot(state) {
    if (state) {
        $('.team table tbody').show();
        $('div.figures').hide();
        $('#headshot button[value=list]').addClass('active');
        $('#headshot button[value=headshot]').removeClass('active');
    } else {
        $('.team table tbody').hide();
        $('div.figures').show();
        resize();
        $('#headshot button[value=headshot]').addClass('active');
        $('#headshot button[value=list]').removeClass('active');
    }
}

function createHeadshot(row) {
    var playerName = row.data('name');
    var number = parseInt(row.children(":eq(0)").text()) || "?";
    var score = parseInt(row.find(".score").text()) || 0;
    var playerClasses = row.attr("class");

    var newFig = row.closest('div.team').find('div.figures').append("<figure class='"+playerClasses+"' data-name='"+playerName+"'>"
        +"<img src='"+row.data('imageurl')+"'/>"
        +"<span class='number'>"+number+"</span>"
        +"<figcaption>"
        +row.children(":eq(1)").data('firstname') + "<br>" + row.children(":eq(2)").data('surname')
        +"</figcaption></figure>");
    newFig.children().last().append(row.find('span').clone());
    newFig.find('span[data-score=0]').remove();
}

/**
 * Crop signature canvas to only contain the signature and no whitespace.
 *
 * @since 1.0.0
 */
function cropSignatureCanvas(canvas) {

    // First duplicate the canvas to not alter the original
    var croppedCanvas = document.createElement('canvas'),
        croppedCtx    = croppedCanvas.getContext('2d');

        croppedCanvas.width  = canvas.width;
        croppedCanvas.height = canvas.height;
        croppedCtx.drawImage(canvas, 0, 0);

    // Next do the actual cropping
    var w = croppedCanvas.width,
        h = croppedCanvas.height,
        pix = {x:[], y:[]},
        imageData = croppedCtx.getImageData(0,0,croppedCanvas.width,croppedCanvas.height),
        x, y, index;

    for (y = 0; y < h; y++) {
        for (x = 0; x < w; x++) {
            index = (y * w + x) * 4;
            if (imageData.data[index+3] > 0) {
                pix.x.push(x);
                pix.y.push(y);
            }
        }
    }

    if (pix.x.length == 0 || pix.y.length == 0) return null;

    pix.x.sort(function(a,b){return a-b});
    pix.y.sort(function(a,b){return a-b});
    var n = pix.x.length-1;

    w = pix.x[n] - pix.x[0];
    h = pix.y[n] - pix.y[0];
    var cut = croppedCtx.getImageData(pix.x[0], pix.y[0], w, h);

    croppedCanvas.width = w;
    croppedCanvas.height = h;
    croppedCtx.putImageData(cut, 0, 0);

    return croppedCanvas.toDataURL();
}

function getText(obj) {
    if (obj === undefined || obj === null) return null;

    return obj.contents().first().text();
}

function setText(obj, text) {
    if (obj === undefined || obj === null) return false;

    obj.firstChild.nodeValue = text;

    return true;
}

