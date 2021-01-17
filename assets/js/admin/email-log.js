(function ($){
  var $doc = $(document);

  var $portal = $('#modal-log-details-view');

  $doc.on('click', 'a.view-email-log', function (e) {

    e.preventDefault();

    var $e = $(e.target);

    var ajaxCall = $.ajax({
      type: "post",
      url: ajaxurl,
      dataType: 'json',
      data: {action: 'groundhogg_view_email_log', preview: $e.attr( 'data-log-id' ) },
      success: function (response) {

        $portal.html( response.data.content );

        fullFrame();
      },
    });

  });

})(jQuery)
