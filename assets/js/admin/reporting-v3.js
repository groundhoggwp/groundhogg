(function ($) {

  const ReportPages = {}

  const { get, routes } = Groundhogg.api
  const { loadingModal } = Groundhogg.element

  const { __ } = wp.i18n

  const ReportsStore = {

    cache: {},

    fetch ({ reports, params, start, end }, reportsReceived = (results) => {}) {
      get(routes.v4.reports, {
        start,
        end,
        reports,
        params
      }).then(r => reportsReceived(r))
    },

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
		</svg>`
  }

  const ReportTypes = {

    percentage: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        let increase = curr - prev
        let diff = Math.floor((increase / (prev === 0 ? 1 : prev)) * 100)

        // language=HTML
        return `
			<div class="inside display-flex space-between gap-20">
				<div class="big-number">${curr}%</div>
				<div class="compare-and-range">
					<div class="gh-report-prev gh-report-prev-${curr >= prev ? 'green' : 'red'}">
						<div class="gh-report-prev-arrow">${curr >= prev ? arrows.up : arrows.down}</div>
						<div class="gh-report-prev-number">${diff}%</div>
					</div>
					<div class="gh-report-range">vs. previous ${''} days.</div>
				</div>
			</div>
        `

      },
      onMount: () => {},
    },

    number: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        let increase = curr - prev
        let diff = Math.floor((increase / (prev === 0 ? 1 : prev)) * 100)

        // language=HTML
        return `
			<div class="inside display-flex space-between gap-20">
				<div class="big-number">${curr}</div>
				<div class="compare-and-range">
					<div class="gh-report-prev gh-report-prev-${curr >= prev ? 'green' : 'red'}">
						<div class="gh-report-prev-arrow">${curr >= prev ? arrows.up : arrows.down}</div>
						<div class="gh-report-prev-number">${diff}%</div>
					</div>
					<div class="gh-report-range">vs. previous ${''} days.</div>
				</div>
			</div>
        `

      },
      onMount: () => {},
    },

    bad_number: {
      render: ({ id, data }) => {

        let { curr, prev } = data

        let increase = curr - prev
        let diff = Math.floor((increase / (prev === 0 ? 1 : prev)) * 100)

        // language=HTML
        return `
			<div class="inside display-flex space-between gap-20">
				<div class="big-number">${curr}</div>
				<div class="compare-and-range">
					<div class="gh-report-prev gh-report-prev-${curr <= prev ? 'green' : 'red'}">
						<div class="gh-report-prev-arrow">${curr <= prev ? arrows.up : arrows.down}</div>
						<div class="gh-report-prev-number">${diff}%</div>
					</div>
					<div class="gh-report-range">vs. previous ${''} days.</div>
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
				<canvas class="line-chart" data-id="${id}"></canvas>
			</div>`
      },
      onMount: ({ id, data }) => {

        let ctx = $(`.line-chart[data-id=${id}]`)[0].getContext('2d')

        let chart = new Chart(ctx, {
          type: 'line',
          data: {
            ...data
          },
          options: {
            tooltips: {
              callbacks: {
                label: (item, data) => {
                  return item.value
                },
                title: (items, data) => {
                  return items[0].label
                }
              },
              mode: 'index',
              intersect: false,
            },
            legend: {
              position: 'top',
              align: 'start'
            }
          }
        })
      },
    },

    bar_chart: {
      render: () => {},
      onMount: () => {},
    },

    pie_chart: {
      render: ({ id }) => {
        // language=HTML
        return `
			<div class="inside">
				<canvas class="pie-chart" data-id="${id}"></canvas>
			</div>`
      },
      onMount: ({ id, data }) => {
        let cuttoff = 11

        let ctx = $(`.pie-chart[data-id=${id}]`)[0].getContext('2d')

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
            datasets: [{
              data: _data.map(({ count }) => count),
              backgroundColor: _data.map((d, i) => adjust('#4fa4ff', -(i * 30)))
            }],
            labels: _data.map(({ value }) => value),
          },
          options: {
            onClick: (e, arr) => {

            },
            legend: {
              position: 'right'
            }
          }
        })
      },
    },

    table: {
      render: () => {

        // language=HTML
        return `
			<table class="groundhogg-report-table">
				<tbody>
				</tbody>
			</table>
			<div class="inside">
				<div class="display-flex flex-end gap-10 align-center">
					<label>${__('Number of records')}</label>
					<div class="gh-input-group">
					</div>
				</div>
			</div>`
      },
      onMount: ({ id, data, renderRow = false }) => {

        if (!renderRow) {
          renderRow = (row) => {
            // language=HTML
            return `
				<tr>
					${Object.values(row).map(item => ` <td>${item}</td>`).join('')}
				</tr>`
          }
        }

        let num = 10

        const setData = () => {
          $(`#${id} tbody`).html(data.slice(0, num).map(row => renderRow(row)).join(''))
          $(`#${id} .gh-input-group`).html([10, 25, 50].map(_num => `<button class="gh-button ${num === _num ? 'primary' : 'secondary'} num-records" data-num="${_num}">${_num}</button>`))

          $(`#${id} .num-records`).on('click', e => {
            num = parseInt(e.target.dataset.num)
            setData()
          })

          $(`.number-total[data-id=${id}]`).on('click', e => {

          })
        }

        setData()
      }
    }

  }

  const renderReport = (report) => {

    // language=HTML
    return `
		<div id="${report.id}" class="gh-panel report ${report.type}" data-id="${report.id}">
			<div class="gh-panel-header">
				<h2>${report.name}</h2>
			</div>
			${ReportTypes[report.type].render(report)}
		</div>`
  }

  const registerReportPage = (slug, name, reports = [], topLevel = true) => {
    ReportPages[slug] = {
      slug,
      name,
      toplevel: topLevel,
      reports,
      priority: 10,
    }
  }

  const Dashboard = {

    currentPage: 'overview',
    startDate: '',
    endDate: '',
    params: {},
    reportData: {},

    ...GroundhoggReporting,

    setPage (page, params) {

      this.demountReports()

      this.currentPage = page
      this.params = params

      this.mount()
    },

    setDate (start, end) {

      this.demountReports()

      this.startDate = start
      this.endDate = end

      this.loadReports()
    },

    loadReports () {

      const { close } = loadingModal()

      get(routes.v4.reports, {
        start: this.startDate,
        end: this.endDate,
        reports: ReportPages[this.currentPage].reports.map(report => report.id),
        params: this.params
      }).then(r => {

        this.reportData = r.report_data

      }).then(() => {
        close()
        this.mountReports()
      })
    },

    renderNav () {

      const tabs = Object.values(ReportPages)
        .sort((a, b) => a.priority - b.priority)
        .filter(p => p.toplevel)
        .map(({
          slug,
          name
        }) => `<a data-slug="${slug}" href="#" class="gh-reporting-nav-item ${slug === this.currentPage ? 'active' : ''}">${name}</a>`)

      // language=HTML
      return `
		  <nav class="gh-reporting-nav">
			  ${tabs.join('')}
		  </nav>`
    },

    render () {

      // language=HTML
      return `
		  <div class="gh-reporting-dashboard">
			  <div class="display-flex space-between">
				  <div class="gh-logo">
					  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1327.4 264.4">
						  <path
							  d="M319.1 83.4v24.8a30 30 0 00-6.1-.6c-14 0-26.1 10.8-26.1 29.7v45.1h-29.2V85.1h29.2v11.4c1.3-3.2 6.6-14.2 23.1-14.2 3 0 6.4.4 9.1 1.1zM432.8 134.2a52.7 52.7 0 01-52.2 52.2 52.5 52.5 0 01-48.2-31.7 52.1 52.1 0 010-40.6 51.1 51.1 0 0127.7-27.9 52.3 52.3 0 0168.5 27.7c2.7 6.5 4 13.3 4.2 20.3zm-26 0a26 26 0 00-26.3-26.1 26.2 26.2 0 1026.3 26.1zM454.2 133.3V85h29.4v48.3c0 15.5 5.3 23.7 22.6 23.7 12.5 0 22-10 22.2-22.9 0 0 .4-30.3.4-49.1H558v97.6h-29.4v-7.8c-.2.2-10.2 11-26 11-39.9 0-48.4-29.6-48.4-52.5zM681.5 134.2v48.3h-29.4v-48.3c0-15.5-5.3-23.7-22.7-23.7-12.5 0-22 10.2-22.2 23.1v48.9h-29.7V85h29.4v7.8c.2-.2 10.2-11 26.1-11 40-.1 48.5 29.5 48.5 52.4zM810.2 48.6v134h-29.6v-7.4a54.5 54.5 0 01-32 10.8c-28 0-52.5-23.9-52.5-52.5a53.3 53.3 0 0152.5-52.3 54 54 0 0132 10.8V48.6h29.6zm-35.3 84.9c0-14.4-11.8-26.3-26.3-26.3a26.4 26.4 0 000 52.8c14.6 0 26.3-12 26.3-26.5zM949.2 138.6v44H920v-44c0-14.4-5.9-23.9-23.3-23.9a23.3 23.3 0 00-22.9 23.3v44.5H844V48.4h29.8V95c.2-.2 10.4-11.4 26.3-11.4 40.6 0 49.1 31.9 49.1 55zM1075.4 134.2a52.7 52.7 0 01-52.2 52.2 52.5 52.5 0 01-48.2-31.7 52.1 52.1 0 010-40.6 52.7 52.7 0 0148-32.1 53.8 53.8 0 0137 15.2 55.7 55.7 0 0111.2 16.7c2.7 6.5 4 13.3 4.2 20.3zm-25.9 0a26.1 26.1 0 00-26.3-26.1 26.2 26.2 0 1026.3 26.1zM1193.8 85v95.3c0 26-18.4 59.3-59.3 59.3a64.5 64.5 0 01-36.8-12.7l19.7-22a29.9 29.9 0 0046.8-22.4A53 53 0 111140.3 82c8.7 0 17.1 2.7 24.1 8.3V85h29.4zm-29.3 49.6a27 27 0 00-26.5-26.9 26.2 26.2 0 000 52.6c13.4.1 26.5-11.3 26.5-25.7zM1316 85v95.3c0 26-18.4 59.3-59.3 59.3a64.5 64.5 0 01-36.8-12.7l19.7-22a29.9 29.9 0 0046.8-22.4A53 53 0 111262.5 82c8.7 0 17.1 2.7 24.1 8.3V85h29.4zm-29.3 49.6a27 27 0 00-26.5-26.9 26.2 26.2 0 000 52.6c13.4.1 26.5-11.3 26.5-25.7z"/>
						  <linearGradient id="a" x1="35.6" x2="199.3" y1="214" y2="50.4" gradientUnits="userSpaceOnUse">
							  <stop offset=".3" stop-color="#db851a"/>
							  <stop offset="1" stop-color="#db6f1a"/>
						  </linearGradient>
						  <path fill="url(#a)"
						        d="M22.7 64.4l83.4-48.2c7-4 15.7-4 22.7 0l83.4 48.2c7 4 11.3 11.5 11.3 19.6v96.3c0 8.1-4.3 15.6-11.3 19.6l-83.4 48.2c-7 4-15.7 4-22.7 0L22.7 200c-7-4-11.3-11.5-11.3-19.6V84a22.5 22.5 0 0111.3-19.6z"/>
						  <path fill="#db5100"
						        d="M183.5 140.8v4.9A66.1 66.1 0 11164 98.8l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6v-23.3h66z"/>
						  <path fill="#fff"
						        d="M183.5 126.1v4.9A66.1 66.1 0 11164 84.1l-24.5 24.3a31.4 31.4 0 103.6 40.9h-25.6V126h66z"/>
					  </svg>
				  </div>
			  </div>
			  <div class="gh-reporting-dashboard-header">
				  ${this.renderNav()}
				  <div class="date-picker">
					  <div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker"></div>
				  </div>
			  </div>
			  <div id="reports-container" class="report-grid">
			  </div>
		  </div>`

    },
    init () {
      this.mount()
    },
    mount () {
      $('#app').html(this.render())
      this.onMount()
    },
    onMount () {
      this.setupCalendar()

      $('.gh-reporting-nav-item').on('click', (e) => {
        this.setPage(e.target.dataset.slug)
      })

      this.loadReports()
    },

    mountReports () {
      $('#reports-container').html(ReportPages[this.currentPage].reports.map(report => renderReport({
        ...report,
        data: this.reportData[report.id]
      })).join(''))

      ReportPages[this.currentPage].reports.forEach(report => ReportTypes[report.type].onMount({
        ...report,
        data: this.reportData[report.id]
      }))

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
            moment(this.start_date).format('LL'),
            moment(this.end_date).format('LL'))
        },
      })
    }
  }

  registerReportPage('overview', __('Overview', 'groundhogg'), [
    {
      id: 'total_new_contacts',
      name: __('New Contacts'),
      type: 'number'
    },
    {
      id: 'total_confirmed_contacts',
      name: __('Confirmed Contacts'),
      type: 'number'
    },
    {
      id: 'total_engaged_contacts',
      name: __('Engaged Contacts'),
      type: 'number'
    },
    {
      id: 'total_unsubscribed_contacts',
      name: __('Unsubscribed Contacts'),
      type: 'bad_number'
    },
    {
      id: 'total_emails_sent',
      name: __('Emails Sent'),
      type: 'number'
    },
    {
      id: 'email_open_rate',
      name: __('Open Rate'),
      type: 'percentage'
    },
    {
      id: 'email_click_rate',
      name: __('Click Thru Rate'),
      type: 'percentage'
    },
    {
      id: 'total_bounces',
      name: __('Bounces'),
      type: 'number'
    },
    {
      id: 'table_contacts_by_lead_source',
      name: __('Top Lead Sources'),
      type: 'table',
      renderRow: ({ value, count }) => {

        try {
          let url = new URL(value)
          return `<tr><td class="link" data-link="${value}">${url.hostname}${url.pathname}</td><td class="number-total" data-value="${value}">${count}</td></tr>`
        } catch (e) {
          return `<tr><td class="link">${value}</td><td class="number-total" data-value="${value}">${count}</td></tr>`
        }

      },
      onMount: () => {

      }
    }
  ])

  registerReportPage('funnel', 'Funnel', [], false)

  registerReportPage('email', 'Email', [], false)

  registerReportPage('broadcast', 'Broadcast', [], false)

  registerReportPage('contacts', 'Contacts', {})
  registerReportPage('funnels', 'Funnels', {})
  registerReportPage('broadcasts', 'Broadcasts', {})
  registerReportPage('forms', 'Forms', {})

  $(() => {
    Dashboard.init()
  })

})(jQuery)