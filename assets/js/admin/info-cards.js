(function ($) {

  $(function () {
    $('.header-info').on('click', function () {
      $(this).next('.content-info').slideToggle(500)
      $(this).children('i').toggleClass('dashicons-arrow-up-alt2')
    })
  })

  $(document).on('tinymce-editor-setup', function (event, editor) {
    editor.settings.toolbar1 = 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link'; //Teeny -fullscreen
    editor.settings.height = 200;
  });

  function renderEmailEditor () {

    setTimeout(function () {

      wp.editor.initialize(
        'email_content',
        {
          tinymce: true,
          // quicktags: true
        }
      )
    }, 50)
  }

  function destroyEmailEditor () {
    wp.editor.remove(
      'email_content'
    )
  }

  $(function () {
    renderEmailEditor()

    $(document).on('GroundhoggModalContentPulled', destroyEmailEditor)
    $(document).on('GroundhoggModalContentPulled', renderEmailEditor)
    $(document).on('GroundhoggModalContentPushed', destroyEmailEditor)
    $(document).on('GroundhoggModalContentPushed', renderEmailEditor)
  })

})(jQuery)