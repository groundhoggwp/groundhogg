var wpghImportExport;

( function( $ ) {

    wpghImportExport = {

        completedRows: 0,
        allRows: 0,
        status: null,
        results: null,
        import_id: null,

        /**
         * Setup the button click event
         */
        init: function() {

            $( '.import' ).on( 'click', function () {
                wpghImportExport.load();
            } );

            $( '.export' ).on( 'click', function () {
                wpghImportExport.export();
            } );

            $( '.delete' ).on( 'click', function () {
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
                        console.log("This file done:", file, results);
                        wpghImportExport.allRows = results.data.length;
                        wpghImportExport.results = results.data;
                        wpghImportExport.completedRows = 0;
                    }
                },
                complete: function()
                {
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
            while ( this.results.length > 0 ){
                var end = 50;
                if ( this.results.length < 50 ){
                    end  = this.results.length;
                }
                var toImport = this.results.splice( 0, end );
                this.send( toImport );
            }

        },

        /**
         * Send the results to the server to create the records
         *
         * @param data
         */
        send: function( data ) {

            var tags = $( '#import_tags' ).val();

            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: { action: 'wpgh_import_contacts', data: data, tags: tags, import_id: this.import_id },
                success: function( response ){
                    if ( typeof response.contacts !== "undefined" ){

                        wpghImportExport.completedRows += response.contacts;
                        wpghImportExport.updateStatus();

                    } else {
                        console.log( response );
                        alert( response );
                        var $spinner = $( '.spinner-import' );
                        $spinner.css( 'visibility', 'hidden' );
                    }
                }
            });

        },

        updateStatus: function () {

            var p = Math.ceil( ( this.completedRows / this.allRows )  * 100 );
            this.status.html( 'Status: ' + p + '%' );
            console.log( 'Status: ' + p + '%' );

            if ( p >= 100 ){
                var $spinner = $( '.spinner-import' );
                $spinner.css( 'visibility', 'hidden' );
            }

        },
        
        export: function() {
            var tags = $( '#export_tags' ).val();
            this.retrieve( tags );

        },
        
        retrieve: function ( tags ) {
            var $spinner = $( '.spinner-export' );
            $spinner.css( 'visibility', 'visible' );
            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: { action: 'wpgh_export_contacts', tags: tags },
                success: function ( json ) {
                    var CSV = Papa.unparse( json, {
                        quotes: false,
                        quoteChar: '"',
                        escapeChar: '"',
                        delimiter: ",",
                        header: true,
                        newline: "\r\n"
                    } );

                    wpghImportExport.makeFile( CSV );
                    $spinner.css( 'visibility', 'hidden' );
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
                success: function ( msg ) {
                    alert( msg );
                    $("#delete_tags").val('').change();
                    $spinner.css( 'visibility', 'hidden' );
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