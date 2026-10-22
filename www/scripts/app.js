document.addEventListener('DOMContentLoaded', () => {
    naja.initialize();

    // hide flash messages after a while
    setTimeout(() => {
	$('.flash').addClass('hidden');
	setTimeout(() => {
	    $('.flashes').remove();
	},300);
    },5000);

    $(document).on('click','.flash',function(){
	$(this).addClass('hidden');
    });

    // nette forms – show validation errors next to inputs instead of a modal window
    Nette.showFormErrors = function(form, errors){
	$(form).find('.form-error-message').remove();
	$(form).find('.has-error').removeClass('has-error');

	let focusElem = null;
	for(let error of errors){
	    let elem = error.element;

	    // hidden inputs have no place for the message
	    if(!$(elem).is(':visible')){
		showToast(error.message);
		continue;
	    }

	    let container = $(elem).closest('.col, .field-group');
	    if(!container.length){
		container = $(elem).parent();
	    }

	    $(elem).addClass('has-error');
	    if(!container.find('.form-error-message').length){
		container.append($('<span class="form-error-message"></span>').text(error.message));
	    }

	    focusElem ??= elem;
	}

	if(focusElem){
	    focusElem.scrollIntoView({behavior: 'smooth', block: 'center'});
	    focusElem.focus({preventScroll: true});
	}
    };

    $(document).on('input change','.has-error',function(){
	$(this).removeClass('has-error');
	$(this).closest('.col, .field-group').find('.form-error-message').remove();
    });

    function showToast(message){
	let flashes = $('.flashes');
	if(!flashes.length){
	    flashes = $('<div class="flashes"></div>').appendTo('body');
	}

	let flash = $('<div class="flash error"></div>').text(message).appendTo(flashes);
	setTimeout(() => {
	    flash.addClass('hidden');
	    setTimeout(() => {
		flash.remove();
	    },300);
	},5000);
    }
});
