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

            $( '.export' ).on( 'click', function () {
                wpghImportExport.export();
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

                    alert( 'Import Complete! Imported ' + wpghImportExport.allRows + ' contacts!' );
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

            var tags = $( '#import_tags' ).val();

            $.ajax({
                type: "post",
                url: ajaxurl,
                data: { action: 'wpgh_import_contacts', data: data, tags: tags }
            });

        },

        updateStatus: function () {

            var p = Math.ceil( this.completedRows / this.allRows ) * 100;
            this.status.html( 'Status: ' + p + '%' );
            console.log( 'Status: ' + p + '%' );
        },
        
        export: function() {

            var tags = $( '#export_tags' ).val();
            this.retrieve( tags );

        },
        
        retrieve: function ( tags ) {
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
        }

    };

    $(function () {
        wpghImportExport.init();
    })

})(jQuery);