(() => {

  const {
    ApiRegistry,
    CommonParams,
    setInRequest,
    getFromRequest,
    addBaseObjectCRUDEndpoints,
    currEndpoint,
    currRoute,
  } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
    Input,
    Textarea,
    InputRepeater,
  } = MakeEl

  ApiRegistry.add('reports', {
    name: __('Reports'),
    description: () => Fragment([
      Pg({}, __('Fetch report data.', 'groundhogg'))
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.reports.endpoints.add('read-all', {
    name: __('Fetch multiple reports', 'groundhogg'),
    description: () => Pg({}, __('Fetch the results for multiple reports.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/reports`,
    params: [
      {
        param: 'reports',
        type: 'string',
        required: true,
        control: ({ param, name, id }) => {
          let others = getFromRequest(param, [])
          let rows = others.map(id => ( [id] ))

          return InputRepeater({
            id,
            rows: rows,
            cells: [
              ({value, ...props}) => MakeEl.Select({
                options: GroundhoggReports,
                selected: value,
                style: {
                  maxWidth: '100%'
                },
                ...props,
              }),
            ],
            onChange: rows => {
              setInRequest(param, rows.map(([id]) => id))
            },
          })
        },
        description: () => Pg({}, __('The IDs of the reports to retrieve.', 'groundhogg')),
      },
      {
        param: 'after',
        type: 'string',
        required: true,
        control: ({ param, name, id }) => Input({
          type: 'date',
          name,
          id,
          value: getFromRequest(param),
          className: 'code',
          onInput: e => setInRequest(param, e.target.value),
        }),
        description: () => Pg({}, __('The beginning of the desired reporting range', 'groundhogg')),
      },
      {
        param: 'before',
        type: 'string',
        required: true,
        control: ({ param, name, id }) => Input({
          type: 'date',
          name,
          id,
          value: getFromRequest(param),
          className: 'code',
          onInput: e => setInRequest(param, e.target.value),
        }),
        description: () => Pg({}, __('The end of the desired reporting range.', 'groundhogg')),
      },
      {
        param: 'params',
        type: 'object',
        required: false,
        subParams: [
          {
            param: '<key>',
            description: () => Fragment([
              Pg({}, __('Any arbitrary key with any arbitrary value.', 'groundhogg')),
            ]),
            type: 'mixed',
            required: false,
            control: ({ param, id, name }) => {

              param = param.replace('.<key>', '') // remove <key> from param since we're editing the meta object directly

              let meta = getFromRequest(param, {})
              let rows = Object.keys(meta).map(key => ( [key, meta[key]] ))

              return InputRepeater({
                id,
                rows: rows,
                cells: [
                  props => Input(props),
                  props => Input(props),
                ],
                onChange: rows => {
                  let newMeta = {}
                  rows.forEach(([key, val]) => newMeta[key] = val)
                  setInRequest(param.replace('.<key>', ''), newMeta)
                },
              })
            },
          },
        ],
        description: () => Fragment([
          Pg({}, __('The params can be used to specify a funnel, broadcast, or other asset.', 'groundhogg')),
        ]),
      }
    ],
    request: {
      report: 'John',
      start: '',
      end: '',
    },
    response: {

    }
  })

  ApiRegistry.reports.endpoints.add('read-single', {
    name: __('Fetch single report', 'groundhogg'),
    description: () => Pg({}, __('Fetch the results for a single report.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/reports/:report`,
    identifiers: [
      {
        param: 'report',
        type: 'string',
        options: GroundhoggReports,
        required: true,
        description: () => Pg({}, __('The ID of the report to retrieve.', 'groundhogg')),
      },
    ],
    params: [
      {
        param: 'after',
        type: 'string',
        required: true,
        control: ({ param, name, id }) => Input({
          type: 'date',
          name,
          id,
          value: getFromRequest(param),
          className: 'code',
          onInput: e => setInRequest(param, e.target.value),
        }),
        description: () => Pg({}, __('The beginning of the desired reporting range', 'groundhogg')),
      },
      {
        param: 'before',
        type: 'string',
        required: true,
        control: ({ param, name, id }) => Input({
          type: 'date',
          name,
          id,
          value: getFromRequest(param),
          className: 'code',
          onInput: e => setInRequest(param, e.target.value),
        }),
        description: () => Pg({}, __('The end of the desired reporting range.', 'groundhogg')),
      },
      {
        param: 'params',
        type: 'object',
        required: false,
        subParams: [
          {
            param: '<key>',
            description: () => Fragment([
              Pg({}, __('Any arbitrary key with any arbitrary value.', 'groundhogg')),
            ]),
            type: 'mixed',
            required: false,
            control: ({ param, id, name }) => {

              param = param.replace('.<key>', '') // remove <key> from param since we're editing the meta object directly

              let meta = getFromRequest(param, {})
              let rows = Object.keys(meta).map(key => ( [key, meta[key]] ))

              return InputRepeater({
                id,
                rows: rows,
                cells: [
                  props => Input(props),
                  props => Input(props),
                ],
                onChange: rows => {
                  let newMeta = {}
                  rows.forEach(([key, val]) => newMeta[key] = val)
                  setInRequest(param.replace('.<key>', ''), newMeta)
                },
              })
            },
          },
        ],
        description: () => Fragment([
          Pg({}, __('The params can be used to specify a funnel, broadcast, or other asset.', 'groundhogg')),
        ]),
      }
    ],
    request: {
      report: '',
      start: '',
      end: '',
    },
    response: {

    }
  })

  ApiRegistry.reports.endpoints.add('read-custom-all', {
    name: __('Fetch all custom reports', 'groundhogg'),
    description: () => Pg({}, __('Fetch the results for all custom reports.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/custom-reports`,
    request: {

    },
    response: {

    }
  })

  ApiRegistry.reports.endpoints.add('read-custom-single', {
    name: __('Fetch single custom report', 'groundhogg'),
    description: () => Pg({}, __('Fetch the results for a single custom report.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/custom-reports/:report`,
    identifiers: [
      {
        param: 'report',
        type: 'string',
        required: true,
        description: () => Pg({}, __('The ID of the report to retrieve.', 'groundhogg')),
      },
    ],
    request: {
      report: '',
    },
    response: {

    }
  })


})()
