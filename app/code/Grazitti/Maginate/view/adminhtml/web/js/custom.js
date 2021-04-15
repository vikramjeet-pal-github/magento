require(['jquery', 'jquery/ui'], function($){
	$(document).ready(function() {
		var parentElement = $("#grazitti_maginate_email-head").closest('#config-edit-form');
		parentElement.addClass('grazitti-section');
		if(parentElement.hasClass("grazitti-section")){
			$('fieldset').hide();
			$('.section-config').each(function(i, obj) {
				$(this).removeClass('active');
				$(this).children().children('a').removeClass('open');
				$(this).children('input').val(0);
				if(i==0){
					$(this).addClass('active');
					$(this).children().children('a').addClass('open');
					$(this).children('input').val(1);
					$(this).children('fieldset').show();
				}
			});
			$(document).on('click', '.section-config', function() {
				if($(this).hasClass("active")){
					$(this).siblings().removeClass('active');
					$(this).siblings().children().children('a').removeClass('open');
					$(this).siblings().children('input').val(0);
					$('fieldset').hide();
					$(this).children('fieldset').show();
				}
			});
		}
	});		
});