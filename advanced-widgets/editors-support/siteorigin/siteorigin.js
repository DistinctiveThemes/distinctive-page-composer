!function($){
	var initEForms = function(){
		$('.cl-eform:not(.inited)').each(function(index, eform){
			var $eform = $(eform);
			$eform.addClass('inited');
			new $cl.EForm($eform);
		});
	};
	$(document).on('panels_setup', function(event, builderView){
		$('.dapper-so-widget').each(function(index, widget){
			$(widget).data('view').getEditDialog().on('form_loaded', initEForms);
		});
		builderView.on('content_change display_builder', function(){
			$('.dapper-so-panels-dialog-wrapper').each(function(index, wrapper){
				$(wrapper).data('view').on('form_loaded', initEForms);
			});
		});
	});
}(jQuery);
