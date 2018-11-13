/* 
Grab a container via the ID of the container and load that content into the box.
Display the box in the correct position of the screen.
close the thickbox and put the content back where it came from.

formliftPopUpOverlay
formliftPopUpWindow
formliftPopUpTitle
formliftPopUpContent
*/

var wpghModal;

( function ( $ ) {

    wpghModal = {

    	overlay: null,
		window: null,
		content: null,
		title: null,
		source: null,
        frameUrl: '',
        args: { height: 500, width:500 },

		init: function ( title, href ) {

    	    this.parseArgs( href );

    		this.overlay = $( '.popup-overlay' );
    		this.window  = $( '.popup-window' );
    		this.content = $( '.popup-content' );
    		this.title   = $( '.popup-title' );
    		this.loader  = $( '.iframe-loader-wrapper' );

    		var exp =/https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)/;
            var urlRegex = new RegExp( exp );

            this.sizeup();

            if ( this.args.source.match( urlRegex ) ){
                this.loader.removeClass( 'hidden' );
    		    this.source = $(
    		        "<div><iframe class='hidden' src='" + this.args.source + "' width='" + this.args.width + "' height='" + ( this.args.height - 100 ) + "' onload='wpghModal.prepareFrame( this )'></iframe></div>"
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

        },

        parseArgs: function(queryArgs){
            var querystart = queryArgs.indexOf("#");
            var listArgs = queryArgs.substring(querystart+1);
            listArgs = listArgs.split('&');
            for( var i = 0; i < listArgs.length; i++ ){
                var args = listArgs[ i ].split( '=' );
                this.args[ args[ 0 ] ] = decodeURIComponent( args[ 1 ] );
            }
            return this.args;
        },

        sizeup: function(){
            /*top: calc(50% - 250px);*/
            /*left: calc(50% - 250px);*/
            var w = Math.max(document.documentElement.clientWidth, window.innerWidth || 0);
            var h = Math.max(document.documentElement.clientHeight, window.innerHeight || 0)

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
        },

		open: function(){
    		this.pullContent();
    		this.showPopUp();
            $(document).trigger( 'wpghModalOpened' );
        },

        close: function(){
            this.pushContent();
            this.hidePopUp();
            this.reset();
            $(document).trigger( 'wpghModalClosed' );
        },

        pullFrame: function( iframe )
        {
            var $iframe = $(iframe);
            var $content = $( '#wpbody-content', $iframe.contents() );
            // console.log( $content );
            this.content.append( $content );
        },

        prepareFrame: function( iframe ){
            var $iframe = $(iframe);
            this.content.removeClass( 'hidden' );
            this.content.css( 'padding', 0 );
            $iframe.removeClass( 'hidden' );
            this.loader.addClass( 'hidden' );

            //if a link is clicked reload the frame.
            // $iframe.contents().find( 'a' ).click( function () {
            //     wpghModal.frameReload();
            // });

            //special handling for email builder.
            $iframe.contents().find( '.choose-template' ).click( function () {
                wpghModal.frameReload();
            });
        },

        frameReload: function()
        {
            this.content.addClass( 'hidden' );
            this.loader.removeClass( 'hidden' );
        },

        /* Switch the content In the source and target between */
        pullContent: function(){
        	this.content.append( this.source.children() );
        	// console.log( this.content.children() );
            $( document ).trigger( 'wpghModalContentPulled' );
        },

        pushContent: function(){
            this.source.append( this.content.children() );
            // console.log( this.source.children() );
            $( document ).trigger( 'wpghModalContentPushed' );
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

        reset: function()
        {
            this.args = { height: 500, width:500 };
            this.content.css( 'padding', '0 20px' );
        },

        reload: function()
        {
            $( document ).on( 'click', '.trigger-popup',
                function(e){
                    e.preventDefault();
                    //console.log(this.href);
                    wpghModal.init( this.title, this.href );
                    wpghModal.open();
                }
            );

            $( document ).on( 'click', '#popup-close',
                function(){
                    wpghModal.close();
                }
            );

            $( document ).on( 'click', '.popup-save',
                function(){
                    wpghModal.close();
                }
            );
        }
	};

	$(function () {
		wpghModal.reload();
		$( '.wpgh-color' ).wpColorPicker();
    });
    $( document ).on( 'wpghAddedStep', function () {
        // wpghModal.reload();
        $( '.wpgh-color' ).wpColorPicker();
    });

} )(jQuery);