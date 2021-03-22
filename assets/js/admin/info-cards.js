(function ($) {

  function init () {
    $('.info-cards-wrap .meta-box-sortables').sortable({
      placeholder: 'sortable-placeholder',
      // connectWith: '.ui-sortable',
      handle: '.hndle',
      // axis: 'y',
      start: function (e, ui) {
        ui.helper.css('left',
          (ui.item.parent().width() - ui.item.width()) / 2)
        ui.placeholder.height(ui.item.height())
        ui.placeholder.width(ui.item.width())
      },
      stop: saveInfoCardOrder
    })

    $(document).on('click', '.info-cards-wrap button.handlediv', function (e) {
      $(this).closest('.info-card').toggleClass('closed')
      saveInfoCardOrder()
    })

    $(document).on('click', '.info-cards-wrap button.handle-order-higher', function (e) {
      $(this).closest('.info-card').insertBefore($(this).closest('.info-card').prev())
      saveInfoCardOrder()
    })

    $(document).on('click', '.info-cards-wrap button.handle-order-lower', function (e) {
      $(this).closest('.info-card').insertAfter($(this).closest('.info-card').next())
      saveInfoCardOrder()
    })

    $(document).on('click', '.expand-all', function (e) {
      $('.info-card').removeClass('closed')
      saveInfoCardOrder()
    })

    $(document).on('click', '.collapse-all', function (e) {
      $('.info-card').addClass('closed')
      saveInfoCardOrder()
    })

    $(document).on('click', '.view-cards', function (e) {
      $('.info-card-views').toggleClass('hidden')
    })

    $(document).on('change', '.hide-card', function (e) {
      var $checkbox = $(this)
      if ($checkbox.is(':checked')) {
        $('.info-card#' + $checkbox.val()).removeClass('hidden')
      } else {
        $('.info-card#' + $checkbox.val()).addClass('hidden')
      }

      saveInfoCardOrder()
    })
  }

  /**
   * Add a new note
   */
  function saveInfoCardOrder () {

    var $cards = $('.info-cards-wrap .info-card')
    var cardOrder = []
    $cards.each(function (i, card) {
      cardOrder.push({
        id: card.id,
        open: !$(card).hasClass('closed'),
        hidden: $(card).hasClass('hidden'),
      })
    })

    adminAjaxRequest(
      {
        action: 'groundhogg_save_card_order',
        cardOrder: cardOrder
      }
    )
  }

  $(document).on('click', '.ic-section-header', function () {
    $(this).closest('.ic-section').toggleClass('open')
  })

  $(document).on('tinymce-editor-setup', function (event, editor) {
    editor.settings.toolbar1 = 'bold,italic,underline,blockquote,strikethrough,bullist,numlist,alignleft,aligncenter,alignright,undo,redo,link' //Teeny -fullscreen
    editor.settings.height = 200
    editor.on('click', function (ed, e) {
      $(document).trigger('to_mce')
    })
  })

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

    init()
  })

})(jQuery)
