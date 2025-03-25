( () => {

  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
    Input,
  } = MakeEl

  ApiRegistry.add('funnels', {
    name: __('Flows'),
    description: () => Fragment([
      Pg({}, __('Add one ore multiple contacts to flows.', 'groundhogg'))
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.funnels.endpoints.add('add-contacts', {
    name: __('Add contacts to a flow', 'groundhogg'),
    description: () => Pg({}, __('Add contacts to a flow in bulk. Using this endpoint will create a background task, the contacts will not be added instantly.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/funnels/:id/start`,
    identifiers: [
      {
        ...CommonParams.id('funnel'),
        description: () => Pg({}, __('The ID of the flow to add contacts to.')),
      },
    ],
    params: [
      {
        param: 'step_id',
        description: () => Pg({}, __('The step within the flow. If not provided the first action will be used.', 'groundhogg')),
        type: 'int',
      },
      {
        param: 'query',
        description: () => Pg({}, __('The query to identify the contacts you wish to add to the flow.', 'groundhogg')),
        type: 'object',
        subParams: [
          CommonParams.filters('contacts'),
        ],
      },
      {
        param: 'now',
        type: 'bool',
        required: false,
        description: () => Fragment([
          Pg({}, __('Whether to add contacts to the flow immediately.', 'groundhogg')),
        ]),
      },
      {
        param: 'date',
        type: 'string',
        description: () => Fragment([
          Pg({}, __('The date you want to add contacts to the flow. Uses the site timezone.', 'groundhogg')),
          Pg({}, __('If not supplied contacts will be added to the flow immediately. Has no affect if <code class="param">now</code> is <code>true</code>.',
            'groundhogg')),
        ]),
        control: ({ param, id }) => Input({
          type: 'date',
          name: param,
          id,
          value: getFromRequest(param),
          onInput: e => setInRequest(param, e.target.value),
        }),
      },
      {
        param: 'time',
        type: 'string',
        description: () => Fragment([
          Pg({}, __('The time of day to add contacts to the flow. Uses the site timezone.', 'groundhogg')),
          Pg({}, __('If not supplied contacts will be added to the flow immediately. Has no affect if <code class="param">now</code> is <code>true</code>.',
            'groundhogg')),
        ]),
        control: ({ param, id }) => Input({
          type: 'time',
          name: param,
          id,
          value: getFromRequest(param),
          onInput: e => setInRequest(param, e.target.value),
        }),
      },
    ],
    request: {
      step_id: 10,
      query: {
        filters: []
      },
      now: true,
    },
    response: {
      status: "success"
    },
  })

  ApiRegistry.funnels.endpoints.add('add-contact', {
    name: __('Add a contact to a flow', 'groundhogg'),
    description: () => Pg({}, __('Add a single contact to a flow.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/funnels/:id/start`,
    identifiers: [
      {
        ...CommonParams.id('funnel'),
        description: () => Pg({}, __('The ID of flow to add the contact to.')),
      },
    ],
    params: [
      {
        param: 'step_id',
        description: () => Pg({}, __('The step within the flow. If not provided the first action will be used.', 'groundhogg')),
        type: 'int',
      },
      {
        param: 'contact_id',
        description: () => Pg({}, __('The ID of the contact to add to the flow.', 'groundhogg')),
        type: 'int',
        required: true,
      },
    ],
    request: {
      step_id: 10,
      contact_id: 1234
    },
    response: {
      status: "success"
    },
  })

} )()
