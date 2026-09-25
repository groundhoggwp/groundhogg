( ($) => {

  const {
    escHTML,
    loadingModal,
    dialog,
  } = Groundhogg.element
  const {
    formatDateTime,
  } = Groundhogg.formatting
  const {
    __,
    _x,
  } = wp.i18n

  const {
    Div,
    Span,
    H3,
    Button,
    Dashicon,
    ToolTip,
    Fragment,
    Skeleton,
    Input,
    Iframe,
    Pg,
    Modal,
    makeEl,
  } = MakeEl

  /**
   * When a message was, in the time zone of whoever is looking. The dates that are stored are in UTC and have no zone
   * on them, so a browser would read one as being in its own zone, and be hours off. The timestamp doesn't have that.
   *
   * @param timestamp unix seconds
   * @param date_created the stored UTC date, for when there's no timestamp
   * @returns {string}
   */
  const formatWhen = ({ timestamp, date_created }) => formatDateTime(
    timestamp ? timestamp * 1000 : String(date_created).replace(' ', 'T') + 'Z',
  )

  const PAGE_SIZE = 25

  const directions = {
    ''        : __('All', 'groundhogg'),
    'outbound': _x('Sent', 'as in messages sent', 'groundhogg'),
    'inbound' : _x('Received', 'as in messages received', 'groundhogg'),
  }

  const sources = {
    broadcast: __('Broadcast', 'groundhogg'),
    flow     : __('Flow', 'groundhogg'),
  }

  /**
   * Wrap a message body so it renders sensibly in an isolated iframe.
   * Plain text bodies (typically replies) get their whitespace preserved.
   *
   * @param content
   * @param scroll   whether the document scrolls, when it has a frame of its own, and doesn't have the height of what's in it
   * @returns {string}
   */
  const messageDocument = (content, scroll = false) => {

    const isHTML = /<[a-z][\s\S]*>/i.test(content)

    if (!isHTML) {
      content = `<div style="white-space:pre-wrap">${ escHTML(content) }</div>`
    }

    return `<!DOCTYPE html><html><head><meta charset="utf-8"><base target="_blank"><style>body{margin:0;padding:12px;font-family:sans-serif;font-size:14px;overflow-y:${ scroll ? 'auto' : 'hidden' };word-wrap:break-word}img{max-width:100%;height:auto}</style></head><body>${ content }</body></html>`
  }

  /**
   * A message, opened. It's what the timeline links to, and it's not from the messages tab so it does its own
   * reading of the message, since the timeline only has who and when.
   *
   * The body is untrusted, it can be from anyone, so nothing of the message is put in the page as HTML, and the body
   * is in a frame that can't run scripts.
   *
   * @param message the message, with its content
   */
  const MessageModal = message => {

    const {
      subject,
      content,
      from_address,
      to_address,
      direction,
      date_created,
    } = message.data

    const line = (label, value) => `<div><b>${ escHTML(label) }</b> ${ escHTML(value) }</div>`

    Modal({}, ({ close }) => Div({
      className: 'message-modal',
    }, [
      Div({
        className: 'message-modal-header display-flex gap-20 has-box-shadow',
      }, [
        Div({
          className: 'message-modal-details',
        }, [
          makeEl('h2', {}, escHTML(subject || __('(no subject)', 'groundhogg'))),
          line(__('From', 'groundhogg'), from_address),
          line(__('To', 'groundhogg'), to_address),
          line(direction === 'inbound' ? __('Received', 'groundhogg') : __('Sent', 'groundhogg'), formatWhen({ timestamp: message.timestamp, date_created })),
        ]),
        Button({
          className: 'gh-button secondary icon text',
          style    : { marginLeft: 'auto' },
          onClick  : close,
        }, Dashicon('no-alt')),
      ]),
      content
        ? Iframe({
          className: 'message-frame',
          sandbox  : 'allow-same-origin allow-popups',
          style    : { height: '60vh' },
        }, messageDocument(content, true))
        : Div({ className: 'message-note' }, __('The content of this message was not kept.', 'groundhogg')),
    ]))
  }

  /**
   * Read a message and open it
   *
   * @param id
   */
  const openMessage = async id => {

    const { close } = loadingModal()

    try {
      const message = await Groundhogg.stores.messages.maybeFetchItem(id)
      MessageModal(message)
    }
    catch (err) {
      dialog({
        message: err.message,
        type   : 'error',
        ttl    : 5000,
      })
    }

    close()
  }

  $(document).on('click', 'a.view-message', e => {
    e.preventDefault()
    openMessage(parseInt($(e.currentTarget).data('message-id')))
  })

  const BetterObjectMessages = ({
    object_type = '',
    object_id = 0,
    title = __('Messages', 'groundhogg'),
    onNewMessage = null, // callback for the "New message" button, the button is hidden if not provided
    ...props
  } = {}) => {

    const State = Groundhogg.createState({
      loaded   : false,
      loading  : false,
      items    : [], // feed items in display order, oldest first
      expanded : [], // keys of expanded items
      direction: '',
      search   : '',
      automated: false, // include completed broadcast and flow emails
      has_more : false,
    })

    // email bodies for automated emails, loaded from the email log when they're expanded. key => html|null
    const bodies = {}

    let searchTimeout

    /**
     * Fetch a page of the conversation. Pages are requested newest first, but displayed oldest
     * first like a chat, so "load earlier" prepends.
     *
     * @param reset start over from the newest item
     * @returns {Promise<*>}
     */
    const fetchFeed = (reset = true) => {

      if (!object_type || !object_id) {
        State.set({ loaded: true })
        return Promise.resolve([])
      }

      let query = {
        object_id,
        object_type,
        limit            : PAGE_SIZE,
        include_automated: State.automated,
      }

      if (!reset && State.items.length) {
        query.before = State.items[0].timestamp
      }

      if (State.direction) {
        query.direction = State.direction
      }

      if (State.search) {
        query.search = State.search
      }

      State.set({ loading: true })

      return Groundhogg.api.get(`${ Groundhogg.api.routes.v4.messages }/feed`, query).then(r => {

        // `before` is inclusive so the boundary item can come back again
        let known = new Set(reset ? [] : State.items.map(i => i.key))
        let items = r.items.filter(i => !known.has(i.key)).reverse()

        State.set({
          loaded  : true,
          loading : false,
          has_more: r.has_more,
          items   : reset ? items : [...items, ...State.items],
        })

        return items
      }).catch(err => {
        State.set({ loaded: true, loading: false })
      })
    }

    /**
     * Load the body of an automated email from the email log, if it was logged.
     * There's nothing stored for these emails other than the event itself.
     *
     * @param item
     * @returns {Promise<*>}
     */
    const loadBody = item => {

      if (item.key in bodies) {
        return Promise.resolve()
      }

      if (!item.has_log) {
        bodies[item.key] = null
        return Promise.resolve()
      }

      return Groundhogg.stores.email_log.fetchItems({
        queued_event_id: item.queued_id,
        limit          : 1,
      }).then(logs => {
        bodies[item.key] = logs.length ? logs[0].data.content : null
      }).catch(() => {
        bodies[item.key] = null
      })
    }

    return Div({
      ...props,
      id       : props.id ?? `messages-widget-${ object_type }-${ object_id }`,
      className: 'messages-widget',
    }, morph => {

      if (!State.loaded) {

        fetchFeed().then(morph)

        return Skeleton({
          style: {
            padding: '10px',
          },
        }, [
          'full',
          'full',
          'full',
        ])
      }

      const reload = () => fetchFeed().then(morph)

      const toggleExpanded = item => {

        if (State.expanded.includes(item.key)) {
          State.set({ expanded: State.expanded.filter(k => k !== item.key) })
          morph()
          return
        }

        State.set({ expanded: [...State.expanded, item.key] })

        if (item.kind === 'event') {
          // show the bubble opening right away, then fill in the body when it arrives
          morph()
          loadBody(item).then(morph)
          return
        }

        morph()
      }

      /**
       * The expanded body of an item
       *
       * @param item
       * @param content
       * @returns {*}
       */
      const Body = (item, content) => {

        if (content === undefined) {
          return Div({ className: 'message-body message-note' }, __('Loading...', 'groundhogg'))
        }

        if (!content) {
          return Div({ className: 'message-body message-note' }, __('The content of this email was not logged.', 'groundhogg'))
        }

        return Div({ className: 'message-body' }, [
          Iframe({
            className: 'message-frame',
            // no scripts, ever. Inbound messages are untrusted. same-origin only so we can size the frame
            sandbox  : 'allow-same-origin allow-popups',
            onLoad   : e => {
              try {
                let frame = e.currentTarget
                frame.style.height = frame.contentDocument.documentElement.scrollHeight + 'px'
              }
              catch (err) {}
            },
          }, messageDocument(content)),
        ])
      }

      /**
       * A single bubble. Outbound on the right, inbound on the left.
       * Composed emails and replies are stored messages, broadcast and flow emails are read from events.
       *
       * @param item
       * @returns {*}
       */
      const Bubble = item => {

        const automated = item.kind === 'event'
        const inbound = !automated && item.data.direction === 'inbound'
        const expanded = State.expanded.includes(item.key)

        const subject = automated ? item.subject : item.data.subject
        const status = automated ? 'sent' : item.data.status
        const date_created = automated ? item.date_created : item.data.date_created

        let sender

        if (automated) {
          sender = sources[item.source]
        }
        else {
          sender = inbound ? item.data.from_address : ( item.data.from_address || __('You', 'groundhogg') )
        }

        let content = automated ? bodies[item.key] : item.data.content

        return Div({
          className: `message-row ${ inbound ? 'inbound' : 'outbound' }`,
          id       : `message-row-${ item.key }`,
        }, Div({
          className: `message-bubble ${ automated ? 'automated' : '' } ${ expanded ? 'expanded' : '' } ${ status === 'failed' ? 'failed' : '' }`,
          role     : 'button',
          tabindex : 0,
          dataKey  : item.key,
          onClick  : e => {

            // don't collapse when interacting inside the expanded body
            if (e.target.closest('.message-body')) {
              return
            }

            toggleExpanded(item)
          },
        }, [
          Div({ className: 'message-meta' }, [
            Span({ className: 'message-sender' }, sender),
            status === 'failed' ? Span({ className: 'pill red' }, __('Failed', 'groundhogg')) : null,
            Span({ className: 'message-time' }, `<abbr title="${ formatWhen({ timestamp: item.timestamp, date_created }) }">${ item.i18n.time_diff }</abbr>`),
          ]),
          Div({ className: 'message-subject' }, subject || __('(no subject)', 'groundhogg')),
          automated ? Div({ className: 'message-source' }, escHTML(item.source_title)) : null,
          expanded
            ? Body(item, content)
            : ( automated ? null : Div({ className: 'message-preview' }, item.preview) ),
        ]))
      }

      return Fragment([
        title ? H3({}, title) : null,

        // Toolbar
        Div({
          className: 'messages-header display-flex gap-10 align-center flex-wrap',
        }, [
          Div({ className: 'gh-input-group' }, Object.keys(directions).map(key => Button({
            id       : `messages-direction-${ key || 'all' }`,
            className: `gh-button small ${ State.direction === key ? 'dark' : 'grey' }`,
            onClick  : e => {
              State.set({ direction: key })
              reload()
            },
          }, directions[key]))),
          Input({
            id         : 'messages-search',
            type       : 'search',
            className  : 'messages-search',
            placeholder: __('Search messages...', 'groundhogg'),
            value      : State.search,
            onInput    : e => {
              let value = e.target.value
              clearTimeout(searchTimeout)
              searchTimeout = setTimeout(() => {
                State.set({ search: value })
                reload().then(() => {
                  // keep focus in the search box after the re-render
                  let input = document.getElementById('messages-search')
                  if (input) {
                    input.focus()
                    input.setSelectionRange(value.length, value.length)
                  }
                })
              }, 300)
            },
          }),
          object_type === 'contact' ? Button({
            id       : 'messages-automated',
            className: `gh-button small ${ State.automated ? 'dark' : 'grey' }`,
            onClick  : e => {
              State.set({ automated: !State.automated })
              reload()
            },
          }, [
            __('Automated emails', 'groundhogg'),
            ToolTip(__('Include completed broadcast and flow emails', 'groundhogg'), 'bottom'),
          ]) : null,
          Div({ className: 'display-flex gap-5', style: { marginLeft: 'auto' } }, [
            Button({
              id       : 'refresh-messages',
              className: 'gh-button secondary text icon',
              onClick  : reload,
            }, [
              Dashicon('update-alt'),
              ToolTip(__('Refresh', 'groundhogg'), 'left'),
            ]),
            typeof onNewMessage === 'function' ? Button({
              id       : 'new-message',
              className: 'gh-button primary small',
              onClick  : onNewMessage,
            }, __('New message', 'groundhogg')) : null,
          ]),
        ]),

        State.automated && State.search ? Pg({ className: 'messages-hint' }, __('Automated emails are not included when searching.', 'groundhogg')) : null,

        // Thread
        Div({
          className: 'messages-thread',
          id       : 'messages-thread',
        }, [
          State.has_more ? Div({ className: 'messages-load-earlier' }, Button({
            id       : 'load-earlier-messages',
            className: 'gh-button secondary text small',
            disabled : State.loading,
            onClick  : e => fetchFeed(false).then(morph),
          }, __('Load earlier messages', 'groundhogg'))) : null,
          ...State.items.map(Bubble),
          State.items.length ? null : Pg({
            style: {
              textAlign: 'center',
            },
          }, State.search || State.direction ? __('No matching messages.', 'groundhogg') : __('No messages yet.', 'groundhogg')),
        ]),
      ])
    })
  }

  Groundhogg.messageViewer = (selector, props = {}) => {
    let el = document.querySelector(selector)
    el.append(BetterObjectMessages(props))
  }

  Groundhogg.ObjectMessages = BetterObjectMessages

} )(jQuery)
