jQuery(document).ready( function() {
    var emailSortables = jQuery( ".email-sortable" ).sortable(
        {
            placeholder: "sortable-placeholder",
            // connectWith: ".email-sortable",
            axis: 'y',
            start: function(e, ui){
                // ui.helper.css( 'left', ui.item.parent().width() - ui.item.width() );
                ui.placeholder.height(ui.item.height());
                //ui.placeholder.width(ui.item.width());
            },
            handle: '.handle',
            stop: function (e, ui) {
                //wpfn_update_funnel_step_order();
            }
        });
    //emailSortables.disableSelection();
    var emailDraggables = jQuery( ".email-draggable" ).draggable({
        connectToSortable: ".email-sortable",
        helper: "clone",
        stop: function (e, ui ) {

            var el = this;
            var block_type = el.id;

            jQuery('#email-content').find('.email-draggable').replaceWith( "<div class='replace-me'></div>" );


            var ajaxCall = jQuery.ajax({
                type : "post",
                url : ajaxurl,
                data : {action: "get_email_block_html", block_type: block_type },
                success: function( html )
                {
                    jQuery('#email-content').find('.replace-me').replaceWith(html);
                }
            });
        }
    });

});

jQuery(function() {

    var content = jQuery( '#email-body' );
    var inside = jQuery( '#email-inside' );
    var contentTextArea = jQuery( '#content' );
    var form = jQuery('form');
    form.on('submit', function(e){
        e.preventDefault();
        contentTextArea.val( inside.html() );
        jQuery(this).unbind('submit').submit();
    });

    var editor = jQuery( '#editor-actions' );

    var pFontSize = jQuery( '#p-size' );
    pFontSize.on( 'change', function( ){
        content.find('.active').find('p').css('font-size', pFontSize.val() + 'px' );
    });

    var pFontFamily = jQuery( '#p-font' );
    pFontFamily.on( 'change', function( ){
        content.find('.active').find('p').css('font-family', pFontFamily.val() );
    });

    var buttonFontSize = jQuery( '#button-size' );
    buttonFontSize.on( 'change', function( ){
        content.find('.active').find('a').css('font-size', buttonFontSize.val() + 'px' );
    });

    var buttonFontFamily = jQuery( '#button-font' );
    buttonFontFamily.on( 'change', function( ){
        content.find('.active').find('a').css('font-family', buttonFontFamily.val() );
    });

    var buttonColor = jQuery( '#button-color' );
    buttonColor.wpColorPicker({
        change: function (event, ui) {
            content.find('.active').find('.email-button').attr('bgcolor', buttonColor.val() );
        }
    });

    var buttonTextColor = jQuery( '#button-text-color' );
    buttonTextColor.wpColorPicker({
        change: function (event, ui) {
            content.find('.active').find('a').css('color', buttonTextColor.val() );
        }
    });

    var buttonLink = jQuery( '#button-link' );
    buttonLink.on( 'change', function( ){
        content.find('.active').find('a').attr('href', buttonLink.val() );
    });

    var spacerSize = jQuery( '#spacer-size' );
    spacerSize.on( 'change', function( ){
        content.find('.active').find('.spacer').css('height', spacerSize.val() + 'px' );
    });

    var imageSRC = jQuery( '#image-src' );
    imageSRC.on( 'change', function( ){
        content.find('.active').find('img').attr('src', imageSRC.val() );
    });

    var imageLink = jQuery( '#image-link' );
    imageLink.on( 'change', function( ){
        content.find('.active').find('a').attr('href', imageLink.val() );
    });

    var imageAltText = jQuery( '#image-alt' );
    imageAltText.on( 'change', function( ){
        content.find('.active').find('img').attr('alt', imageAltText.val() );
    });

    var imageTitle = jQuery( '#image-title' );
    imageTitle.on( 'change', function( ){
        content.find('.active').find('img').attr('title', imageTitle.val() );
    });

    var imageWidth = jQuery( '#image-width' );
    imageWidth.on( 'change', function( ){
        content.find('.active').find('img').css('width', imageWidth.val() + '%' );
    });

    content.on("click", function(e) {

        e.preventDefault();

        var el = jQuery(e.target);

        if ( el.hasClass('dashicons-trash') ){
            el.closest('.row').remove();
        }

        //apply & remove active class
        if ( el.closest('#email-body').length ){
           jQuery('.row').removeClass("active");
        }

        el.closest('.row').addClass('active');

        //show appropriate-settings
        editor.find('.postbox').addClass('hidden');

        if ( el.closest( '.text_block' ).length ){
            jQuery( '#text_block-editor' ).removeClass( 'hidden' );

            pFontSize.val( content.find('.active').find('p').css( 'font-size' ).replace('px', '') );
            pFontFamily.val( content.find('.active').find('p').css( 'font-family' ).replace(/"/g, '') );

        } else if ( el.closest( '.button_block' ).length ){
            jQuery( '#button_block-editor' ).removeClass( 'hidden' );

            buttonFontSize.val( content.find('.active').find('a').css( 'font-size' ).replace('px', '') );
            buttonFontFamily.val( content.find('.active').find('a').css( 'font-family' ).replace(/"/g, '') );
            buttonColor.val( content.find('.active').find('.email-button').attr( 'bgcolor' ) );
            buttonTextColor.val(  content.find('.active').find('a').css( 'color' ) );
            buttonLink.val(  content.find('.active').find('a').attr( 'href' ) );

        } else if ( el.closest( '.spacer_block' ).length ){

            jQuery( '#spacer_block-editor' ).removeClass( 'hidden' );
            spacerSize.val( content.find('.active').find('.spacer').css( 'height' ).replace('px', '') );

        } else if ( el.closest( '.image_block' ).length ){
            jQuery( '#image_block-editor' ).removeClass( 'hidden' );

            imageSRC.val( content.find('.active').find('img').attr('src' ) );
            imageLink.val( content.find('.active').find('a').attr('href' ) );
            imageAltText.val( content.find('.active').find('img').attr('alt' ) );
            imageTitle.val( content.find('.active').find('img').attr('title' ) );
            imageWidth.val( Math.ceil( ( content.find('.active').find('img').width() / content.find('.active').find('img').closest('div').width() ) * 100 ) );

        }
    });
});