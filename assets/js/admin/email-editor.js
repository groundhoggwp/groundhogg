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
});

var WPFNEmailEditor = {};

WPFNEmailEditor.init = function () {
    this.content = jQuery( '#email-body' );
    this.actions = jQuery( '#editor-actions' );
    this.contentInside = jQuery( '#email-inside' );
    this.textarea = jQuery( '#content' );
    this.form = jQuery('form');
    this.form.on('submit', WPFNEmailEditor.switchContent );
};

WPFNEmailEditor.switchContent = function( e ){
    e.preventDefault();
    jQuery('.row').removeClass('active');
    if ( WPFNEmailEditor.richText ){
        WPFNEmailEditor.richText.simpleEditor().destroy();
    }
    WPFNEmailEditor.textarea.val( WPFNEmailEditor.contentInside.html() );
    WPFNEmailEditor.form.unbind( 'submit' ).submit();
};

WPFNEmailEditor.getActive = function () {
  return this.content.find( '.active' );
};

WPFNEmailEditor.hideActions = function (){
  this.actions.find( '.postbox' ).addClass( 'hidden' );
};

// paragraphs
WPFNEmailEditor.pFont = jQuery( '#p-font' );
WPFNEmailEditor.pFont.on( 'change', function( ){ WPFNEmailEditor.getActive().find('.simple-editor-content').css('font-family', WPFNEmailEditor.pFont.val() );});
WPFNEmailEditor.pFont.update = function () { WPFNEmailEditor.pFont.val( WPFNEmailEditor.getActive().find('.simple-editor-content').css( 'font-family' ).replace(/"/g, '') );};

WPFNEmailEditor.pSize = jQuery( '#p-size' );
WPFNEmailEditor.pSize.on( 'change', function(){WPFNEmailEditor.getActive().find('.simple-editor-content').css('font-size', WPFNEmailEditor.pSize.val() + 'px' );});
WPFNEmailEditor.pSize.update = function () {WPFNEmailEditor.pSize.val( WPFNEmailEditor.getActive().find('.simple-editor-content').css( 'font-size' ).replace('px', '') );};

// h1
WPFNEmailEditor.h1Font = jQuery( '#h1-font' );
WPFNEmailEditor.h1Font.on( 'change', function( ){WPFNEmailEditor.getActive().find('h1').css('font-family', WPFNEmailEditor.h1Font.val() );});
WPFNEmailEditor.h1Font.update = function () { WPFNEmailEditor.h1Font.val( WPFNEmailEditor.getActive().find('h1').css( 'font-family' ).replace(/"/g, '') );};

WPFNEmailEditor.h1Size = jQuery( '#h1-size' );
WPFNEmailEditor.h1Size.on( 'change', function(){WPFNEmailEditor.getActive().find('h1').css('font-size', WPFNEmailEditor.h1Size.val() + 'px' );});
WPFNEmailEditor.h1Size.update = function () { WPFNEmailEditor.h1Size.val( WPFNEmailEditor.getActive().find('h1').css( 'font-size' ).replace('px', '') );};

// h2
WPFNEmailEditor.h2Font = jQuery( '#h2-font' );
WPFNEmailEditor.h2Font.on( 'change', function( ){WPFNEmailEditor.getActive().find('h2').css('font-family', WPFNEmailEditor.h2Font.val() );});
WPFNEmailEditor.h2Font.update = function () { WPFNEmailEditor.h2Font.val( WPFNEmailEditor.getActive().find('h2').css( 'font-family' ).replace(/"/g, '') );};

WPFNEmailEditor.h2Size = jQuery( '#h2-size' );
WPFNEmailEditor.h2Size.on( 'change', function(){ WPFNEmailEditor.getActive().find('h2').css('font-size', WPFNEmailEditor.h2Size.val() + 'px' );});
WPFNEmailEditor.h2Size.update = function () { WPFNEmailEditor.h2Size.val( WPFNEmailEditor.getActive().find('h2').css( 'font-size' ).replace('px', '') || 30 );};

WPFNEmailEditor.textOptions = jQuery( '#text_block-editor' );
WPFNEmailEditor.showTextOptions = function () {
    this.showOptions( this.textOptions );

    this.pFont.update();
    this.pSize.update();
};

//buttons
WPFNEmailEditor.buttonSize = jQuery( '#button-size' );
WPFNEmailEditor.buttonSize.on( 'change', function(){WPFNEmailEditor.getActive().find('a').css('font-size', WPFNEmailEditor.buttonSize.val() + 'px' );});
WPFNEmailEditor.buttonSize.update = function () { WPFNEmailEditor.buttonSize.val( WPFNEmailEditor.getActive().find('a').css( 'font-size' ).replace('px', '') );};

WPFNEmailEditor.buttonFont = jQuery( '#button-font' );
WPFNEmailEditor.buttonFont.on( 'change', function( ){WPFNEmailEditor.getActive().find('a').css('font-family', WPFNEmailEditor.buttonFont.val() );});
WPFNEmailEditor.buttonFont.update = function () { WPFNEmailEditor.buttonFont.val( WPFNEmailEditor.getActive().find('a').css( 'font-family' ).replace(/"/g, '') );};

WPFNEmailEditor.buttonColor = jQuery( '#button-color' );
WPFNEmailEditor.buttonColor.wpColorPicker({change: function (event, ui) {WPFNEmailEditor.getActive().find('.email-button').attr('bgcolor', WPFNEmailEditor.buttonColor.val() );}});

WPFNEmailEditor.buttonTextColor = jQuery( '#button-text-color' );
WPFNEmailEditor.buttonTextColor.wpColorPicker({change: function (event, ui) {WPFNEmailEditor.getActive().find('a').css('color', WPFNEmailEditor.buttonTextColor.val() );}});

WPFNEmailEditor.buttonLink = jQuery( '#button-link' );
WPFNEmailEditor.buttonLink.on( 'change', function( ){WPFNEmailEditor.getActive().find('a').attr('href', WPFNEmailEditor.buttonLink.val() );});
WPFNEmailEditor.buttonLink.update = function () { WPFNEmailEditor.buttonLink.val( WPFNEmailEditor.getActive().find('a').attr( 'href' ));};

WPFNEmailEditor.buttonOptions = jQuery( '#button_block-editor' );
WPFNEmailEditor.showButtonOptions = function() {
    this.showOptions( this.buttonOptions );

    this.buttonFont.update();
    this.buttonSize.update();
    // this.buttonColor.update();
    // this.buttonTextColor.update();
    this.buttonLink.update();
};

//spacer
WPFNEmailEditor.spacerSize = jQuery( '#spacer-size' );
WPFNEmailEditor.spacerSize.on( 'change', function(){WPFNEmailEditor.getActive().find('.spacer').css('height', WPFNEmailEditor.spacerSize.val() + 'px' );});
WPFNEmailEditor.spacerSize.update = function () { WPFNEmailEditor.spacerSize.val( WPFNEmailEditor.getActive().find('.spacer').height() );};

//divider
WPFNEmailEditor.dividerWidth = jQuery( '#divider-width' );
WPFNEmailEditor.dividerWidth.on( 'change', function(){WPFNEmailEditor.getActive().find('hr').css('width', WPFNEmailEditor.dividerWidth.val() + '%' );});
WPFNEmailEditor.dividerWidth.update = function () { WPFNEmailEditor.dividerWidth.val( Math.ceil( ( WPFNEmailEditor.getActive().find('hr').width() / WPFNEmailEditor.getActive().find('hr').closest('div').width() ) * 100 ) );};

WPFNEmailEditor.dividerOptions = jQuery( '#divider_block-editor' );
WPFNEmailEditor.showDividerOptions = function() {
    this.showOptions( this.dividerOptions );
    this.dividerWidth.update();
};

//images
WPFNEmailEditor.imageSRC = jQuery( '#image-src' );
WPFNEmailEditor.imageSRC.on( 'change', function(){WPFNEmailEditor.getActive().find('img').attr('src', WPFNEmailEditor.imageSRC.val() );});
WPFNEmailEditor.imageSRC.update = function () {WPFNEmailEditor.imageSRC.val( WPFNEmailEditor.getActive().find('img').attr('src') );};

WPFNEmailEditor.imageLink = jQuery( '#image-link' );
WPFNEmailEditor.imageLink.on( 'change', function(){WPFNEmailEditor.getActive().find('a').attr('href', WPFNEmailEditor.imageLink.val() );});
WPFNEmailEditor.imageLink.update = function () {WPFNEmailEditor.imageLink.val( WPFNEmailEditor.getActive().find('a').attr('href') );};

WPFNEmailEditor.imageAltText = jQuery( '#image-alt' );
WPFNEmailEditor.imageAltText.on( 'change', function(){WPFNEmailEditor.getActive().find('img').attr('alt', WPFNEmailEditor.imageAltText.val() );});
WPFNEmailEditor.imageAltText.update = function () {WPFNEmailEditor.imageAltText.val( WPFNEmailEditor.getActive().find('img').attr('alt') );};

WPFNEmailEditor.imageTitle = jQuery( '#image-title' );
WPFNEmailEditor.imageTitle.on( 'change', function( ){WPFNEmailEditor.getActive().find('img').attr('title', WPFNEmailEditor.imageTitle.val() );});
WPFNEmailEditor.imageTitle.update = function () {WPFNEmailEditor.imageTitle.val( WPFNEmailEditor.getActive().find('img').attr('title') );};

WPFNEmailEditor.imageWidth = jQuery( '#image-width' );
WPFNEmailEditor.imageWidth.on( 'change', function( ){WPFNEmailEditor.getActive().find('img').css('width', WPFNEmailEditor.imageWidth.val() + '%' );});
WPFNEmailEditor.imageWidth.update = function () { WPFNEmailEditor.imageWidth.val( Math.ceil( ( WPFNEmailEditor.getActive().find('img').width() / WPFNEmailEditor.getActive().find('img').closest('div').width() ) * 100 ) );};

WPFNEmailEditor.imageAlignment = jQuery( '#image-align' );
WPFNEmailEditor.imageAlignment.on( 'change', function( ){WPFNEmailEditor.getActive().find('.image-wrapper').css('text-align', WPFNEmailEditor.imageAlignment.val() );});
WPFNEmailEditor.imageAlignment.update = function () {WPFNEmailEditor.imageAlignment.val( WPFNEmailEditor.getActive().find('.image-wrapper').css('text-align') );};

WPFNEmailEditor.imageOptions = jQuery( '#image_block-editor' );
WPFNEmailEditor.showImageOptions = function() {
    this.showOptions( this.imageOptions );

    this.imageSRC.update();
    this.imageWidth.update();
    this.imageAlignment.update();
    this.imageLink.update();
    this.imageAltText.update();
    this.imageTitle.update();
};

WPFNEmailEditor.htmlContent = jQuery( '#custom-html-content' );
WPFNEmailEditor.htmlContent.on( 'change', function( ){WPFNEmailEditor.getActive().find('.content-inside').html( WPFNEmailEditor.htmlContent.val().trim() );});
WPFNEmailEditor.htmlContent.update = function () {WPFNEmailEditor.htmlContent.val( WPFNEmailEditor.getActive().find('.content-inside').html().trim() );};

WPFNEmailEditor.codeOptions = jQuery( '#code_block-editor' );
WPFNEmailEditor.showCodeOptions = function() {
    this.showOptions( this.codeOptions );
    this.htmlContent.update();
};

WPFNEmailEditor.spacerOptions = jQuery( '#spacer_block-editor' );
WPFNEmailEditor.showSpacerOptions = function() {
    this.showOptions( this.spacerOptions );

    this.spacerSize.update();
};

WPFNEmailEditor.showOptions = function( el ){
    this.actions.find( '.postbox' ).addClass( 'hidden' );
    el.removeClass( 'hidden' );
};

WPFNEmailEditor.makeActive = function ( el ) {
    if ( el.closest('#email-body').length ){
        jQuery('.row').removeClass("active");
    }

    el.closest('.row').addClass('active');
    this.active = el.closest('.row');
};

WPFNEmailEditor.richText = null;
WPFNEmailEditor.active = null;

WPFNEmailEditor.action = function( e )
{
    e.preventDefault();

    WPFNEmailEditor.active = el;

    var el = jQuery(e.target);

    if ( el.hasClass('dashicons-trash') ){
        el.closest('.row').remove();
    } else if ( el.hasClass('dashicons-admin-page') ){
        WPFNEmailEditor.richText.simpleEditor().destroy();
        el.closest('.row').removeClass('active');
        el.closest('.row').clone().insertAfter( el.closest('.row') );
    } else {

        if ( el.closest('.row').hasClass('active') )
            return true;

        WPFNEmailEditor.makeActive( el );

        if ( WPFNEmailEditor.richText ){
            console.log('destroy-editor');
            WPFNEmailEditor.richText.simpleEditor().destroy();
        }

        if ( el.closest( '.text_block' ).length ){

            WPFNEmailEditor.richText = el.closest('.content-wrapper');

            WPFNEmailEditor.richText.simpleEditor({
                defaultParagraphSeparator: 'p',
                actions: ["heading1", "heading2", "paragraph", "bold", "italic",  "underline", "color", "strikethrough", "alignLeft", "alignRight", "alignCenter", "olist", "ulist","link", "unlink"]
            });

            WPFNEmailEditor.showTextOptions();

        } else if ( el.closest( '.button_block' ).length ){

            WPFNEmailEditor.showButtonOptions();

        } else if ( el.closest( '.spacer_block' ).length ){

            WPFNEmailEditor.showSpacerOptions();

        } else if ( el.closest( '.image_block' ).length ){

            WPFNEmailEditor.showImageOptions();

        } else if ( el.closest( '.divider_block' ).length ){

            WPFNEmailEditor.showDividerOptions();

        } else if ( el.closest( '.code_block' ) ){

            WPFNEmailEditor.showCodeOptions();

        }
    }
};

jQuery(function() {
    WPFNEmailEditor.init();
    WPFNEmailEditor.content.on("click", WPFNEmailEditor.action );
});