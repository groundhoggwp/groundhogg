(function ( $, nonces, endpoints ) {
    
    function picker( selector, args ) {
        $( selector ).select2( args );
    }

    function apiPicker( selector, endpoint, multiple, tags ) {
        $( selector ).select2({
            tags: tags,
            multiple: multiple,
            tokenSeparators: ['/',',',';'],
            ajax: {
                url: endpoint,
                dataType: 'json',
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', nonces._wprest );
                },
                results: function(data, page) {
                    return {
                        results: data.results
                    };
                }
            }
        });
    }

    function linkPicker( selector )
    {
        $( selector ).autocomplete({
            source: function( request, response ) {
                $.ajax( {
                    url: ajaxurl,
                    method: 'post',
                    dataType: "json",
                    data: {
                        action: 'wp-link-ajax',
                        _ajax_linking_nonce: nonces._ajax_linking_nonce,
                        term: request.term
                    },
                    success: function( data ) {
                        var $return = [];
                        for ( var item in data ) {
                            if (data.hasOwnProperty( item ) ) {
                                item = data[ item ];
                                $return.push( { label: item.title  + ' (' + item.info + ')', value: item.permalink } );
                            }
                        }
                        response( $return );
                    }
                } );
            },
            minLength: 0
        } );
    }

    function buildPickers()
    {
        picker(     '.gh-select2',          {} );
        apiPicker(  '.gh-tag-picker',           endpoints.tags, true, true );
        apiPicker(  '.gh-single-tag-picker',    endpoints.tags, false, false );
        apiPicker(  '.gh-single-tag-picker',    endpoints.tags, false, false );
        apiPicker(  '.gh-email-picker',         endpoints.emails, false, false );
        apiPicker(  '.gh-sms-picker',           endpoints.sms, false, false );
        apiPicker(  '.gh-contact-picker',       endpoints.contacts, false, false );
        apiPicker(  '.gh-benchmark-picker',     endpoints.benchmarks, false, false );
        apiPicker(  '.gh-metakey-picker',       endpoints.metakeys, false, false );
        apiPicker(  '.gh-metakey-picker',       endpoints.metakeys, false, false );
        linkPicker( '.gh-link-picker' );
    }

    $(function () {
        buildPickers();
    });

    $( document ).on( 'new-step gh-init-pickers', function () {
        buildPickers();
    });

})(jQuery, groundhogg_nonces, groundhogg_endpoints);