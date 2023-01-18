( function (gh) {

  const {
    nonces,
    i18n,
    routes,
    reCAPTCHA,
  } = gh

  const { _wprest } = nonces

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
      }
      else {
        dotsHolder.innerText = dotsHolder.innerText + '.'
      }
    }, 500)

    return {
      stop,
    }
  }

  let ajaxFinEvt = new CustomEvent('ajaxfinished')
  let formSubmitted = new CustomEvent('ghformsubmitted')

  const apiPostFormData = async (url, data, opts = {}) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'X-WP-Nonce': _wprest,
      },
      body: data,
      ...opts,
    })

    return response.json()
  }

  function inIframe () {
    try {
      return window.self !== window.top
    }
    catch (e) {
      return true
    }
  }

  const submitForm = (form) => {

    let submitText = ''

    let btn = form.querySelector('button[type="submit"]')

    btn.disabled = true
    submitText = btn.innerHTML
    btn.innerHTML = i18n.submitting
    let { stop } = loadingDots(btn)

    form.parentNode.querySelectorAll('.gh-success, .gh-errors').forEach(el => el.remove())

    let fd = new FormData(form)

    let uuid = form.id

    apiPostFormData(`${ routes.forms }/${ uuid }/`, fd).then(r => {

      stop()
      btn.innerHTML = submitText
      btn.disabled = false

      if (r.code) {

        let message

        switch (r.code) {
          case 'failed_to_submit':
            message = `
                  <p>${ r.message }</p>
                  <ul>${ r.additional_errors.map(err => `<li><b>${ err.data }:</b> ${ err.message }</li>`).
              join('') }</ul>`
            break
          default:
            message = `<p>${ r.message }</p>`
        }

        let msg = document.createElement('div')
        //language=HTML
        msg.innerHTML = message
        msg.classList.add(...['gh-errors'])

        form.parentNode.appendChild(msg)

        return

      }

      if (r.url) {
        setTimeout(() => {
          window.open(r.url, inIframe() ? '_parent' : '_self')
        }, 500)
      }

      if (r.message) {
        let msg = document.createElement('div')
        //language=HTML
        msg.innerHTML = r.message
        msg.classList.add(...['gh-success'])
        form.parentNode.appendChild(msg)

        form.reset()

      }

    }).then(() => {

      form.dispatchEvent(ajaxFinEvt)
      form.dispatchEvent(formSubmitted)

    }).catch(e => {
      alert(e.message)
    })

  }

  const formEventListener = e => {

    e.preventDefault()

    let form = e.currentTarget

    let hasRecaptcha = form.querySelectorAll('.gh-recaptcha-v3').length > 0
    let hasRecaptchaResponse = form.querySelectorAll('input[name="g-recaptcha-response"]').length > 0

    if (hasRecaptcha && !hasRecaptchaResponse) {

      grecaptcha.ready(() => {
        grecaptcha.execute(reCAPTCHA.site_key, { action: 'submit' }).then((_token) => {

          // Add your logic to submit to your backend server here.
          const input = document.createElement('input')
          input.type = 'hidden'
          input.name = 'g-recaptcha-response'
          input.value = _token

          form.appendChild(input)

          submitForm(form)
        })
      })

      // quit here, submit handled in callback
      return
    }

    submitForm(form)

  }

  /**
   * Adds Groundhogg form event listener based on given selector
   *
   * @param selector
   */
  const addFormEventListeners = (selector = '') => {

    document.querySelectorAll(`${ selector } form.gh-form.gh-form-v2`.trim()).forEach(__form => {

      // Don't add the event listener twice
      if (__form.hasAttribute('ghformhandler')) {
        return
      }

      __form.addEventListener('submit', formEventListener)

      __form.setAttribute('ghformhandler', 'true')

    })
  }

  // Which events to add form event listeners to, and the relevant selector
  const events = {
    'load': '',
    'elementor/popup/show': '.elementor-location-popup',
  }

  Object.keys(events).forEach(event => {
    window.addEventListener(event, e => addFormEventListeners(events[event]))
  })

} )(Groundhogg)
