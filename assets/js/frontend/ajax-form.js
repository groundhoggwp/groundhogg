(function (gh) {

  const {
    nonces,
    i18n
  } = gh

  const { _wprest, _ghnonce, _wpnonce } = nonces
  const { adminAjax } = gh

  const loadingDots = (el) => {

    let dotsHolder = document.createElement('span')
    dotsHolder.classList.add('loading-dots')

    el.appendChild(dotsHolder)

    const stop = () => {
      clearInterval(interval)
      dotsHolder.remove()
    }

    const interval = setInterval(() => {
      if (dotsHolder.innerText.length >= 3) {
        dotsHolder.innerText = '.'
      } else {
        dotsHolder.innerText(dotsHolder.innerText + '.')
      }
    }, 500)

    return {
      stop
    }
  }

  function insertAfter(newNode, referenceNode) {
    referenceNode.parentNode.insertBefore(newNode, referenceNode.nextSibling);
  }

  let ajaxFinEvt = new CustomEvent( 'ajaxfinished' )

  const handleAjaxForms = () => {

    document.querySelectorAll('form.gh-form.ajax-submit').forEach(__form => {

      __form.addEventListener('submit', e => {

        e.preventDefault()

        let form = e.currentTarget
        let submitText = ''

        let btn = form.querySelector('button[type="submit"]')

        btn.disabled = true
        submitText = btn.innerHTML
        let { stop } = loadingDots(btn)

        let fd = new FormData(form)
        fd.append('_ghnonce', _ghnonce)
        fd.append('action', 'groundhogg_ajax_form_submit')

        //check if google is active
        let captchaValidated = true

        // If this element is present in the form then captcha has not been verified
        if (form.querySelectorAll('.gh-recaptcha-v3').length > 0) {
          captchaValidated = false
        }

        // Captcha has been verified
        if (fd.has('g-recaptcha-response')) {
          captchaValidated = true
        }

        if (captchaValidated) {
          adminAjax(fd).then(r => {

            stop()
            btn.innerHTML = submitText

            if (r.success === undefined) {
              return
            }

            if (r.success) {

              let msg = document.createElement('div')
              msg.innerHTML = r.data.message
              msg.classList.add( ...['gh-message-wrapper', 'gh-form-success-wrapper'])

              form.parentNode.appendChild(msg)

              form.reset()

            } else {

              let msg = document.createElement('div')
              msg.innerHTML = r.data.html

              form.parentNode.insertBefore( msg.firstChild, form)

            }

            form.dispatchEvent( ajaxFinEvt )

          }).catch(e => {
            alert(e.message)
          })
        }

      })

    })
  }

  window.addEventListener('load', handleAjaxForms)

})(Groundhogg)