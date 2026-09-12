( ($) => {

  const {
    Div,
    Span,
    Button,
    An,
    Pg,
    H3,
    Fragment,
    Calendar,
    Modal,
    MiniModal,
    ItemPicker,
    makeEl,
    startOfMonth,
  } = MakeEl

  const {
    broadcasts: BroadcastsStore,
    campaigns : CampaignsStore,
  } = Groundhogg.stores

  const {
    get: apiGet,
    post: apiPost,
    routes,
  } = Groundhogg.api

  const {
    adminPageURL,
    dialog,
    dangerConfirmationModal,
  } = Groundhogg.element

  const { formatNumber } = Groundhogg.formatting
  const { urlEncodeFilters } = Groundhogg.filters
  const {
    userHasCap,
    getOwner,
  } = Groundhogg.user

  const {
    __,
    _x,
    _n,
    sprintf,
  } = wp.i18n

  const {
    timeZone,
    locale,
  } = Groundhogg

  const siteTimeParts = new Intl.DateTimeFormat('en-US', {
    timeZone,
    year  : 'numeric',
    month : '2-digit',
    day   : '2-digit',
    hour  : '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })

  /**
   * send_time is stored as a UTC timestamp, but the calendar lays events out using
   * the *local* components of a Date. Rebuild the date from its parts in the site's
   * timezone so a broadcast lands on the day it was scheduled for, no matter where
   * the browser happens to be.
   *
   * @param timestamp {number} unix timestamp in seconds
   * @return {Date}
   */
  const inSiteTime = timestamp => {

    let parts = {}

    siteTimeParts.formatToParts(new Date(timestamp * 1000)).forEach(({
      type,
      value,
    }) => parts[type] = value)

    return new Date(
      parseInt(parts.year),
      parseInt(parts.month) - 1,
      parseInt(parts.day),
      // some engines render midnight as hour 24
      parseInt(parts.hour) % 24,
      parseInt(parts.minute),
      parseInt(parts.second),
    )
  }

  const timeFormat = new Intl.DateTimeFormat(locale, { timeStyle: 'short' })
  const dateTimeFormat = new Intl.DateTimeFormat(locale, {
    dateStyle: 'full',
    timeStyle: 'short',
  })

  /**
   * Whether a day has already been and gone
   *
   * @param date {Date}
   * @return {boolean}
   */
  const isPast = date => date < new Date(new Date().setHours(0, 0, 0, 0))

  /**
   * Y-m-d, which is the format the broadcast scheduler expects
   *
   * @param date {Date}
   * @return {string}
   */
  const ymd = date => `${ date.getFullYear() }-${ String(date.getMonth() + 1).padStart(2, '0') }-${ String(date.getDate()).padStart(2, '0') }`

  const statusLabels = () => ( {
    pending  : _x('Pending', 'broadcast status', 'groundhogg'),
    scheduled: _x('Scheduled', 'broadcast status', 'groundhogg'),
    sending  : _x('Sending', 'broadcast status', 'groundhogg'),
    sent     : _x('Sent', 'broadcast status', 'groundhogg'),
    cancelled: _x('Cancelled', 'broadcast status', 'groundhogg'),
  } )

  // the pill colour used for each status, matching the calendar legend
  const statusPills = {
    pending  : 'purple',
    scheduled: 'blue',
    sending  : 'orange',
    sent     : 'green',
    cancelled: 'red',
  }

  // the displayed month lives in the URL so leaving the page and coming back
  // lands on the month the user was looking at. This is deliberately here and
  // not in MakeEl.Calendar, which has no business knowing about the address bar.
  const HASH_PREFIX = 'broadcast-calendar/'

  /**
   * The month named by the URL hash, if it names one at all
   *
   * @return {Date|null}
   */
  const monthFromHash = () => {

    let hash = window.location.hash.replace(/^#/, '')

    if (!hash.startsWith(HASH_PREFIX)) {
      return null
    }

    let matches = hash.slice(HASH_PREFIX.length).match(/^(\d{4})-(\d{2})$/)

    if (!matches) {
      return null
    }

    let month = parseInt(matches[2]) - 1
    let date = new Date(parseInt(matches[1]), month, 1)

    // rejects the likes of 2026-13, which Date would happily roll over
    return date.getMonth() === month ? date : null
  }

  /**
   * Record the displayed month in the URL.
   *
   * replaceState rather than assigning to location.hash, so that paging through
   * a year of broadcasts doesn't leave a year of history entries between the
   * user and the page they came from.
   *
   * @param month {Date}
   */
  const monthToHash = month => {

    let hash = `#${ HASH_PREFIX }${ month.getFullYear() }-${ String(month.getMonth() + 1).padStart(2, '0') }`

    if (window.location.hash === hash) {
      return
    }

    history.replaceState({}, document.title, window.location.pathname + window.location.search + hash)
  }

  const State = Groundhogg.createState({
    month     : monthFromHash() ?? startOfMonth(new Date()),
    broadcasts: [],
    statuses  : [], // empty means all of them
    campaigns : [], // { id, text } as the picker wants them, empty means all
    loading   : true,
  })

  /**
   * The range of send times the visible grid could possibly contain.
   *
   * The grid shows leading and trailing days from the adjacent months, and the
   * site timezone may be up to a day away from the browser's, so pad both ends
   * rather than trying to reproduce the grid maths here.
   *
   * @param month {Date} the first of the displayed month
   * @return {{after: string, before: string}}
   */
  const monthRange = month => {

    const PADDING = 8 * 24 * 60 * 60 * 1000

    let start = new Date(month.getFullYear(), month.getMonth(), 1)
    let end = new Date(month.getFullYear(), month.getMonth() + 1, 1)

    return {
      after : new Date(start.getTime() - PADDING).toISOString(),
      before: new Date(end.getTime() + PADDING).toISOString(),
    }
  }

  /**
   * Fetch the broadcasts for the currently displayed month
   *
   * @return {Promise<*>}
   */
  const fetchBroadcasts = () => {

    const {
      after,
      before,
    } = monthRange(State.month)

    let query = {
      after,
      before,
      orderby: 'send_time',
      order  : 'ASC',
      limit  : 500,
      // the calendar doesn't need the email behind each broadcast
      context: 'embed',
    }

    if (State.statuses.length) {
      query.status = State.statuses
    }

    if (State.campaigns.length) {
      // a group per campaign, because groups are OR'd and the filter itself ANDs
      // every campaign named within one group
      query.filters = urlEncodeFilters(State.campaigns.map(({ id }) => [
        {
          type     : 'campaigns',
          campaigns: [id],
        },
      ]))
    }

    return BroadcastsStore.fetchItems(query)
  }

  /**
   * Fetch the broadcasts for the current month and re-render
   *
   * @param morph {function}
   */
  const load = morph => {

    State.set({ loading: true })
    morph()

    fetchBroadcasts().then(broadcasts => {
      State.set({
        broadcasts,
        loading: false,
      })
      morph()
    }).catch(e => {
      State.set({
        broadcasts: [],
        loading   : false,
      })
      morph()
      dialog({
        type   : 'error',
        message: e.message,
      })
    })
  }

  /**
   * Re-render the mounted calendar
   */
  const morphCalendar = () => document.getElementById('gh-broadcast-calendar-wrap')?.morph()

  /**
   * Throw away what's cached and fetch the displayed month again, optionally
   * moving to a different month first.
   *
   * @param month {Date|null} the month to show, or null to stay put
   */
  const refresh = (month = null) => {

    BroadcastsStore.clearResultsCache()
    BroadcastsStore.clearItems()

    if (month) {
      State.set({ month: startOfMonth(month) })
      monthToHash(State.month)
    }

    load(morphCalendar)
  }

  const broadcastTime = broadcast => inSiteTime(broadcast.data.send_time)

  const isEmail = broadcast => broadcast.data.object_type !== 'sms'

  /**
   * A link to the contacts page showing everyone matched by a set of filters
   *
   * @param filters {Array} filter groups
   * @return {string}
   */
  const filtersURL = filters => adminPageURL('gh_contacts', {
    filters: urlEncodeFilters(filters),
  })

  /**
   * A link to the contacts the broadcast was sent to, using the query it was
   * scheduled with
   *
   * @param broadcast {Object}
   * @return {string|false}
   */
  const recipientsURL = broadcast => {

    let query = broadcast.data.query

    if (!query || typeof query !== 'object' || !Object.keys(query).length) {
      return false
    }

    query = { ...query }

    // filters are stored expanded, but travel through the URL base64 encoded
    ;[
      'filters',
      'filters1',
      'filters2',
      'filters3',
      'include_filters',
      'exclude_filters',
      'exclude_filters1',
      'exclude_filters2',
    ].forEach(key => {
      if (Array.isArray(query[key])) {
        query[key] = urlEncodeFilters(query[key])
      }
    })

    return adminPageURL('gh_contacts', {
      ...query,
      is_searching: 'on',
    })
  }

  const reportURL = broadcast => adminPageURL('gh_reporting', {
    tab      : 'broadcasts',
    broadcast: broadcast.ID,
  })

  const editObjectURL = broadcast => adminPageURL(isEmail(broadcast) ? 'gh_emails' : 'gh_sms', {
    action                       : 'edit',
    [broadcast.data.object_type] : broadcast.data.object_id,
  })

  /**
   * The contents of an event chip
   *
   * @param broadcast {Object}
   * @return {Element}
   */
  const BroadcastChip = broadcast => Fragment([
    Span({
      className: 'gh-calendar-event-time',
    }, timeFormat.format(broadcastTime(broadcast))),
    Span({
      className: 'gh-calendar-event-title',
    }, [
      makeEl('span', {
        className: `dashicons dashicons-${ isEmail(broadcast) ? 'email-alt' : 'smartphone' } gh-calendar-event-type`,
      }),
      broadcast.title,
    ]),
  ])

  /**
   * A single stat in the details popover
   *
   * @param number {number}
   * @param label {string}
   * @param href {string|false} makes the number a link to the matching contacts
   * @return {Element}
   */
  const Stat = (number, label, href = false) => Div({
    className: 'gh-broadcast-calendar-stat',
  }, [
    href && number > 0 ? An({
      className: 'gh-broadcast-calendar-stat-number',
      href,
    }, formatNumber(number)) : Span({
      className: 'gh-broadcast-calendar-stat-number',
    }, formatNumber(number)),
    Span({
      className: 'gh-broadcast-calendar-stat-label',
    }, label),
  ])

  /**
   * The popover shown when a broadcast is clicked in the calendar
   *
   * @param broadcast {Object}
   * @param el {Element} the chip that was clicked, used to anchor the popover
   * @param reload {function} re-fetches the month, used after cancelling
   */
  const openBroadcast = (broadcast, el, reload) => {

    const { ID } = broadcast
    const status = broadcast.data.status
    const labels = statusLabels()
    const showsStats = [
      'sent',
      'sending',
    ].includes(status)

    const DetailsState = Groundhogg.createState({
      report   : null,
      cancelled: false,
    })

    const detailsId = `gh-broadcast-calendar-details-${ ID }`

    MiniModal({
      target         : el,
      from           : 'left',
      dialogClasses  : 'gh-broadcast-calendar-details',
      closeOnFocusout: true,
    }, ({ close }) => Div({
      id       : detailsId,
      className: 'display-flex column gap-10',
    }, morph => {

      // stats are expensive to calculate, so they're only fetched when asked for
      if (showsStats && DetailsState.report === null) {
        DetailsState.set({ report: false })
        apiGet(`${ routes.v4.broadcasts }/${ ID }/report`).
          then(r => {
            DetailsState.set({ report: r.report })
            morph()
          }).
          catch(() => {})
      }

      const owner = getOwner(broadcast.data.scheduled_by)
      const totalContacts = parseInt(broadcast.meta?.total_contacts ?? 0)
      const report = DetailsState.report

      return Fragment([

        H3({
          style: { margin: 0 },
        }, broadcast.title),

        Div({
          className: 'display-flex gap-5 align-center',
        }, [
          Span({
            className: `pill sm ${ statusPills[status] ?? 'dark' }`,
          }, labels[status] ?? status),
          Span({
            className: 'gh-text sm',
          }, dateTimeFormat.format(broadcastTime(broadcast))),
        ]),

        owner ? Pg({
          className: 'gh-text sm',
          style    : { margin: 0 },
          /* translators: %s: the display name of the user that scheduled the broadcast */
        }, sprintf(__('Scheduled by %s', 'groundhogg'), owner.data.display_name)) : null,

        !showsStats && totalContacts ? Pg({
          className: 'gh-text sm',
          style    : { margin: 0 },
          /* translators: %s: the number of contacts the broadcast will be sent to */
        }, sprintf(_n('Sending to %s contact', 'Sending to %s contacts', totalContacts, 'groundhogg'), formatNumber(totalContacts))) : null,

        showsStats ? Div({
          className: 'gh-broadcast-calendar-details-stats',
        }, report ? [
          Stat(report.sent ?? 0, _x('Sent', 'stats', 'groundhogg'), filtersURL([
            [
              {
                type        : 'broadcast_received',
                broadcast_id: ID,
              },
            ],
          ])),
          isEmail(broadcast) ? Stat(report.opened ?? 0, _x('Opened', 'stats', 'groundhogg'), filtersURL([
            [
              {
                type        : 'broadcast_opened',
                broadcast_id: ID,
              },
            ],
          ])) : null,
          Stat(report.clicked ?? 0, _x('Clicked', 'stats', 'groundhogg'), filtersURL([
            [
              {
                type        : 'broadcast_link_clicked',
                broadcast_id: ID,
                is_sms      : !isEmail(broadcast),
              },
            ],
          ])),
        ] : Array(3).fill(0).map(() => Div({
          className: 'skeleton-loading',
          style    : { height: '52px' },
        }))) : null,

        Div({
          className: 'display-flex gap-5 flex-wrap',
        }, [

          showsStats ? An({
            className: 'gh-button secondary small',
            href     : reportURL(broadcast),
          }, __('Report', 'groundhogg')) : null,

          isEmail(broadcast) ? Button({
            className: 'gh-button secondary small',
            type     : 'button',
            onClick  : e => {
              close()
              Groundhogg.components.EmailPreviewModal(broadcast.data.object_id, {})
            },
          }, __('Preview', 'groundhogg')) : null,

          An({
            className: 'gh-button secondary text small',
            href     : editObjectURL(broadcast),
          }, isEmail(broadcast) ? __('Edit email', 'groundhogg') : __('Edit SMS', 'groundhogg')),

          recipientsURL(broadcast) ? An({
            className: 'gh-button secondary text small',
            href     : recipientsURL(broadcast),
          }, __('Recipients', 'groundhogg')) : null,

          status !== 'sent' && status !== 'cancelled' && userHasCap('cancel_broadcasts') ? Button({
            className: 'gh-button danger text small',
            type     : 'button',
            onClick  : e => dangerConfirmationModal({
              alert      : `<p>${ __('Are you sure you want to cancel this broadcast? Any contacts that have not received it yet never will.', 'groundhogg') }</p>`,
              confirmText: __('Cancel broadcast', 'groundhogg'),
              closeText  : __('Never mind', 'groundhogg'),
              onConfirm  : () => {
                apiPost(`${ routes.v4.broadcasts }/${ ID }/cancel`).then(() => {
                  close()
                  dialog({ message: __('Broadcast cancelled.', 'groundhogg') })
                  reload()
                }).catch(err => {
                  dialog({
                    type   : 'error',
                    message: err.message,
                  })
                })
              },
            }),
          }, __('Cancel', 'groundhogg')) : null,
        ]),
      ])
    }))
  }

  /**
   * Open the broadcast scheduler pre-filled with the clicked day
   *
   * @param date {Date}
   */
  const scheduleOn = date => Modal({}, () => Groundhogg.BroadcastScheduler({
    when: 'later',
    date: ymd(date),
  }))

  /**
   * The legend, which doubles as the status filter
   *
   * @param morph {function}
   * @return {Element}
   */
  const StatusFilter = morph => {

    const labels = statusLabels()

    return Div({
      className: 'gh-broadcast-calendar-legend',
    }, Object.keys(labels).map(status => Button({
      className: `gh-broadcast-calendar-legend-item ${ State.statuses.length && !State.statuses.includes(status) ? 'is-muted' : '' }`,
      type     : 'button',
      'aria-pressed': State.statuses.includes(status) ? 'true' : 'false',
      onClick  : e => {

        State.set({
          statuses: State.statuses.includes(status)
            ? State.statuses.filter(s => s !== status)
            : [
              ...State.statuses,
              status,
            ],
        })

        load(morph)
      },
    }, [
      Span({
        className: `gh-broadcast-calendar-legend-swatch status-${ status }`,
      }),
      labels[status],
    ])))
  }

  /**
   * Narrow the calendar to one or more campaigns
   *
   * @param morph {function}
   * @return {Element}
   */
  const CampaignFilter = morph => ItemPicker({
    id          : 'gh-broadcast-calendar-campaigns',
    className   : 'gh-broadcast-calendar-campaign-filter',
    noneSelected: __('All campaigns', 'groundhogg'),
    selected    : State.campaigns,
    fetchOptions: async search => {

      let campaigns = await CampaignsStore.fetchItems({
        search,
        limit: 20,
      })

      return campaigns.map(({
        ID,
        data,
      }) => ( {
        id  : ID,
        text: data.name,
      } ))
    },
    onChange    : items => {
      State.set({ campaigns: items })
      load(morph)
    },
  })

  const BroadcastCalendar = () => Div({
    id: 'gh-broadcast-calendar-wrap',
  }, morph => Calendar({
    id             : 'gh-broadcast-calendar',
    className      : 'gh-broadcast-calendar',
    month          : State.month,
    events         : State.broadcasts,
    loading        : State.loading,
    maxEventsPerDay: 3,
    getEventDate   : broadcastTime,
    renderEvent    : BroadcastChip,
    eventProps     : ({ event }) => ( {
      className: `status-${ event.data.status }`,
      title    : `${ dateTimeFormat.format(broadcastTime(event)) } — ${ event.title }`,
    } ),
    dayProps       : ({ date }) => ( {
      // a day that already has broadcasts can still take another one, only the
      // past is off limits
      className: isPast(date) ? 'is-past not-clickable' : '',
    } ),
    headerActions  : () => [
      CampaignFilter(morph),
      StatusFilter(morph),
      An({
        className: 'gh-button secondary text small',
        // nonced server side so the switch is remembered. The bare URL still
        // switches if that's missing, it just won't be the new default.
        href     : window.GroundhoggBroadcastCalendar?.tableUrl ?? adminPageURL('gh_broadcasts', { layout: 'table' }),
      }, __('List view', 'groundhogg')),
    ],
    onMonthChange  : month => {
      State.set({ month })
      monthToHash(month)
      load(morph)
    },
    onEventClick   : ({
      event,
      el,
    }) => openBroadcast(event, el, () => refresh()),
    onDayClick     : userHasCap('schedule_broadcasts') ? ({ date }) => {

      // days in the past can't be scheduled for
      if (isPast(date)) {
        return
      }

      scheduleOn(date)
    } : null,
  }))

  $(() => {

    let mount = document.getElementById('gh-broadcast-calendar-mount')

    if (!mount) {
      return
    }

    mount.append(BroadcastCalendar())

    load(morphCalendar)

    // a broadcast can be scheduled from a day in the calendar or from the button
    // in the page header, either way it has to appear right away
    document.addEventListener('groundhogg/broadcast/scheduled', e => {

      let sendTime = e.detail?.broadcast?.data?.send_time

      // it may well have been scheduled into a month we're not looking at
      refresh(sendTime ? inSiteTime(sendTime) : null)
    })

    // someone navigated to a different month, either with back/forward or by
    // following a link to one. replaceState doesn't fire this, so our own writes
    // can't land us back in here.
    window.addEventListener('hashchange', () => {

      let month = monthFromHash()

      // a hash belonging to something else, or the month already on screen
      if (!month || month.getTime() === State.month.getTime()) {
        return
      }

      State.set({ month })
      load(morphCalendar)
    })
  })

  Groundhogg.BroadcastCalendar = BroadcastCalendar

} )(jQuery)
