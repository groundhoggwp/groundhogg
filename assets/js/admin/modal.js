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
        args: {},

		init: function ( title, href ) {

    	    this.parseArgs( href );

    		this.overlay = $( '.popup-overlay' );
    		this.window  = $( '.popup-window' );
    		this.content = $( '.popup-content' );
    		this.title   = $( '.popup-title' );
    		this.source  = $( '#' + this.args.source );
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
                this.args[ args[ 0 ] ] = args[ 1 ];
            }
            return this.args;
        },

		open: function(){
    		this.pullContent();
    		this.showPopUp();
		},

        close: function(){
            this.pushContent();
            this.hidePopUp();
        },

        /* Switch the content In the source and target between */
        pullContent: function(){
        	this.content.append( this.source.children() );
        	console.log( this.content.children() );
            $( document ).trigger( 'wpghModalContentPulled' );
        },

        pushContent: function(){
            this.source.append( this.content.children() );
            console.log( this.source.children() );
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

        reload: function()
        {
            $( document ).on( 'click', '.trigger-popup',
                function(){
                    //console.log(this.href);
                    wpghModal.init( this.title, decodeURIComponent( this.href ) );
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