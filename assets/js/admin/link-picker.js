(function( $ ) {

    $.fn.linkPicker = function() {

        this.each(function() {
            $(this).click(function() {
                wpActiveEditor = true; //we need to override this var as the link dialogue is expecting an actual wp_editor instance
                wpLink.active = $(this).attr('data-target');
                wpLink.open(wpLink.active); //open the link popup
                return false;
            });
        });

        return this;

    };

}( jQuery ));

jQuery(function ($) {
    jQuery( '.wp-link-text-field' ).css( 'display', 'none' );
    jQuery( '.link-target' ).css( 'display', 'none' );

    $('body').on('click', '#wp-link-submit', function(event) {
        var linkAtts = wpLink.getAttrs();//the links attributes (href, target) are stored in an object, which can be access via  wpLink.getAttrs()
        $( '#' + wpLink.active ).val(linkAtts.href);//get the href attribute and add to a textfield, or use as you see fit
        wpLink.textarea = $('body'); //to close the link dialogue, it is again expecting an wp_editor instance, so you need to give it something to set focus back to. In this case, I'm using body, but the textfield with the URL would be fine
        wpLink.close();//close the dialogue
//trap any events
        event.preventDefault ? event.preventDefault() : event.returnValue = false;
        event.stopPropagation();
        return false;
    });
    $('body').on('click', '#wp-link-cancel, #wp-link-close', function(event) {
        wpLink.textarea = $('body');
        wpLink.close();
        event.preventDefault ? event.preventDefault() : event.returnValue = false;
        event.stopPropagation();
        return false;
    });
});