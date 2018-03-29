$(document).ready(function() {
	$('tr.player').click(function() {
		$(this).css('background','red');
	});

	$('#match-card').prepend("<button id='headshot' data-mode='L'><i class='glyphicon glyphicon-th-list'></i></button>");

	$('#headshot').click(function() {
		if ($('#headshot').data('mode') == 'H') {
			$('.team table tbody').show();
			$('div.figures').hide();

			$('#headshot').data('mode','L');
			$('#headshot i').removeClass('glyphicon-th-large').addClass('glyphicon-th-list');
		} else {
			$('.team table tbody').hide();
			$('div.figures').show();
			resize();

			$('#headshot').data('mode','H');
			$('#headshot i').removeClass('glyphicon-th-list').addClass('glyphicon-th-large');
		}
	});

	$('span.card-red').html("<img class='card card-red' src='img/red-card.png'/>");
	$('span.card-yellow').html("<img class='card card-yellow' src='img/yellow-card.png'/>");

	$('div.team table').append("<div class='figures'></div>");
	$('div.figures').hide();

	$('tr.player').each(function(index) {
		var number = parseInt($(this).children(":eq(0)").text()) || "?";
		var score = parseInt($(this).find(".score").text()) || 0;
		var newFig = $(this).closest('div.team').find('div.figures').append("<figure class='"+$(this).attr("class")+"'>"
			+"<img src='"+$(this).data('imageurl')+"'/>"
			+"<span class='number'>"+number+"</span>"
			+"<figcaption>"
			+$(this).children(":eq(1)").data('firstname') + "<br>" + $(this).children(":eq(2)").data('surname')
			+"</figcaption></figure>");
		newFig.children().last().append($(this).find('span').clone());
		newFig.find('span[data-score=0]').remove();
	});
	resize();


	$(window).resize(resize);
});

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
