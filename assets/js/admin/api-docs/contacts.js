(()=>{

  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n
  const { copyObject, andList } = Groundhogg.element

  const {
    Fragment,
    Pg,
    Input,
    InputRepeater,
  } = MakeEl

  // Contacts
  ApiRegistry.add('contacts', {
    name: __('Contacts', 'groundhogg'),
    description: '',
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.contacts.endpoints.add('list', {
    name: __('List contacts', 'groundhogg'),
    description: () => Pg({}, __('Retrieve a list of contacts.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/contacts`,
    params: [
      // name, type, required, default, description
      CommonParams.filters('contacts'),
      CommonParams.search('contacts', ['first_name', 'last_name', 'email']),
      CommonParams.limit('contacts'),
      CommonParams.offset('contacts'),
      CommonParams.order('contacts'),
      CommonParams.orderby('contacts', ['ID', 'first_name', 'last_name', 'email', 'optin_status', 'date_created']),
      // CommonParams.found_rows('contacts'),

    ],
    request: {
      search: 'John',
      limit: 20,
    },
    response: {
      'total_items': 99,
      'items': [
        {
          'ID': 1234,
          'data': {
            'email': 'john@example.com',
            'first_name': 'John',
            'last_name': 'Doe',
            'full_name': 'John Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2023-10-18 13:05:25',
            'date_optin_status_changed': '2023-10-18 13:05:25',
            'age': false,
          },
          'meta': {
            'locale': 'en_US',
            'primary_phone': '4658444269',
            'mobile_phone': '',
            'country': 'US',
            'region': 'NY',
            'city': 'New York',
            'birthday': '',
          },
          'tags': [
            {
              'ID': 11,
              'data': {
                'tag_id': 11,
                'tag_slug': 'customer',
                'tag_name': 'Customer',
              },
            },
          ],
          'user': false,
          'is_marketable': true,
          'is_deliverable': true,
        },
      ],
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('create', {
    name: __('Add multiple contacts', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __('Adds multiple contacts at once.', 'groundhogg')),
      Pg({}, __('If an email address is used that already exists in the DB, it will update the existing contact record.', 'groundhogg')),
    ]),
    method: 'POST',
    endpoint: `${ apiRoot }/contacts`,
    required: [
      'data',
      'data.email',
    ],
    params: [
      // name, type, required, default, description
      {
        param: 'data',
        description: () => Pg({}, __('The data object must contain the basic contact fields such as first_name, last_name, and email.', 'groundhogg')),
        type: 'object',
        default: null,
        subParams: [
          {
            param: 'email',
            description: () => Pg({}, __('The contact\'s email address.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'first_name',
            description: () => Pg({}, __('The contact\'s first name.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'last_name',
            description: () => Pg({}, __('The contact\'s last name.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'optin_status',
            description: () => Fragment([
              Pg({}, __('The contact\'s opt-in status, represented as an integer.', 'groundhogg')),
              Pg({},
                andList(Object.keys(Groundhogg.filters.optin_status).map(os => `<code>${ os }</code> for <b>${ Groundhogg.filters.optin_status[os] }</b>`))),
            ]),
            type: 'int',
          },
        ],
      },
      {
        param: 'meta',
        description: () => Fragment([
          Pg({}, __('The meta object can contain any number of arbitrary key&rarr;value pairs.', 'groundhogg')),
          Pg({},
            __('All custom fields, as well as some of the other basic fields (primary_phone, street_address_1, etc.) must be added within the meta object.',
              'groundhogg')),
        ]),
        type: 'object',
        required: false,
        subParams: [
          {
            param: '<key>',
            description: Pg({}, __('Any arbitrary key with any arbitrary value.', 'groundhogg')),
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
      },
      CommonParams.tags('tags'),
    ],
    repeater: true,
    request: [
      {
        'data': {
          'first_name': 'John',
          'last_name': 'Doe',
          'email': 'john@example.com',
          'optin_status': 2,
        },
        'meta': {
          'custom_field': 'abc',
          'primary_phone': '+1 555 555-5555',
          'Foo': 'bar',
        },
      },
      {
        'data': {
          'email': 'jane@example.com',
          'first_name': 'Jane',
          'last_name': 'Doe',
          'optin_status': 2,
        },
        'meta': {
          'custom_field': '123',
        },
        'tags': [
          25,
        ],
      },
    ],
    response: {
      'total_items': 2,
      'items': [
        {
          'ID': 1234,
          'data': {
            'email': 'john@example.com',
            'first_name': 'John',
            'last_name': 'Doe',
            'full_name': 'John Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2024-05-31 13:55:51',
            'date_optin_status_changed': '2024-05-31 13:55:51',
          },
          'meta': {
            'locale': 'en_US',
            'custom_field': 'abc',
            'primary_phone': '+1 555 555-5555',
            'foo': 'bar',
          },
          'tags': [],
          'is_marketable': true,
          'is_deliverable': true,
        },
        {
          'ID': 4321,
          'data': {
            'email': 'jane@example.com',
            'first_name': 'Jane',
            'last_name': 'Doe',
            'full_name': 'Jane Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2024-06-01 19:35:53',
            'date_optin_status_changed': '2024-06-01 19:35:53',
          },
          'meta': {
            'locale': 'en_US',
            'custom_field': '123',
          },
          'tags': [],
          'is_marketable': true,
          'is_deliverable': true,
        },
      ],
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('update', {
    name: __('Update multiple contacts', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __('Update multiple contacts at once.', 'groundhogg')),
      Pg({},
        __('Trying to update a contact that does not exist will not create a new contact. Instead use POST which will update contacts if they already exist.',
          'groundhogg')),
    ]),
    method: 'PATCH',
    endpoint: `${ apiRoot }/contacts`,
    required: [
      'ID'
    ],
    params: [
      {
        param: 'ID',
        type: 'int',
        description: () => Fragment([
          Pg({}, __('The ID of the contact.', 'groundhogg')),
          Pg({}, __('The ID is normally required unless the email address is provided within the data object.', 'groundhogg')),
        ]),
      },
      ...ApiRegistry.contacts.endpoints.create.params,
    ],
    repeater: true,
    request: [
      {
        'data': {
          'first_name': 'John',
          'last_name': 'Doe',
          'email': 'john@example.com',
          'optin_status': 2,
        },
        'meta': {
          'custom_field': 'abc',
          'primary_phone': '+1 555 555-5555',
          'Foo': 'bar',
        },
      },
      {
        'data': {
          'email': 'jane@example.com',
          'first_name': 'Jane',
          'last_name': 'Doe',
          'optin_status': 2,
        },
        'meta': {
          'custom_field': '123',
        },
        'tags': [
          25,
        ],
      },
    ],
    response: {
      'total_items': 2,
      'items': [
        {
          'ID': 1234,
          'data': {
            'email': 'john@example.com',
            'first_name': 'John',
            'last_name': 'Doe',
            'full_name': 'John Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2024-05-31 13:55:51',
            'date_optin_status_changed': '2024-05-31 13:55:51',
          },
          'meta': {
            'locale': 'en_US',
            'custom_field': 'abc',
            'primary_phone': '+1 555 555-5555',
            'foo': 'bar',
          },
          'tags': [],
          'is_marketable': true,
          'is_deliverable': true,
        },
        {
          'ID': 4321,
          'data': {
            'email': 'jane@example.com',
            'first_name': 'Jane',
            'last_name': 'Doe',
            'full_name': 'Jane Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2024-06-01 19:35:53',
            'date_optin_status_changed': '2024-06-01 19:35:53',
          },
          'meta': {
            'locale': 'en_US',
            'custom_field': '123',
          },
          'tags': [],
          'is_marketable': true,
          'is_deliverable': true,
        },
      ],
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('bulk-update', {
    name: __('Bulk update contacts', 'groundhogg'),
    description: () => Pg({}, __('Bulk update contacts with the same information using a query.', 'groundhogg')),
    method: 'PATCH',
    endpoint: `${ apiRoot }/contacts`,
    params: [
      // name, type, required, default, description
      {
        param: 'query',
        description: () => Pg({}, __('The query to identify the contacts you wish to update.', 'groundhogg')),
        type: 'object',
        required: true,
        subParams: [
          CommonParams.filters('contacts'),
        ],
      },
      ( param => {
        param.required = false
        param.subParams.splice(0, 3)
        return param
      } )(copyObject(ApiRegistry.contacts.endpoints.create.params[0])),
      ApiRegistry.contacts.endpoints.create.params[1],
      CommonParams.tags('add_tags'),
      CommonParams.tags('remove_tags'),
      {
        param: 'bg',
        description: () => Pg({}, __('Whether to update the contacts using a background task.', 'groundhogg')),
        type: 'bool',
        required: false,
        default: false,
      },
    ],
    request: {
      query: {},
      data: {
        optin_status: 1,
      },
      meta: {
        custom_field: 'abc',
      },
    },
    response: {
      'total_items': 99,
      'items': [
        {
          'ID': 1234,
          'data': {
            'email': 'john@example.com',
            'first_name': 'John',
            'last_name': 'Doe',
            'full_name': 'John Doe',
            'user_id': 0,
            'owner_id': 1,
            'optin_status': 2,
            'date_created': '2023-10-18 13:05:25',
            'date_optin_status_changed': '2023-10-18 13:05:25',
            'age': false,
          },
          'meta': {
            'locale': 'en_US',
            'primary_phone': '4658444269',
            'mobile_phone': '',
            'country': 'US',
            'region': 'NY',
            'city': 'New York',
            'birthday': '',
          },
          'tags': [
            {
              'ID': 11,
              'data': {
                'tag_id': 11,
                'tag_slug': 'customer',
                'tag_name': 'Customer',
              },
            },
          ],
          'user': false,
          'is_marketable': true,
          'is_deliverable': true,
        },
      ],
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('delete', {
    name: __('Delete contacts', 'groundhogg'),
    description: () => Pg({}, __('Delete many contacts that match the query at once.', 'groundhogg')),
    method: 'DELETE',
    endpoint: `${ apiRoot }/contacts`,
    params: [
      CommonParams.filters('contacts'),
      CommonParams.search('contacts', ['first_name', 'last_name', 'email']),
      CommonParams.limit('contacts'),
      CommonParams.offset('contacts'),
      {
        param: 'bg',
        description: () => Pg({}, __('Whether to delete the contacts using a background task.', 'groundhogg')),
        type: 'bool',
        required: false,
        default: false,
      },
    ],
    request: {},
    response: {
      items: [
        {
          ID: 1234,
          data: {},
          meta: {},
        },
      ],
      total_items: 10,
    },
  })

  ApiRegistry.contacts.endpoints.add('create-single', {
    name: __('Add a contact', 'groundhogg'),
    description: () => Pg({}, __('Adds a contact.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/contacts`,
    required: [
      'data',
      'data.email'
    ],
    params: ApiRegistry.contacts.endpoints.create.params,
    request: {
      data: {
        first_name: 'John',
        last_name: 'Doe',
        email: 'john@example.com',
        optin_status: 2,
      },
      meta: {
        custom_field: 'abc',
        primary_phone: '+1 555 555-5555',
      },
    },
    response: {
      'item': {
        'ID': 1234,
        'data': {
          'email': 'john@example.com',
          'first_name': 'John',
          'last_name': 'Doe',
          'full_name': 'John Doe',
          'user_id': 0,
          'owner_id': 1,
          'optin_status': 2,
          'date_created': '2024-05-31 13:55:51',
          'date_optin_status_changed': '2024-05-31 13:55:51',
        },
        'meta': {
          'locale': 'en_US',
          'custom_field': 'abc',
          'primary_phone': '+1 555 555-5555',
          'foo': 'bar',
        },
        'tags': [],
        'user': false,
        'is_marketable': true,
        'is_deliverable': true,
      },
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('get-single', {
    name: __('Retrieve a contact', 'groundhogg'),
    description: () => Pg({}, __('Retrieve a single contact.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/contacts/<id>`,
    identifiers: [
      CommonParams.id('contact'),
    ],
    request: {},
    response: ApiRegistry.contacts.endpoints['create-single'].response,
  })

  ApiRegistry.contacts.endpoints.add('update-single', {
    name: __('Update a contact', 'groundhogg'),
    description: () => Pg({}, __('Update a single contact.', 'groundhogg')),
    method: 'PATCH',
    endpoint: `${ apiRoot }/contacts/<id>`,
    identifiers: [
      CommonParams.id('contact'),
    ],
    params: [
      ApiRegistry.contacts.endpoints['create-single'].params[0],
      ApiRegistry.contacts.endpoints['create-single'].params[1],
      CommonParams.tags('add_tags'),
      CommonParams.tags('remove_tags'),
    ],
    request: {
      data: {
        first_name: 'John',
        last_name: 'Doe',
      },
      meta: {
        custom_field: 'foo',
      },
    },
    response: ApiRegistry.contacts.endpoints['create-single'].response,
  })

  ApiRegistry.contacts.endpoints.add('delete-single', {
    name: __('Delete a contact', 'groundhogg'),
    description: () => Pg({}, __('Delete a single contact.', 'groundhogg')),
    method: 'DELETE',
    endpoint: `${ apiRoot }/contacts/<id>`,
    identifiers: [
      CommonParams.id('contact'),
    ],
    request: {},
    response: {
      'status': 'success',
    },
  })

  ApiRegistry.contacts.endpoints.add('merge', {
    name: __('Merge contacts', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __('Merge other contact records into one.', 'groundhogg')),
      Pg({},
        __('All activity, events, and history will be retained. The most recent available information will be used from each contact record.', 'groundhogg')),
    ]),
    method: 'POST',
    endpoint: `${ apiRoot }/contacts/<id>/merge`,
    identifiers: [
      CommonParams.id('contact'),
    ],
    params: [
      {
        param: 'others',
        type: 'int[]',
        description: () => Pg({}, __('An array of contact IDs.', 'groundhogg')),
        control: ({ param, name, id }) => {

          let others = getFromRequest(param, [])
          let rows = others.map(id => ( [id] ))

          return InputRepeater({
            id,
            rows: rows,
            cells: [
              props => Input({
                ...props,
                type: 'number',
              }),
            ],
            onChange: rows => {
              setInRequest(param, rows.map(([id]) => parseInt(id)))
            },
          })
        },
      },
    ],
    request: {
      others: [11, 12],
    },
    response: {
      'status': 'success',
    },
  })

})()
