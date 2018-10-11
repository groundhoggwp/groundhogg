jQuery( function($){$('.gh-select2' ).select2();});
jQuery( function($){$('.gh-tag-picker' ).width(450).select2({
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
});});