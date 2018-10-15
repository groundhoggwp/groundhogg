var wpghImportExport;

( function( $ ) {

    wpghImportExport = {

        completedRows: 0,
        allRows: 0,
        status: null,
        results: null,

        /**
         * Setup the button click event
         */
        init: function() {

            $( '.import' ).on( 'click', function () {
                wpghImportExport.load();
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
                    quotes: false,
                    quoteChar: '"',
                    escapeChar: '"',
                    delimiter: ",",
                    header: true,
                    newline: "\r\n",
                    complete: function(results, file) {

                        console.log("This file done:", file, results);

                        wpghImportExport.allRows = results.data.length;
                        wpghImportExport.results = results.data;

                    }
                },
                before: function(file, inputElem)
                {
                    // executed before parsing each file begins;
                    // what you return here controls the flow
                },
                error: function(err, file, inputElem, reason)
                {
                    // executed if an error occurs while loading the file,
                    // or if before callback aborted for some reason
                },
                complete: function()
                {
                    wpghImportExport.import();

                    alert( 'Import Complete!' );
                }
            });
        },

        /**
         * Iterate through the results and import them
         */
        import: function() {

            while ( this.results.length > 0 ){

                var end = 50;
                if ( this.results.length < 50 ){
                    end  = this.results.length;
                }

                var toImport = this.results.splice( 0, end );

                this.send( toImport );

                this.completedRows += end;
                this.updateStatus();

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
                data: { action: 'wpgh_import_contacts', data: data }
            });

        },

        updateStatus: function () {

            var p = Math.ceil( this.completedRows / this.allRows ) * 100;
            this.status.html( 'Status: ' + p + '%' );
            console.log( 'Status: ' + p + '%' );
        }

    };

    $(function () {
        wpghImportExport.init();
    })

})(jQuery);