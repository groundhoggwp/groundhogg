jQuery(function ($) {
    var $preferences = $( 'input[name="preference"]' ).change( function () {
        $preferences.closest( 'li' ).removeClass( 'checked' );
        if ( $( this ).is( ':checked' ) ){
            $( this ).closest( 'li' ).addClass( 'checked' );
        }
    });
});