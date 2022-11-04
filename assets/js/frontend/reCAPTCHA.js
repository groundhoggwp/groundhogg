( function (reCAPTCHA) {

  function protectForms () {

    document.querySelectorAll('form.gh-form:not(.gh-form-v2)').forEach(form => {

      // only if the form has recaptcha
      if (!form.querySelector('.gh-recaptcha-v3')) {
        return;
      }

      form.addEventListener('submit', e => {

        if (!form.querySelector('.gh-recaptcha-v3')) {
          return;
        }

        e.preventDefault()

        grecaptcha.ready(function () {
          grecaptcha.execute(reCAPTCHA.site_key, { action: 'submit' }).then((_token) => {

            // Add your logic to submit to your backend server here.
            const input = document.createElement('input')
            input.type = 'hidden'
            input.name = 'g-recaptcha-response'
            input.value = _token

            form.appendChild(input)

            // dont use
            form.dispatchEvent(new Event('submit'))
          })
        })

        return false;

      })

    })
  }

  window.addEventListener( 'load', protectForms )

} )(ghReCAPTCHA)
