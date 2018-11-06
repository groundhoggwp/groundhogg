const wpgh = {

    buildSelect2: function(){
      jQuery('.gh-select2' ).css( 'width', '100%' ).select2();
    },

    buildTagPicker: function() {
        jQuery('.gh-tag-picker' ).css( 'width', '100%' ).select2({
          tags:true,
          multiple: true,
          tokenSeparators: ['/',',',';'],
          ajax: {
              url: ajaxurl + '?action=gh_get_tags',
              dataType: 'json',
              results: function(data, page) {
                  return {
                      results: data.results
                  };
              }
          }
        });
    },

    buildEmailPicker: function() {
        jQuery('.gh-email-picker' ).css( 'width', '100%' ).select2({
            ajax: {
                url: ajaxurl + '?action=gh_get_emails',
                dataType: 'json',
                results: function(data, page) {
                    return {
                        results: data.results
                    };
                }
            }
        });
    },

    buildContactPicker: function (){
        jQuery('.gh-contact-picker' ).css( 'width', '100%' ).select2({
            ajax: {
                url: ajaxurl + '?action=gh_get_contacts',
                dataType: 'json',
                results: function(data, page) {
                    return {
                        results: data.results
                    };
                }
            }
        });
    },

    buildBenchmarkPicker: function(){
        jQuery('.gh-benchmark-picker' ).css( 'width', '100%' ).select2({
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
        jQuery('.gh-metakey-picker' ).css( 'width', '100%' ).select2({
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
        this.buildContactPicker();
        this.buildTagPicker();
        this.buildBenchmarkPicker();
        this.buildMetaKeyPicker();

    },

};

jQuery( function () {
    wpgh.init()
});
jQuery( document ).on( 'wpghAddedStep', function () {
    wpgh.init()
} );
// jQuery( document ).on( 'wpghModalContentPulled', function () {
//     wpgh.init()
// } );