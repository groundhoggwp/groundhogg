(function( $ ) {

    $.fn.wpghToolBar = function() {

        var html = '<wpgh-toolbar class="action-icons"><div style="margin: 5px 3px 5px 3px;"><span class="dashicons dashicons-admin-page"></span><hr/><span class="dashicons dashicons-move handle"></span><hr/><span class="dashicons dashicons-trash"></span></div></wpgh-toolbar>';

        this.each(function() {

            var row = $( this );

            if ( row.find( 'wpgh-toolbar' ).length === 0 )
                row.prepend( html );

        });

        return this;

    };

}( jQuery ));


jQuery(function($) {
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
                //wpgh_update_funnel_step_order();
            }
        });
    //emailSortables.disableSelection();
    var emailDraggables = jQuery( ".email-draggable" ).draggable({
        connectToSortable: ".email-sortable",
        helper: "clone",
        start: function ( e, ui ) {
            var el = this;
            var block_type = el.id;
            var html = jQuery( '.' + block_type + '_template' ).children().first().clone();
            $('#temp-html').html( html );
        },
        stop: function ( e, ui ) {
            $('#email-content').find('.email-draggable').replaceWith( $('#temp-html').html() );
        }
    });

    var $sticky = $('.editor-actions-inner');
    $sticky.css( 'height', 'auto' );
    $sticky.css( 'width', $sticky.parent().width() );
    // var $stickyrStopper = $('#sidebar-stop');
    if (!!$sticky.offset()) { // make sure ".sticky" element exists

        var generalSidebarHeight = $sticky.innerHeight();
        var stickyTop = $sticky.offset().top;
        var stickOffset = 32;
        // var stickyStopperPosition = $stickyrStopper.offset().top;
        // var stopPoint = stickyStopperPosition - generalSidebarHeight - stickOffset;
        // var diff = stopPoint + stickOffset;

        $(window).scroll(function(){ // scroll event
            var windowTop = $(window).scrollTop(); // returns number

            // if (stopPoint < windowTop) {
            //     $sticky.css({ position: 'absolute', top: diff });
            // } else if (stickyTop < windowTop+stickOffset) {
            if (stickyTop < windowTop+stickOffset) {
                $sticky.css({ position: 'fixed', top: stickOffset });
            } else {
                $sticky.css({position: 'absolute', top: 'initial'});
            }
        });

    }

    $( '.row' ).wpghToolBar();

    /* init sidebar */
    $('.sidebar').stickySidebar({
        topSpacing: 40,
        bottomSpacing: 40
    });
});

var WPGHEmailEditor = {};

WPGHEmailEditor.init = function () {
    this.content = jQuery( '#email-body' );
    this.actions = jQuery( '#editor-actions' );
    this.contentInside = jQuery( '#email-inside' );
    this.textarea = jQuery( '#content' );
    this.form = jQuery('form');
    this.form.on('submit', WPGHEmailEditor.switchContent );
};

WPGHEmailEditor.switchContent = function( e ){
    e.preventDefault();
    jQuery('.spinner').css('visibility','visible');
    jQuery('.row').removeClass('active');
    jQuery('wpgh-toolbar').remove();
    if ( WPGHEmailEditor.richText ){
        WPGHEmailEditor.richText.simpleEditor().destroy();
    }
    WPGHEmailEditor.textarea.val( WPGHEmailEditor.contentInside.html() );
    WPGHEmailEditor.form.unbind( 'submit' ).submit();
};

WPGHEmailEditor.getActive = function () {
  return this.content.find( '.active' );
};

WPGHEmailEditor.hideActions = function (){
  this.actions.find( '.postbox' ).addClass( 'hidden' );
};

// paragraphs
WPGHEmailEditor.pFont = jQuery( '#p-font' );
WPGHEmailEditor.pFont.update = function () { WPGHEmailEditor.pFont.val( WPGHEmailEditor.getActive().find('.simple-editor-content').css( 'font-family' ).replace(/"/g, '') );};
WPGHEmailEditor.pFont.apply = function() { WPGHEmailEditor.getActive().find('.simple-editor-content').css('font-family', WPGHEmailEditor.pFont.val() ) };
WPGHEmailEditor.pFont.on( 'change', WPGHEmailEditor.pFont.apply );

WPGHEmailEditor.pSize = jQuery( '#p-size' );
WPGHEmailEditor.pSize.update = function () {WPGHEmailEditor.pSize.val( WPGHEmailEditor.getActive().find('.simple-editor-content').css( 'font-size' ).replace('px', '') );};
WPGHEmailEditor.pSize.apply = function() { WPGHEmailEditor.getActive().find('.simple-editor-content').css('font-size', WPGHEmailEditor.pSize.val() + 'px' ) };
WPGHEmailEditor.pSize.on( 'change', WPGHEmailEditor.pSize.apply );

// h1
WPGHEmailEditor.h1Font = jQuery( '#h1-font' );
WPGHEmailEditor.h1Font.apply = function() {WPGHEmailEditor.getActive().find('h1').css('font-family', WPGHEmailEditor.h1Font.val() )};
WPGHEmailEditor.h1Font.update = function () { try{ WPGHEmailEditor.h1Font.val( WPGHEmailEditor.getActive().find('h1').css( 'font-family' ).replace(/"/g, '') ); } catch (e){}};
WPGHEmailEditor.h1Font.on( 'change', WPGHEmailEditor.h1Font.apply );


WPGHEmailEditor.h1Size = jQuery( '#h1-size' );
WPGHEmailEditor.h1Size.apply = function(){ WPGHEmailEditor.getActive().find('h1').css('font-size', WPGHEmailEditor.h1Size.val() + 'px' );};
WPGHEmailEditor.h1Size.update = function () { try{ WPGHEmailEditor.h1Size.val( WPGHEmailEditor.getActive().find('h1').css( 'font-size' ).replace('px', '') ); } catch (e){}};
WPGHEmailEditor.h1Size.on( 'change', WPGHEmailEditor.h1Size.apply );

// h2
WPGHEmailEditor.h2Font = jQuery( '#h2-font' );
WPGHEmailEditor.h2Font.apply = function(){ WPGHEmailEditor.getActive().find('h2').css('font-family', WPGHEmailEditor.h2Font.val() ); };
WPGHEmailEditor.h2Font.update = function () { try{ WPGHEmailEditor.h2Font.val( WPGHEmailEditor.getActive().find('h2').css( 'font-family' ).replace(/"/g, '') );} catch (e){}};
WPGHEmailEditor.h2Font.on( 'change', WPGHEmailEditor.h2Font.apply );

WPGHEmailEditor.h2Size = jQuery( '#h2-size' );
WPGHEmailEditor.h2Size.apply = function(){ WPGHEmailEditor.getActive().find('h2').css('font-size', WPGHEmailEditor.h2Size.val() + 'px' ); };
WPGHEmailEditor.h2Size.update = function () { try{ WPGHEmailEditor.h2Size.val( WPGHEmailEditor.getActive().find('h2').css( 'font-size' ).replace('px', '') || 30 ); } catch(e) {}};
WPGHEmailEditor.h2Size.on( 'change', WPGHEmailEditor.h2Size.apply );

WPGHEmailEditor.textOptions = jQuery( '#text_block-editor' );
WPGHEmailEditor.showTextOptions = function () {
    this.showOptions( this.textOptions );

    this.pFont.update();
    this.pSize.update();
    this.h1Font.update();
    this.h1Size.update();
    this.h2Font.update();
    this.h2Size.update();
};

//buttons
WPGHEmailEditor.buttonSize = jQuery( '#button-size' );
WPGHEmailEditor.buttonSize.on( 'change', function(){WPGHEmailEditor.getActive().find('a').css('font-size', WPGHEmailEditor.buttonSize.val() + 'px' );});
WPGHEmailEditor.buttonSize.update = function () { WPGHEmailEditor.buttonSize.val( WPGHEmailEditor.getActive().find('a').css( 'font-size' ).replace('px', '') );};

WPGHEmailEditor.buttonFont = jQuery( '#button-font' );
WPGHEmailEditor.buttonFont.on( 'change', function( ){WPGHEmailEditor.getActive().find('a').css('font-family', WPGHEmailEditor.buttonFont.val() );});
WPGHEmailEditor.buttonFont.update = function () { WPGHEmailEditor.buttonFont.val( WPGHEmailEditor.getActive().find('a').css( 'font-family' ).replace(/"/g, '') );};

WPGHEmailEditor.buttonColor = jQuery( '#button-color' );
WPGHEmailEditor.buttonColor.wpColorPicker({change: function (event, ui) {WPGHEmailEditor.getActive().find('.email-button').attr('bgcolor', WPGHEmailEditor.buttonColor.val() );}});

WPGHEmailEditor.buttonTextColor = jQuery( '#button-text-color' );
WPGHEmailEditor.buttonTextColor.wpColorPicker({change: function (event, ui) {WPGHEmailEditor.getActive().find('a').css('color', WPGHEmailEditor.buttonTextColor.val() );}});

WPGHEmailEditor.buttonLink = jQuery( '#button-link' );
WPGHEmailEditor.buttonLink.on( 'change', function( ){WPGHEmailEditor.getActive().find('a').attr('href', WPGHEmailEditor.buttonLink.val() );});
WPGHEmailEditor.buttonLink.update = function () { WPGHEmailEditor.buttonLink.val( WPGHEmailEditor.getActive().find('a').attr( 'href' ));};

WPGHEmailEditor.buttonText = jQuery( '#button-text' );
WPGHEmailEditor.buttonText.on( 'change', function( ){WPGHEmailEditor.getActive().find('a').html( WPGHEmailEditor.buttonText.val() );});
WPGHEmailEditor.buttonText.update = function () { WPGHEmailEditor.buttonText.val( WPGHEmailEditor.getActive().find('a').html());};


WPGHEmailEditor.buttonOptions = jQuery( '#button_block-editor' );
WPGHEmailEditor.showButtonOptions = function() {
    this.showOptions( this.buttonOptions );

    this.buttonFont.update();
    this.buttonSize.update();
    this.buttonLink.update();
    this.buttonText.update();
};

//spacer
WPGHEmailEditor.spacerSize = jQuery( '#spacer-size' );
WPGHEmailEditor.spacerSize.on( 'change', function(){WPGHEmailEditor.getActive().find('.spacer').css('height', WPGHEmailEditor.spacerSize.val() + 'px' );});
WPGHEmailEditor.spacerSize.update = function () { WPGHEmailEditor.spacerSize.val( WPGHEmailEditor.getActive().find('.spacer').height() );};

//divider
WPGHEmailEditor.dividerWidth = jQuery( '#divider-width' );
WPGHEmailEditor.dividerWidth.on( 'change', function(){WPGHEmailEditor.getActive().find('hr').css('width', WPGHEmailEditor.dividerWidth.val() + '%' );});
WPGHEmailEditor.dividerWidth.update = function () { WPGHEmailEditor.dividerWidth.val( Math.ceil( ( WPGHEmailEditor.getActive().find('hr').width() / WPGHEmailEditor.getActive().find('hr').closest('div').width() ) * 100 ) );};

WPGHEmailEditor.dividerOptions = jQuery( '#divider_block-editor' );
WPGHEmailEditor.showDividerOptions = function() {
    this.showOptions( this.dividerOptions );
    this.dividerWidth.update();
};

//images
WPGHEmailEditor.imageSRC = jQuery( '#image-src' );
WPGHEmailEditor.imageSRC.on( 'change', function(){WPGHEmailEditor.getActive().find('img').attr('src', WPGHEmailEditor.imageSRC.val() );});
WPGHEmailEditor.imageSRC.update = function () {WPGHEmailEditor.imageSRC.val( WPGHEmailEditor.getActive().find('img').attr('src') );};

WPGHEmailEditor.imageLink = jQuery( '#image-link' );
WPGHEmailEditor.imageLink.on( 'change', function(){WPGHEmailEditor.getActive().find('a').attr('href', WPGHEmailEditor.imageLink.val() );});
WPGHEmailEditor.imageLink.update = function () {WPGHEmailEditor.imageLink.val( WPGHEmailEditor.getActive().find('a').attr('href') );};

WPGHEmailEditor.imageAltText = jQuery( '#image-alt' );
WPGHEmailEditor.imageAltText.on( 'change', function(){WPGHEmailEditor.getActive().find('img').attr('alt', WPGHEmailEditor.imageAltText.val() );});
WPGHEmailEditor.imageAltText.update = function () {WPGHEmailEditor.imageAltText.val( WPGHEmailEditor.getActive().find('img').attr('alt') );};

WPGHEmailEditor.imageTitle = jQuery( '#image-title' );
WPGHEmailEditor.imageTitle.on( 'change', function( ){WPGHEmailEditor.getActive().find('img').attr('title', WPGHEmailEditor.imageTitle.val() );});
WPGHEmailEditor.imageTitle.update = function () {WPGHEmailEditor.imageTitle.val( WPGHEmailEditor.getActive().find('img').attr('title') );};

WPGHEmailEditor.imageWidth = jQuery( '#image-width' );
WPGHEmailEditor.imageWidth.on( 'change', function( ){WPGHEmailEditor.getActive().find('img').css('width', WPGHEmailEditor.imageWidth.val() + '%' );});
WPGHEmailEditor.imageWidth.update = function () { WPGHEmailEditor.imageWidth.val( Math.ceil( ( WPGHEmailEditor.getActive().find('img').width() / WPGHEmailEditor.getActive().find('img').closest('div').width() ) * 100 ) );};

WPGHEmailEditor.imageAlignment = jQuery( '#image-align' );
WPGHEmailEditor.imageAlignment.on( 'change', function( ){WPGHEmailEditor.getActive().find('.image-wrapper').css('text-align', WPGHEmailEditor.imageAlignment.val() );});
WPGHEmailEditor.imageAlignment.update = function () {WPGHEmailEditor.imageAlignment.val( WPGHEmailEditor.getActive().find('.image-wrapper').css('text-align') );};

WPGHEmailEditor.imageOptions = jQuery( '#image_block-editor' );
WPGHEmailEditor.showImageOptions = function() {
    this.showOptions( this.imageOptions );

    this.imageSRC.update();
    this.imageWidth.update();
    this.imageAlignment.update();
    this.imageLink.update();
    this.imageAltText.update();
    this.imageTitle.update();
};

WPGHEmailEditor.htmlContent = jQuery( '#custom-html-content' );
WPGHEmailEditor.htmlContent.on( 'change', function( ){WPGHEmailEditor.getActive().find('.content-inside').html( WPGHEmailEditor.htmlContent.val().trim() );});
WPGHEmailEditor.htmlContent.update = function () {WPGHEmailEditor.htmlContent.val( WPGHEmailEditor.getActive().find('.content-inside').html().trim() );};

WPGHEmailEditor.codeOptions = jQuery( '#code_block-editor' );
WPGHEmailEditor.showCodeOptions = function() {
    this.showOptions( this.codeOptions );
    this.htmlContent.update();
};

WPGHEmailEditor.spacerOptions = jQuery( '#spacer_block-editor' );
WPGHEmailEditor.showSpacerOptions = function() {
    this.showOptions( this.spacerOptions );

    this.spacerSize.update();
};


WPGHEmailEditor.emailAlignment = jQuery( '#email-align' );
WPGHEmailEditor.emailAlignment.apply = function(){

    if ( jQuery( this ).val() === 'left' ){
        jQuery( '#email-inside' ).css( 'margin-left', '0' );
        jQuery( '#email-inside' ).css( 'margin-right', 'auto' );
    } else {
        jQuery( '#email-inside' ).css( 'margin-left', 'auto' );
        jQuery( '#email-inside' ).css( 'margin-right', 'auto' );
    }

};
WPGHEmailEditor.emailAlignment.change( WPGHEmailEditor.emailAlignment.apply );


WPGHEmailEditor.emailOptions = jQuery( '#email-editor' );
WPGHEmailEditor.showEmailOptions = function () {
    this.showOptions( this.emailOptions );
};

WPGHEmailEditor.showOptions = function( el ){
    this.actions.find( '.postbox' ).addClass( 'hidden' );
    el.removeClass( 'hidden' );
};

WPGHEmailEditor.makeActive = function ( el ) {
    if ( el.closest('#email-body').length ){
        jQuery('.row').removeClass("active");
    }

    el.closest('.row').addClass('active');
    this.active = el.closest('.row');
};

WPGHEmailEditor.richText = null;
WPGHEmailEditor.active = null;

WPGHEmailEditor.action = function( e )
{
    e.preventDefault();

    WPGHEmailEditor.active = el;

    var el = jQuery(e.target);

    if ( el.hasClass('dashicons-trash') ){
        el.closest('.row').remove();
        WPGHEmailEditor.showEmailOptions();

    } else if ( el.hasClass('dashicons-admin-page') ){
        WPGHEmailEditor.richText.simpleEditor().destroy();
        el.closest('.row').removeClass('active');
        el.closest('.row').clone().insertAfter( el.closest('.row') );
    } else {

        if ( el.closest('.row').hasClass('active') )
            return true;

        WPGHEmailEditor.makeActive( el );

        if ( WPGHEmailEditor.richText ){
            WPGHEmailEditor.richText.simpleEditor().destroy();
        }

        if ( el.closest( '.text_block' ).length ){

            WPGHEmailEditor.richText = el.closest('.content-wrapper');

            WPGHEmailEditor.richText.simpleEditor({
                defaultParagraphSeparator: 'p',
                actions: ["heading1", "heading2", "paragraph", "bold", "italic",  "underline", "color", "strikethrough", "alignLeft", "alignCenter", "alignRight", "olist", "ulist","link", "unlink"]
            });

            WPGHEmailEditor.showTextOptions();

        } else if ( el.closest( '.button_block' ).length ){

            WPGHEmailEditor.showButtonOptions();

        } else if ( el.closest( '.spacer_block' ).length ){

            WPGHEmailEditor.showSpacerOptions();

        } else if ( el.closest( '.image_block' ).length ){

            WPGHEmailEditor.showImageOptions();

        } else if ( el.closest( '.divider_block' ).length ){

            WPGHEmailEditor.showDividerOptions();

        } else if ( el.closest( '.code_block' ).length ){

            WPGHEmailEditor.showCodeOptions();

        } else if ( el.closest( '#editor' ).length ) {

            jQuery('.row').removeClass('active');

            WPGHEmailEditor.showEmailOptions();

        }
    }
};

jQuery(function() {
    WPGHEmailEditor.init();
    WPGHEmailEditor.content.on("click", WPGHEmailEditor.action );
});