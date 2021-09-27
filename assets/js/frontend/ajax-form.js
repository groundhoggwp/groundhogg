(function ($, gh) {

  const {
    routes,
    nonces,
  } = gh

  const { tracking, ajax } = routes
  const { _wprest, _ghnonce, _wpnonce } = nonces

  $.fn.serializeFormJSON = function () {

    var o = {}
    var a = this.serializeArray()
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]]
        }
        o[this.name].push(this.value || '')
      } else {
        o[this.name] = this.value || ''
      }
    })
    return o
  }

  $.fn.hideButton = function () {
    var $button = this.find('.gh-submit-button').hide()
    $button.before('<div class="gh-loader" style="font-size: 10px;margin: 20px"></div>')
  }

  $.fn.showButton = function () {
    var $button = this.find('.gh-submit-button').show()
    $('.gh-loader').remove()
  }

  $(function () {
    $('form.gh-form.ajax-submit').on('submit', function (e) {

      e.preventDefault()

      // Remove any messages
      $('.gh-form-errors-wrapper').remove()
      $('.gh-message-wrapper').remove()

      var $form = $(this)

      var data = new FormData($form[0])

      data.append('_ghnonce', _ghnonce)
      data.append('action', 'groundhogg_ajax_form_submit')

      //check if google is active
      let captchaValidated = true

      // If this element is present in the form then captcha has not been verified
      if ($form.find('.gh-recaptcha-v3').length > 0) {
        captchaValidated = false
      }

      // Captcha has been verified
      if (data.has('g-recaptcha-response')) {
        captchaValidated = true
      }

      if (captchaValidated) {

        $form.hideButton()

        $.ajax({
          method: 'POST',
          // dataType: 'json',
          url: ajax,
          data: data,
          processData: false,
          contentType: false,
          cache: false,
          timeout: 600000,
          enctype: 'multipart/form-data',
          success: function (response) {
            if (response.success == undefined) {
              console.log(response)
              $form.showButton()
              return
            }

            if (response.success) {

              $form.after('<div class="gh-message-wrapper gh-form-success-wrapper">' + response.data.message + '</div>')
              $form.trigger('reset')

            } else {
              $form.before(response.data.html)
            }

            $form.showButton()

          },
          error: function (e) {
            console.log(e)
            $form.showButton()
          }
        })
      }

    })
  })

})(jQuery, Groundhogg)