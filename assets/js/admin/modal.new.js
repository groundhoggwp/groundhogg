/* 
Grab a container via the ID of the container and load that content into the box.
Display the box in the correct position of the screen.
close the thickbox and put the content back where it came from.
*/

var GroundhoggModal = {};

( function ( $, modal, defaults ) {

    $.extend( modal, {

    	overlay: null,
		window: null,
		content: null,
		title: null,
		source: null,
        frameUrl: '',
        args: {},
        defaults: {},

		init: function ( title, href ) {

    	    var self=this;

    	    Object.assign( this.args, defaults );

    	    // console.log( href );

    	    if ( typeof href == "string" ){
    	        this.parseArgs( href );
            } else {
    	        this.args = $.extend( defaults, href );
            }

    		this.overlay = $( '.popup-overlay' );
    		this.window  = $( '.popup-window' );
    		this.content = $( '.popup-content' );
    		this.title   = $( '.popup-title' );
    		this.loader  = $( '.iframe-loader-wrapper' );

    		//Hi

            this.sizeup();

            if ( this.matchUrl( this.args.source ) ){
                this.loader.removeClass( 'hidden' );
    		    this.source = $(
    		        "<div><iframe class='hidden' src='" + this.args.source + "' width='" + this.args.width + "' height='" + ( this.args.height - 100 ) + "' style='margin-bottom: -5px;' onload='GroundhoggModal.prepareFrame( this )'></iframe></div>"
                );
            } else {
                this.source  = $( '#' + this.args.source );
            }

    		this.title.text( title );

    		if ( typeof this.args.footer !== "undefined" && this.args.footer === 'false' ){
    		    $( '.popup-footer' ).addClass( 'hidden' );
            } else {
    		    $( '.popup-footer' ).removeClass( 'hidden' );
            }

            if ( typeof this.args.footertext !== "undefined" ) {
                $( '#popup-close-footer' ).text( this.args.footertext );
            }

            self.open();
        },

        matchUrl: function (maybeUrl){
            var exp =/(https|http)?:\/\/((www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6})|(localhost)\b([-a-zA-Z0-9@:%_\+.~#?&\/=]*)/;
            var urlRegex = new RegExp( exp );
            return maybeUrl.match( urlRegex );
        },

        parseArgs: function(queryArgs){
            var querystart = queryArgs.indexOf("#");
            var listArgs = queryArgs.substring(querystart+1);
            listArgs = listArgs.split('&');
            for( var i = 0; i < listArgs.length; i++ ){
                var args = listArgs[ i ].split( '=' );
                this.args[ args[ 0 ] ] = decodeURIComponent( args[ 1 ].replace( '+', ' ' ) );
            }
            return this.args;
        },

        sizeup: function(){
            /*top: calc(50% - 250px);*/
            /*left: calc(50% - 250px);*/
            var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0);

            if ( this.args.height > h ){
                this.args.height = h - 50;
            }

            if ( this.args.width > w ){
                this.args.width = w - 50;
            }

    	    this.window.css( 'height', this.args.height + 'px' );
    	    this.window.css( 'width', this.args.width + 'px' );
    	    this.window.css( 'top', 'calc( 50% - ' + ( this.args.height / 2 ) + 'px )' );
    	    this.window.css( 'left', 'calc( 50% - ' + ( this.args.width / 2 ) + 'px )' );
    	    this.content.css( 'height', ( this.args.height - 100 ) + 'px');
    	    this.loader.css( 'height', ( this.args.height - 100 ) + 'px');
        },

		open: function(){
    		this.pullContent();
    		this.showPopUp();
            $(document).trigger( 'GroundhoggModalOpened' );
        },

        close: function(){
            this.pushContent();
            this.hidePopUp();
            if ( this.args.preventSave === undefined || this.args.preventSave === false || this.args.preventSave === 'false' ){
                $(document).trigger( 'GroundhoggModalClosed' );
            }
            this.reset();
        },

        pullFrame: function( iframe )
        {
            var $iframe = $(iframe);
            var $content = $( '#wpbody-content', $iframe.contents() );
            this.content.append( $content );
        },

        prepareFrame: function( iframe ){
    	    var self = this;
            var $iframe = $(iframe);
            $iframe.removeClass( 'hidden' );

            this.content.removeClass( 'hidden' );
            this.content.css( 'padding', 0 );
            this.loader.addClass( 'hidden' );
        },

        frameReload: function()
        {
            this.content.addClass( 'hidden' );
            this.loader.removeClass( 'hidden' );
        },

        /* Switch the content In the source and target between */
        pullContent: function(){
        	this.content.append( this.source.children() );
            $( document ).trigger( 'GroundhoggModalContentPulled' );
        },

        pushContent: function(){
            this.source.append( this.content.children() );
            $( document ).trigger( 'GroundhoggModalContentPushed' );
        },

        /* Load the PopUp onto the screen */
        showPopUp: function(){
        	this.overlay.removeClass( 'hidden' );
        	this.window.removeClass( 'hidden' );
            this.overlay.fadeIn();
            this.window.fadeIn();
        },

        /* Close the PopUp */
        hidePopUp: function(){
            this.window.addClass( 'hidden' );
            this.overlay.addClass( 'hidden' );
        },

        getDefaults: function()
        {
            return JSON.parse(JSON.stringify(defaults));
        },

        reset: function()
        {
            this.args = this.getDefaults();
            this.content.css( 'padding', '0 20px' );
        },

        reload: function()
        {
            var self = this;

            $( document ).on( 'click', '.trigger-popup',
                function(e){
                    e.preventDefault();
                    //console.log(this.href);
                    self.init( this.title, this.href );

                    if ( $(this).hasClass( 'no-padding' ) ){
                        $( '.popup-content' ).css( 'padding', '0' )
                    }
                }
            );

            $( document ).on( 'click', '.popup-close',
                function(){
                    $(document).trigger( 'modal-closed' );
                    self.close();
                }
            );
        }
	} );

	$(function () {
		modal.reload();
		$( '.wpgh-color' ).wpColorPicker();
    });

    $( document ).on( 'new-step', function () {
        $( '.wpgh-color' ).wpColorPicker();
    });

} )(jQuery, GroundhoggModal, GroundhoggModalDefaults);