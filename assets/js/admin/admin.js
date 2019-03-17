var wpgh;
(function($) {
    wpgh = {
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

        init:  function () {
            this.buildSelect2();
            this.buildEmailPicker();
            this.buildSMSPicker();
            this.buildContactPicker();
            this.buildTagPicker();
            this.buildSingleTagPicker();
            this.buildBenchmarkPicker();
            this.buildMetaKeyPicker();
        },
    };

    $( function () {
        wpgh.init()
    });
    $( document ).on( 'wpghAddedStep', function () {
        wpgh.init()
    });
})(jQuery);
