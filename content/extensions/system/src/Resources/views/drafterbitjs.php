 (function($){
  drafTerbit = {
    baseUrl: "<?php echo base_url() ?>",
    adminUrl: "<?php echo admin_url() ?>",
    csrfToken: "<?php echo csrf_token() ?>",

    //replace datatable search box;
    replaceDTSearch: function(dt) {
      $('.dataTables_filter').remove();


      $(document).on('keydown', "input[type=search]", function(e){
        var code = e.keyCode || e.which;
        if (code == 13) {
          e.preventDefault();
        }}
      );

      //search filter
      $(document).on('keyup', "input[type=search]", function(e){

        var val = $(this).val();
        dt.api().search($(this).val()).draw();
        
      });
    },

    initAjaxForm: function(){
      $('form.ajax-form').ajaxForm({
        dataType: 'json',
        success: function(response){
          
          if(response.error) {
            if(response.error.type == 'validation') {
              var messages =response.error.messages

              for(k in messages) {
                var ctn = $(':input[name="'+k+'"]').closest('.form-group');
                ctn.addClass('has-error');
                ctn.append('<span class="help-block">'+messages[k]+'</span>');
              }
            }
          }

        }
      });
    }
  
  }
})(jQuery);