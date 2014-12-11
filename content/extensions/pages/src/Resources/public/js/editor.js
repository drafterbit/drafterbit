(function($, CKEDITOR){

	var form = $('#page-edit-form'),
		spinner = $('i.spinner'),
		id = $('input[name="id"]'),
		closeText = $('.dt-editor-close-text');

	// remove error message
	$(':input').on('focus', function(){
		$(this).siblings('.error-msg').remove();
		$(this).closest('.form-group').removeClass('has-error');
	});

	form.ajaxForm({

		dataType: 'json',
		beforeSerialize: function() {
			// fixes ckeditor content
			for ( instance in CKEDITOR.instances ) {
			    CKEDITOR.instances[instance].updateElement();
			}
		},

		beforeSend: function(){
			spinner.removeClass('fa-check');
			spinner.addClass('fa-spin fa-spinner');
		},

		success:function(data){
			
			dirty = false;
			spinner.removeClass('fa-spin fa-spinner');
			spinner.addClass('fa-check');

			if(data.error) {
				if(data.error.type == 'validation') {
					for(name in data.error.messages) {
						var inputCtn = $(':input[name="'+name+'"]').closest('.form-group');
						inputCtn.addClass('has-error');

						if(!inputCtn.children('.error-msg').length) {
							inputCtn.append('<span class="help-block error-msg">'+data.error.messages[name]+'</span>');
						}
					}
				}
			} else {

	            if (data.id) {
	                id.val(data.id);
            		
            		$.notify(data.message, data.status);
	            }
			}

            closeText.text('Close');
		}
	});

    // check form before leaving page
    window.onbeforeunload = (function() {

        form.on('change', ':input', function() {
            dirty = true;
        });

        for ( instance in CKEDITOR.instances ) {
		    CKEDITOR.instances[instance].on('change', function(){
            	dirty = true;
        	});
		}

        return function(e) {
            if (dirty) return 'Discard unsaved changes ?';
        };

    })();

})(jQuery, CKEDITOR);