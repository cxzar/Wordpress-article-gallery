$(document).ready(function(){

	var items = $('#og-grid li'),
	itemsByTags = {};
	numOfTag = 0;

	// Looping though all the li items:

	items.each(function(i){
		var elem = $(this),
		tags = elem.data('tags').split(',');

		// Adding a data-id attribute. Required by the Quicksand plugin:
		elem.attr('data-id',i);

		$.each(tags,function(key,value){

			// Removing extra whitespace:
			value = $.trim(value);

			if(!(value in itemsByTags)){
				// Create an empty array to hold this item:
				itemsByTags[value] = [];
				numOfTag++;
			}

			// Each item is added to one array per tag:
			itemsByTags[value].push(elem);
		});

	});

	if(numOfTag > 1){
		// Creating the "Everything" option in the menu:
		createList('All');

		// Looping though the arrays in itemsByTags:
		$.each(itemsByTags,function(k,v){
			createList(k);
		});
	}else{
		$('ul#portfolio-filter').remove();
	}


	$('#portfolio-filter a').bind('click',function(e){
		$(this).css('outline','none');
		$('ul#portfolio-filter .current').removeClass('current');
		$(this).parent().addClass('current');

		var filterVal = $(this).text().toLowerCase().replace(' ','-');
		if(filterVal == 'all') {
			$('ul#og-grid li.hidden').fadeIn('slow').removeClass('hidden');
		} else {

			$('ul#og-grid li').each(function() {
				if(!$(this).hasClass(filterVal)) {
					$(this).fadeOut('normal').addClass('hidden');
				} else {
					$(this).fadeIn('slow').removeClass('hidden');
				}
			});
		}
		$('.og-close').trigger('click');

		return false;
	});

	$('#portfolio-filter a:first').click();

	function createList(text){
		// This is a helper function that takes the
		// text of a menu button and array of li items
		if(text != ''){
			var li = $('<li>');
			var a = $('<a>',{
				html: text,
				'data-filter': '.'+text,
				href:'#',
				'class':'filter',
			}).appendTo(li);

			li.appendTo('#portfolio-filter');
		}
	}
});

$(function() {
	Grid.init();
});