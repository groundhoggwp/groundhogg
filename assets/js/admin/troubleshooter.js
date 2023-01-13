( ($) => {

  const {
    input,
    toggle,
    textarea,
    tinymceElement,
    improveTinyMCE,
    spinner,
    select,
    icons,
    loadingDots,
    adminPageURL,
    dialog,
    modal,
    confirmationModal,
  } = Groundhogg.element

  const {
    linkPicker,
  } = Groundhogg.pickers

  const { currentUser } = Groundhogg

  const { userHasCap } = Groundhogg.user

  const {
    options: Options,
    funnels: FunnelsStore,
  } = Groundhogg.stores

  const { post, delete: _delete, get, patch, routes, ajax } = Groundhogg.api

  let { smtp, ticket_defaults = {} } = GroundhoggTroubleshooter

  let ticket = {
    safe_mode: GroundhoggTroubleshooter.safe_mode_enabled,
    message: '',
    subject: '',
    host: '',
    email: currentUser.data.user_email,
    name: currentUser.data.display_name,
    mood: 'Great',
    wp_experience: 'Novice',
    gh_experience: 'Novice',
    admin_access: true,
    authorization: true,
    ...ticket_defaults,
  }

  const holdButton = (btn) => {

    let $btn = $(btn)
    $btn.prop('disabled', true)
    let { stop } = loadingDots(btn)

    return () => {
      $btn.prop('disabled', false)
      stop()
    }
  }

  improveTinyMCE({
    height: 300,
  })
  const { sprintf, __, _x, _n } = wp.i18n

  const stepTemplate = ({
    inside = () => '', logo = icons.groundhogg_black, showBack = true,
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
            ${ showBack ? `<div class="back" style="margin-top: 20px">
				<button id="back" class="gh-button secondary text">⬅️${ __('Back', 'groundhogg') }</button>
			</div>` : '' }
        </div>`

  }

  const faq = ({
    title = '', content = '', show = true,
  }) => {

    if (!show) {
      return ''
    }

    //language = HTML
    return `<div class="gh-panel outlined closed faq">
                    <div class="gh-panel-header">
                        <h2>${ title }</h2>
                        <button class="toggle-indicator"></button>
                    </div>
                    <div class="inside">
                        ${ content }
                    </div>
                </div>`
  }

  const faqOnMount = () => {
    $('.gh-panel.outlined .gh-panel-header').on('click', e => {
      $('.gh-panel.outlined').addClass('closed')
      $(e.target).closest('.gh-panel').toggleClass('closed')
    })
  }

  const steps = [
    {
      id: 'start', render: () => {

        return stepTemplate({
          showBack: false, inside: () => {
            // language=HTML
            return `
                <h1>${ __('This is the Groundhogg Troubleshooter', 'groundhogg') }</h1>
                <p>
                    ${ __('The troubleshooter can identify common problems that Groundhogg users run into frequently and attempt to solve them.',
                            'groundhogg') }</p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="start" class="gh-button primary medium">
                        ${ __('Start Troubleshooter!', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#start').on('click', () => next())
      }, next: () => 'issues-found',
    },
    {
      id: 'issues-found', render: () => {

        let { smtp } = GroundhoggTroubleshooter

        return stepTemplate({
          showBack: false, inside: () => {
            // language=HTML
            return `
                <h1>${ __('We have identified a few possible issues', 'groundhogg') }</h1>
                <p>
                    ${ __('Click on an issue to see how to resolve it. If you are having an issue it\'s most likely related to one of the below.',
                            'groundhogg') }</p>
                ${ faq({
                    show: !GroundhoggTroubleshooter.cron_is_working,
                    title: `⚠️ ${ __('Cron Jobs not working', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'The event queue is not processing at regular 1 minute intervals. This could be caused by not having initially setup your cron jobs, or they are no longer working.') }</p>
                        <button id="cron" class="gh-button primary">
                            ${ __('Troubleshoot Cron Jobs') }
                        </button>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.required_updates.length > 0,
                    title: `⚠️ ${ __('Missing required updates', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'It looks like you have pending updates for some of your plugins. If you believe your issue is related to a bug or conflict, updating your plugins will probably resolve it.',
                            'groundhogg') }</p>
                        <button id="update-plugins" class="gh-button primary">
                            ${ __('Update plugins!') }
                        </button>`,
                }) }
                ${ faq({
                    show: !GroundhoggTroubleshooter.timezone.matches,
                    title: `⚠️ ${ __('Timezone mismatch', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'The timezone of your site, and <b>your</b> timezone do not match. If your issue is related to when emails are sending, changing the timezone will likely fix it.',
                            'groundhogg') }</p>
                        <p>${ __('Your timezone is:', 'groundhogg') }
                            <b>${ GroundhoggTroubleshooter.timezone.user }</b>
                        </p>
                        <button id="set-timezone" class="gh-button primary">
                            ${ __('Set my timezone!') }
                        </button>`,
                }) }
                ${ faq({
                    show: [smtp.marketing, smtp.wordpress, smtp.transactional].every(v => v === 'wp_mail'),
                    title: `⚠️ ${ __('No Official SMTP integration', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'You are not using an official Groundhogg SMTP integration. This can cause issues with deliverability and send speed.',
                            'groundhogg') }</p>
                        <button id="fix-smtp" class="gh-button primary">
                            ${ __('Install an official SMTP integration') }
                        </button>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.missing_db_tables.length,
                    title: `⚠️ ${ __('Missing database tables', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'Some of the required Groundhogg database tables are missing. This could impact performance of some features.',
                            'groundhogg') }</p>
                        <button id="fix-tables" class="gh-button primary">
                            ${ __('Fix missing tables') }
                        </button>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.length > 0,
                    title: `⚠️ ${ __('Recent failed events', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'Looks like there are some recent failed events in the queue.', 'groundhogg') }</p>
                        <button id="failed-events" class="gh-button primary">
                            ${ __('Fix failed events') }
                        </button>`,
                }) }
                ${ faq({
                    show: !GroundhoggTroubleshooter.php.is_recommended,
                    title: `⚠️ ${ __('Recommended PHP Version', 'groundhogg') }`, content: `<p>
                            ${ sprintf(__(
                            'The minimum recommended PHP version for Groundhogg is %1$s. Update your PHP to %1$s for the best results.',
                            'groundhogg'), GroundhoggTroubleshooter.php.recommended) }</p>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="other" class="gh-button secondary text">
                        ${ __('I have a different issue', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      }, onMount: ({ next, prev }) => {

        if ( ! $('.gh-panel.faq').length ){
          next('other-issue')
          return;
        }

        $('#other').on('click', () => next('other-issue'))
        $('#cron').on('click', () => next('cron'))
        $('#failed-events').on('click', () => next('failed-events'))

        $('#update-plugins').on('click', () => {
          window.open(`${ Groundhogg.url.admin }update-core.php`, '_blank')
        })

        $('#set-timezone').on('click', () => {
          window.open(`${ Groundhogg.url.admin }options-general.php`, '_blank')
        })

        $('#fix-smtp').on('click', () => {
          if (GroundhoggTroubleshooter.smtp.any_service_installed) {
            window.open(adminPageURL('gh_settings', { tab: 'email' }), '_blank')
          }
          else if (GroundhoggTroubleshooter.helper_installed) {
            window.open(adminPageURL('gh_extensions', { terms: [183] }), '_blank')
          }
          else {
            window.open('https://www.groundhogg.io/downloads/tag/sending-service/', '_blank')
          }
        })

        $('#fix-tables').on('click', e => {

          let $btn = $(e.target)
          $btn.prop('disabled', true)
          $btn.text(__('Attempting to fix tables'))
          const { stop } = loadingDots(e.target)

          ajax({
            action: 'groundhogg_fix_missing_tables',
          }).then(r => {

            stop()

            // All tables installed
            if (r.data.missing_tables.length === 0) {
              $btn.text(__('All tables are now installed!'))
              dialog({
                message: __('Issue fixed!'),
              })
              return
            }

            confirmationModal({
              width: 500,
              alert: `<p>${ __(
                'Something is not right. We were unable to fix the issue automatically. Your next step is to open a ticket.') }</p>`,
              confirmText: __('Open a ticket'),
              onConfirm: () => {

                ticket.subject = 'Unable to install some db tables'
                // language=HTML
                ticket.message = `
                    <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
                    <p>The following tables were unable to be installed automatically:</p>
                    <ul>
                        ${ r.data.missing_tables.map(t => `<li>${ t }</li>`).join('') }
                    </ul>
                    <p>Installing these tables requires manual intervention.</p>
                    ${ r.data.db_errors.length ? `<p>These errors were reported:</p>` : '' }
                    ${ r.data.db_errors.map(err => `<pre>${ err }</pre>`).join('') }
                    <h3><u>Customer notes</u></h3>
                    <p><i>Please add any additional details you think are relevant...</i></p>`

                next('ticket')
              },
            })

          })
        })

        faqOnMount()
      }, next: () => 'business-info',
    },
    {
      id: 'failed-events',
      render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Fix failed events', 'groundhogg') }</h1>
                <p>
                    ${ __('Generally, failed events are no big deal and don\'t impact the performance of your site. However, if you are expecting something to happen and it isn\'t this might be why.') }</p>
                <table class="wp-list-table widefat striped">
                    <thead>
                    <tr>
                        <th>${ __('Count') }</th>
                        <th>${ __('Error') }</th>
                        <th>${ __('Message') }</th>
                    </tr>
                    </thead>
                    <tbody>
                    ${ GroundhoggTroubleshooter.recent_failed_events.map(e => {
                        //language=HTML
                        return `<tr><td>${ e.count }</td><td><code>${ e.error_code }</code></td><td><pre>${ e.error_message }</pre></td></tr>`
                    }).join('') }
                    </tbody>
                </table>
                <p><b>${ __('Error meanings') }</b></p>
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.filter(
                            e => e.error_code === 'email_not_ready').length,
                    title: `<code>email_not_ready</code>`,
                    //language=HTML
                    content: `<p>${ __(
                            'An email you are trying to send in a funnel or broadcast is not marked as ready. All emails must be marked as ready before they can be sent.',
                            'groundhogg') }</p><p><a  class="gh-button primary" href="${ adminPageURL('gh_events', {
                        status: 'failed',
                        error_code: 'email_not_ready',
                    }) }">${ __('See which emails are failing') }</a></p>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.filter(
                            e => e.error_code === 'non_marketable').length,
                    title: `<code>non_marketable</code>`,
                    //language=HTML
                    content: `<p>${ __(
                            'Marketing emails and SMS cannot be sent to contacts that are not marketable. If you believe that the contacts should be marketable then you may have set your compliance settings to strict.',
                            'groundhogg') }</p>
<p><a href="https://help.groundhogg.io/article/203-why-are-my-contacts-unmarketable" target="_blank">${ __(
                            'Why are my contacts unmarketable?') }</a></p>
<p><a  class="gh-button primary" href="${ adminPageURL('gh_events', {
                        status: 'failed',
                        error_code: 'non_marketable',
                    }) }">${ __('Which contacts are not marketable') }</a></p>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.filter(
                            e => e.error_code === 'wp_mail_failed').length,
                    title: `<code>wp_mail_failed</code>`,
                    //language=HTML
                    content: `<p>${ __(
                            'Your email could not be sent due to an SMTP error. This could be a result of a issue with your SMTP configuration, or lack of one.',
                            'groundhogg') }</p>
<p><a href="https://help.groundhogg.io/article/410-why-do-i-need-an-smtp-service" target="_blank">${ __(
                            'Why do I need an SMTP service?') }</a></p>
<button class="gh-button primary" id="fix-smtp">${ __('Fix SMTP issues.') }</button>`,
                }) }
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.filter(e => e.error_code === 'missing').length,
                    title: `<code>missing</code>`,
                    //language=HTML
                    content: `<p>${ __(
                            'An expect resource could not be found, it may have been deleted.',
                            'groundhogg') }</p>
<p><a  class="gh-button primary" href="${ adminPageURL('gh_events', {
                        status: 'failed',
                        error_code: 'missing',
                    }) }">${ __('See events with missing resources') }</a></p>`,
                }) }
                <p>${ __('Once you believe you have corrected the issue you can retry the events like this!') }</p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#fix-smtp').on('click', () => {
          next('smtp-issue')
        })
        $('#ticket').on('click', () => {

          ticket.subject = 'Failed events'
          // language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>There are multiple failed events in the queue and the cause is unknown.</p>
              <ul>
                  ${ GroundhoggTroubleshooter.recent_failed_events.map(e => {
                      //language=HTML
                      return `<li><code>${ e.error_code }</code> (${ e.count }) ${ e.error_message }</li>`
                  }).join('') }
              </ul>
              <h3><u>Customer notes</u></h3>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })
        faqOnMount()
      }, next: () => '',
    },
    {
      id: 'cron', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Did you set up your cron jobs?', 'groundhogg') }</h1>
                <ul>
                    <li>
                        <button id="what-is-cron"
                                class="gh-button text secondary">
                            ${ __('What is a cron job?', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="cron-job-org"
                                class="gh-button text secondary">
                            ${ __('Yes, using cron-job.org', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="cron-cpanel" class="gh-button text secondary">
                            ${ __('Yes, using CPanel', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="cron-host-panel"
                                class="gh-button text secondary">
                            ${ __('Yes, using my host panel or CLI', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="cron-other" class="gh-button text secondary">
                            ${ __('Yes, using some other method', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="not-yet" class="gh-button text secondary">
                            ${ __('Not yet', 'groundhogg') }
                        </button>
                    </li>
                </ul>`
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#what-is-cron').on('click', () => next('what-is-cron'))
        $('#cron-job-org').on('click', () => next('cron-job-org'))
        $('#cron-cpanel').on('click', () => next('cron-cpanel'))
        $('#cron-host-panel').on('click', () => next('cron-host-panel'))
        $('#cron-other').on('click', () => next('cron-other'))
        $('#not-yet').on('click', () => window.open(adminPageURL('gh_tools', { tab: 'cron' })))
      }, next: () => '',
    },
    {
      id: 'what-is-cron',
      render: () => {
        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Never heard of a cron job before?', 'groundhogg') }</h1>
                <p>
                    ${ __('A cron job is a process which ensures that all your WordPress processes run on time. Like sending emails or processing funnel events.') }</p>
                <p>
                    ${ __('If your cron job is not functioning, it can impact multiple WordPress systems, including Groundhogg.') }</p>
                <p>
                    ${ __('Fortunately, we have a simple guide for you to get it working.') }</p>
                <div class="space-between"
                     style="margin-top: 40px">
                    <button id="go-to-cron-setup" class="gh-button primary">
                        ${ __('Setup my cron jobs', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next, prev }) => {
        $('#go-to-cron-setup').
          on('click', () => window.open(adminPageURL('gh_tools', { tab: 'cron' })))
      },
      next: () => '',

    },
    {
      id: 'cron-job-org', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Cron-Job.org not working?', 'groundhogg') }</h1>
                <p>
                    ${ __('Cron-job.org is a great solution for many, but is known to have issues in the following situations.') }</p>
                ${ faq({
                    title: __('Hosting with SiteGround?', 'groundhogg'), // language=HTML
                    content: `<p>
                            ${ __('SiteGround is known to block requests from cron-job.org') }</p>
                        <p>
                            ${ __('Instead, you will have to create cron jobs through the SiteGround admin panel.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/469-setting-up-a-cron-job-on-siteground">${ __(
                            'Read the guide') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Hosting with Kinsta?', 'groundhogg'), // language=HTML
                    content: `<p>
                            ${ __('Kinsta is known to block requests from cron-job.org') }</p>
                        <p>
                            ${ __(
                            'Instead, you will have to contact Kinsta Support and have them create the cron jobs for you using their internal system.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/444-all-the-cron-jobs-for-groundhogg">${ __(
                            'Provide them with this article.') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using Cloudflare, or another CDN?', 'groundhogg'), // language=HTML
                    content: ` <p>
                            ${ __(
                            'Content delivery networks are known to potentially cause issues if they are not fine tuned.') }</p>
                        <p>
                            ${ __(
                            'If you have not already done so, you will need to exclude multiple Groundhogg files and assets from being cached by your CDN.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/438-cloudflare-compatibility">${ __(
                            'Groundhogg & Cloudflare') }</a></p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using a caching plugin?', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes caching plugins can be overly aggressive in what they choose to cache. You can prevent this by excluding some Groundhogg assets from being cached.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Other common issues with cron-job.org', 'groundhogg'), // language=HTML
                    content: `<ul>
                            <li>
                                ${ __(
                            'Your host may be blocking requests from cron-job.org. If so, have them whitelist the follow IP addresses: <code>195.201.26.157</code> <code>116.203.134.67</code> <code>116.203.129.16</code> <code>23.88.105.37</code>') }
                            </li>
                            <li>
                                ${ __(
                            'Your cron-jobs may have been deactivated after repeated failures due to site down time or some other error.') }
                            </li>
                        </ul>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {

        $('#ticket').on('click', () => {

          ticket.subject = 'Cron-Job.org not working'
          // language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>The external cron job created with cron-job.org is not working.</p>
              <h3><u>Customer notes</u></h3>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })

        faqOnMount()
      }, next: () => '',

    },
    {
      id: 'cron-cpanel', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('CPanel cron jobs not working?', 'groundhogg') }</h1>
                <p>
                    ${ __('CPanel cron jobs can be tricky to set up properly because of the lack of error reporting or feedback. These are the most common issues when setting them up.') }</p>
                ${ faq({
                    title: __('Have you tried using Cron-Job.org?', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Cron-Job.org is free, and incredibly reliable. If your host allows it, cron-job.org is a great alternative to server based cron jobs.') }</p>
<p><a href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org" target="_blank">${ __(
                            'Use Cron-Job.org instead!') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Incorrect path to cron file', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on the host, the path to your cron files may be different from what you might have read in the documentation. ') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct file path is, ask your host and they will tell you.') }</p>`,
                }) }
                ${ faq({
                    title: __('Invalid cron command', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'If you are using a command such as <code>wget</code> or <code>php</code>, those commands may not be available to your installation, or may require additional information.') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct command to use is, ask your host and they will tell you, or consult your host\'s cron job documentation.') }</p>`,
                }) }
                ${ faq({
                    title: __('Host limitations', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on who you host with, and the level of hosting you pay for, there may be limitations on internal cron jobs that are unknown to you.') }</p>
                        <p>${ __(
                            'Consult your hosting support to find our if their are limitations on your account.') }</p>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#ticket').on('click', () => {

          ticket.subject = 'CPanel cron job not working'
          // language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>The external cron job created with Cpanel is not working.</p>
              <h3><u>Customer notes</u></h3>
              <ul>
                  <li>If you can, please add the exact cron commands here as you have set up!</li>
                  <li>Or add a screenshot of the configuration!</li>
              </ul>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })

        faqOnMount()
      }, next: () => '',

    },
    {
      id: 'cron-host-panel', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Host panel cron jobs not working?', 'groundhogg') }</h1>
                <p>
                    ${ __('Cron jobs can sometimes be tricky to set up properly because of the lack of error reporting or feedback from common host panel systems. These are the most common issues when setting them up.') }</p>
                ${ faq({
                    title: __('Have you tried using Cron-Job.org?', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Cron-Job.org is free, and incredibly reliable. If your host allows it, cron-job.org is a great alternative to server based cron jobs.') }</p>
<p><a href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org" target="_blank">${ __(
                            'Use Cron-Job.org instead!') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Incorrect path to cron file', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on the host, the path to your cron files may be different from what you might have read in the documentation. ') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct file path is, ask your host and they will tell you.') }</p>`,
                }) }
                ${ faq({
                    title: __('Invalid cron command', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'If you are using a command such as <code>wget</code> or <code>php</code>, those commands may not be available to your installation, or may require additional information.') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct command to use is, ask your host and they will tell you, or consult your host\'s cron job documentation.') }</p>`,
                }) }
                ${ faq({
                    title: __('Host limitations', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on who you host with, and the level of hosting you pay for, there may be limitations on internal cron jobs that are unknown to you.') }</p>
                        <p>${ __(
                            'Consult your hosting support to find our if their are limitations on your account.') }</p>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#ticket').on('click', () => {

          ticket.subject = 'Host Panel/CLI cron job not working'
          // language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>The external cron job created with the host panel/CLI is not working.</p>
              <h3><u>Customer notes</u></h3>
              <ul>
                  <li>If you can, please add the exact cron commands here as you have set up!</li>
                  <li>Or add a screenshot of the configuration!</li>
              </ul>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })

        faqOnMount()
      }, next: () => '',

    },
    {
      id: 'cron-other', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Cron jobs not working?', 'groundhogg') }</h1>
                <p>
                    ${ __('Cron jobs can sometimes be tricky to set up properly because of the lack of error reporting or feedback from common platforms. These are the most common issues when setting them up.') }</p>
                ${ faq({
                    title: __('Have you tried using Cron-Job.org?', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Cron-Job.org is free, and incredibly reliable. If your host allows it, cron-job.org is a great alternative to server based cron jobs.') }</p>
<p><a href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org" target="_blank">${ __(
                            'Use Cron-Job.org instead!') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Incorrect path to cron file', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on the host, the path to your cron files may be different from what you might have read in the documentation. ') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct file path is, ask your host and they will tell you.') }</p>`,
                }) }
                ${ faq({
                    title: __('Invalid cron command', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'If you are using a command such as <code>wget</code> or <code>php</code>, those commands may not be available to your installation, or may require additional information.') }</p>
                        <p>${ __(
                            'If you are unsure of what the correct command to use is, ask your host and they will tell you, or consult your host\'s cron job documentation.') }</p>`,
                }) }
                ${ faq({
                    title: __('Host limitations', 'groundhogg'),
                    // language=HTML
                    content: `<p>${ __(
                            'Depending on who you host with, and the level of hosting you pay for, there may be limitations on internal cron jobs that are unknown to you.') }</p>
                        <p>${ __(
                            'Consult your hosting support to find our if their are limitations on your account.') }</p>`,
                }) }
                ${ faq({
                    title: __('Hosting with SiteGround?', 'groundhogg'), // language=HTML
                    content: `<p>
                            ${ __('SiteGround is known to block requests from external cron job providers.') }</p>
                        <p>
                            ${ __('Instead, you will have to create cron jobs through the SiteGround admin panel.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/469-setting-up-a-cron-job-on-siteground">${ __(
                            'Read the guide') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Hosting with Kinsta?', 'groundhogg'), // language=HTML
                    content: `<p>
                            ${ __('Kinsta is known to block requests from external cron job providers.') }</p>
                        <p>
                            ${ __(
                            'Instead, you will have to contact Kinsta Support and have them create the cron jobs for you using their internal system.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/444-all-the-cron-jobs-for-groundhogg">${ __(
                            'Provide them with this article.') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using Cloudflare, or another CDN?', 'groundhogg'), // language=HTML
                    content: ` <p>
                            ${ __(
                            'Content delivery networks are known to potentially cause issues if they are not fine tuned.') }</p>
                        <p>
                            ${ __(
                            'If you have not already done so, you will need to exclude multiple Groundhogg files and assets from being cached by your CDN.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/438-cloudflare-compatibility">${ __(
                            'Groundhogg & Cloudflare') }</a></p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using a caching plugin?', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes caching plugins can be overly aggressive in what they choose to cache. You can prevent this by excluding some Groundhogg assets from being cached.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Other common issues with cron jobs.', 'groundhogg'), // language=HTML
                    content: `<ul>
                            <li>
                                ${ __(
                            'Your host may be blocking requests from external cron job providers.') }
                            </li>
                            <li>
                                ${ __(
                            'Your cron-jobs may have been deactivated after repeated failures due to site down time or some other error.') }
                            </li>
                        </ul>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next, prev }) => {

        $('#ticket').on('click', () => {

          ticket.subject = 'Cron job not working'
          // language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>The external cron job created with the "other method" is not working.</p>
              <h3><u>Customer notes</u></h3>
              <ul>
                  <li>How did you create the cron-job? Using what service?</li>
                  <li>Please add a screenshot of the configuration.</li>
              </ul>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })

        faqOnMount()
      }, next: () => '',

    },
    {
      id: 'other-issue', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('What kind of issue are you having?', 'groundhogg') }</h1>
                <ul>
                    <li>
                        <button id="emails-not-sending"
                                class="gh-button text secondary">
                            ${ __('My emails/broadcasts are not sending', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="funnel-not-running"
                                class="gh-button text secondary">
                            ${ __('My funnel is not running', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="funnel-not-working"
                                class="gh-button text secondary">
                            ${ __('My funnel is not working the way it is supposed to', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="cant-unsubscribe"
                                class="gh-button text secondary">
                            ${ __('Contacts can\'t unsubscribe', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="tracking-links-not-working"
                                class="gh-button text secondary">
                            ${ __('Tracking links in emails do not work', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="integration"
                                class="gh-button text secondary">
                            ${ __('An integration/addon is not working', 'groundhogg') }
                        </button>
                    </li>
                    <li>
                        <button id="docs" class="gh-button text secondary">
                            ${ __('I have a different issue.', 'groundhogg') }
                        </button>
                    </li>
                </ul>`
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#emails-not-sending').on('click', () => {
          if (GroundhoggTroubleshooter.recent_failed_events.filter(e => e.error_code === 'wp_mail_failed').length) {
            // wp_mail_failed is in play
            next('smtp-issue')
          }
          else {
            // Probably cron related
            next('cron')
          }
        })
        $('#funnel-not-running').
          on('click', () => GroundhoggTroubleshooter.cron_is_working ? next('funnel-not-working') : next('cron'))
        $('#funnel-not-working').
          on('click', () => GroundhoggTroubleshooter.cron_is_working ? next('funnel-not-working') : next('cron'))
        $('#cant-unsubscribe').on('click', () => next('unsubscribe-common-issues'))
        $('#tracking-links-not-working').on('click', e => next('tracking-link-common-issues'))
        $('#docs').on('click', () => next('docs'))
        $('#integration').on('click', () => next('integration-not-working'))
      }, next: () => '',
    },
    {
      id: 'funnel-not-working',
      render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Issue with your Funnel?', 'groundhogg') }</h1>
                <p>
                    ${ __('Which funnels are you having problems with?') }</p>
                <div class="inside">
                    ${ select({
                        id: 'funnels',
                    }) }
                </div>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next }) => {

        let funnels = []

        $('#funnels').ghPicker({
          endpoint: FunnelsStore.route,
          getParams: (q) => {
            return {
              ...q,
              status: 'active',
            }
          },
          data: FunnelsStore.getItems().map(f => ( {
            id: f.ID,
            text: f.data.title,
          } )),
          getResults: ({ items }) => {
            FunnelsStore.itemsFetched(items)
            return items.map(f => ( { id: f.ID, text: f.data.title } ))
          },
          placeholder: __('Select a funnel...', 'groundhogg'),
          width: '100%',
          multiple: true,
        }).on('change', ({ target }) => {
          funnels = $(target).val().map(id => FunnelsStore.get(parseInt(id)))
        })

        $('#ticket').on('click', () => {

          if (!funnels.length) {
            dialog({
              message: __('Select a funnel first!'),
              type: 'error',
            })
            return
          }

          ticket.subject = 'Issue with my funnels'
          //language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>I'm having issues with the following funnels:</p>
              <ul>
                  ${ funnels.map(f => `<li><a href="${ f.admin }">${ f.data.title }</a></li>`).join('') }
              </ul>
              <h3><u>Customer notes</u></h3>
              <p><i>Please answer the following questions:</i></p>
              <p><b>What do you expect to happen?</b></p>
              <p></p>
              <p><b>What is actually happening?</b></p>
              <p></p>
          `

          next('ticket')
        })
        faqOnMount()
      },
    },
    {
      id: 'integration-not-working',
      render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Integration/Addon Issues', 'groundhogg') }</h1>
                <p>
                    ${ __('Which integrations/addons are you having problems with?') }</p>
                <div class="inside">
                    ${ select({
                        id: 'integrations',
                    }) }
                </div>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next }) => {

        let integrations = Object.values(GroundhoggTroubleshooter.active_plugins)
        let chosen = []

        $('#integrations').select2({
          data: integrations.map(i => ( {
            id: i.Name,
            text: i.Name,
          } )),
          placeholder: __('Select an integration or addon...', 'groundhogg'),
          width: '100%',
          multiple: true,
        }).on('change', ({ target }) => {
          chosen = $(target).val()
        })

        $('#ticket').on('click', () => {

          if (!chosen.length) {
            dialog({
              message: __('Select an integration or addon first!'),
              type: 'error',
            })
            return
          }

          ticket.subject = 'Issue with ' + chosen.join(', ')
          //language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>I'm having issues with the following integrations/addons:</p>
              <ul>
                  ${ chosen.map(i => `<li>${ i }</li>`).join('') }
              </ul>
              <h3><u>Customer notes</u></h3>
              <p><i>Please answer the following questions:</i></p>
              <p><b>What do you expect to happen?</b></p>
              <p></p>
              <p><b>What is actually happening?</b></p>
              <p></p>
              <p><b>Please include links to any relevant pages or assets:</b></p>
              <ul>
                  <li></li>
                  <li></li>
                  <li></li>
              </ul>
          `

          next('ticket')
        })
        faqOnMount()
      },
    },
    {
      id: 'smtp-issue',
      render: () => {
        let { smtp } = GroundhoggTroubleshooter

        return stepTemplate({
          showBack: true,
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('There is a problem with your SMTP configuration', 'groundhogg') }</h1>
                <p>
                    ${ __('We have detected that there is a problem with your SMTP configuration that is most likely responsible for your issues.') }</p>
                ${ faq({
                    show: GroundhoggTroubleshooter.recent_failed_events.filter(
                            e => e.error_code === 'wp_mail_failed').length,
                    title: `⚠️ ${ __('SMTP Errors', 'groundhogg') }`,
                    //language=HTML
                    content: `<p>
                            ${ __(
                            'SMTP errors have been detected. Resolving them will allow you to send emails.',
                            'groundhogg') }</p>
                            <p>${ __('The Error:') }</p>
                            <pre>${ GroundhoggTroubleshooter.recent_failed_events.find(
                            e => e.error_code === 'wp_mail_failed').error_message }</pre>
                            <p>${ __('Before contacting support, try:') }</p>
                            <ul>
                                <li>${ __('Re-entering your SMTP/API credentials') }</li>
                                <li>${ __('Checking the status of your account with your SMTP provider') }</li>
                                <li>${ __('Checking if your host supports SMTP') }</li>
                            </ul>`,
                }) }
                ${ faq({
                    show: [smtp.marketing, smtp.wordpress, smtp.transactional].every(v => v === 'wp_mail'),
                    title: `⚠️ ${ __('No official SMTP integration in use', 'groundhogg') }`, content: `<p>
                            ${ __(
                            'You are not using an official Groundhogg SMTP integration. This can cause issues with deliverability and send speed.',
                            'groundhogg') }</p>
                        <button id="fix-smtp" class="gh-button primary">
                            ${ __('Install an official SMTP integration') }
                        </button>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('I still need help', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next }) => {
        $('#fix-smtp').on('click', () => {
          if (GroundhoggTroubleshooter.smtp.any_service_installed) {
            window.open(adminPageURL('gh_settings', { tab: 'email' }), '_blank')
          }
          else if (GroundhoggTroubleshooter.helper_installed) {
            window.open(adminPageURL('gh_extensions', { terms: [183] }), '_blank')
          }
          else {
            window.open('https://www.groundhogg.io/downloads/tag/sending-service/', '_blank')
          }
        })
        $('#ticket').on('click', () => {

          ticket.subject = 'SMTP Configuration issue'
          //language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>There is an issue with the SMTP configuration:</p>
              <pre>${ GroundhoggTroubleshooter.recent_failed_events.filter(
                      e => e.error_code === 'wp_mail_failed').length
                      ? GroundhoggTroubleshooter.recent_failed_events.find(
                              e => e.error_code === 'wp_mail_failed').error_message
                      : 'No official service installed' }</pre>
              <p><b>Active Services</b></p>
              <table>
                  <tbody>
                  <tr>
                      <td>WordPress email</td>
                      <td style="padding-left: 15px">${ GroundhoggTroubleshooter.smtp.wordpress }</td>
                  </tr>
                  <tr>
                      <td>Marketing email</td>
                      <td style="padding-left: 15px">${ GroundhoggTroubleshooter.smtp.marketing }</td>
                  </tr>
                  <tr>
                      <td>Transactional email</td>
                      <td style="padding-left: 15px">${ GroundhoggTroubleshooter.smtp.transactional }</td>
                  </tr>
                  </tbody>
              </table>
              <h3><u>Customer notes</u></h3>
              <p><i>Please add any additional details you think are relevant...</i></p>`

          next('ticket')
        })
        faqOnMount()
      },
    },
    {
      id: 'unsubscribe-common-issues',
      render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Common reasons why contacts can\'t unsubscribe', 'groundhogg') }</h1>
                <p>
                    ${ __('Here are some common reasons why a contact may be unable to unsubscribe. We suggest you try a few of the suggestions before opening a ticket.') }</p>
                ${ faq({
                    title: __('Aggressive host level caching', 'groundhogg'), // language=HTML
                    content: `  
                        <p>${ __(
                            'It is pretty common these days for hosts to implement aggressive caching at the host level to reduce the burden on their servers.') }</p>
                        <ul>
                            <li>Kinsta</li>
                            <li>WPEngine</li>
                            <li>Flywheel</li>
                            <li>SiteGround</li>
                        </ul>
                        <p>${ __(
                            'Are known to have aggressive host level caching systems. This can have a negative affect on dynamic content everywhere on your site.') }</p>
                        <p>${ __(
                            'Specifically, these caching systems can prevent tracking links from working if not correctly configured.') }</p>
                        <ul>
                            <li>${ __('Object Caching') }</li>
                            <li>${ __('CDN Caching') }</li>
                            <li>${ __('Page Caching *') }</li>
                        </ul>
                        <p>${ __('Read our docs on the subject to configure your caching for the best results:') } <a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using a caching plugin?', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes caching plugins can be overly aggressive in what they choose to cache. You can prevent this by excluding some Groundhogg assets from being cached.') }</p>
                        <p>${ __(
                            'Specifically, these caching systems can prevent the unsubscribe page from working if not correctly configured.') }</p>
                        <ul>
                            <li>${ __('Object Caching') }</li>
                            <li>${ __('CDN Caching') }</li>
                            <li>${ __('Page Caching *') }</li>
                        </ul>
                        <p>${ __('Read our docs on the subject to configure your caching for the best results:') } <a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Broken permalinks', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes a rogue plugin or settings change can break the WordPress permalinks. Re-saving them can be an easy way to restore them!') }</p>
                        <p>
                        <button id="resave-permalinks" class="gh-button primary">${ __(
                            'Re-save permalinks') }</button>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('I still need help', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next }) => {

        $('#resave-permalinks').on('click', e => {

          let release = holdButton(e.currentTarget)
          ajax({
            action: 'groundhogg_resave_permalinks',
          }).then(r => {
            if (r.success) {
              dialog({
                message: __('Permalinks re-saved!'),
              })
            }
            release()

          })
        })

        $('#ticket').on('click', () => {
          ticket.subject = 'Contacts can\'t unsubscribe'
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>Contacts are unable to unsubscribe. Potential problems:</p>
              <ul>
                  <li>Caching</li>
                  <li>Permalinks</li>
                  <li>SMTP issue</li>
                  <li>Issue with managed page</li>
              </ul>
              <h3><u>Customer notes</u></h3>
              <p><i>Please add any additional details you think are relevant...</i></p>`

          next('ticket')
        })
        faqOnMount()
      },
    },
    {
      id: 'tracking-link-common-issues',
      render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Common reasons tracking links aren\'t working', 'groundhogg') }</h1>
                <p>
                    ${ __('Here are some common reasons why tracking links may not work. We suggest you try a few of the suggestions before opening a ticket.') }</p>
                ${ faq({
                    show: [smtp.marketing, smtp.wordpress, smtp.transactional].some(v => v === 'sendgrid'),
                    title: __('SendGrid known issue', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'SendGrid by default rewrites links in your emails before they are sent to the recipient. We recommend you disable this functionality.') }</p>
                        <p><a target="_blank"
                              href="https://help.groundhogg.io/article/313-disable-tracking-links-on-sendgrid">${ __(
                            'Disable tracking links in SendGrid') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Aggressive host level caching', 'groundhogg'), // language=HTML
                    content: `  
                        <p>${ __(
                            'It is pretty common these days for hosts to implement aggressive caching at the host level to reduce the burden on their servers.') }</p>
                        <ul>
                            <li>Kinsta</li>
                            <li>WPEngine</li>
                            <li>Flywheel</li>
                            <li>SiteGround</li>
                        </ul>
                        <p>${ __(
                            'Are known to have aggressive host level caching systems. This can have a negative affect on dynamic content everywhere on your site.') }</p>
                        <p>${ __(
                            'Specifically, these caching systems can prevent tracking links from working if not correctly configured.') }</p>
                        <ul>
                            <li>${ __('Object Caching') }</li>
                            <li>${ __('CDN Caching') }</li>
                            <li>${ __('Page Caching *') }</li>
                        </ul>
                        <p>${ __('Read our docs on the subject to configure your caching for the best results:') } <a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Using a caching plugin?', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes caching plugins can be overly aggressive in what they choose to cache. You can prevent this by excluding some Groundhogg assets from being cached.') }</p>
                        <p>${ __(
                            'Specifically, these caching systems can prevent tracking links from working if not correctly configured.') }</p>
                        <ul>
                            <li>${ __('Object Caching') }</li>
                            <li>${ __('CDN Caching') }</li>
                            <li>${ __('Page Caching *') }</li>
                        </ul>
                        <p>${ __('Read our docs on the subject to configure your caching for the best results:') } <a target="_blank"
                              href="https://help.groundhogg.io/article/208-caching-compatibility">${ __(
                            'Groundhogg & Caching') }</a></p>`,
                }) }
                ${ faq({
                    title: __('Broken permalinks', 'groundhogg'), // language=HTML
                    content: `  <p>
                            ${ __(
                            'Sometimes a rogue plugin or settings change can break the WordPress permalinks. Re-saving them can be an easy way to restore them!') }</p>
                        <p>
                        <button id="resave-permalinks" class="gh-button primary">${ __(
                            'Re-save permalinks') }</button>`,
                }) }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('I still need help', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      },
      onMount: ({ next }) => {

        $('#resave-permalinks').on('click', e => {

          let release = holdButton(e.currentTarget)
          ajax({
            action: 'groundhogg_resave_permalinks',
          }).then(r => {
            if (r.success) {
              dialog({
                message: __('Permalinks re-saved!'),
              })
            }
            release()

          })
        })

        $('#ticket').on('click', () => {
          ticket.subject = 'Tracking links not working'
          //language=HTML
          ticket.message = `
              <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
              <p>Tracking links are not working. Potential problems:</p>
              <ul>
                  <li>Caching</li>
                  <li>Permalinks</li>
                  <li>SMTP issue</li>
                  <li>Issue with managed page</li>
              </ul>
              <h3><u>Customer notes</u></h3>
              <p><i>Please add any additional details you think are relevant...</i></p>`
          next('ticket')
        })
        faqOnMount()
      },
    },
    {
      id: 'docs', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Describe your issue in a few words...', 'groundhogg') }</h1>
                <p>
                    ${ __('We\'ll see if we can find anything related to your problem. Try to use simple keywords like <b>cron jobs</b>, <b>GDPR</b>, <b>MailHawk</b>, <b>tracking</b>, etc.') }</p>
                <div class="inside">
                    ${ input({
                        id: 'question',
                        className: 'full-width input',
                        placeholder: __('Type your question...'),
                    }) }
                </div>
                <ul id="doc-results"></ul>
            `
          },
        })
      },

      onMount: ({ next, prev }) => {

        let timeout
        let search

        $('#question').on('input change', e => {
          search = e.target.value
          maybeSearch()
        })

        const maybeSearch = () => {

          if (timeout) {
            clearTimeout(timeout)
          }
          else {
            $('#doc-results').html(spinner())
          }

          timeout = setTimeout(() => {
            ajax({
              action: 'groundhogg_doc_search',
              query: search,
            }).then((r) => {

              $('#doc-results').
                html(`${ r.articles.results.map(a => `<li><a href="${ a.url }" target="_blank">${ a.name }</a></li>`).
                  join('') }
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('Still need help? Contact support!', 'groundhogg') }
                    </button>
                </div>`)

              $('#ticket').on('click', () => {
                ticket.subject = search
                //language=HTML
                ticket.message = `
                    <p><i>This message was generated by the Groundhogg troubleshooter.</i></p>
                    <p><b>What are you expecting to happen?</b></p>
                    <p></p>
                    <p><b>What is actually happening?</b></p>
                    <p></p>
                    <p><b>Any other details you think are relevant?</b></p>
                    <p></p>
                `
                next('ticket')
              })

            })
          }, 2000)

        }

      }, next: () => '',

    },
    {
      id: 'safe-mode', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Have you tried Safe Mode?', 'groundhogg') }</h1>
                <p>
                    ${ __('Sometimes issues can be caused by a plugin conflict. You can enable safe mode to temporarily and safely disable all plugins (except Groundhogg plugins). Then you can test to see if your issues still exists.') }</p>
                <p>
                    ${ __('Once you have identified if the issue is still present or not, you can disable safe mode and all your plugins will be reactivated safely.') }</p>
                <div class="display-flex column gap-20 inside align-center">
                    <button id="enable-safe-mode"
                            class="gh-button primary medium"><b>${ __(
                            'Turn on Safe Mode!') }</b></button>
                </div>
                <p>
                    ${ __('It is rare, but some less popular themes have also been known to cause issues. You should also try enabling a default WordPress theme as well.') }</p>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="ticket" class="gh-button secondary text">
                        ${ __('I\'ve already tried safe mode', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#enable-safe-mode').on('click', e => {

          let release = holdButton( e.currentTarget )

          $(e.target).text(__('Enabling safe mode'))

          ajax({
            action: 'groundhogg_enable_safe_mode',
          }).then(() => {
            dialog({
              message: __('Safe mode enabled'),
            })
            release()
            ticket.safe_mode = true
            next('safe-mode-2')
          })
        })

        $('#ticket').on('click', () => {
          ticket.safe_mode = true
          next('ticket')
        })
      }, next: () => '',
    },
    {
      id: 'safe-mode-2', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Debugging with Safe Mode', 'groundhogg') }</h1>
                <p>
                    ${ __('Let\'s see if the issue still exists with safe mode on.') }</p>
                <ol>
                    <li>${ __('Try to replicate the issue while safe mode is enabled') }</li>
                    <li>${ __('Is the issue gone? Reactivate each plugin 1 by 1 until the issue reappears.') }</li>
                    <li>${ __('Found the plugin that causes the issue? Disable safe mode and the deactivate it.') }</li>
                </ol>
                <div class="space-between align-center"
                     style="margin-top: 40px">
                    <button id="disable-safe-mode" class="gh-button secondary text">
                        ${ __('Safe mode did not fix the problem', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#disable-safe-mode').on('click', () => {
          ajax({
            action: 'groundhogg_disable_safe_mode',
          }).then(() => {
            dialog({
              message: __('Safe mode disabled'),
            })
            next('ticket')
          })
        })
      }, next: () => '',
    },
    {
      id: 'ticket', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Open a support ticket', 'groundhogg') }</h1>
                <p>${ __('Get in touch with our support team so we can solve your issue.') }</p>
                <p><b>${ __('What is the problem?') }</b></p>
                <p>${ input({
                    id: 'ticket-subject',
                    className: 'full-width input',
                    placeholder: __('Briefly describe your issue...'),
                    value: ticket.subject,
                }) }</p>
                <p><b>${ __('Can you describe the problem in detail?') }</b></p>
                ${ textarea({
                    id: 'ticket-message',
                    value: ticket.message,
                }) }
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {

        if (!ticket.safe_mode) {
          next('safe-mode')
          return
        }

        Options.fetch([
          'gh_support_license',
        ]).then(r => {
          if (!Options.get('gh_support_license')) {
            next('need-license')
          }
        })

        $('#next').on('click', () => next())
        tinymceElement('ticket-message', {
          quicktags: false,
        }, content => ticket.message = content)
      }, next: () => 'ticket-2',

    },
    {
      id: 'ticket-2', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Additional Information', 'groundhogg') }</h1>
                <p>
                    ${ __('Answering these questions will help us resolve your issue faster, and help us tailor our response to match your needs.') }</p>
                <p><b>${ __('Who do you host with?') }</b></p>
                <p>${ input({
                    name: 'host',
                    id: 'host',
                    value: ticket.host,
                    required: true,
                    className: 'full-width input',
                    placeholder: __('The name of your host. Like "Cloudways" or "WPEngine"'),
                }) }</p>
                <p><b>${ __('How are you feeling today?') }</b></p>
                <p>${ select({
                    name: 'mood',
                    id: 'mood',
                    className: 'full-width input',
                }, [
                    'Great',
                    'Okay',
                    'Panicked',
                    'Frustrated',
                    'Angry',
                ], ticket.mood) }</p>
                <p><b>${ __('How experienced are you with WordPress?') }</b></p>
                <p>${ select({
                    name: 'wp_experience',
                    id: 'wp_experience',
                    className: 'full-width input',
                }, [
                    'Novice',
                    'Intermediate',
                    'Expert',
                ], ticket.wp_experience) }</p>
                <p><b>${ __('How experienced are you with Groundhogg?') }</b></p>
                <p>${ select({
                    name: 'gh_experience',
                    id: 'gh_experience',
                    className: 'full-width input',
                }, [
                    'Novice',
                    'Intermediate',
                    'Expert',
                ], ticket.gh_experience) }</p>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#next').on('click', () => {

          if (!ticket.host) {
            dialog({
              message: __('Please tell us who you host with!', 'groundhogg'),
              type: 'error',
            })
            return
          }

          next()
        })
        $('#host, #mood, #wp_experience, #gh_experience').on('input change', (e) => {
          ticket[e.target.name] = e.target.value
        })

        $('#host').autocomplete({
          source: [
            'AWS',
            'A2 Hosting',
            'AccuWeb',
            'BlueHost',
            'Cloudways',
            'Closte',
            'Digital Ocean',
            'DreamHost',
            'Flywheel',
            'GoDaddy',
            'GreenGeeks',
            'HostGator',
            'Hostwinds',
            'Hostinger',
            'IONOS',
            'InMotion',
            'Kinsta',
            'Linode',
            'Liquid Web',
            'Pagely',
            'Rocket.NET',
            'SiteGround',
            'Vultr',
            'WPEngine',
            'WPMUDev',
          ],
        })
      }, next: () => 'ticket-3',

    },
    {
      id: 'ticket-3', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Access & Authorization', 'groundhogg') }</h1>
                <p><b>${ __('Provide Administrative Access') }</b></p>
                <p>
                    ${ __('Allow Groundhogg support staff to login to your website and attempt to resolve the issue? No passwords are shared, instead an admin account is created and an expiring login link is sent to us.') }</p>
                <p><label>${ input({
                    type: 'checkbox',
                    checked: true,
                    name: 'admin_access',
                    id: 'admin_access',
                    value: 1,
                }) } ${ __('Yes, I allow Groundhogg support to login to my site.') }</label>
                </p>
                <p class="gh-text danger hidden">
                    ${ __('Not allowing us to login and debug your site may lead to longer resolution times and prevent us from solving your issue.') }
                </p>
                <p><b>${ __('Debug Authorization') }</b></p>
                <p>
                    ${ __('Sometimes stuff goes wrong, and changes to your site may need to be made including plugins, settings, and more. You can authorize our support team to make these changes on your behalf.') }</p>
                <p><label>${ input({
                    type: 'checkbox',
                    checked: true,
                    name: 'authorization',
                    id: 'authorization',
                    value: 1,
                }) }
                    ${ __('Yes, I authorize Groundhogg support make changes to settings, activate, or deactivate plugins.') }</label>
                </p>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="next" class="gh-button primary">
                        ${ __('Next 👉', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {

        $('#authorization, #admin_access').on('change', (e) => {
          ticket[e.target.name] = e.target.checked
          if (e.target.name === 'admin_access') {
            $('.gh-text.danger').toggleClass('hidden')
          }
        })
        $('#next').on('click', () => next())
      }, next: () => 'ticket-4',

    },
    {
      id: 'ticket-4', render: () => {

        return stepTemplate({
          showBack: true, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Who should we send replies to?', 'groundhogg') }</h1>
                <p><b>${ __('Your Name') }</b></p>
                <p>${ input({
                    name: 'name',
                    id: 'name',
                    className: 'full-width input',
                    value: ticket.name,
                }) }</p>
                <p><b>${ __('Your Email') }</b></p>
                <p>${ input({
                    type: 'email',
                    name: 'email',
                    id: 'email',
                    className: 'full-width input',
                    value: ticket.email,
                }) }</p>
                <div class="display-flex column gap-20 inside align-center">
                    <button id="submit-ticket"
                            class="gh-button primary medium"><b>${ __(
                            'Submit Ticket') }</b></button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {

        $('#submit-ticket').on('click', e => {

          let $btn = $(e.currentTarget)
          $btn.prop('disabled', true)
          let { stop } = loadingDots(e.target)

          ajax({
            action: 'groundhogg_submit_support_ticket',
            ...ticket,
          }).then(r => {

            stop()

            if (r.success) {
              dialog({
                message: __('Ticket sent!'),
              })
              next('ticket-sent')
              return
            }

            let error = r.data[0]

            switch (error.code) {
              case 'license_check_failed':
                dialog({
                  message: __('Your license is invalid.'),
                  type: 'error',
                })
                next('invalid-license')
                break
              case 'UNABLE_TO_CREATE_TICKET':
                $btn.prop('disabled', false)
                dialog({
                  message: __('Unable to created ticket. Try again in a bit.'),
                  type: 'error',
                })
                break
            }

          })
        })

      }, next: () => '',

    },
    {
      id: 'ticket-sent', render: () => {

        return stepTemplate({
          showBack: false, inside: () => {
            // language=HTML
            return `
                <h1>${ __('Your ticket has been sent.', 'groundhogg') }</h1>
                <p>
                    ${ __('Our support team will look at your ticket as soon as possible!',
                            'groundhogg') }</p>
                <p>
                    ${ __('Please note our hours of operation are <b>Monday to Friday</b>, <b>8:00 AM to 6:00 PM EST</b>.',
                            'groundhogg') }</p>
                <p>${ __('Our current average ticket <u>resolution</u> time is under 24 hours.', 'groundhogg') }</p>
                <p>
                    ${ __('Keep track of your tickets from <a href="https://www.groundhogg.io/account/tickets/">your account.</a>',
                            'groundhogg') }</p>
                <p>${ __('Thank you in advance for your patience.', 'groundhogg') }</p>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="next" class="gh-button secondary text">
                        ${ __('Restart troubleshooter', 'groundhogg') }
                    </button>
                </div>
            `
          },
        })
      }, onMount: ({ next, prev }) => {
        $('#next').on('click', () => next())
      }, next: () => 'issues-found',
    },
    {
      id: 'need-license',
      render: () => {
        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Please provide your license key', 'groundhogg') }</h1>
                <p>
                    ${ __('A license key is required to request support. If you have previously purchased a license for Groundhogg you can enter it now! <i><a href="https://www.groundhogg.io/account/licenses/" target="_blank">Where do I find my license?</a></i>',
                            'groundhogg') }</p>
                <div class="display-flex gap-10 inside stretch space-between">
                    ${ input({
                        placeholder: __('Your license key'),
                        id: 'license',
                        value: Options.get('gh_support_license'),
                    }) }
                    <button id="activate" class="gh-button primary medium">
                        ${ __('Activate', 'groundhogg') }
                    </button>
                </div>
                <p>${ __('Don\'t have a license yet? You\'re missing out!') }</p>
                <p><b>${ __('Benefits of licensed support') }</b></p>
                <ul>
                    <li>${ __('Support can login to your site and diagnose issues fast.') }</li>
                    <li>${ __('24 hour <b>resolution</b> time.') }</li>
                    <li>${ __('Rated ⭐⭐⭐⭐⭐ by our customers.') }</li>
                    <li>
                        ${ __('Included in all of our <a href="https://groundhogg.io/pricing/" target="_blank">standard plans</a>.') }
                    </li>
                </ul>
                <div class="display-flex column gap-20 inside align-center">
                    <button data-link="https://groundhogg.io/pricing/?utm_source=plugin&utm_medium=button&utm_campaign=troubleshooter&utm_content=license"
                            class="link gh-button primary medium"><b>${ __(
                            'Purchase a License Now!') }</b></button>
                </div>
                <p>${ __('Alternative options for receiving technical support') }</p>
                <ul>
                    <li><a href="https://www.facebook.com/groups/groundhoggwp/" target="_blank">${ __(
                            'Join our Facebook User Group.') }</a></li>
                    <li><a href="https://help.groundhogg.io/" target="_blank">${ __('Search our documentation.') }</a>
                    </li>
                    <li><a href="https://academy.groundhogg.io/" target="_blank">${ __(
                            'Take a course on Groundhogg Academy.') }</a></li>
                    <li><a href="https://www.youtube.com/Groundhogg" target="_blank">${ __(
                            'Subscribe to our YouTube channel!') }</a></li>
                </ul>`
          },
        })
      },
      onMount: ({ next }) => {

        $('.link').on('click', e => {
          window.open(e.target.dataset.link, '_blank')
        })

        let license = Options.get('gh_support_license')

        $('#license').on('change input', e => {
          license = e.target.value
        })

        $('#next').on('click', () => next())

        $('#activate').on('click', e => {

          let $btn = $(e.currentTarget)
          let { stop } = loadingDots(e.currentTarget)
          $btn.prop('disabled', true)

          ajax({
            action: 'groundhogg_check_support_license',
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

            next('ticket')

          })

        })
      },
      next: () => 'issues-found',
    },
    {
      id: 'invalid-license',
      render: () => {
        return stepTemplate({
          inside: () => {
            // language=HTML
            return `
                <h1>${ __('Unfortunately, your license is no longer valid', 'groundhogg') }</h1>
                <p>${ __('This might have happened for a variety of reasons.') }</p>
                <ul>
                    <li>${ __('You missed a renewal payment & your license expired') }</li>
                    <li>${ __('You recently requested a refund') }</li>
                    <li>${ __('You upgraded or received a new license key') }</li>
                    <li>${ __('Your license does not include premium support access') }</li>
                </ul>
                <p>
                    ${ __('If you are unsure you can go to <a href="https://www.groundhogg.io/account/licenses/">Groundhogg.io</a> and message us via Live Chat!') }</p>
                <p>
                    ${ __('Have a different license key to try? <i><a href="https://www.groundhogg.io/account/licenses/" target="_blank">Where do I find my license?</a></i>',
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
                <p>${ __('Alternative options for receiving technical support') }</p>
                <ul>
                    <li><a href="https://www.facebook.com/groups/groundhoggwp/" target="_blank">${ __(
                            'Join our Facebook User Group.') }</a></li>
                    <li><a href="https://help.groundhogg.io/" target="_blank">${ __('Search our documentation.') }</a>
                    </li>
                    <li><a href="https://academy.groundhogg.io/" target="_blank">${ __(
                            'Take a course on Groundhogg Academy.') }</a></li>
                    <li><a href="https://www.youtube.com/Groundhogg" target="_blank">${ __(
                            'Subscribe to our YouTube channel!') }</a></li>
                </ul>
                <div class="space-between align-right gap-10"
                     style="margin-top: 40px">
                    <button id="next" class="gh-button secondary text">
                        ${ __('Restart troubleshooter', 'groundhogg') }
                    </button>
                </div>`
          },
        })
      },
      onMount: ({ next }) => {

        $('#next').on('click', () => next())

        $('.link').on('click', e => {
          window.open(e.target.dataset.link, '_blank')
        })

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

            next('ticket')

          })

        })
      },
      next: () => 'issues-found',
    },

  ]

  let currentStep = steps[0]

  if (window.location.hash) {
    let id = window.location.hash.substring(1)
    currentStep = steps.find(s => s.id === id)
  }

  history.pushState({ id: currentStep.id }, currentStep.id, `#${ currentStep.id }`)

  const mount = () => {

    wp.editor.remove('ticket-message')

    $('#troubleshooter').html(currentStep.render())

    const next = (id = false) => {

      let _next = id ? id : currentStep.next()

      currentStep = steps.find(s => s.id === _next)

      if (currentStep.beforeMount) {
        _next = currentStep.beforeMount()
        currentStep = steps.find(s => s.id === _next)
      }

      history.pushState({ id: currentStep.id }, currentStep.id, `#${ currentStep.id }`)
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

    mount()

  })

} )(jQuery)
