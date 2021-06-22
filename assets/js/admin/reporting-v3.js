(function ($) {

  const ReportPages = {}

  const { get, routes } = Groundhogg.api
  const { loadingModal } = Groundhogg.element

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

  const quickStateReport = (name, {
    id,
    total = 1234,
    arrowDirection = 'up',
    arrowColor = 'green',
    prev = 0,
    prevRange,
    isPercentage = false
  }) => {

    // language=HTML
    return `
		<div id="${id}" class="gh-report gh-quickstat-report gh-panel">
			<div class="gh-report-header">
				<h2 class="gh-report-name">${name}</h2>
			</div>
			<div class="gh-report-details">
				<div class="big-number">${total}${isPercentage ? '%' : ''}</div>
				<div class="compare-and-range">
					<div class="gh-report-prev gh-report-prev-${arrowColor}">
						<div class="gh-report-prev-arrow">${arrowDirection === 'up' ? arrows.up : arrows.down}</div>
						<div class="gh-report-prev-number">${prev}%</div>
					</div>
					<div class="gh-report-range">vs. previous ${prevRange} days.</div>
				</div>
			</div>
		</div>`
  }

  const tableReport = (name, { id, rows }, {
    labels = [],
    cells = (r, i) => {},
    sort = (a, b) => {}
  }) => {
    // language=HTML
    return `
		<div id="${id}" class="gh-report gh-table-report gh-panel">
			<div class="gh-report-header">
				<h2 class="gh-report-name">${name}</h2>
			</div>
			<div class="gh-report-details">
				<table class="gh-report-table">
					<thead>
					<tr>
						${labels.map(header => `<th>${header}</th>`).join('')}
					</tr>
					</thead>
					<tbody>
					${rows
						.sort(sort)
						.map((r, i) => `<tr>${cells(r, i)
							.map(cell => `<td>${cell}</td>`)
							.join('')}</tr>`).join('')}
					</tbody>
				</table>
			</div>
		</div>`
  }

  const lineChartReport = (name, { id }) => {
    // language=HTML
    return `
		<div id="${id}" class="gh-report gh-line-chart-report gh-panel">
			<div class="gh-report-header">
				<h2 class="gh-report-name">${name}</h2>
			</div>
			<div class="gh-report-details">
				<canvas></canvas>
			</div>
		</div>`
  }

  const createLineChart = (id, { chart }) => {

    const $canvas = $(`#${id} canvas`)
    const context = $canvas[0].getContext('2d')
    return new Chart(context, chart)
  }

  const registerReportPage = (slug, name, opts) => {
    ReportPages[slug] = {
      slug,
      name,
      toplevel: true,
      reports: [],
      view: (reports) => {},
      onMount: () => {},
      onDemount: () => {},
      priority: 10,
      ...opts
    }
  }

  const Dashboard = {

    reports: {},
    currentPage: 'overview',
    params: {},
    startDate: '',
    endDate: '',

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
        reports: ReportPages[this.currentPage].reports,
        params: this.params
      }).then(r => this.reports = r.reports).then(() => {
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
			  <div class="gh-reporting-dashboard-header">
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
				  ${this.renderNav()}
				  <div class="date-picker">
					  <div class="daterange daterange--double groundhogg-datepicker" id="groundhogg-datepicker"></div>
				  </div>
			  </div>
			  <div id="reports-container" class="gh-reporting-dashboard-reports-container">
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
      $('#reports-container').html(ReportPages[this.currentPage].view(this.reports))
      this.onReportMount()
    },

    demountReports () {
      ReportPages[this.currentPage].onDemount(this.reports, {
        setPage: (page, params) => {
          this.setPage(page, params)
        }
      })
    },

    onReportMount () {

      ReportPages[this.currentPage].onMount(this.reports, {
        setPage: (page, params) => {
          this.setPage(page, params)
        }
      })
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

  registerReportPage('overview', 'Overview', {
    lineChart: null,
    reports: [
      'total_new_contacts',
      'total_confirmed_contacts',
      'total_engaged_contacts',
      'total_unsubscribed_contacts',
      'chart_new_contacts',
      'total_emails_sent',
      'email_open_rate',
      'email_click_rate',
      'table_top_converting_funnels',
      'table_top_performing_emails'
    ],
    view: (reports) => {

      const {
        total_new_contacts,
        total_confirmed_contacts,
        total_engaged_contacts,
        total_unsubscribed_contacts,
        chart_new_contacts,
        total_emails_sent,
        email_open_rate,
        email_click_rate,
        table_top_converting_funnels,
        table_top_performing_emails
      } = reports

      // language=HTML
      return `
		  <div class="gh-report-column">
			  <div class="gh-report-row">
				  ${quickStateReport('New Contacts', total_new_contacts)}
				  ${quickStateReport('Confirmed Contacts', total_confirmed_contacts)}
				  ${quickStateReport('Engaged Contacts', total_engaged_contacts)}
				  ${quickStateReport('Unsubscribed Contacts', total_unsubscribed_contacts)}
			  </div>
			  <div class="gh-report-row">
				  ${lineChartReport('New Contacts', chart_new_contacts)}
			  </div>
			  <div class="gh-report-row">
				  ${quickStateReport('Emails Sent', total_emails_sent)}
				  ${quickStateReport('Open Rate', email_open_rate)}
				  ${quickStateReport('Click Thru Rate', email_click_rate)}
			  </div>
			  <div class="gh-report-row">
				  ${tableReport('Top Converting Funnels', table_top_converting_funnels, {
					  labels: ['Funnel', 'Conversion Rate'],
					  cells: ({
						  funnel,
						  cvr
					  }, i) => [`<span class="row-count">${i + 1}.</span> <a href="#" data-slug="funnel" data-funnel="${funnel.ID}">${funnel.title}</a>`, `${cvr}%`]
				  })}
				  ${tableReport('Top Converting Funnels', table_top_converting_funnels, {
					  labels: ['Funnel', 'Conversion Rate'],
					  cells: ({
						  funnel,
						  cvr
					  }, i) => [`<span class="row-count">${i + 1}.</span> <a href="#" data-slug="funnel" data-funnel="${funnel.ID}">${funnel.title}</a>`, `${cvr}%`]
				  })}
			  </div>
		  </div>`
    },
    onMount (reports, { setPage }) {
      const {
        total_new_contacts,
        total_confirmed_contacts,
        total_engaged_contacts,
        total_unsubscribed_contacts,
        chart_new_contacts
      } = reports

      this.lineChart = createLineChart('chart_new_contacts', chart_new_contacts)

      $('#table_top_converting_funnels a').on('click', (e) => {
        e.preventDefault()
        setPage(e.target.dataset.slug, {
          funnel: parseInt(e.target.dataset.funnel)
        })
      })
    },
    onDemount (reports) {
      this.lineChart.destroy()
    }
  })

  registerReportPage('funnel', 'Funnel', {
    toplevel: false
  })
  registerReportPage('funnels', 'Funnels', {})
  registerReportPage('emails', 'Emails', {})

  $(() => {
    Dashboard.init()
  })

})(jQuery)