jQuery(function($) {
    $('#glsdialog').jqm({onHide:clearDialog});
    $('#glsdialog').jqmAddClose($('#glsdialog-close a')); 
    $('.glslabel').each(function() {
        var glsid = $(this).text();
        var span  = $(this);
        $('#glsdialog').jqmAddTrigger(span.parent()); 
        span.parent().click(function(){
            var url = span.attr("rel");
            $.get(url, function(data, textStatus) {
                $('#loading-mask').hide();
                $('#glsdialog-content').html(data);
            });
        });
    });
    function clearDialog(hash) {
        $('#glsdialog-content').html("");
        hash.w.fadeOut('10',function(){ hash.o.remove(); $('#loading-mask').show();})
    }
});
