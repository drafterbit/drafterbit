(function($, drafTerbit) {

    var dt = $("#log-data-table").dataTable(
        {
            ajax: {
                url: drafTerbit.deskUrl+'system/log/data'
            },
            columns: [
                {data: 'id', orderable: false, searchable:false, render: function(d,t,f,m) { return '<input type="checkbox" name="log[]" value="'+d+'">' } },
                {data: 'time'},
                {data: 'msg'}
            ],
            drawCallback: function() {
                drafTerbit.handleFooter();
            }
        }
    );

    drafTerbit.replaceDTSearch(dt);

    $('#log-checkall').checkAll({showIndeterminate:true});

})(jQuery,drafTerbit);