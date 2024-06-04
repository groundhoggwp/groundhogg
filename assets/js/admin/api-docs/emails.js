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

  ApiRegistry.add('emails', {
    name: __('Emails'),
    description: '',
    endpoints: Groundhogg.createRegistry(),
  })

  const EmailRepeater = ({ param, name, id }) => {

    let others = getFromRequest(param, [])
    let rows = others.map(em => ([em]))

    return InputRepeater({
      id,
      rows: rows,
      cells: [
        props => Input({
          ...props,
          type: 'email',
        }),
      ],
      onChange: rows => {
        setInRequest(param, rows.map(([em]) => em))
      },
    })
  }

  ApiRegistry.emails.endpoints.add('compose', {
    name: __('Send a composed email', 'groundhogg'),
    description: () => Pg({}, __('Send a composed (custom) email to a contact.', 'groundhogg')),
    method: 'POST',
    endpoint: `${apiRoot}/emails/send`,
    params: [
      {
        param: 'to',
        description: () => Pg({}, __('An array of email addresses to send the email.', 'groundhogg')),
        type: 'string[]',
        required: true,
        control: EmailRepeater,
      },
      {
        param: 'cc',
        description: () => Pg({}, __('An array of email addresses to CC on the email.', 'groundhogg')),
        type: 'string[]',
        control: EmailRepeater,
      },
      {
        param: 'bcc',
        description: () => Pg({}, __('An array of email addresses to BCC on the email.', 'groundhogg')),
        type: 'string[]',
        control: EmailRepeater,
      },
      {
        param: 'from_email',
        description: () => Pg({},
          __('The email to send the email from. If using a sending service the email address must be authenticated.',
            'groundhogg')),
        type: 'string',
        default: Groundhogg.defaults.from_email,
        control: ({ param, id, name }) => Input({
          id,
          name,
          type: 'email',
          value: getFromRequest(param),
          onInput: e => {
            setInRequest(param, e.target.value)
          },
        }),
      },
      {
        param: 'from_name',
        description: () => Pg({}, __('The name that will appear in the from header.', 'groundhogg')),
        type: 'string',
        default: Groundhogg.defaults.from_name,
      },
      {
        param: 'type',
        description: () => Pg({},
          __('The type of email so that the appropriate sending service is used.', 'groundhogg')),
        type: 'string',
        default: 'wordpress',
        options: [
          'wordpress',
          'marketing',
          'transactional',
        ],
      },
      {
        param: 'subject',
        description: () => Pg({}, __('The subject line of the email.', 'groundhogg')),
        type: 'string',
      },
      {
        param: 'content',
        description: () => Pg({}, __('The HTML content of the email.', 'groundhogg')),
        type: 'string',
        control: ({ param, id, name }) => Textarea({
          id,
          name,
          className: 'full-width',
          value: getFromRequest(param),
          onInput: e => {
            setInRequest(param, e.target.value)
          },
        }),
      },
    ],
    request: {
      to: [Groundhogg.user.getCurrentUser().data.user_email],
      subject: 'Hey there!',
      content: `<p>I'm sending you an email from the API!</p>`,
    },
    response: {
      'status': 'success',
    },
  })

  ApiRegistry.emails.endpoints.add('send', {
    name: __('Send an email template', 'groundhogg'),
    description: () => Pg({}, __('Send an email using a template to a contact.', 'groundhogg')),
    method: 'POST',
    endpoint: `${apiRoot}/emails/<id>/send`,
    identifiers: [
      {
        param: 'id',
        type: 'int',
        required: true,
        description: () => Pg({}, 'The ID of the template to send.'),
      },
    ],
    params: [
      {
        param: 'to',
        type: 'int|string',
        required: true,
        description: () => Pg({}, 'The ID or email address of the contact to send to.'),
        control: ({ param, id, name }) => Input({
          id,
          name,
          value: getFromRequest(param),
          onInput: e => {
            setInRequest(param, e.target.value)
          },
        }),
      },
    ],
    request: {
      to: Groundhogg.user.getCurrentUser().data.user_email,
    },
    response: {
      'status': 'success',
    },
  })

  addBaseObjectCRUDEndpoints(ApiRegistry.emails.endpoints, {
    plural: __('emails'),
    singular: __('email'),
    route: `${apiRoot}/emails`,
    meta: true,
    searchableColumns: [
      'title',
      'subject',
      'content',
    ],
    orderByColumns: [
      'ID',
      'title',
      'subject',
      'date_created',
      'from_user',
    ],
    readParams: [
      {
        ...CommonParams.filters('emails'),
        control: ({ param, id }) => {

          let filters = getFromRequest(param)

          if (!Array.isArray(filters)) {
            filters = []
          }

          const filterRegistry = Groundhogg.filters.createFilterRegistryFromConfig({
            'group': 'email',
            'name': 'Emails',
            'filters': [],
            'stringColumns': { 'title': 'Title', 'subject': 'Subject', 'content': 'Content' },
            'selectColumns': {
              'message_type': [
                'Message Type',
                { 'marketing': 'Marketing', 'transactional': 'Transactional' }],
            },
            'FilterRegistry': {
              'groups': { 'table': 'Emails' },
              'filters': {
                'title': {
                  'type': 'title',
                  'name': 'Title',
                  'group': 'table',
                  'defaults': { 'value': '', 'compare': 'equals' },
                },
                'subject': {
                  'type': 'subject',
                  'name': 'Subject',
                  'group': 'table',
                  'defaults': { 'value': '', 'compare': 'equals' },
                },
                'content': {
                  'type': 'content',
                  'name': 'Content',
                  'group': 'table',
                  'defaults': { 'value': '', 'compare': 'equals' },
                },
                'message_type': {
                  'type': 'message_type',
                  'name': 'Message Type',
                  'group': 'table',
                  'defaults': { 'value': 'marketing' },
                },
                'from_user': { 'type': 'from_user', 'name': 'From User', 'group': 'table', 'defaults': {} },
                'author': { 'type': 'author', 'name': 'Author', 'group': 'table', 'defaults': {} },
                'funnel': { 'type': 'funnel', 'name': 'Funnel', 'group': 'table', 'defaults': {} },
                'campaigns': { 'type': 'campaigns', 'name': 'Campaigns', 'group': 'table', 'defaults': {} },
              },
            },
          })

          Groundhogg.filters.registerEmailFilters(filterRegistry, 'email')

          return Groundhogg.filters.Filters({
            id,
            filters,
            filterRegistry,
            onChange: filters => {

              filters = filters.map(group => group.map(({ id, ...filter }) => filter))

              if (currEndpoint().method === 'GET') {
                filters = Groundhogg.functions.base64_json_encode(filters)
              }

              setInRequest(param, filters)
            },
          })
        },
      },
    ],
    dataParams: [
      {
        param: 'title',
        description: () => Pg({}, __('The admin title of the email.', 'groundhogg')),
        type: 'string',
      },
      {
        param: 'subject',
        description: () => Pg({}, __('The subject line of the email.', 'groundhogg')),
        type: 'string',
      },
      {
        param: 'content',
        description: () => Pg({}, __('The HTML content of the email.', 'groundhogg')),
        type: 'string',
        control: ({ param, id, name }) => Textarea({
          id,
          name,
          className: 'full-width',
          value: getFromRequest(param),
          onInput: e => {
            setInRequest(param, e.target.value)
          },
        }),
      },
      {
        param: 'plain_text',
        description: () => Pg({}, __('The plain-text version of the email.', 'groundhogg')),
        type: 'string',
        control: ({ param, id, name }) => Textarea({
          id,
          name,
          className: 'full-width',
          value: getFromRequest(param),
          onInput: e => {
            setInRequest(param, e.target.value)
          },
        }),
      },
      {
        param: 'message_type',
        description: () => Pg({}, __('The type of message.', 'groundhogg')),
        type: 'string',
        default: 'marketing',
        options: [
          'marketing',
          'transactional',
        ],
      },
      {
        param: 'status',
        description: () => Pg({}, __('Status of the email.', 'groundhogg')),
        type: 'string',
        default: 'draft',
        options: [
          'draft',
          'ready',
          'trash',
        ],
      },
      {
        param: 'is_template',
        description: () => Pg({}, __('Whether the email should appear as a template.', 'groundhogg')),
        type: 'bool',
        default: false,
      },
    ],
    metaParams: [
      {
        param: 'template',
        type: 'string',
        options: [
          'boxed',
          'full-width',
          'full-width-contained',
        ],
        default: 'boxed',
        description: () => Pg({}, __('The template to use for the email.')),
      },
      {
        param: 'width',
        type: 'int',
        default: 500,
        description: () => Pg({},
          __('The width of the email if using <code>boxed</code> or <code>full-width-contained</code>.')),
      },
      {
        param: 'browser_view',
        type: 'bool',
        default: false,
        description: () => Pg({}, __('Whether the "view in browser" link should be shown.')),
      },
      {
        param: 'alignment',
        type: 'string',
        default: 'left',
        options: [
          'left',
          'center',
        ],
        description: () => Pg({}, __('The alignment of the content. Only applies to <code>boxed</code>')),
      },
    ],
    moreParams: [
      {
        param: 'campaigns',
        type: 'int[]',
        description: () => Pg({}, __('Any campaign IDs to associate with this email.')),
        control: ({ param, name, id }) => {

          let others = getFromRequest(param, [])
          let rows = others.map(id => ([id]))

          return InputRepeater({
            id,
            rows: rows,
            cells: [
              props => Input({
                ...props,
                type: 'number',
                placeholder: __('Campaign ID'),
              }),
            ],
            onChange: rows => {
              setInRequest(param, rows.map(([id]) => parseInt(id)))
            },
          })
        },
      },
    ],
  })

})()
