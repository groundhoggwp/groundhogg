jQuery( document ).ready( function( $ ) {
    // Uploading files
    var file_frame;

    jQuery('#upload_image_button').on('click', function( event ){
        event.preventDefault();
        // If the media frame already exists, reopen it.
        if ( file_frame ) {
            // Open frame
            file_frame.open();
            return;
        }
        // Create the media frame.
        file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select a image to upload',
            button: {
                text: 'Use this image',
            },
            multiple: false	// Set to true to allow multiple files to be selected

        });
        // When an image is selected, run a callback.
        file_frame.on( 'select', function() {
            // We set multiple to false so only get one image from the uploader
            var attachment = file_frame.state().get('selection').first().toJSON();
            // Do something with attachment.id and/or attachment.url here
            // $( '#image-preview' ).attr( 'src', attachment.url );
            var content = jQuery( '#email-body' );

            $( '#image-src' ).val( attachment.url );
            content.find('.active').find('img').attr('src', attachment.url );
            $( '#image-alt' ).val( attachment.alt );
            content.find('.active').find('img').attr('alt', attachment.alt );
            $( '#image-title' ).val( attachment.title );
            content.find('.active').find('img').attr('title', attachment.title );
        });
        // Finally, open the modal
        file_frame.open();
    });
    // Restore the main ID when the add media button is pressed
});
