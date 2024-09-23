( function ($, nonces) {

  const { sprintf, __, _x, _n } = wp.i18n

  const {
    emails: EmailsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores

  const {
    ItemPicker,
    Table,
    Tr,
    Td,
    Th,
    THead,
    TBody,
    Fragment,
    Button,
    Div,
    Span,
  } = MakeEl

  const { loadingModal, adminPageURL } = Groundhogg.element

  const { base64_json_encode } = Groundhogg.functions

  const openInContactsView = (filters) => {
    window.open(adminPageURL('gh_contacts', {
      filters: base64_json_encode(filters),
    }), '_blank')
  }

  const ReportTable = ( id, report ) => {

    let { label, data, no_data = '', per_page = 10, orderby = 0 } = report

    if (!Array.isArray(label)) {
      label = Object.values(label)
    }

    let sortable = data.length && data[0].orderby

    const State = Groundhogg.createState({
      per_page,
      orderby,
      orderby2: 0,
      order: 'DESC',
      page: 0,
    })
    const compareRows = (a, b, k = State.orderby) => {

      if (!sortable || !a.orderby) {
        return 0
      }

      let av = a.orderby[k]
      let bv = b.orderby[k]

      // Avoid deep recursion if already checking orderby2
      if ( av === bv && k !== State.orderby2 ){
        return compareRows( a, b, State.orderby2 )
      }

      if (State.order === 'ASC') {
        return av - bv
      }

      return bv - av
    }

    const getData = () => data.sort(compareRows).slice(State.per_page * State.page, ( State.per_page * State.page ) + State.per_page)

    const TableBody = () => TBody({}, getData().map(({ orderby = {}, cellClasses = [], ...row }) => Tr({}, Object.keys(row).map((k,i) => {
      return Td({ dataColname: k, className: `${cellClasses[i] ?? ''}` }, `${ row[k] }`)
    }))))

    return Div( {
      id: `report-${id}`
    }, morph => Fragment([
      Div({
        className: 'table-scroll'
      }, Table({
        className: 'groundhogg-report-table',
      }, [
        THead({}, Tr({}, label.map((l, i) => Th({
          id: `order-${ i }`,
          className: `${ State.orderby === i || State.orderby2 === i ? 'sorted' : '' } ${ State.order === 'ASC' ? 'asc' : 'desc' }`,
          onClick: e => {

            if (!sortable) {
              return
            }

            if (State.orderby === i) {
              State.set({
                order: State.order === 'ASC' ? 'DESC' : 'ASC',
              })
            }
            else {
              State.set({
                orderby: i,
                orderby2: State.orderby,
                order: 'DESC',
              })
            }
            morph()
          },
        }, Div({
          className: `display-flex ${ i === 0 ? 'flex-start' : ( i === label.length - 1 ? 'flex-end' : 'center' )}`,
        }, [
          Span({
            className: 'column-name',
          }, l),
          sortable ? Span({}, [
            Span({
              className: 'sorting-indicator asc',
            }),
            Span({
              className: 'sorting-indicator desc',
            }),
          ]) : null,
        ]))))),
        TableBody(),
      ])),
      data.length > State.per_page ? Div({
        style: {
          padding: '10px',
        },
        className: 'display-flex gap-10 flex-end',
      }, [
        State.page > 0 ? Button({
          id: `report-${id}-prev`,
          className: 'gh-button secondary',
          onClick: e => {
            State.set({
              page: State.page - 1,
            })
            morph()
          },
        }, 'Prev') : null,
        ( State.page + 1 ) * State.per_page < data.length ? Button({
          id: `report-${id}-next`,
          className: 'gh-button secondary',
          onClick: e => {
            State.set({
              page: State.page + 1,
            })
            morph()
          },
        }, 'Next') : null,
      ]) : null,
    ]))
  }

  // reporting might be undefined at this point
  if ( typeof GroundhoggReporting !== 'undefined' ){
    const reporting = GroundhoggReporting
    $.extend(reporting || {}, {

      data: {},
      calendar: null,
      charts: {},

      init: function () {

        this.initCalendar()
        this.initFunnels()
        this.initCountry()
        this.initBroadcast()
        this.initCampaignFilter()

      },

      async initCampaignFilter () {

        let el = document.getElementById('report-campaign-filter')

        if (!el) {
          return
        }

        let campaignId = this.other.campaign

        if (campaignId && !CampaignsStore.has(campaignId)) {
          await CampaignsStore.fetchItem(campaignId)
        }

        el.append(ItemPicker({
          id: 'report-campaign',
          noneSelected: __('Filter by campaign...', 'groundhogg'),
          multiple: false,
          selected: campaignId ? ( ({ ID, data }) => ( { id: ID, text: data.name } ) )(CampaignsStore.get(campaignId)) : [],
          fetchOptions: async (search) => {
            let campaigns = await CampaignsStore.fetchItems({
              search,
              limit: 20,
            })

            return campaigns.map(({ ID, data }) => ( { id: ID, text: data.name } ))
          },
          onChange: item => {

            if (!item) {
              this.other.campaign = null
            }
            else {
              this.other.campaign = item.id
            }

            this.refresh(this.calendar)
          },
        }))

      },

      initCalendar: function () {

        var self = this

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
              start: moment().subtract(3, 'month').startOf('month'),
              end: moment().subtract(1, 'month').endOf('month'),
            }, {
              label: 'This year',
              start: moment().startOf('year'),
              end: moment().endOf('year'),
            },
          ],
          format: {
            preset: GroundhoggReporting.date_format,
            // preset: 'MMM D, YYYY'
          },
          earliest_date: 'January 1, 2017',
          latest_date: moment(),
          start_date: self.dates.start_date,
          end_date: self.dates.end_date,
          callback: function () {
            self.refresh(this)
          },
        })

        // run it with defaults
        this.calendar.calendarSaveDates()
      },

      initFunnels: function () {

        var self = this

        $('#funnel-id').change(function () {
          self.refresh(self.calendar)
        })
      },

      initBroadcast: function () {

        var self = this

        $('#broadcast-id').change(function () {
          self.refresh(self.calendar)
        })
      },

      initCountry: function () {

        var self = this

        $('#country').change(function () {
          self.refresh(self.calendar)
        })
      },

      refresh: function (calendar) {

        var self = this

        let { close } = loadingModal()

        var start = calendar.start_date.format('YYYY-MM-DD'),
          end = calendar.end_date.format('YYYY-MM-DD')

        $.ajax({
          type: 'post',
          url: ajaxurl,
          dataType: 'json',
          data: {
            action: 'groundhogg_refresh_dashboard_reports',
            reports: self.reports,
            start: start,
            end: end,
            data: {
              ...reporting.other,
            },
          },
          success: function (json) {

            self.data = json.data.reports
            self.renderReports()

            close()

            $('.wrap').removeClass('blurred')

            window.dispatchEvent(new Event('resize'))

          },
          failure: function (response) {

            alert('Unable to retrieve data...')

          },
        })

      },

      get_other_data: function () {

        var self = this
        var data = {}

        $('.post-data').each(function (i) {
          var $this = $(this)
          var name = $this.attr('name')

          // Backwards compat for the funnel area, but also want the form to
          // work...
          if (name === 'funnel') {
            name = 'funnel_id'
          }

          data[name] = $this.val()
        })

        return data
      },

      renderReports: function () {
        for (var i = 0; i < this.reports.length; i++) {
          var report_id = this.reports[i]
          var report_data = this.data[report_id]
          this.renderReport(report_id, report_data)
        }

      },

      renderReport: function (report_id, report_data) {

        var $report = $('#' + report_id)

        if (!$report.length) {
          return
        }

        var type = report_data.type

        switch (type) {
          case 'quick_stat':
            this.renderQuickStatReport($report, report_data)
            break
          case 'chart':
            this.renderChartReport($report, report_data.chart, report_id)
            break
          case 'table':
            this.renderTable($report, report_data, report_id)
            break
        }

      },

      renderQuickStatReport: function ($report, report_data) {

        $report.find('.groundhogg-quick-stat-number').html(report_data.number)
        $report.find('.groundhogg-quick-stat-previous').
          removeClass('green red').
          addClass(report_data.compare.arrow.color)
        $report.find('.groundhogg-quick-stat-compare').
          html(report_data.compare.text)
        $report.find('.groundhogg-quick-stat-arrow').
          removeClass('up down').
          addClass(report_data.compare.arrow.direction)
        $report.find('.groundhogg-quick-stat-prev-percent').
          html(report_data.compare.percent)

      },

      renderChartReport: function ($report, report_data, report_id) {

        if (report_data.data.labels && report_data.data.labels.length === 0) {
          $report.closest('.gh-donut-chart-wrap').html(report_data.no_data)
          return;
        }

        if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
          var funcName = report_data.options.tooltips.callbacks.label
          report_data.options.tooltips.callbacks.label = window[funcName]
        }

        if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
          var funcName = report_data.options.tooltips.callbacks.title
          report_data.options.tooltips.callbacks.title = window[funcName]
        }

        if (this.charts[report_id]) {
          this.charts[report_id].destroy()
        }

        var ctx = $report[0].getContext('2d')

        switch (report_id) {
          case 'chart_unsub_reasons':
            report_data.options.onClick = (e, arr) => {
              let index = arr[0]._index
              let { reason } = report_data.data.rawResults[index]

              openInContactsView([
                [
                  {
                    type: 'unsubscribed',
                    reasons: [reason],
                    date_range: 'between',
                    before: this.calendar.end_date.format('YYYY-MM-DD'),
                    after: this.calendar.start_date.format('YYYY-MM-DD'),
                  },
                ],
              ])
            }
            break
        }

        var chart = new Chart(ctx, report_data)
        this.charts[report_id] = chart

        // draw Hover line in the graph
        var draw_line = Chart.controllers.line.prototype.draw
        Chart.helpers.extend(Chart.controllers.line.prototype, {
          draw: function () {
            draw_line.apply(this, arguments)
            if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
              var ap = this.chart.tooltip._active[0]
              var ctx = this.chart.ctx
              var x = ap.tooltipPosition().x
              var topy = this.chart.scales['y-axis-0'].top
              var bottomy = this.chart.scales['y-axis-0'].bottom

              ctx.save()
              ctx.beginPath()
              ctx.moveTo(x, topy)
              ctx.lineTo(x, bottomy)
              ctx.lineWidth = 1
              ctx.strokeStyle = '#727272'
              ctx.setLineDash([10, 10])
              ctx.stroke()
              ctx.restore()
            }
          },

        })

        Chart.plugins.register({
          afterDraw: function (chart) {
            if (chart.data.datasets.length === 0) {
              // No data is present
              var ctx = chart.chart.ctx
              var width = chart.chart.width
              var height = chart.chart.height
              chart.clear()

              ctx.save()
              ctx.textAlign = 'center'
              ctx.textBaseline = 'middle'
              ctx.font = '16px normal \'Helvetica Nueue\''
              ctx.fillText('No data to display', width / 2, height / 2)
              ctx.restore()
            }
          },
        })

      },

      renderTable: function ($report, report_data, id) {

        let { data, no_data = '' } = report_data

        if (!data.length) {
          $report.html(no_data)
          return
        }

        $report.html(ReportTable(id, report_data))
      },

    })
  }

  $(function () {

    if ( typeof GroundhoggReporting !== 'undefined' ){
      GroundhoggReporting.init()
    }

  })

  Groundhogg.reporting = {
    ReportTable
  }

} )(jQuery, groundhogg_nonces)
