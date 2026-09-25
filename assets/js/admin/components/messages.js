( ($) => {

  const {
    escHTML,
    loadingModal,
    dialog,
    adminPageURL,
    moreMenu,
  } = Groundhogg.element
  const {
    formatDateTime,
  } = Groundhogg.formatting
  const {
    __,
    _x,
    sprintf,
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
    An,
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

  /**
   * A pill that filters, the one that's on is bold. Like the ones for tasks.
   *
   * @param id
   * @param color
   * @param active
   * @param onClick
   * @param children
   */
  const FilterPill = ({ id, color = 'colorless', active = false, onClick, ...props }, children) => Span({
    id,
    className: `pill ${ color } clickable ${ active ? 'bold active' : '' }`,
    role     : 'button',
    tabindex : 0,
    onClick,
    onKeydown: e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault()
        onClick(e)
      }
    },
    ...props,
  }, children)

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

    const rootId = props.id ?? `messages-widget-${ object_type }-${ object_id }`

    /**
     * It reads from the top down to the newest, which is at the bottom and where a reply is written, so it's
     * where it starts and where it goes to when there's something new. Only a panel that scrolls is moved.
     */
    const scrollToNewest = () => {
      const thread = document.getElementById(rootId)?.querySelector('.messages-thread')

      if (thread) {
        thread.scrollTop = thread.scrollHeight
      }
    }

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
      id       : rootId,
      className: 'messages-widget',
    }, morph => {

      if (!State.loaded) {

        fetchFeed().then(morph).then(scrollToNewest)

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

      const reload = () => fetchFeed().then(morph).then(scrollToNewest)

      /**
       * What makes an email a reply to a message, so that a mail client puts it in the same conversation. It's the id of
       * the message, and of what is before it in the thread, when there's one that is an id.
       *
       * @param message
       * @returns {{}}
       */
      const replyHeaders = message => {

        const isId = id => /^<[^<>\s@]+@[^<>\s@]+>$/.test(id || '')
        const { message_id, thread_id } = message.data

        if (!isId(message_id)) {
          return {}
        }

        return {
          'In-Reply-To': message_id,
          'References' : [ thread_id, message_id ].filter((id, i, ids) => isId(id) && ids.indexOf(id) === i).join(' '),
        }
      }

      /**
       * A message that was received, read or not read. Whatever else shows what has to be read is told.
       *
       * @param item
       * @param read
       */
      const setRead = (item, read) => Groundhogg.api.post(`${ Groundhogg.api.routes.v4.messages }/read`, {
        message_id: item.ID,
        read,
      }).then(() => {
        item.data.is_read = read ? 1 : 0
        window.dispatchEvent(new CustomEvent('messagesread', { detail: { object_type, object_id } }))
        morph()
      }).catch(err => dialog({ message: err.message, type: 'error', ttl: 5000 }))

      const toggleExpanded = item => {

        if (State.expanded.includes(item.key)) {
          State.set({ expanded: State.expanded.filter(k => k !== item.key) })
          morph()
          return
        }

        State.set({ expanded: [...State.expanded, item.key] })

        // it's read when it's opened, not when it's just there. What's in a bubble is only the start of it
        const live = State.items.find(i => i.key === item.key) ?? item

        if (live.kind === 'message' && live.data.direction === 'inbound' && Number(live.data.is_read) === 0) {
          setRead(live, true)
        }

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

        // received, and not read yet
        const unread = inbound && Number(item.data.is_read) === 0

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
            unread ? Span({ className: 'message-unread-dot', title: __('Not read', 'groundhogg') }) : null,
            Span({ className: 'message-sender' }, sender),
            status === 'failed' ? Span({ className: 'pill red' }, __('Failed', 'groundhogg')) : null,
            Span({ className: 'message-time' }, `<abbr title="${ formatWhen({ timestamp: item.timestamp, date_created }) }">${ item.i18n.time_diff }</abbr>`),
            inbound ? Button({
              className: 'gh-button secondary text icon message-more',
              onClick  : e => {

                // not the bubble opening
                e.stopPropagation()

                // as it is now. This is the handler of the element that was first made for it, what was rendered since is not what it was made with
                const live = State.items.find(i => i.key === item.key) ?? item
                const notRead = Number(live.data.is_read) === 0

                moreMenu(e.currentTarget, [
                  // a reply, to who it was from and with the subject of it, it's in the thread when it's sent
                  object_type === 'contact' ? {
                    key     : 'reply',
                    text    : __('Reply', 'groundhogg'),
                    onSelect: () => Groundhogg.components.emailModal({
                      to     : [ live.data.from_address ],
                      subject: /^\s*re:/i.test(live.data.subject) ? live.data.subject : sprintf(__('Re: %s', 'groundhogg'), live.data.subject || ''),
                      headers: replyHeaders(live),
                    }, reload),
                  } : null,
                  {
                    key     : 'read',
                    text    : notRead ? __('Mark read', 'groundhogg') : __('Mark unread', 'groundhogg'),
                    onSelect: () => setRead(live, notRead),
                  },
                ].filter(Boolean))
              },
            }, Dashicon('ellipsis')) : null,
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
          className: 'messages-header display-flex gap-5 align-center flex-wrap',
        }, [
          ...Object.keys(directions).map(key => FilterPill({
            id     : `messages-direction-${ key || 'all' }`,
            color  : { '': 'colorless', outbound: 'blue', inbound: 'green' }[key],
            active : State.direction === key,
            onClick: e => {
              State.set({ direction: key })
              reload()
            },
          }, directions[key])),
          object_type === 'contact' ? FilterPill({
            id     : 'messages-automated',
            color  : 'orange',
            active : State.automated,
            title  : __('Include completed broadcast and flow emails', 'groundhogg'),
            onClick: e => {
              State.set({ automated: !State.automated })
              reload()
            },
          }, __('Automated', 'groundhogg')) : null,
          Input({
            id         : 'messages-search',
            type       : 'search',
            className  : 'messages-search',
            placeholder: __('Search...', 'groundhogg'),
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
          Button({
            id       : 'refresh-messages',
            className: 'gh-button secondary text icon',
            style    : { marginLeft: 'auto' },
            onClick  : reload,
          }, [
            Dashicon('update-alt'),
            ToolTip(__('Refresh', 'groundhogg'), 'left'),
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

        // where a message is written, under what it's in reply to
        typeof onNewMessage === 'function' ? Div({ className: 'messages-compose' }, Button({
          id       : 'new-message',
          className: 'messages-compose-button',
          onClick  : onNewMessage,
        }, __('Write a message...', 'groundhogg'))) : null,
      ])
    })
  }

  /**
   * The messages of a contact in a panel that slides in.
   *
   * @param contact {{ID: number, name: string, email: string}}
   * @param onClose called when the panel is closed
   */
  const MessagesSidebar = ({ contact, onClose = () => {} }) => {

    let sidebar

    sidebar = MakeEl.Sidebar({
      className: 'messages-sidebar',
      header   : An({
        // the messages tab, it's where the conversation is
        href     : adminPageURL('gh_contacts', { action: 'edit', contact: contact.ID }, 'messages'),
        className: 'messages-sidebar-contact',
      }, escHTML(contact.name || contact.email)),
      onClose,
    }, [
      BetterObjectMessages({
        object_type : 'contact',
        object_id   : contact.ID,
        title       : false,
        onNewMessage: () => Groundhogg.components.emailModal({
          to: [ contact.email ],
        }, () => {
          // what was sent is in the conversation
          sidebar.querySelector('#refresh-messages')?.click()
        }),
      }),
    ])

    return sidebar
  }

  Groundhogg.MessagesSidebar = MessagesSidebar

  Groundhogg.messageViewer = (selector, props = {}) => {
    let el = document.querySelector(selector)
    el.append(BetterObjectMessages(props))
  }

  Groundhogg.ObjectMessages = BetterObjectMessages

} )(jQuery)
