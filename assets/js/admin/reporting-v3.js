( function ($) {

  const ReportPages = []
  const { StepTypes } = Groundhogg
  const { get, routes } = Groundhogg.api
  const {
    options: OptionsStore,
    funnels: FunnelsStore,
    broadcasts: BroadcastsStore,
    emails: EmailsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores
  const { loadingModal, icons, modal, input, select, tooltip, adminPageURL } = Groundhogg.element
  const { formatNumber } = Groundhogg.formatting

  const { __, sprintf } = wp.i18n

  function utf8_to_b64 (str) {
    return window.btoa(unescape(encodeURIComponent(str)))
  }

  function b64_to_utf8 (str) {
    return decodeURIComponent(escape(window.atob(str)))
  }

  const base64_json_encode = (stuff) => {
    return utf8_to_b64(JSON.stringify(stuff))
  }

  function isValidHostname (value) {
    if (typeof value !== 'string') {
      return false
    }

    const validHostnameChars = /^[a-z0-9-.]{1,253}\.?$/g
    if (!validHostnameChars.test(value)) {
      return false
    }

    if (value.endsWith('.')) {
      value = value.slice(0, value.length - 1)
    }

    if (value.length > 253) {
      return false
    }

    const labels = value.split('.')

    const isValid = labels.every(function (label) {
      const validLabelChars = /^([a-zA-Z0-9-]+)$/g

      const validLabel = (
        validLabelChars.test(label) &&
        label.length < 64 &&
        !label.startsWith('-') &&
        !label.endsWith('-')
      )

      return validLabel
    })

    return isValid
  }

  const arrows = {
    //language=HTML
    up: `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 492 492">
            <path fill="currentColor"
                  d="M442.6 185.4L265.1 7.8c-5-5-11.8-7.8-19.2-7.8-7.2 0-14 2.8-19 7.8L49.3 185.4a27 27 0 000 38l16.2 16.2a27.2 27.2 0 0038.3 0L207.5 136v329c0 14.9 11.6 27 26.4 27h22.8a27.6 27.6 0 0027.6-27V134.9l104.4 104.8c5 5 11.7 7.8 18.9 7.8s13.8-2.8 18.9-7.8l16-16.2a27 27 0 000-38z"/>
        </svg>`,
    //language=HTML
    down: `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 492 492">
            <path fill="currentColor"
                  d="M49.4 306.6l177.5 177.6c5 5 11.8 7.8 19.2 7.8 7.2 0 14-2.8 19-7.8l177.6-177.6a27 27 0 000-38l-16.2-16.2a27.2 27.2 0 00-38.3 0L284.5 356V27c0-14.9-11.6-27-26.4-27h-22.8a27.6 27.6 0 00-27.6 27v330.2L103.2 252.4c-5-5-11.7-7.8-18.9-7.8s-13.8 2.8-18.9 7.8l-16 16.2a27 27 0 000 38z"/>
        </svg>`,
  }

  const renderReport = (report) => {

    // language=HTML
    return `
        <div id="${ report.id }" class="gh-panel report ${ report.type } ${ report.rows
                ? `grid-rows-${ report.rows }`
                : '' } ${ report.columns ? `grid-columns-${ report.columns }` : '' }" data-id="${ report.id }">
            <div class="gh-panel-header">
                <h2>${ report.name }
                    ${ report.description ? `<span class="dashicons dashicons-info-outline"></span>` : '' }
                </h2>
            </div>
            ${ ReportTypes[report.type].render(report) }
        </div>`
  }

  const Dashboard = {

    currentPage: 'overview',
    startDate: '',
    endDate: '',
    params: {},
    reportData: {},

    ...GroundhoggReporting,

    setPage (page) {

      this.currentPage = page
      this.params = this.currentPage.split('/')

      history.pushState({
          page,
        }, '',
        `#${ page }`)

      this.mount()
    },

    setDate (start, end) {

      this.startDate = start
      this.endDate = end

      this.loadReports()
    },

    loadReports () {

      if (!getPage(this.currentPage).reports.length) {
        this.mountReports()
        return
      }

      const { close } = loadingModal()

      get(routes.v4.reports, {
        start: this.startDate,
        end: this.endDate,
        reports: getPage(this.currentPage).reports.map(report => report.id),
        params: this.params,
      }).then(r => {

        this.diff = r.diff
        this.reportData = r.report_data
        this.mountReports().then(() => {
          close()
        })
      })
    },

    renderNav () {

      const tabs = ReportPages.sort((a, b) => a.priority - b.priority).filter(p => !p.parent).map(({
        slug,
        name,
      }) => `<a data-slug="${ slug }" href="#" class="gh-reporting-nav-item ${ this.currentPage.match(slug)
        ? 'active'
        : '' }">${ name }</a>`)

      // language=HTML
      return `
          <nav class="gh-reporting-nav">
              ${ tabs.join('') }
          </nav>`
    },

    render () {

      // language=HTML
      return `
          <div class="gh-reporting-dashboard">
              <div class="display-flex space-between">
                  <div class="gh-logo">
                      ${ icons.groundhogg_black }
                  </div>
              </div>
              <div class="gh-reporting-dashboard-header">
                  ${ this.renderNav() }
                  <div class="date-picker">
                      <div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker"></div>
                  </div>
              </div>
              <div id="before-reports"></div>
              <div id="reports-container" class="report-grid">
              </div>
          </div>`

    },
    init () {

      if (window.location.hash) {
        this.currentPage = window.location.hash.substring(1)
        this.params = this.currentPage.split('/')
      }

      history.pushState({ page: this.currentPage }, '',
        `#${ this.currentPage }`)

      window.addEventListener('popstate', (e) => {

        let state = e.state

        if (state && state.page) {
          this.currentPage = state.page
          this.params = this.currentPage.split('/')
          this.mount()
        }
      })

      this.mount()
    },
    mount () {
      $('#app').html(this.render())
      this.onMount()
    },
    onMount () {
      this.setupCalendar()

      $('.gh-reporting-nav-item').on('click', (e) => {
        e.preventDefault()
        this.setPage(e.target.dataset.slug)
      })

      this.loadReports()
    },

    async mountReports () {

      try {
        await getPage(this.currentPage).preload(this.params)
      }
      catch (e) {}

      try {
        $('#before-reports').html(getPage(this.currentPage).beforeReports(this.params))
      }
      catch (e) {}

      $('#reports-container').html(getPage(this.currentPage).reports.map(report => renderReport({
        ...report,
        data: this.reportData[report.id],
      })).join(''))

      getPage(this.currentPage).reports.forEach(report => {

        try {
          ReportTypes[report.type].onMount({
            ...report,
            data: this.reportData[report.id],
          })
        }
        catch (e) {}

        try {
          report.onMount({
            ...report,
            data: this.reportData[report.id],
          }, (page) => {
            this.setPage(page)
          })
        }
        catch (e) {}

        if (report.description) {
          tooltip(`#${ report.id } .gh-panel-header span`, {
            content: report.description,
          })
        }
      })

      try {
        getPage(this.currentPage).onMount()
      }
      catch (e) {}

      $(window).trigger('resize')
    },

    setupCalendar () {

      const self = this

      this.calendar = new Calendar({
        element: $('#groundhogg-datepicker'),
        presets: [
          {
            label: 'Last 30 days',
            start: moment().subtract(29, 'days'),
            end: moment(),
          }, {
            label: 'This month',
            start: moment().startOf('month'),
            end: moment().endOf('month'),
          }, {
            label: 'Last month',
            start: moment().subtract(1, 'month').startOf('month'),
            end: moment().subtract(1, 'month').endOf('month'),
          }, {
            label: 'Last 7 days',
            start: moment().subtract(6, 'days'),
            end: moment(),
          }, {
            label: 'Last 3 months',
            start: moment(this.latest_date).subtract(3, 'month').startOf('month'),
            end: moment(this.latest_date).subtract(1, 'month').endOf('month'),
          }, {
            label: 'This year',
            start: moment().startOf('year'),
            end: moment().endOf('year'),
          },
        ],
        earliest_date: 'January 1, 2017',
        latest_date: moment(),
        start_date: this.start_date,
        end_date: this.end_date,
        callback () {
          self.setDate(
            moment(this.start_date).format('YYYY-MM-DD'),
            moment(this.end_date).format('YYYY-MM-DD'))
        },
      })
    },
  }

  const registerReportPage = (slug, name, reports = [], parent = false, opts = {}) => {
    ReportPages.push({
      slug,
      name,
      parent,
      reports,
      priority: 10,
      ...opts,
    })
  }

  const getPage = (slug) => {
    return ReportPages.find(p => slug.match(p.slug))
  }

  const ReportTypes = {

    percentage: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        if (typeof curr === 'undefined') {
          // language=HTML
          return `
              <div class="inside">
                  <div class="big-number">${ formatNumber(curr) }%</div>
              </div>`
        }

        let increase = curr - prev
        let diff = Math.floor(( increase / ( prev === 0 ? 1 : prev ) ) * 100)

        // language=HTML
        return `
            <div class="inside display-flex space-between gap-20">
                <div class="big-number">${ formatNumber(curr) }%</div>
                <div class="compare-and-range">
                    <div class="gh-report-prev gh-report-prev-${ curr >= prev ? 'green' : 'red' }">
                        <div class="gh-report-prev-arrow">${ curr >= prev ? arrows.up : arrows.down }</div>
                        <div class="gh-report-prev-number">${ diff }%</div>
                    </div>
                    <div class="gh-report-range">${ sprintf(__('vs. previous %s', 'groundhogg'), Dashboard.diff) }</div>
                </div>
            </div>
        `

      },
      onMount: () => {},
    },

    number: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        if (typeof curr === 'undefined') {
          // language=HTML
          return `
              <div class="inside">
                  <div class="big-number">${ formatNumber(curr) }</div>
              </div>`
        }

        let increase = curr - prev
        let diff = Math.floor(( increase / ( prev === 0 ? 1 : prev ) ) * 100)

        // language=HTML
        return `
            <div class="inside display-flex space-between gap-20">
                <div class="big-number">${ formatNumber(curr) }</div>
                <div class="compare-and-range">
                    <div class="gh-report-prev gh-report-prev-${ curr >= prev ? 'green' : 'red' }">
                        <div class="gh-report-prev-arrow">${ curr >= prev ? arrows.up : arrows.down }</div>
                        <div class="gh-report-prev-number">${ diff }%</div>
                    </div>
                    <div class="gh-report-range">${ sprintf(__('vs. previous %s', 'groundhogg'), Dashboard.diff) }</div>
                </div>
            </div>
        `

      },
      onMount: () => {},
    },

    bad_number: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        if (typeof curr === 'undefined') {
          // language=HTML
          return `
              <div class="inside">
                  <div class="big-number"><span class="gh-text danger">${ formatNumber(curr) }</span></div>
              </div>`
        }

        let increase = curr - prev
        let diff = Math.floor(( increase / ( prev === 0 ? 1 : prev ) ) * 100)

        // language=HTML
        return `
            <div class="inside display-flex space-between gap-20">
                <div class="big-number">${ formatNumber(curr) }</div>
                <div class="compare-and-range">
                    <div class="gh-report-prev gh-report-prev-${ curr <= prev ? 'green' : 'red' }">
                        <div class="gh-report-prev-arrow">${ curr <= prev ? arrows.up : arrows.down }</div>
                        <div class="gh-report-prev-number">${ diff }%</div>
                    </div>
                    <div class="gh-report-range">${ sprintf(__('vs. previous %s', 'groundhogg'), Dashboard.diff) }</div>
                </div>
            </div>
        `

      },
      onMount: () => {},
    },

    line_chart: {
      render: ({ id }) => {
        // language=HTML
        return `
            <div class="inside">
                <canvas class="line-chart" data-id="${ id }"></canvas>
            </div>`
      },
      onMount: ({ id, data }) => {

        let ctx = $(`.line-chart[data-id=${ id }]`)[0].getContext('2d')

        let chart = new Chart(ctx, {
          type: 'line',
          data: {
            ...data,
          },
          options: {
            tooltips: {
              callbacks: {
                label: (item, data) => {
                  return item.value
                },
                title: (items, data) => {
                  return items[0].label
                },
              },
              mode: 'index',
              intersect: false,
            },
            legend: {
              position: 'top',
              align: 'start',
            },
          },
        })
      },
    },

    bar_chart: {
      render: ({ id }) => {
        // language=HTML
        return `
            <div class="inside">
                <canvas class="bar-chart" data-id="${ id }"></canvas>
            </div>`
      },
      onMount: ({ id, data }) => {

        let ctx = $(`.bar-chart[data-id=${ id }]`)[0].getContext('2d')

        let chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: data.map(({ name }) => name),
            datasets: [
              {
                backgroundColor: 'rgb(245, 129, 21)',
                data: data.map(({ complete }) => complete),
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,

            legend: {
              display: false,
            },
          },
        })
      },
    },

    pie_chart: {
      render: ({ id }) => {
        // language=HTML
        return `
            <div class="inside">
                <canvas class="pie-chart" data-id="${ id }"></canvas>
            </div>`
      },
      onMount: ({ id, data }) => {
        let cuttoff = 11

        let ctx = $(`.pie-chart[data-id=${ id }]`)[0].getContext('2d')

        let _data = data

        if (_data.length > cuttoff) {
          _data = data.slice(0, cuttoff)

          let _rest = data.slice(cuttoff).reduce((carr, i) => {
            return carr + parseInt(i.count)
          }, 0)

          _data.push({ count: _rest, value: __('Other') })
        }

        let chart = new Chart(ctx, {
          type: 'doughnut',
          data: {
            datasets: [
              {
                data: _data.map(({ count }) => count),
                backgroundColor: _data.map((d, i) => adjust('#4fa4ff', -( i * 30 ))),
              },
            ],
            labels: _data.map(({ value }) => value),
          },
          options: {
            onClick: (e, arr) => {

            },
            legend: {
              position: 'right',
            },
          },
        })
      },
    },

    table: {
      render: ({ data, headers = [] }) => {

        // language=HTML
        return `
            <table class="gh-report-table">
                ${ headers.length ? `<thead><tr>${ headers.map(h => `<th>${ h }</th>`).join('') }</tr></thead>` : '' }
                <tbody>
                </tbody>
            </table>
            ${ data.length > 10 ?
                    `<div class="inside">
                <div class="display-flex flex-end gap-10 align-center">
                    <label>${ __('Number of records') }</label>
                    <div class="gh-input-group">
                    </div>
                </div>
            </div>` : '' }`
      },
      onMount: ({ id, data, renderRow = false }) => {

        if (!renderRow) {
          renderRow = (row) => {
            // language=HTML
            return `
                <tr>
                    ${ Object.values(row).map(item => ` <td>${ item }</td>`).join('') }
                </tr>`
          }
        }

        let num = 10

        const setData = () => {
          $(`#${ id } tbody`).html(data.slice(0, num).map(row => renderRow(row)).join(''))
          $(`#${ id } .gh-input-group`).
            html([10, 25, 50].filter(n => n < data.length).map(_num => `<button class="gh-button ${ num === _num
              ? 'primary'
              : 'secondary' } num-records" data-num="${ _num }">${ _num }</button>`))

          $(`#${ id } .num-records`).on('click', e => {
            num = parseInt(e.target.dataset.num)
            setData()
          })
        }

        setData()
      },
    },

  }

  const onMountHasQuery = ({ id, data }) => {
    $(`#${ id }`).on('click', '.big-number', e => {
      showContacts(data.query)
    })
  }

  const CommonReports = {

    new_contacts: {
      id: 'total_new_contacts',
      name: __('New Contacts'),
      type: 'number',
      onMount: onMountHasQuery,
    },
    confirmed_contacts: {
      id: 'total_confirmed_contacts',
      name: __('Confirmed Contacts'),
      type: 'number',
      onMount: onMountHasQuery,
    },
    engaged_contacts: {
      id: 'total_engaged_contacts',
      name: __('Engaged Contacts'),
      type: 'number',
      onMount: onMountHasQuery,
    },
    unsubscribed_contacts: {
      id: 'total_unsubscribed_contacts',
      name: __('Unsubscribed Contacts'),
      type: 'bad_number',
      onMount: onMountHasQuery,
    },
    forms: {
      name: __('Forms'),
      type: 'table',
      headers: [
        __('ID'),
        __('Name'),
        __('Impressions'),
        __('Submissions'),
        __('Conversion Rate'),
      ],
      renderRow: ({ id, name, impressions, submissions }) => {
        // language=HTML
        return `
            <tr>
                <td>${ id }.</td>
                <td class="large">${ name }</td>
                <td class="number-total">${ impressions }</td>
                <td class="number-total">${ submissions }</td>
                <td class="number-total">${ Math.floor(submissions / Math.max(impressions, 1)) * 100 }%</td>
            </tr>`
      },
    },
    funnel_performance: {
      id: 'table_funnel_performance',
      name: __('Funnel Performance'),
      type: 'table',
      headers: [
        __('Name'),
        __('Active Contacts'),
        __('Conversion Rate'),
      ],
      renderRow: ({ id, title, active, conversion, query }) => {
        //language=HTML
        return `
            <tr>
                <td class="link large" data-funnel="${ id }">${ title }</td>
                <td class="number-total contacts" data-query="${ query }">${ active }</td>
                <td class="number-total">${ conversion }%</td>
            </tr>`
      },
      onMount: ({ id, title }, setPage) => {
        $(`#${ id }`).on('click', '.link', e => {
          setPage(`funnels/${ e.target.dataset.funnel }`)
        }).on('click', '.contacts', e => {
          showContacts(e.target.dataset.query)
        })
      },
    },
    broadcast_performance: {
      id: 'table_broadcast_performance',
      name: __('Broadcast Performance'),
      type: 'table',
      headers: [
        __('Name'),
        __('Sent'),
        __('Open Rate'),
        __('CTR'),
      ],
      renderRow: ({ report, title, id }) => {
        // language=html
        return `
            <tr>
                <td class="link" data-broadcast="${ id }">${ title }</td>
                <td class="number-total">${ report.sent }</td>
                <td class="number-total">${ report.open_rate }%</td>
                <td class="number-total">${ report.click_through_rate }%</td>
            </tr>`
      },
      onMount: ({ id }, setPage) => {
        $(`#${ id }`).on('click', '.link', e => {
          setPage(`broadcasts/${ e.target.dataset.broadcast }`)
        })
      },
    },
    lead_sources: {
      id: 'table_contacts_by_lead_source',
      name: __('Top Lead Sources'),
      type: 'table',
      renderRow: ({ value, count, query }) => {

        const leadSource = () => {
          if (isValidHostname(value)) {
            try {
              let url = new URL(`https://${ value }`)
              return `<td class="link" data-link="${ url }">${ url.hostname }</td>`
            }
            catch (e) {
            }
          }

          return ` <td>${ value }</td>`
        }

        // language=HTML
        return `
            <tr>
                ${ leadSource() }
                <td class="number-total contacts" data-value="${ value }" data-query="${ query }">${ count }</td>
            </tr>`

      },
      onMount: ({ id }) => {
        $(`#${ id }`).on('click', '.link', e => {
          window.open(e.currentTarget.dataset.link, '_blank')
        }).on('click', '.contacts', e => {
          showContacts(e.target.dataset.query)
        })
      },
    },
    source_pages: {
      id: 'table_contacts_by_source_page',
      name: __('Top Source Pages'),
      type: 'table',
      renderRow: ({ value, count }) => {
        //language=HTML
        return `
            <tr>
                <td class="link" data-link="${ value }">${ value }</td>
                <td class="number-total" data-value="${ value }">${ count }</td>
            </tr>`
      },
      onMount: ({ id }) => {
        $(`#${ id }`).on('click', '.link', e => {
          window.open(e.currentTarget.dataset.link, '_blank')
        })
      },
    },
    link_clicks: {
      id: 'link_clicks',
      name: __('Link Clicks'),
      type: 'table',
      renderRow: ({ value, count }) => {
        //language=HTML
        return `
            <tr>
                <td class="link large" data-link="${ value }">${ value }</td>
                <td class="number-total" data-value="${ value }">${ count }</td>
            </tr>`
      },
      onMount: ({ id }) => {
        $(`#${ id }`).on('click', '.link', e => {
          window.open(e.currentTarget.dataset.link, '_blank')
        })
      },
    },
  }

  registerReportPage('overview', __('Overview', 'groundhogg'), [
    CommonReports.new_contacts,
    CommonReports.confirmed_contacts,
    CommonReports.engaged_contacts,
    CommonReports.unsubscribed_contacts,
    {
      id: 'total_emails_sent',
      name: __('Emails Sent'),
      type: 'number',
    },
    {
      id: 'email_open_rate',
      name: __('Open Rate'),
      type: 'percentage',
    },
    {
      id: 'email_click_rate',
      name: __('Click Thru Rate'),
      type: 'percentage',
    },
    {
      id: 'total_bounces',
      name: __('Bounces'),
      type: 'number',
    },
    CommonReports.lead_sources,
    CommonReports.source_pages,
    CommonReports.funnel_performance,
    CommonReports.broadcast_performance,
  ])

  registerReportPage(/funnels\/[0-9]+\/email\/[0-9]+/, 'Email', [
    {
      id: 'funnel_emails_sent',
      name: __('Emails Sent'),
      type: 'number',
    },
    {
      id: 'funnel_opens',
      name: __('Opens'),
      type: 'number',
    },
    {
      ...CommonReports.link_clicks,
      rows: 3,
    },
    {
      id: 'funnel_open_rate',
      name: __('Open Rate'),
      type: 'percentage',
    },
    {
      id: 'funnel_clicks',
      name: __('Clicks'),
      type: 'number',
    },
    {
      id: 'funnel_click_rate',
      name: __('Click Thru Rate'),
      type: 'percentage',
    },
    {
      id: 'funnel_unsubscribes',
      name: __('Unsubscribes'),
      type: 'bad_number',
    },
  ], 'funnels', {
    priority: 1,
    preload: ([a, funnelId, b, emailId]) => {
      return FunnelsStore.fetchItem(funnelId)
    },
    beforeReports: ([route, funnelId, route1, emailId]) => {
      return `<h1>${ FunnelsStore.get(funnelId).steps.find(s => s.ID == emailId).export.email.data.title }</h1>`
    },
  })

  registerReportPage(/funnels\/[0-9]+/, 'Funnel', [
    {
      id: 'active_contacts_in_funnel',
      name: __('Active Contacts'),
      type: 'number',
      onMount: onMountHasQuery,
    },
    {
      id: 'total_funnel_conversion_rate',
      name: __('Conversion Rate'),
      type: 'percentage',
    },
    {
      id: 'chart_funnel_breakdown',
      name: __('Progress'),
      type: 'bar_chart',
      columns: 2,
      rows: 3,
    },
    {
      id: 'funnel_emails_sent',
      name: __('Emails Sent'),
      type: 'number',
    },
    {
      id: 'funnel_open_rate',
      name: __('Open Rate'),
      type: 'percentage',
    },
    {
      id: 'funnel_click_rate',
      name: __('Click Thru Rate'),
      type: 'percentage',
    },
    {
      id: 'funnel_unsubscribes',
      name: __('Unsubscribes'),
      type: 'bad_number',
    },
    {
      id: 'funnel_email_performance',
      name: __('Email Performance'),
      type: 'table',
      headers: [
        __('Name'),
        __('Sent'),
        __('Open Rate'),
        __('CTR'),
      ],
      renderRow: ({ sent, opened, clicked, title, id }) => {
        // language=html
        return `
            <tr>
                <td class="link large" data-email="${ id }">${ title }</td>
                <td class="number-total">${ sent }</td>
                <td class="number-total">${ Math.floor(( opened / Math.max(sent, 1) ) * 100) }%</td>
                <td class="number-total">${ Math.floor(( clicked / Math.max(opened, 1) ) * 100) }%</td>
            </tr>`
      },
      onMount: ({ id }, setPage) => {
        $(`#${ id }`).on('click', '.link', e => {
          setPage(`funnels/${ Dashboard.params[1] }/email/${ e.target.dataset.email }`)
        })
      },
    },
    {
      id: 'table_funnel_stats',
      name: __('Steps'),
      type: 'table',
      rows: 5,
      headers: [
        '',
        __('Name'),
        __('Waiting'),
        __('Complete'),
      ],
      renderRow: ({ id, funnel, group, type, name, waiting, complete }) => {
        //language=HTML
        return `
            <tr>
                <td class="step ${ group }">${ StepTypes.getType(type).svg }</td>
                <td class="link large" data-funnel="${ funnel }" data-step="${ id }">${ name }</td>
                <td class="number-total">${ waiting }</td>
                <td class="number-total">${ complete }</td>
            </tr>`
      },
      onMount: ({ id }) => {
        $(`#${ id }`).on('click', '.link', e => {
          window.open(adminPageURL('gh_funnels', {
            action: 'edit',
            funnel: e.currentTarget.dataset.funnel,
          }, e.currentTarget.dataset.step), '_blank')
        })
      },
    },
    {
      id: 'funnel_forms',
      name: __('Forms'),
      ...CommonReports.forms,
      columns: 4,
    },
  ], 'funnels', {
    priority: 1,
    preload: ([route, id]) => {
      return FunnelsStore.fetchItem(id)
    },
    beforeReports: ([route, id]) => {
      //language=HTML
      return `
          <div class="display-flex gap-20 align-bottom">
              <h1>${ FunnelsStore.get(id).data.title }</h1>
              <button class="gh-button secondary edit-funnel" data-funnel="${ id }">${ __('Edit Funnel') }</button>
          </div>`
    },
    onMount: () => {
      $('.edit-funnel').on('click', e => {
        window.open(adminPageURL('gh_funnels', {
          action: 'edit',
          funnel: e.currentTarget.dataset.funnel,
        }), '_blank')
      })
    },
  })

  registerReportPage(/broadcasts\/[0-9]+/, 'Broadcasts', [
    {
      id: 'broadcast_emails_sent',
      name: __('Emails Sent'),
      type: 'number',
    },
    {
      id: 'broadcast_emails_opened',
      name: __('Open Rate'),
      type: 'percentage',
    },
    {
      id: 'broadcast_emails_clicked',
      name: __('Click Thru Rate'),
      type: 'percentage',
    },
    {
      id: 'broadcast_unsubscribes',
      name: __('Unsubscribes'),
      type: 'bad_number',
    },
    {
      id: 'broadcast_pie_results',
      name: __('Results'),
      type: 'pie_chart',
    },
    {
      ...CommonReports.link_clicks,
      columns: 4,
    },
  ], 'broadcasts', {
    priority: 1,
    preload: ([route, id]) => {
      return BroadcastsStore.fetchItem(id)
    },
    beforeReports: ([route, id]) => {
      return `<h1>${ BroadcastsStore.get(id).object.data.title }</h1>`
    },
  })

  registerReportPage('contacts', 'Contacts', [
    CommonReports.new_contacts,
    CommonReports.confirmed_contacts,
    CommonReports.engaged_contacts,
    CommonReports.unsubscribed_contacts,
    CommonReports.lead_sources,
    CommonReports.source_pages,
  ])

  registerReportPage(/campaigns\/[0-9]+/, 'Campaigns', [
    CommonReports.funnel_performance,
    CommonReports.broadcast_performance,
  ], 'campaigns', {
    priority: 1,
    preload: ([route, id]) => {
      return CampaignsStore.fetchItem(id)
    },
    beforeReports: ([route, id]) => {
      return `<h1>${ CampaignsStore.get(id).data.name }</h1>`
    },
  })

  registerReportPage('campaigns', 'Campaigns', [
    {
      id: 'campaigns_table',
      name: __('Campaigns'),
      type: 'table',
      columns: 4,
      headers: [
        __('Campaign'),
        __('Funnels'),
        __('Broadcasts'),
      ],
      renderRow: ({ id, name, funnels, broadcasts }) => {
        //language=HTML
        return `
            <tr>
                <td class="link large" data-campaign="${ id }">${ name }</td>
                <td class="number-total">${ funnels }</td>
                <td class="number-total">${ broadcasts }</td>
            </tr>`
      },
      onMount: ({ id }, setPage) => {
        $(`#${ id }`).on('click', '.link', e => {
          setPage(`campaigns/${ e.target.dataset.campaign }`)
        })
      },
    },
  ])

  registerReportPage('funnels', 'Funnels', [
    {
      ...CommonReports.funnel_performance,
      columns: 4,
    },
  ])
  registerReportPage('broadcasts', __('Broadcasts', 'groundhogg'), [
    CommonReports.broadcast_performance,
    {
      id: 'broadcasts_sent',
      name: __('Broadcasts Sent'),
      type: 'number',
    },
    {
      id: 'broadcast_emails_sent',
      name: __('Total Broadcast Emails Sent'),
      type: 'number',
    },
    {
      id: 'broadcast_open_rate',
      name: __('Open Rate'),
      type: 'percentage',
    },
    {
      id: 'broadcast_click_rate',
      name: __('Click Thru Rate'),
      type: 'percentage',
    },
  ])
  registerReportPage('forms', __('Forms', 'groundhogg'), [
    {
      id: 'table_form_activity',
      columns: 3,
      rows: 3,
      ...CommonReports.forms,
    },
    {
      id: 'all_form_submissions',
      name: __('Form Submissions'),
      type: 'number',
    },
    {
      id: 'form_engagement_rate',
      name: __('Engagement Rate'),
      type: 'percentage',
      description: __('The percentage of contacts which engage with email content after subscribing.'),
    },
  ], false, {
    onMount: () => {

    },
  })

  const showContacts = (query) => {
    window.open(adminPageURL('gh_contacts', {
      filters: query,
    }), '_blank')
  }

  registerReportPage('custom', __('Custom'), [], false, {
    onMount: () => {
      Groundhogg.loadCustomReportsPage()
    },
  })

  $(() => {
    Dashboard.init()
  })

} )
(jQuery)
