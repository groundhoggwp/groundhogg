( ($) => {

  const {
    moreMenu,
    loadingModal,
    icons,
    select,
    modal,
    input,
    uuid,
    dialog,
    dangerConfirmationModal,
    adminPageURL,
  } = Groundhogg.element

  const {
    routes,
    post,
    get,
  } = Groundhogg.api

  const {
    options: OptionsStore,
  } = Groundhogg.stores

  const { createFilters } = Groundhogg.filters.functions

  const { metaPicker } = Groundhogg.pickers

  const { __ } = wp.i18n

  function adjust (color, amount) {
    return '#' + color.replace(/^#/, '').
      replace(/../g,
        color => ( '0' + Math.min(255, Math.max(0, parseInt(color, 16) + amount)).toString(16) ).substr(-2))
  }

  function utf8_to_b64 (str) {
    return window.btoa(unescape(encodeURIComponent(str)))
  }

  function b64_to_utf8 (str) {
    return decodeURIComponent(escape(window.atob(str)))
  }

  const base64_json_encode = (stuff) => {
    return utf8_to_b64(JSON.stringify(stuff))
  }

  const ReportTypes = {

    pie_chart: {
      name: __('Pie Chart', 'groundhogg'),
      settings: ({ field = '' }) => {
        // language=HTML
        return `
            <div class="row">
                <div class="col">
                    <label for="value">${ __('Custom Field') }</label>
                    ${ input({
                        id: 'field',
                        value: field,
                    }) }
                </div>
            </div>`
      },

      settingsOnMount: (filter, updateReport) => {
        metaPicker('#field').on('change', e => {
          updateReport({
            field: e.target.value,
          })
        })
      },

      render: ({ id, data }) => {

        // language=HTML
        return `
            <div class="inside">
                <canvas class="pie-chart" data-id="${ id }"></canvas>
            </div>
        `
      },
      onMount: ({ id, field, data }) => {

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

              if (arr.length && arr[0]._index === cuttoff && arr[0]._view.label === __('Other')) {

                let _data = data.slice(cuttoff)

                modal({
                  content: `<canvas class="pie-chart-large" style="height: 600px" data-id="${ id }"></canvas>`,
                  onOpen: () => {

                    let ctx = $(`.pie-chart-large[data-id=${ id }]`)[0].getContext('2d')

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

                        maintainAspectRatio: false,
                        aspectRatio: 1,
                        onClick: (e, arr) => {

                          if (arr.length && arr[0]._view) {
                            window.open(adminPageURL('gh_contacts', {
                              meta_key: field,
                              meta_value: arr[0]._view.label,
                            }), '_blank')
                          }

                        },
                        legend: {
                          position: 'bottom',
                        },
                      },
                    })
                  },
                })

                return
              }

              if (arr.length && arr[0]._view) {
                window.open(adminPageURL('gh_contacts', {
                  meta_key: field,
                  meta_value: arr[0]._view.label,
                }), '_blank')
              }

            },
            legend: {
              position: 'right',
            },
          },
        })

      },
    },
    table: {
      name: __('Table', 'groundhogg'),
      settings: ({ field = '' }) => {
        // language=HTML
        return `
            <div class="row">
                <div class="col">
                    <label for="value">${ __('Custom Field') }</label>
                    ${ input({
                        id: 'field',
                        value: field,
                    }) }
                </div>
            </div>`
      },

      settingsOnMount: (filter, updateReport) => {
        metaPicker('#field').on('change', e => {
          updateReport({
            field: e.target.value,
          })
        })
      },

      render: ({ id, data, num = 10 }) => {

        // language=HTML
        return `
            <table class="gh-report-table">
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
      onMount: ({ id, data, field }) => {

        let num = 10

        const setData = () => {
          $(`#${ id } tbody`).html(data.slice(0, num).map(row => dataRow(row)).join(''))
          $(`#${ id } .gh-input-group`).
            html([10, 25, 50].filter(i => i < data.length).map(_num => `<button class="gh-button ${ num === _num
              ? 'primary'
              : 'secondary' } num-records" data-num="${ _num }">${ _num }</button>`))

          $(`#${ id } .num-records`).on('click', e => {
            num = parseInt(e.target.dataset.num)
            setData()
          })

          $(`.number-total[data-id=${ id }]`).on('click', e => {
            window.open(adminPageURL('gh_contacts', {
              meta_key: field,
              meta_value: e.target.dataset.value,
            }), '_blank')
          })
        }

        const dataRow = ({ value, count }) => {
          // language=HTML
          return `
              <tr>
                  <td>${ value }</td>
                  <td class="number-total" data-id="${ id }" data-value="${ value }">${ count }</td>
              </tr>`
        }

        setData()

      },
    },
    number: {
      name: __('Number', 'groundhogg'),
      settings: ({ value = 'contacts', field = '' }) => {

        //  total number of contacts
        //  SUM of a custom field
        //  AVERAGE of a custom field

        const maybeExtra = () => {

          switch (value) {
            case 'contacts':
              return ''
            case 'sum':
            case 'average':
              // language=HTML
              return `
                  <div class="row">
                      <div class="col">
                          <label for="value">${ __('Custom Field') }</label>
                          ${ input({
                              id: 'field',
                              value: field,
                          }) }
                      </div>
                  </div>`
          }

        }

        // language=HTML
        return `
            <div class="row">
                <div class="col">
                    <label for="value">${ __('Report Value') }</label>
                    ${ select({
                        id: 'value',
                    }, {
                        contacts: __('Total number of contacts', 'groundhogg'),
                        // sum: __('Sum of a custom field', 'groundhogg'),
                        // average: __('Average of a custom field', 'groundhogg'),
                    }, value) }
                </div>
            </div>
            ${ maybeExtra() }`
      },

      settingsOnMount: (filter, updateReport) => {
        $('#value').on('change', e => {
          updateReport({
            value: e.target.value,
          }, true)
          $('#value').focus()
        })

        metaPicker('#field').on('change', e => {
          updateReport({
            field: e.target.value,
          })
        })
      },

      render: ({ id, data }) => {

        // language=HTML
        return `
            <div class="inside">
                <div data-id="${ id }" class="big-number display-flex center">
                    ${ data }
                </div>
            </div>`
      },

      onMount: ({ id, filters }) => {
        $(`.big-number[data-id=${ id }]`).on('click', e => {
          window.open(adminPageURL('gh_contacts', {
            filters: base64_json_encode(filters),
          }), '_blank')
        })
      },

    },
  }

  const renderReport = (report) => {

    // language=HTML
    return `
        <div id="${ report.id }" class="gh-panel report ${ report.type }" data-id="${ report.id }">
            <div class="gh-panel-header">
                <h2>${ report.name }</h2>
                <button class="report-more gh-button secondary text icon" data-id="${ report.id }">
                    ${ icons.verticalDots }
                </button>
            </div>
            ${ ReportTypes[report.type].render(report) }
        </div>`
  }

  let reports = []

  const loadReports = () => {

    return get(`${ routes.v4.root }/custom-reports`).then(r => {
      reports = r.items ?? []
      mount()
    })

  }

  const loadReport = (id) => {

    get(`${ routes.v4.root }/custom-reports/${ id }`).then(r => {
      reports = reports.map(report => report.id === id ? r.item : report)
      mount()
    })

  }

  const mount = () => {
    $('#reports-container').
      html(reports.sort(({ order: a }, { order: b }) => a - b).map(report => renderReport(report)).join(''))
    onMount()
  }

  const renderReportEdit = (report) => {

    // language=HTML
    return `
        <h2>${ __('Create Report') }</h2>
        <div class="gh-rows-and-columns">
            <div class="row">
                <div class="col">
                    <label for="name">${ __('Name', 'groundhogg') }</label>
                    ${ input({
                        id: 'name',
                        value: report.name,
                    }) }
                </div>
            </div>
            <div class="row">
                <div class="col">
                    <label for="report-type">${ __('Report Type', 'groundhogg') }</label>
                    ${ select({
                        id: 'report-type',
                    }, Object.keys(ReportTypes).map(type => ( {
                        value: type,
                        text: ReportTypes[type].name,
                    } )), report.type) }
                </div>
            </div>
            ${ ReportTypes[report.type].settings(report) }
            <div class="row">
                <div class="col">
                    <label>${ __('Filter Contacts', 'groundhogg') }</label>
                    <div id="filters-here"></div>
                </div>
            </div>
        </div>
        <div class="space-between align-right" style="margin-top: 20px">
            <button class="gh-button primary" id="save">${ __('Save Report', 'groundhogg') }</button>
        </div>`
  }

  /**
   * Save the reports based on the current state
   *
   * @return {Promise<*>}
   */
  const commitReports = () => {
    return OptionsStore.patch({
      // filter out data
      gh_custom_reports: reports.map(({ data, ...report }) => report),
    })
  }

  const editReport = (report) => {

    modal({
      width: 400,
      // language=HTML
      content: renderReportEdit(report),
      dialogClasses: 'overflow-visible',
      onOpen: ({ close, setContent }) => {

        const updateReport = (props, reload = false) => {

          report = {
            ...report,
            ...props,
          }

          if (reload) {
            setContent(renderReportEdit(report))
            onMount()
          }
        }

        const onMount = () => {

          ReportTypes[report.type].settingsOnMount(report, updateReport)

          $('#name').on('change input', e => {
            updateReport({
              name: e.target.value,
            })
          })

          $('#report-type').on('change', e => {
            updateReport({
              type: e.target.value,
            }, true)
            $('#report-type').focus()
          })

          createFilters('#filters-here', report.filters, (filters) => {
            updateReport({
              filters,
            })
          }).init()

          $('#save').on('click', () => {

            // if the report already exists
            if (reports.find(r => r.id === report.id)) {
              reports = reports.map(r => r.id === report.id ? report : r)
            }
            else {
              reports.push(report)
            }

            commitReports().then(() => {
              dialog({
                message: __('Reports saved!', 'groundhogg'),
              })
            }).then(() => {
              loadReport(report.id)
              close()
            })
          })
        }

        onMount()
      },

    })

  }

  const onMount = () => {
    reports.forEach(report => ReportTypes[report.type].onMount(report))
    $('.report-more').on('click', e => {

      let reportId = e.currentTarget.dataset.id
      let report = reports.find(r => r.id === reportId)

      moreMenu(e.currentTarget, {
        items: [
          {
            key: 'edit',
            text: __('Edit'),
          },
          {
            key: 'delete',
            text: `<span class="gh-text danger">${ __('Delete') }</span>`,
          },
        ],
        onSelect: k => {
          switch (k) {
            case 'edit':
              editReport(report)
              break
            case 'delete':

              dangerConfirmationModal({
                alert: `<p>${ __('Are you sure you want to delete this report?', 'groundhogg') }</p>`,
                confirmText: __('Delete'),
                onConfirm: () => {
                  reports = reports.filter(r => r.id !== report.id)
                  commitReports().then(() => {
                    dialog({
                      message: __('Report deleted.', 'groundhogg'),
                    })
                    mount()
                  })
                },
              })

              break
          }
        },
      })
    })
  }

  const loadCustomReportsPage = () => {
    $('.date-picker').
      replaceWith(`<button id="add-report" class="gh-button primary">${ __('Create New Report', 'groundhogg') }</button>`)

    $('#add-report').on('click', e => {

      let newReport = {
        type: 'number',
        name: __('New Report'),
        id: uuid(),
        filters: [],
        order: reports.length,
      }

      editReport(newReport)
    })

    let commitTimeout

    $('#reports-container').sortable({
      handle: 'h2',
      tolerance: 'pointer',
      placeholder: 'report-placeholder',
      start: (e, ui) => {
        ui.placeholder.addClass(reports.find(r => r.id === ui.item.data('id')).type)
        ui.placeholder.height(ui.item.height())
      },
      update: () => {

        $('.report').each((i, el) => {
          let $report = $(el)
          reports.find(r => r.id == $report.data('id')).order = $report.index()
        })

        if (commitTimeout) {
          clearTimeout(commitTimeout)
        }

        commitTimeout = setTimeout(() => {
          commitReports()
        })

      },
    })

    const { close } = loadingModal()
    loadReports().then( () => close() )
  }

  Groundhogg.loadCustomReportsPage = loadCustomReportsPage

} )(jQuery)
