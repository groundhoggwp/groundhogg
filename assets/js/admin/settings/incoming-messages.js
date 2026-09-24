( ($) => {

  const {
    dangerConfirmationModal,
  } = Groundhogg.element

  const {
    CopyInput,
  } = Groundhogg.components

  const {
    get,
    post,
    routes,
    ApiError,
  } = Groundhogg.api

  const {
    __,
    sprintf,
  } = wp.i18n

  const {
    Div,
    Fragment,
    Pg,
    Button,
    Table,
    Tr,
    Th,
    Td,
    makeEl,
  } = MakeEl

  const ROUTE = routes.v4.inbox

  /**
   * A rough "3 hours ago", good enough for a settings page.
   *
   * @param seconds unix timestamp
   * @returns {string}
   */
  const timeAgo = seconds => {

    const diff = Math.max(0, Math.floor(Date.now() / 1000) - seconds)

    if (diff < 60) {
      return __('just now', 'groundhogg')
    }

    // the largest unit the difference reaches, cascading down to minutes
    const units = [
      [ 2592000, __('a month', 'groundhogg'), __('%d months', 'groundhogg') ],
      [ 86400, __('a day', 'groundhogg'), __('%d days', 'groundhogg') ],
      [ 3600, __('an hour', 'groundhogg'), __('%d hours', 'groundhogg') ],
      [ 60, __('a minute', 'groundhogg'), __('%d minutes', 'groundhogg') ],
    ]

    const [ threshold, singular, plural ] = units.find(([ seconds_ ]) => diff >= seconds_) ?? units[units.length - 1]
    const n = Math.floor(diff / threshold)

    /* translators: %s: a relative time, like "3 hours" */
    return sprintf(__('%s ago', 'groundhogg'), n <= 1 ? singular : sprintf(plural, n))
  }

  const IncomingMessagesSettings = () => {

    const State = Groundhogg.createState({
      loaded          : false,
      loading         : false,
      error           : '',
      provisioned     : false,
      site_matches    : true,
      active          : false,
      address         : '',
      reply_address   : '',
      endpoint_current: true,
      last_received   : 0,
      reply_to_enabled: true,
      has_license     : false,
    })

    let morph

    /**
     * Run a request, update state from the result, and re-render. Never called synchronously during the
     * initial render - the component isn't in the DOM yet at that point, and morph() needs
     * document.getElementById() to find it (see load(), which handles that first fetch instead).
     *
     * @param request a promise of the new state
     */
    const settle = request => request.then(state => {
      State.set({ ...state, loaded: true, loading: false })
      morph()
    }).catch(err => {

      const message = err instanceof ApiError ? err.message : __('Something went wrong. Please try again.', 'groundhogg')

      // shown inline (see errorNotice below) - no need for a toast saying the same thing
      State.set({ loading: false, error: message })
      morph()
    })

    /**
     * The first read of the state, on mount. Nothing here calls morph() until the promise resolves,
     * by which point the component has been appended to the page.
     */
    const load = () => {
      State.set({ loading: true, error: '' })
      return settle(get(ROUTE))
    }

    /**
     * One of the buttons. The component is already mounted whenever this runs, so showing the loading
     * state right away is safe.
     *
     * @param route enable|update|rotate|disable|forget
     */
    const call = route => {
      State.set({ loading: true, error: '' })
      morph()
      return settle(post(`${ ROUTE }/${ route }`, {}))
    }

    const confirmThen = (message, route) => dangerConfirmationModal({
      alert    : `<p>${ message }</p>`,
      onConfirm: () => call(route),
    })

    return Div({
      id: 'incoming-messages-settings-inner',
    }, m => {

      morph = m

      if (!State.loaded && !State.loading) {
        load()
      }

      if (!State.loaded) {
        return Pg({}, __('Loading…', 'groundhogg'))
      }

      const {
        provisioned,
        site_matches,
        address,
        endpoint_current,
        last_received,
        reply_to_enabled,
        has_license,
      } = State

      const Row = (label, content) => Tr({}, [
        Th({ style: { textAlign: 'left', whiteSpace: 'nowrap', paddingRight: '15px' } }, label),
        Td({}, content),
      ])

      // a small status pill, the same one used for message delivery status elsewhere
      const Status = (pillClass, pillText, text) => Div({
        className: 'display-flex gap-10 align-center',
        style    : { marginBottom: '15px' },
      }, [
        makeEl('span', { className: `pill ${ pillClass }` }, pillText),
        makeEl('b', {}, text),
      ])

      // the maintenance/destructive actions, visually lighter and separated from the status above
      const Actions = (...buttons) => Div({
        className: 'display-flex gap-10 flex-wrap',
        style    : { marginTop: '20px', paddingTop: '15px', borderTop: '1px solid var(--gh-dark-10)' },
      }, buttons)

      const errorNotice = State.error ? Div({
        className: 'notice notice-error inline',
        style    : { margin: '0 0 15px' },
      }, Pg({}, State.error)) : null

      // the same wording as Inbox_Client::enable()'s no_license error, for when this is reached some other way
      const licenseNeededText = __('A Groundhogg license is needed to receive messages. Activate your license on the Licenses tab.', 'groundhogg')

      // not set up yet
      if (!provisioned) {

        if (!has_license) {
          return Fragment([
            errorNotice,
            Status('tag', __('Off', 'groundhogg'), __('Incoming messages are off.', 'groundhogg')),
            Pg({}, licenseNeededText),
          ])
        }

        return Fragment([
          errorNotice,
          Status('tag', __('Off', 'groundhogg'), __('Incoming messages are off.', 'groundhogg')),
          Pg({ className: 'description' }, __('Turning them on gives your site a private address to send mail to. Your site has to be reachable over HTTPS, so that the messages can be delivered to it.', 'groundhogg')),
          Button({
            type     : 'button',
            className: `gh-button primary ${ State.loading ? 'loading-dots' : '' }`,
            disabled : State.loading,
            onClick  : () => call('enable'),
          }, __('Turn on incoming messages', 'groundhogg')),
        ])
      }

      // set up, but for a different site (a staging copy, ...)
      if (!site_matches) {
        return Fragment([
          errorNotice,
          Status('orange', __('Different site', 'groundhogg'), __('This inbox belongs to another site.', 'groundhogg')),
          Div({
            className: 'notice notice-warning inline',
          }, Pg({}, __('This site has the settings of an inbox that was set up for a different site, and is probably a copy of it, like a staging site. Messages are not received here, and replies are not sent to that inbox, so that they do not end up in the wrong place.', 'groundhogg'))),
          !has_license ? Pg({}, licenseNeededText) : null,
          Actions(
            has_license ? Button({
              type     : 'button',
              className: `gh-button primary ${ State.loading ? 'loading-dots' : '' }`,
              disabled : State.loading,
              onClick  : () => call('enable'),
            }, __('Set up an inbox for this site', 'groundhogg')) : null,
            Button({
              type     : 'button',
              className: 'gh-button secondary text',
              disabled : State.loading,
              onClick  : () => confirmThen(__('Forget this inbox? The site it belongs to keeps it.', 'groundhogg'), 'forget'),
            }, __('Forget the other site\'s inbox', 'groundhogg')),
          ),
        ])
      }

      return Fragment([
        errorNotice,
        Status('green', __('On', 'groundhogg'), __('Incoming messages are on.', 'groundhogg')),

        !endpoint_current ? Div({
          className: 'notice notice-warning inline',
        }, [
          Pg({}, __('The address of your site has changed, for example because the permalinks were changed. Messages cannot be delivered to it until Groundhogg is told.', 'groundhogg')),
          Button({
            type     : 'button',
            className: `gh-button primary ${ State.loading ? 'loading-dots' : '' }`,
            disabled : State.loading,
            onClick  : () => call('update'),
          }, __('Update the address', 'groundhogg')),
        ]) : null,

        Table({ className: 'form-table', role: 'presentation' }, [
          Row(__('Inbox address', 'groundhogg'), [
            CopyInput(address),
            Pg({ className: 'description' }, __('BCC this address on emails you send from your own mailbox, or forward your support address to it. In Google Workspace, you can add a routing rule that changes the envelope recipient to it.', 'groundhogg')),
            Pg({ className: 'description' }, __('Keep it private. Anyone that has it can add messages to your contacts.', 'groundhogg')),
          ]),
          Row(__('Replies', 'groundhogg'),
            reply_to_enabled
              ? __('Replies to emails that you send from Groundhogg are received here, and do not go to your own mailbox.', 'groundhogg')
              : __('Replies to emails that you send from Groundhogg go to the address they were sent from. Something has turned this off.', 'groundhogg'),
          ),
          Row(__('Last message', 'groundhogg'), [
            last_received ? timeAgo(last_received) : __('No message has been received yet.', 'groundhogg'),
            Pg({ className: 'description' }, __('If messages should be arriving and this is old, check that the inbox address is set up where you send from, and that your license is active.', 'groundhogg')),
          ]),
        ]),

        Actions(
          Button({
            type     : 'button',
            className: 'gh-button secondary text',
            disabled : State.loading,
            onClick  : () => confirmThen(__('Replace the secret that Groundhogg signs messages with?', 'groundhogg'), 'rotate'),
          }, __('Replace the secret', 'groundhogg')),
          has_license ? Button({
            type     : 'button',
            className: 'gh-button secondary text',
            disabled : State.loading,
            onClick  : () => call('enable'),
          }, __('Set up again', 'groundhogg')) : null,
          Button({
            type     : 'button',
            className: 'gh-button danger text',
            style    : { marginLeft: 'auto' },
            disabled : State.loading,
            onClick  : () => confirmThen(__('Turn off incoming messages? Mail sent to the inbox address will be rejected.', 'groundhogg'), 'disable'),
          }, __('Turn off incoming messages', 'groundhogg')),
        ),
      ])
    })
  }

  $(() => {
    let mount = document.getElementById('incoming-messages-settings')

    if (mount) {
      mount.append(IncomingMessagesSettings())
    }
  })

} )(jQuery)
