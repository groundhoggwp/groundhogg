var wpgh;
(function($) {
    wpgh = {

        elements : [],

        buildSelect2: function(){
            $('.gh-select2' ).css( 'width', '100%' ).select2();
        },
        buildTagPicker: function() {
            $('.gh-tag-picker' ).css( 'width', '100%' ).select2({
                tags:true,
                multiple: true,
                tokenSeparators: ['/',',',';'],
                ajax: {
                    url: gh_admin_object.tags_endpoint,
                    dataType: 'json',
                    beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', gh_admin_object.nonce );
                    },
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildSingleTagPicker: function() {
            $('.gh-single-tag-picker' ).css( 'width', '100%' ).select2({
                tags:false,
                multiple: false,
                tokenSeparators: ['/',',',';'],
                ajax: {
                    url: gh_admin_object.tags_endpoint,
                    dataType: 'json',
                    beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', gh_admin_object.nonce );
                    },
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildEmailPicker: function() {
            $('.gh-email-picker' ).css( 'width', '100%' ).select2({
                ajax: {
                    url: gh_admin_object.emails_endpoint,
                    dataType: 'json',
                    beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', gh_admin_object.nonce );
                    },
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildSMSPicker: function() {
            $('.gh-sms-picker' ).css( 'width', '100%' ).select2({
                ajax: {
                    url: gh_admin_object.sms_endpoint,
                    dataType: 'json',
                    beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', gh_admin_object.nonce );
                    },
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildContactPicker: function (){
            $('.gh-contact-picker' ).css( 'width', '100%' ).select2({
                ajax: {
                    url: gh_admin_object.contacts_endpoint,
                    dataType: 'json',
                    beforeSend: function ( xhr ) {
                        xhr.setRequestHeader( 'X-WP-Nonce', gh_admin_object.nonce );
                    },
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildBenchmarkPicker: function(){
            $('.gh-benchmark-picker' ).css( 'width', '100%' ).select2({
                ajax: {
                    url: ajaxurl + '?action=gh_get_benchmarks',
                    dataType: 'json',
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildMetaKeyPicker: function(){
            $('.gh-metakey-picker' ).css( 'width', '100%' ).select2({
                ajax: {
                    url: ajaxurl + '?action=gh_get_meta_keys',
                    dataType: 'json',
                    results: function(data, page) {
                        return {
                            results: data.results
                        };
                    }
                }
            });
        },
        buildLinkPicker : function(){
            $('.gh-link-picker' ).autocomplete({
                source: function( request, response ) {
                    $.ajax( {
                        url: ajaxurl,
                        method: 'post',
                        dataType: "json",
                        data: {
                            action: 'wp-link-ajax',
                            _ajax_linking_nonce: gh_admin_object._ajax_linking_nonce,
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
                minLength: 2
            } );
        },

        init:  function () {
            this.buildSelect2();
            this.buildEmailPicker();
            this.buildSMSPicker();
            this.buildContactPicker();
            this.buildTagPicker();
            this.buildSingleTagPicker();
            this.buildBenchmarkPicker();
            this.buildMetaKeyPicker();
            this.buildLinkPicker();
        },
    };

    $( function () {
        wpgh.init()
    });
    $( document ).on( 'wpghAddedStep', function () {
        wpgh.init()
    });
})(jQuery);
