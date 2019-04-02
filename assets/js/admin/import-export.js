var wpghImportExport;

( function( $ ) {

    wpghImportExport = {

        completedRows: 0,
        skippedRows: 0,
        allRows: 0,
        status: null,
        results: null,
        import_id: null,
        tags: null,
        size: 100,
        contactsToDelete: 0,
        deletedContacts: 0,


        /**
         * Setup the button click event
         */
        init: function() {

            $( '.import' ).on( 'click', function () {
                wpghImportExport.load();
            } );

            $( '.export' ).on( 'click', function () {
                wpghImportExport.export( 'wpgh_export_contacts' );
            } );

            $( '.query-export' ).on( 'click', function () {
                wpghImportExport.export( 'wpgh_query_export_contacts' );
            } );

            $( '.bulk-delete' ).on( 'click', function () {
                wpghImportExport.bulkDelete();
            } );

            this.status = $( '.import-status' );

            console.log( 'Importer Ready' );
        },

        /**
         * Parse the given file and send it to the importer
         */
        load: function () {
            $('input[type=file]').parse({
                config: {
                    download: true,
                    delimiter: ",",
                    header: true,
                    complete: function(results, file) {
                        // console.log("This file done:", file, results);
                        wpghImportExport.allRows = results.data.length;
                        wpghImportExport.results = results.data;
                        wpghImportExport.completedRows = 0;
                        wpghImportExport.skippedRows = 0;
                    }
                },
                complete: function()
                {
                    wpghImportExport.tags = $( '#import_tags' ).val();
                    wpghImportExport.import_id = wpghImportExport.guidGenerator();
                    wpghImportExport.import();
                }
            });
        },

        /**
         * Iterate through the results and import them
         */
        import: function() {

            var $spinner = $( '.spinner-import' );
            $spinner.css( 'visibility', 'visible' );

            var end = this.size;

            if ( this.results.length < this.size ){
                end  = this.results.length;
            }

            var toImport = this.results.splice( 0, end );

            for ( var i = 0; i < toImport.length; i++ ){
                this.clean( toImport[ i ] )
            }

            this.send( toImport );

        },

        clean: function( obj ){
            var propNames = Object.getOwnPropertyNames(obj);
            for (var i = 0; i < propNames.length; i++) {
                var propName = propNames[i];
                if (obj[propName] === null || obj[propName] === undefined || obj[propName] === '' ) {
                    delete obj[propName];
                }
            }
        },

        /**
         * Send the results to the server to create the records
         *
         * @param data
         */
        send: function( data ) {

            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: { action: 'wpgh_import_contacts', data: data, tags: this.tags, import_id: this.import_id },
                success: function( response ){
                    if ( typeof response.completed !== "undefined" ){
                        wpghImportExport.completedRows += response.completed;
                        wpghImportExport.skippedRows += response.skipped;
                        wpghImportExport.updateStatus();

                        if ( wpghImportExport.results.length > 0 ){
                            wpghImportExport.import();
                        }

                    } else {
                        console.log( response );
                        alert( response );
                        var $spinner = $( '.spinner-import' );
                        $spinner.css( 'visibility', 'hidden' );
                    }
                },
                error: function ( response ) {
                    console.log( response );
                    alert( response );
                    var $spinner = $( '.spinner-import' );
                    $spinner.css( 'visibility', 'hidden' );
                }
            });

        },

        updateStatus: function () {

            var p = Math.ceil( ( ( this.completedRows + this.skippedRows ) / this.allRows )  * 100 );

            $( '#import-loader-wrap' ).removeClass( 'hidden' );
            $( '#import-loader' ).animate( { 'width' : p + '%' }, 'slow' );
            $( '#import-loader-percentage' ).text( p + '%' );

            this.status.html( 'Status: ' + p + '% | Completed: ' + this.completedRows + ' | Skipped: ' + this.skippedRows );
            console.log( { status: p, completed: this.completedRows, skipped: this.skippedRows } );

            if ( p >= 100 ){
                var $spinner = $( '.spinner-import' );
                $spinner.css( 'visibility', 'hidden' );
            }

        },
        
        export: function( hook ) {
            var tags = $( '#export_tags' ).val();
            this.retrieve( hook, tags );

        },
        
        retrieve: function ( hook, tags ) {
            var $spinner = $( '.spinner-export' );
            $spinner.css( 'visibility', 'visible' );
            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: { action: hook, tags: tags },
                success: function ( json ) {

                    $spinner.css( 'visibility', 'hidden' );

                    var CSV = Papa.unparse( json, {
                        quotes: false,
                        quoteChar: '"',
                        escapeChar: '"',
                        delimiter: ",",
                        header: true,
                        newline: "\r\n"
                    } );

                    wpghImportExport.makeFile( CSV );
                }
            });
        },

        makeFile : function (text) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(text));

            var today = new Date();
            var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
            var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
            var dateTime = date+' '+time;

            element.setAttribute('download', 'contacts-' + dateTime + '.csv' );

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        },

        bulkDelete : function () {

            var tags = $( '#delete_tags' ).val();
            var $spinner = $( '.spinner-delete' );
            $spinner.css( 'visibility', 'visible' );

            $.ajax({
                type: "post",
                url: ajaxurl,
                data: { action: 'wpgh_bulk_delete_contacts', tags: tags },
                dataType: 'json',
                success: function ( response ) {

                    console.log( response );

                    if ( response.complete !== undefined ){
                        $("#delete_tags").val('').change();
                        $spinner.css( 'visibility', 'hidden' );

                        $( '#delete-loader-percentage' ).text( response.message );

                    } else {

                        wpghImportExport.deletedContacts += response.contactsDeleted;

                        var p = Math.round( ( wpghImportExport.deletedContacts / parseInt( response.totalContacts ) * 100 ) );

                        $( '#delete-loader-wrap' ).removeClass( 'hidden' );
                        $( '#delete-loader' ).animate( { 'width' : p + '%' }, 'slow' );
                        $( '#delete-loader-percentage' ).text( p + '%' );

                        wpghImportExport.bulkDelete();

                    }
                }
            });
        },

        guidGenerator : function () {
            var S4 = function() {
                return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
            };
            return (S4()+S4()+"-"+S4()+"-"+S4()+"-"+S4()+"-"+S4()+S4()+S4());
        }

    };

    $(function () {
        wpghImportExport.init();
    })

})(jQuery);