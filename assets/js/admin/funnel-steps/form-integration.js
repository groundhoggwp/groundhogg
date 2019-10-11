var FormIntegration = {};

(function ($,gh,fi) {

    $.extend( fi, {

        action_prefix : 'get_form_integration_map_',

        init: function () {

            var self = this;

            $(document).on('change', '.form-integration-picker', function () {
                var $picker = $(this);
                var $step = $picker.closest('.step');
                var type = $step.attr('data-type');

                showSpinner();

                var args = {
                    action: self.action_prefix + type,
                    step_id: $step.attr('id'),
                    form_id: $picker.val()
                };

                adminAjaxRequest(args, function (response) {
                    $step.find('.field-map-wrapper').html(response.data.map);
                    hideSpinner();
                });

            });

        }
    } );

    $(function () {
       fi.init();
    });

})( jQuery, Groundhogg, FormIntegration );