jQuery(document).ready(function($){
	$('.hide-on-load .inside').slideToggle(200);

	$('.cp-meta-box .handlediv').click(function(){
		var parent = $(this).parent();
		$(parent).children('.inside').slideToggle(200);
	});
},jQuery);