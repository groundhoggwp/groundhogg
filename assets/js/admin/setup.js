( ($) => {

  const {
    input,
    toggle,
    spinner,
    icons,
    loadingDots,
    adminPageURL,
    dialog,
    modal,
  } = Groundhogg.element

  const {
    linkPicker,
  } = Groundhogg.pickers

  const { currentUser } = Groundhogg

  const { userHasCap } = Groundhogg.user

  let optedIntoTelementry, acceptedMarketing, installedMailhawk,
    isLicensed = false

  const {
    options: Options,
  } = Groundhogg.stores

  const { post, delete: _delete, get, patch, routes, ajax } = Groundhogg.api

  const { sprintf, __, _x, _n } = wp.i18n

  const downloadTemplate = (download) => {
    //language=HTML
    return `
        <div class="gh-panel outlined download">
            <div class="inside">
                <div class="space-between align-top gap-20">
                    <img src="${ download.info.thumbnail }">
                    <div class="right">
                        <h2>${ download.info.title }</h2>
                        <div class="description">
                            ${ download.info.excerpt }
                        </div>
                    </div>
                </div>
            </div>
            <div class="plugin-actions space-between">
                <p>
                    <a href="#" data-id="${ download.info.id }"
                       class="more-details" target="_blank">${ __(
                            'More details') }</a>
                </p>
                <button data-link="${ download.info.link }&utm_source=plugin&utm_medium=extension&utm_campaign=guided_setup&utm_content=${ currentStep.id }"
                        data-id="${ download.info.id }"
                        class="link gh-button buy-download secondary">
                    ${ __('Purchase', 'groundhogg-helper') }
                </button>
            </div>
        </div>`
  }

  const downloadMoreDetails = (download) => {

    modal({
      dialogClasses: 'no-padding',
      //language=HTML
      content: `
          <img class="thumbnail" src="${ download.info.thumbnail }">
          <div id="more-details-content">
              <h1>${ download.info.title }</h1>
              <div class="gh-panel outlined">
                  <div class="inside display-flex gap-10">
                      <a style="display: inline-block" class="gh-button primary"
                    href="https://groundhogg.io/secure/checkout/?edd_action=add_to_cart&download_id=${ download.info.id }">${ __('Buy Now!',
        'groundhogg') }</a>
                  <a style="display: inline-block" class="gh-button secondary"
                     href="${ download.info.link }">${ __('View on Groundhogg.io',
        'groundhogg') }</a></div>
              </div>
              
              ${ download.info.content }
          </div>`,
      width: 500,
    })
  }

  const stepTemplate = ({
    inside = () => '',
    logo = icons.groundhogg_black,
    showBack = true,
  }) => {

    // language=HTML
    return `
        <div class="step">
            <div class="logo">
                ${ logo }
            </div>
            <div class="gh-panel">
                <div class="inside">
                    ${ inside() }
                </div>
            </div>
            ${ showBack ?
                    `<div class="back" style="margin-top: 20px">
				<button id="back" class="gh-button secondary text">⬅️${ __('Back',
                            'groundhogg') }</button>
			</div>` : '' }
        </div>`

  }

  const steps = [
    {
      id: 'start',
      render: () => {

        return stepTemplate({
          showBack: false,
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Welcome to Groundhogg 🎉', 'groundhogg') }</h1>
                <p>
                    ${ __('In just a few minutes you\'ll have Groundhogg configured for your site! Click the button below to start the guided setup.',
                            'groundhogg') }</p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="start" class="gh-button primary big">
                        ${ __('Let\'s get started!', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {
        $('#start').on('click', () => next())
      },
      next: () => 'business-info',
    },
    {
      id: 'business-info',
      render: () => {

        let {
          gh_business_name = '',
          gh_phone = '',
          gh_street_address_1 = '',
          gh_street_address_2 = '',
          gh_city = '',
          gh_region = '',
          gh_country = '',
          blogname = '',
        } = Options.items

        return stepTemplate({
          showBack: false,
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Business Details', 'groundhogg') }</h1>
                <p>
                    ${ __('Your business information is needed so your list knows who is sending them emails. All this information will appear in your email footer.',
                            'groundhogg') }</p>
                <div class="gh-rows-and-columns">
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="business-name">${ __('Business Name',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_business_name',
                                id: 'business-name',
                                placeholder: __('My Business', 'groundhogg'),
                                value: gh_business_name
                                        ? gh_business_name
                                        : blogname,
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="business-name">${ __('Business Phone',
                                    'groundhogg') }</label>
                            ${ input({
                                type: 'tel',
                                name: 'gh_phone',
                                id: 'business-phone',
                                placeholder: __('+1 (555) 555-5555',
                                        'groundhogg'),
                                value: gh_phone,
                            }) }
                        </div>
                    </div>
                </div>
                <h2>${ __('Business Address', 'groundhogg') }</h2>
                <div class="gh-rows-and-columns">
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="line1">${ __('Line 1',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_street_address_1',
                                id: 'line1',
                                value: gh_street_address_1,
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="line2">${ __('Line 2',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_street_address_2',
                                id: 'line2',
                                value: gh_street_address_2,
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="city">${ __('City',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_city',
                                value: gh_city,
                                id: 'city',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="state">${ __('State',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_region',
                                id: 'state',
                                value: gh_region,
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="country">${ __('Country',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_country',
                                id: 'country',
                                value: gh_country,
                            }) }
                        </div>
                        <div class="gh-col">

                        </div>
                    </div>
                </div>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('Skip', 'groundhogg') }
                    </button>
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        let options = {}

        $('input.input').on('input change', e => {
          options[e.target.name] = e.target.value
        })

        $('#next').on('click', () => {
          Options.patch(options)
          next()
        })
        $('#skip').on('click', () => next())
      },
      next: () => 'compliance-info',
    },
    {
      id: 'compliance-info',
      render: () => {

        let {
          gh_privacy_policy = '',
          gh_terms = '',
        } = Options.items

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Privacy & Compliance', 'groundhogg') }</h1>
                <p>
                    ${ __('Answer the questions below and we\'ll configure Groundhogg to be in compliance with the relevant privacy and marketing regulations.',
                            'groundhogg') }</p>
                <div class="gh-rows-and-columns">
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="privacy-policy">${ __(
                                    'Link to Privacy Policy',
                                    'groundhogg') }</label>
                            ${ input({
                                type: 'url',
                                className: 'link-picker input',
                                name: 'gh_privacy_policy',
                                id: 'privacy-policy',
                                value: gh_privacy_policy,
                                placeholder: 'https://',
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="terms">${ __(
                                    'Link to Terms & Conditions',
                                    'groundhogg') }</label>
                            ${ input({
                                type: 'url',
                                className: 'link-picker input',
                                name: 'gh_terms',
                                id: 'terms',
                                value: gh_terms,
                                placeholder: 'https://',
                            }) }
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <p>${ __('Do you market to people in these areas?',
                                    'groundhogg') }</p>
                            <div class="display-flex" style="gap:30px">
                                <label>${ input(
                                        { type: 'checkbox', id: 'canada' }) }
                                    ${ __('Canada', 'groundhogg') }</label>
                                <label>${ input(
                                        { type: 'checkbox', id: 'eu' }) }
                                    ${ __('European Union',
                                            'groundhogg') }</label>
                                <label>${ input({
                                    type: 'checkbox',
                                    id: 'california',
                                }) }
                                    ${ __('California (United States)',
                                            'groundhogg') }</label>
                            </div>
                        </div>
                    </div>
                    <div class="gh-row">
                        <div class="gh-col">
                            <p>
                                ${ __('Is your business registered within the European Union?',
                                        'groundhogg') }</p>
                            <div class="display-flex" style="gap:30px">
                                <label>${ input(
                                        { type: 'checkbox', id: 'in-eu' }) }
                                    ${ __('Yes', 'groundhogg') }</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('Skip', 'groundhogg') }
                    </button>
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        let options = {}

        $('#canada').on('change', e => {

          if (e.target.checked) {
            options = {
              ...options,
              gh_strict_confirmation: ['on'],
              gh_confirmation_grace_period: 14,
            }
          }
          else {
            delete options.gh_strict_confirmation
            delete options.gh_confirmation_grace_period
          }

        })

        $('#eu, #california').on('change', e => {

          if (e.target.checked) {
            options = {
              ...options,
              gh_enable_gdpr: ['on'],
            }
          }
          else {
            delete options.gh_enable_gdpr
          }

        })

        $('#in-eu').on('change', e => {

          if (e.target.checked) {
            options = {
              ...options,
              gh_strict_gdpr: ['on'],
              gh_disable_unnecessary_cookies: ['on'],
            }
          }
          else {
            delete options.gh_strict_gdpr
            delete options.gh_disable_unnecessary_cookies
          }

        })

        linkPicker('input.link-picker').on('input change', e => {
          options[e.target.name] = e.target.value
        })

        $('#next').on('click', () => {
          Options.patch(options)
          next()
        })

        $('#skip').on('click', () => next())
      },
      next: () => 'sending-email',
    },
    {
      id: 'sending-email',
      render: () => {

        let {
          gh_override_from_name = '',
          gh_override_from_email = '',
        } = Options.items

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Sending Email', 'groundhogg') }</h1>
                <p>
                    ${ __('Who do you want your emails to send from? <i>This can always be changed later.</i>',
                            'groundhogg') }</p>
                <div class="gh-rows-and-columns">
                    <div class="gh-row">
                        <div class="gh-col">
                            <label for="from-name">${ __('From Name',
                                    'groundhogg') }</label>
                            ${ input({
                                name: 'gh_override_from_name',
                                id: 'from-name',
                                value: gh_override_from_name,
                                placeholder: __('John Doe', 'groundhogg'),
                            }) }
                        </div>
                        <div class="gh-col">
                            <label for="from-email">${ __('From Address',
                                    'groundhogg') }</label>
                            ${ input({
                                type: 'email',
                                name: 'gh_override_from_email',
                                id: 'from-email',
                                value: gh_override_from_email,
                                placeholder: __('john@example.com',
                                        'groundhogg'),
                            }) }
                        </div>
                    </div>
                </div>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('Skip', 'groundhogg') }
                    </button>
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#next').on('click', () => next())
        $('#skip').on('click', () => next())
      },
      next: () => 'license',
    },
    {
      id: 'license',
      render: () => {
        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Have a license key?', 'groundhogg') }</h1>
                <p>
                    ${ __('If you have previously purchased a license for Groundhogg you can enter it now! <i><a href="https://www.groundhogg.io/account/licenses/" target="_blank">Where do I find my license?</a></i>',
                            'groundhogg') }</p>
                <div class="display-flex gap-10 inside stretch space-between">
                    ${ input({
                        placeholder: __('Your license key'),
                        id: 'license',
                        value: Options.get('gh_master_license'),
                    }) }
                    <button id="activate" class="gh-button primary medium">
                        ${ __('Activate', 'groundhogg') }
                    </button>
                </div>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('I don\'t have a license yet.', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next }) => {

        let license = Options.get('gh_master_license')

        $('#license').on('change input', e => {
          license = e.target.value
        })

        $('#activate').on('click', e => {

          let $btn = $(e.currentTarget)
          let { stop } = loadingDots(e.currentTarget)
          $btn.prop('disabled', true)

          ajax({
            action: 'gh_guided_setup_license',
            license,
          }).then((r) => {

            if (!r.success) {
              dialog({
                type: 'error',
                message: r.data[0].message,
              })
              stop()
              $btn.prop('disabled', false)
              return
            }

            dialog({
              message: __('License verified', 'groundhogg'),
            })

            isLicensed = true

            next()

          })

        })
        $('#skip').on('click', () => next())
      },
      next: () => 'telemetry',
    },
    {
      id: 'telemetry',
      render: () => {

        if (isLicensed) {
          return stepTemplate({
            inside: () => {
              // language=HTML
              return `
                  <h1>${ __('Help Improve Groundhogg', 'groundhogg') }</h1>
                  <p>
                      ${ __('You can help improve Groundhogg by enabling anonymous telemetry. This will occasionally send us data about Groundhogg and how you use it. We use this data to improve feature, fix bugs, and create new products.',
                              'groundhogg') }</p>
                  <div class="inside display-flex column align-center gap-20">
                      <button id="optin" class="gh-button primary medium">
                          <b>${ __('Yes, I\'m In!', 'groundhogg') }</b></button>
                  </div>
                  <p><b>${ __('What information is shared?', 'groundhogg') }</b>
                  </p>
                  <ul>
                      <li>
                          ${ __('Your name and email address.', 'groundhogg') }
                      </li>
                      <li>${ __('Total number of contacts', 'groundhogg') }</li>
                      <li>${ __('Number of emails sent over time',
                              'groundhogg') }
                      </li>
                      <li>${ __('Number of active funnels', 'groundhogg') }</li>
                      <li>
                          ${ __('Statistics such as open rate and click through rate',
                                  'groundhogg') }
                      </li>
                      <li>${ __('Error messages and plugin failures',
                              'groundhogg') }
                      </li>
                      <li>
                          ${ __('System info such as WordPress version and language',
                                  'groundhogg') }
                      </li>
                      <li>${ __('Installed plugins', 'groundhogg') }</li>
                  </ul>
                  <p><b>${ __('What information is <b>NOT</b> shared?',
                          'groundhogg') }</b></p>
                  <ul>
                      <li>
                          ${ __('Any personally identifiable information about your users or contacts',
                                  'groundhogg') }
                      </li>
                      <li>${ __('Any site content such as emails or posts',
                              'groundhogg') }
                      </li>
                      <li>
                          ${ __('Passwords, usernames, or any data that might impact security',
                                  'groundhogg') }
                      </li>
                  </ul>
                  <p>🔒
                      <i>${ __(
                              'We do not sell or share any of your information with third party vendors.',
                              'groundhogg') }</i>
                  </p>
                  <p><i>${ __('You can opt out at any time.',
                          'groundhogg') }</i></p>
                  <div class="space-between align-center"
                       style="margin-top: 40px">
                      <button id="skip" class="gh-button secondary text">
                          ${ __('No thanks, I don\'t want to help improve Groundhogg.',
                                  'groundhogg') }
                      </button>
                  </div>`
            },
          })
        }

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Get 15% OFF any premium plan!', 'groundhogg') }</h1>
                <p>
                    ${ __('You can help improve Groundhogg and get 15% OFF the first year of any paid plan by enabling anonymous telemetry.',
                            'groundhogg') }</p>
                <div class="inside display-flex column align-center gap-20">
                    <label>${ input({
                        type: 'checkbox',
                        id: 'marketing',
                        checked: true,
                    }) }
                        ${ __('Add me to the email list.',
                                'groundhogg') }</label>
                    <button id="optin" class="gh-button primary medium">
                        <b>${ __('Yes, I want 15% OFF', 'groundhogg') }</b>
                    </button>
                </div>
                <p><b>${ __('What information is shared?', 'groundhogg') }</b>
                </p>
                <ul>
                    <li>${ __('Your name and email address.', 'groundhogg') }
                    </li>
                    <li>${ __('Total number of contacts', 'groundhogg') }</li>
                    <li>
                        ${ __('Number of emails sent over time', 'groundhogg') }
                    </li>
                    <li>${ __('Number of active funnels', 'groundhogg') }</li>
                    <li>
                        ${ __('Statistics such as open rate and click through rate',
                                'groundhogg') }
                    </li>
                    <li>${ __('Error messages and plugin failures',
                            'groundhogg') }
                    </li>
                    <li>
                        ${ __('System info such as WordPress version and language',
                                'groundhogg') }
                    </li>
                    <li>${ __('Installed plugins', 'groundhogg') }</li>
                </ul>
                <p><b>${ __('What information is <b>NOT</b> shared?',
                        'groundhogg') }</b></p>
                <ul>
                    <li>
                        ${ __('Any personally identifiable information about your users or contacts',
                                'groundhogg') }
                    </li>
                    <li>${ __('Any site content such as emails or posts',
                            'groundhogg') }
                    </li>
                    <li>
                        ${ __('Passwords, usernames, or any data that might impact security',
                                'groundhogg') }
                    </li>
                </ul>
                <p>🔒
                    <i>${ __(
                            'We do not sell or share any of your information with third party vendors.',
                            'groundhogg') }</i>
                </p>
                <p><i>${ __('You can opt out at any time.', 'groundhogg') }</i>
                </p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('No thanks, I don\'t want to save 15% OFF.',
                                'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        let marketing = true

        $('#marketing').on('change', e => {
          marketing = e.target.checked
        })

        $('#optin').on('click', e => {

          if (marketing) {
            acceptedMarketing = true
          }

          let $btn = $(e.currentTarget)
          let { stop } = loadingDots(e.currentTarget)
          $btn.prop('disabled', true)

          ajax({
            action: 'gh_guided_setup_telemetry',
            marketing,
          }).then(() => {
            optedIntoTelementry = true
            stop()
            next()
          })

        })
        $('#skip').on('click', () => next())
      },
      next: () => isLicensed ? ( !GroundhoggGuidedSetup.mailhawkInstalled
        ? 'mailhawk'
        : 'community' ) : 'integrations',
    },
    {
      id: 'integrations',
      render: () => {

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Useful Integrations', 'groundhogg') }</h1>
                <p>
                    ${ __('We noticed you\'re using some plugins which we integrate with! Integrations can unlock powerful marketing and segmentation features you can use to increase leads & sales.',
                            'groundhogg') }</p>
                <div id="services">
                    ${ GroundhoggGuidedSetup.integrations.map(
                            d => downloadTemplate(d)).join('') }
                </div>
                <p>
                    ${ __('You can unlock all these integrations by signing up for our <b>Pro Plan</b>.',
                            'groundhogg') }</p>
                <div class="display-flex column gap-20 inside align-center">
                    ${ optedIntoTelementry ? `<p class="pill green">${ __(
                            'Use code <b>IFOUND15OFF</b> to save 15% off your first year!',
                            'groundhogg') }</p>` : '' }
                    <button data-link="https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=button&utm_campaign=guided_setup&utm_content=integrations"
                            class="link gh-button primary medium"><b>${ __(
                            'Get PRO Now!') }</b></button>
                </div>
                <p><b>${ __('Why go PRO?', 'groundhogg') }</b></p>
                <ul>
                    <li>
                        ${ __('Flat-rate pricing. <i>Your bill will never increase as you grow.</i>',
                                'groundhogg') }
                    </li>
                    <li>
                        ${ __('Grandfather pricing guarantee. If our price goes up, your bill won\'t!',
                                'groundhogg') }
                    </li>
                    <li>
                        ${ __('Instant access to 45+ addons and integrations to boost your marketing and sales.',
                                'groundhogg') }
                    </li>
                    <li>
                        ${ __('Access to our premium support desk for high level intervention.',
                                'groundhogg') }
                    </li>
                    <li>
                        ${ __('Can\'t renew? All your extensions will still work!',
                                'groundhogg') }
                    </li>
                    <li>${ __('Annual billing.', 'groundhogg') }</li>
                </ul>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('I\'ll do this later', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('.more-details').on('click', e => {

          e.preventDefault()

          const $link = $(e.currentTarget)

          let downloadId = $link.data('id')
          let download = GroundhoggGuidedSetup.integrations.find(
            d => d.info.id == downloadId)

          downloadMoreDetails(download)

        })

        $('.gh-button.link').on('click', e => {

          let link = e.currentTarget.dataset.link
          window.open(link, '_blank')
        })

        $('#skip').on('click', () => next())
      },
      next: () => !GroundhoggGuidedSetup.mailhawkInstalled
        ? 'mailhawk'
        : 'subscribe',
    },
    {
      id: 'mailhawk',
      render: () => {

        return stepTemplate({
          logo: icons.mailhawk,
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Install MailHawk', 'groundhogg') }</h1>
                <h2>${ __('A better WordPress SMTP plugin.',
                        'groundhogg') }</h2>
                <p>
                    ${ __('Use MailHawk to deliver your email! We get that sending email can be a pain. MailHawk makes it painless to send email with excellent deliverability and no-nonsense pricing starting from just <b>$10/year</b>.',
                            'groundhogg') }</p>
                <div class="display-flex column gap-20 inside align-center">
                    <button id="install-mailhawk"
                            class="gh-button primary medium"><b>${ __(
                            'Install MailHawk!') }</b>
                    </button>
                </div>
                <p><b>${ __('Why use an MailHawk?', 'groundhogg') }</b></p>
                <ul>
                    <li>${ __('Simple & transparent pricing!', 'groundhogg') }
                    </li>
                    <li>
                        ${ __('Works with Groundhogg and all your other WordPress plugins.',
                                'groundhogg') }
                    </li>
                    <li>${ __('Email bounce handling.', 'groundhogg') }</li>
                    <li>${ __('No hidden fees!', 'groundhogg') }</li>
                </ul>
                <p><i>${ __(
                        'MailHawk is a service owned and operated by Groundhogg Inc.',
                        'groundhogg') }</i></p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('I don\'t need this service right now.',
                                'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#install-mailhawk').on('click', (e) => {

          let $btn = $(e.currentTarget)
          $btn.prop('disabled', true)
          let { stop } = loadingDots(e.currentTarget)

          var data = {
            'action': 'groundhogg_mailhawk_remote_install',
            'nonce': GroundhoggGuidedSetup.install_mailhawk_nonce,
          }

          ajax(data).then(r => {
            stop()
            installedMailhawk = r

            dialog({
              message: __(
                'MailHawk was installed. You will be able to complete the setup at the end.',
                'groundhogg'),
            })

            next()
          })

        })
        $('#skip').on('click', () => next())
      },
      next: () => installedMailhawk ? 'subscribe' : ( isLicensed
        ? 'community'
        : 'smtp' ),
    },
    {
      id: 'smtp',
      render: () => {

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('SMTP Service', 'groundhogg') }</h1>
                <p>
                    ${ __('We\'ve detected you\'re not using an official Groundhogg SMTP service. For best results we recommend using one of the options listed below.',
                            'groundhogg') }</p>
                <div id="services">
                    ${ GroundhoggGuidedSetup.smtpProducts.filter(
                            d => d.info.id !== 90048).
                            map(d => downloadTemplate(d)).
                            join('') }
                </div>
                <p>
                    ${ __('You can unlock all these SMTP services by signing up for our <b>Pro Plan</b>.',
                            'groundhogg') }</p>
                <div class="display-flex column gap-20 inside align-center">
                    ${ optedIntoTelementry ? `<p class="pill green">${ __(
                            'Use code <b>IFOUND15OFF</b> to save 15% off your first year!',
                            'groundhogg') }</p>` : '' }
                    <button data-link="https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=button&utm_campaign=guided_setup&utm_content=smtp"
                            class="link gh-button primary medium"><b>${ __(
                            'Get PRO Now!') }</b></button>
                </div>
                <p><b>${ __('Why use an official SMTP Service?',
                        'groundhogg') }</b></p>
                <ul>
                    <li>${ __('Better deliverability!', 'groundhogg') }</li>
                    <li>${ __('Support and email troubleshooting!',
                            'groundhogg') }
                    </li>
                    <li>${ __('Configure multiple SMTP services.',
                            'groundhogg') }
                    </li>
                    <li>${ __('Email bounce handling.', 'groundhogg') }</li>
                </ul>
                <div class="space-between align-center"
                     style=" margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('I\'ll set this up later', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('.more-details').on('click', e => {

          e.preventDefault()

          const $link = $(e.currentTarget)

          let downloadId = $link.data('id')
          let download = GroundhoggGuidedSetup.smtpProducts.find(
            d => d.info.id == downloadId)

          downloadMoreDetails(download)

        })

        $('.gh-button.link').on('click', e => {

          let link = e.currentTarget.dataset.link
          window.open(link, '_blank')
        })

        $('#skip').on('click', () => next())
      },
      next: () => 'subscribe',
    },
    {
      id: 'subscribe',
      beforeMount: () => acceptedMarketing ? 'community' : 'subscribe',
      render: () => {

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Subscribe', 'groundhogg') }</h1>
                <p>
                    ${ __('Stay up to date on the latest changes & improvements, courses, articles, deals and promotions available to the Groundhogg community by subscribing!',
                            'groundhogg') }</p>
                <div class="display-flex gap-10 inside stretch space-between">
                    ${ input({
                        placeholder: __('Your best email address',
                                'groundhogg'),
                        id: 'email',
                        value: currentUser.data.user_email,
                    }) }
                    <button id="subscribe" class="gh-button primary medium">
                        ${ __('Subscribe', 'groundhogg') }
                    </button>
                </div>
                <p><b>${ __('Why subscribe?', 'groundhogg') }</b></p>
                <ul>
                    <li>
                        ${ __('First to know about events, articles, deals, promotions and more!',
                                'groundhogg') }
                    </li>
                    <li>
                        ${ __('Tailored onboarding experience.', 'groundhogg') }
                    </li>
                    <li>${ __('Unsubscribe anytime.', 'groundhogg') }</li>
                    <li>
                        ${ __('🔒 Groundhogg does not sell or share your data with third party vendors.',
                                'groundhogg') }
                    </li>
                </ul>
                <div class="space-between align-center"
                     style=" margin-top: 40px">
                    <button id="skip" class="gh-button secondary text">
                        ${ __('I don\'t want to stay informed...',
                                'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        let email = currentUser.data.user_email

        $('#email').on('input change', e => email = e.target.value)

        $('#subscribe').on('click', e => {

          let $btn = $(e.currentTarget)
          $btn.prop('disabled', true)
          let { stop } = loadingDots(e.currentTarget)

          ajax({
            action: 'gh_guided_setup_subscribe',
            email,
          }).then(() => {
            stop()
            next()
          })
        })

        $('#skip').on('click', () => next())
      },
      next: () => 'community',
    },
    {
      id: 'community',
      render: () => {

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Join the Community', 'groundhogg') }</h1>
                <p>
                    ${ __('Ways you can participate with, learn from, and support other like-minded business owners and entrepreneurs.',
                            'groundhogg') }</p>
                <div class="display-flex gap-20 space-between">
                    <div>
                        <h2>${ __('Follow us on Twitter', 'groundhogg') }</h2>
                        <p>${ __('For fast updates and notifications.',
                                'groundhogg') }</p>
                    </div>
                    <button data-link="https://twitter.com/groundhoggwp"
                            class="social gh-button space-between gap-10 secondary medium"><span
                            class="dashicons dashicons-twitter"></span>${ __(
                            'Follow', 'groundhogg') }
                    </button>
                </div>
                <div class="display-flex gap-20 space-between">
                    <div>
                        <h2>${ __('Join the Facebook Support Group',
                                'groundhogg') }</h2>
                        <p>
                            ${ __('To get support and guidance for all things Groundhogg.',
                                    'groundhogg') }</p>
                    </div>
                    <button data-link="https://facebook.com/groups/groundhoggwp"
                            class="social gh-button space-between gap-10 secondary medium"><span
                            class="dashicons dashicons-facebook"></span>${ __(
                            'Join', 'groundhogg') }
                    </button>
                </div>
                <div class="display-flex gap-20 space-between">
                    <div>
                        <h2>${ __('Subscribe to our YouTube channel',
                                'groundhogg') }</h2>
                        <p>
                            ${ __('For tutorials, opinions, and strategies guaranteed to help you launch, grow, and scale.',
                                    'groundhogg') }</p>
                    </div>
                    <button data-link="https://www.youtube.com/groundhogg"
                            class="social gh-button space-between gap-10 secondary medium"><span
                            class="dashicons dashicons-youtube"></span>${ __(
                            'Subscribe', 'groundhogg') }
                    </button>
                </div>
                <div class="space-between align-center"
                     style=" margin-top: 40px">
                    <button id="next" class="gh-button medium primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('.gh-button.social').on('click', e => {

          let link = e.currentTarget.dataset.link
          window.open(link, '_blank')
        })

        $('#next').on('click', () => next())
      },
      next: () => 'next-steps',
    },
    {
      id: 'next-steps',
      render: () => {

        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Next Steps...', 'groundhogg') }</h1>
                <p>
                    ${ sprintf(
                            __('Congrats %s, you\'re all set to use Groundhogg. Here are some next steps for you so you can start leveraging Groundhogg.',
                                    'groundhogg'),
                            currentUser.data.display_name) }</p>
                ${ installedMailhawk ? `<div class="display-flex gap-20 space-between">
					<div>
						<h2>${ __('Configure Mailhawk', 'groundhogg') }</h2>
						<p>
							${ __('Complete the MailHawk setup process so you can start sending better email!.',
                        'groundhogg') }</p>
					</div>
					<button id="mailhawk" class="gh-button space-between gap-10 primary  medium">${ __(
                        'Go to MailHawk') }
					</button>
				</div>` : '' }
                <div class="display-flex gap-20 space-between">
                    <div>
                        <h2>${ __('Register for Groundhogg Academy',
                                'groundhogg') }</h2>
                        <p>
                            ${ __('<b>FREE</b> courses to help you get started with Groundhogg and implement real-world marketing strategies.',
                                    'groundhogg') }</p>
                    </div>
                    <button data-target="_blank"
                            data-link="https://academy.groundhogg.io/course/groundhogg-quickstart/?utm_source=plugin&utm_medium=button&utm_campaign=guided_setup&utm_content=next-steps"
                            class="link gh-button space-between gap-10 secondary medium"><span
                            class="dashicons dashicons-welcome-learn-more"></span>${ __(
                            'Register FREE') }
                    </button>
                </div>
                ${ isLicensed ? `
				<div class="display-flex gap-20 space-between">
					<div>
						<h2>${ __('Install your Premium Features', 'groundhogg') }</h2>
						<p>
							${ __('Download the extension manager to install your premium features and integrations.',
                        'groundhogg') }</p>
					</div>
					<button data-target="_blank" data-link="https://www.groundhogg.io/account/all-access-downloads/?utm_source=plugin&utm_medium=button&utm_campaign=guided_setup&utm_content=next-steps" class="link gh-button space-between gap-10 secondary medium"><span class="dashicons dashicons-download"></span>${ __(
                        'Install!') }</button>
				</div>
				<div class="display-flex center">
					${ optedIntoTelementry ? `<p class="pill green">${ __(
                        'Use code <b>IFOUND15OFF</b> to save 15% off your first year!',
                        'groundhogg') }</p>` : '' }
				</div>` : `
				<div class="display-flex gap-20 space-between">
					<div>
						<h2>${ __('Sign up for PRO', 'groundhogg') }</h2>
						<p>
							${ __('Instant access to 45+ addons and integrations to help you maximize your marketing and sales.',
                        'groundhogg') }</p>
					</div>
					<button data-target="_blank" data-link="https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=button&utm_campaign=guided_setup&utm_content=next-steps" class="link gh-button space-between gap-10 secondary medium">${ __(
                        'Sign up!') }</button>
				</div>
				<div class="display-flex center">
					${ optedIntoTelementry ? `<p class="pill green">${ __(
                        'Use code <b>IFOUND15OFF</b> to save 15% off your first year!',
                        'groundhogg') }</p>` : '' }
				</div>
				` }
                <div class="space-between align-center"
                     style=" margin-top: 40px">
                    <button data-target="_self"
                            data-link="${ adminPageURL('groundhogg') }"
                            class="link gh-button medium primary">
                        ${ __('Finish! 🎉', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#mailhawk').on('click', () => {

          let {
            register_url = '',
            client_state = '',
            redirect_uri = '',
            partner_id = '',
          } = installedMailhawk

          var form = document.createElement('form')
          form.setAttribute('method', 'POST')
          form.setAttribute('action', register_url)

          function groundhogg_mailhawk_append_form_input (name, value) {
            var input = document.createElement('input')
            input.setAttribute('type', 'hidden')
            input.setAttribute('name', name)
            input.setAttribute('value', value)
            form.appendChild(input)
          }

          groundhogg_mailhawk_append_form_input('mailhawk_plugin_signup', 'yes')
          groundhogg_mailhawk_append_form_input('state', client_state)
          groundhogg_mailhawk_append_form_input('redirect_uri', redirect_uri)
          groundhogg_mailhawk_append_form_input('partner_id', partner_id)

          document.body.appendChild(form)
          form.submit()

        })

        $('.gh-button.link').on('click', e => {
          let link = e.currentTarget.dataset.link
          window.open(link, e.currentTarget.dataset.target)
        })

        $('#done').on('click', () => next())
      },
      next: () => {},
    },
  ]

  let currentStep = steps[0]

  if (window.location.hash) {
    let id = window.location.hash.substring(1)
    currentStep = steps.find(s => s.id === id)
  }

  history.pushState({ id: currentStep.id }, currentStep.id,
    `#${ currentStep.id }`)

  const mount = () => {

    $('#guided-setup').html(currentStep.render())

    const next = (id = false) => {

      let _next = id ? id : currentStep.next()

      currentStep = steps.find(s => s.id === _next)

      if (currentStep.beforeMount) {
        _next = currentStep.beforeMount()
        currentStep = steps.find(s => s.id === _next)
      }

      history.pushState({ id: currentStep.id }, currentStep.id,
        `#${ currentStep.id }`)
      window.scrollTo(0, 0)
      mount()
    }

    const prev = () => {
      history.back()
    }

    $('#back').on('click', () => prev())

    currentStep.onMount({ next })

  }

  window.addEventListener('popstate', (e) => {

    let state = e.state

    if (state && state.id) {
      currentStep = steps.find(s => s.id === state.id)
      mount()
    }
  })

  $(() => {

    $('#guided-setup').html(spinner())

    // preload options
    Options.fetch([
      'gh_business_name',
      'gh_phone',
      'gh_street_address_1',
      'gh_street_address_2',
      'gh_city',
      'gh_region',
      'gh_country',
      'gh_privacy_policy',
      'gh_master_license',
      'gh_terms',
      'gh_override_from_name',
      'gh_override_from_email',
      'blogname',
    ]).then(() => {
      isLicensed = Options.get('gh_master_license') != false

      mount()
    })

  })

} )(jQuery)
