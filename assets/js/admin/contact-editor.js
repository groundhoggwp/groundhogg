jQuery(function($){
    $( '#meta-table' ).click(function( e ){
        if ( $( e.target ).closest( '.deletemeta' ).length ){
            $( e.target ).closest( 'tr' ).remove();
        }
    });
    $( '.addmeta' ).click(function(){

        var $newMeta = "<tr>" +
            "<th>" +
            "<input type='text' class='input' name='newmetakey[]' placeholder='" + $('.metakeyplaceholder').text() + "'>" +
            "</th>" +
            "<td>" +
            "<input type='text' class='regular-text' name='newmetavalue[]' placeholder='" + $('.metavalueplaceholder').text() + "'>" +
            " <span class=\"row-actions\"><span class=\"delete\"><a style=\"text-decoration: none\" href=\"javascript:void(0)\" class=\"deletemeta\"><span class=\"dashicons dashicons-trash\"></span></a></span></span>\n" +
            "</td>" +
            "</tr>";
        $('#meta-table').find( 'tbody' ).prepend( $newMeta );

    })
});