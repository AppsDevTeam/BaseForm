var $submittedForm = null;
var blockSubmit = false;

$('form.ajax-form').on('submit', function(e) {
	if(blockSubmit) {
		$submittedForm = $(this);
		return false;
	} else {
		$submittedForm = null;
		if($(this).find('input.error-ajax').length) {
			$('html, body').animate({
				scrollTop: $('input.error-ajax', this).offset().top
			}, 500);
			return false;
		} else {
			$.nette.ajax({}, this, e).done(function() {

			});
		}
	}
});	

$('form').parent().has('[id*=snippet]').on('change', '[data-invalidate]', function(e) {
	blockSubmit = true;
	var $input = $(this);
	var $form = $input.closest('form');
	var $submit = $form.find('input[type=submit]');
	var $_invalidate = $form.find('input[name=_invalidate]');
	$_invalidate.val($input.data('invalidate'));
	$submit.attr('formnovalidate', 'formnovalidate');
	$.nette.ajax({}, $submit, e).done(function() {
		$submit.attr('formnovalidate', null);
		$_invalidate.val('');
		blockSubmit = false;
		if ($submittedForm) {
			$submittedForm.trigger('submit');
		}	
	});
});