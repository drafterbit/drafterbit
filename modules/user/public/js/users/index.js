(function($, drafTerbit) {

    if (window.location.hash == '') {
        window.location.hash = '#group=0&status=all';
    }

    var hash = window.location.hash.substr(1);

    drafTerbit.user = function(){

        var renderCol1 = function(d,t,f,m){
            return '<input type="checkbox" name="users[]" value="'+d+'">';
        }
        var renderCol2 = function(d,t,f,m){
            return '<a class="user-edit-link" href="'+drafTerbit.deskUrl+'user/edit/'+f.id+'">'+d+'</a>'
        }
        var renderCol3 = function(d,t,f,m){
            if(d == 1) {
                return __('Enabled');
            }

            return __('Disabled');
        }

        var filterByStatus = function(){
            drafTerbit.blog.dt.api().ajax.reload();
            window.location.hash = status;
        }

        return {

            handleIndexTable: function(tableSelector, token) {
                drafTerbit.user.dt =  $(tableSelector).dataTable(
                  {
                      processing: true,
                      serverSide: true,
                      responsive: true,
                      ajax: {
                          /*data: function(data) {
                              return Qs.parse(hash);
                          },*/
                          url: drafTerbit.deskUrl+"user/data?token="+token,
                      },
                      columns: [
                          {data: 'id', orderable: false, searchable:false, targets:0, render: renderCol1 },
                          {data: 'realname', render: renderCol2 },
                          {data: 'email'},
                          {data: 'status', render: renderCol3}
                      ]
                    }
                );

                drafTerbit.replaceDTSearch(drafTerbit.user.dt);

                  $('#users-checkall').checkAll({showIndeterminate:true});
            },

            handleIndexForm: function(formSelector) {

                $(formSelector).ajaxForm(
                    {
                        beforeSend: function(){
                            if (confirm(__('Are you sure you want to delete those users, this con not be undone ?'))) {
                                return true;
                            } else {
                                return false;
                            }
                        },
                        success: function(response){
                            $.notify(response.message, response.status);
                            drafTerbit.user.dt.api().ajax.reload();
                        }
                    }
                );
            },

            listenTableFilter: function(statusFilterSelector, groupFilterSelector, filterSelector) {

                var param = Qs.parse(hash);

                $(statusFilterSelector+' option[value="'+param.status+'"]').prop('selected', true);
                $(groupFilterSelector+' option[value="'+param.group+'"]').prop('selected', true);

                $(filterSelector).on(
                    'change',
                    function(){

                        var param = {
                            group: $(groupFilterSelector).val(),
                            status: $(statusFilterSelector).val()
                        }

                        hash = Qs.stringify(param);
                        window.location.hash = hash;
                        drafTerbit.user.dt.api().ajax.reload();
                    }
                );
            }
        }
    }();

})(jQuery, drafTerbit);
