(()=>{

  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
  } = MakeEl

  ApiRegistry.add('tags', {
    name: __('Tags'),
    description: '',
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.tags.endpoints.add('list', {
    name: __('List tags', 'groundhogg'),
    description: () => Pg({}, __('Retrieve a list of tags.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/tags`,
    params: [
      // name, type, required, default, description
      CommonParams.search('tags', ['tag_name', 'tag_description', 'tag_slug']),
      CommonParams.limit('tags'),
      CommonParams.offset('tags'),
      CommonParams.order('tags'),
      CommonParams.orderby('tags', ['tag_id', 'tag_name', 'tag_slug']),

    ],
    request: {
      search: 'Customer',
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

  ApiRegistry.tags.endpoints.add('create', {
    name: __('Create multiple tags', 'groundhogg'),
    description: () => Pg({}, __('Create multiple new tags at once.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/tags/`,
    required: [
      'data',
      'data.tag_name'
    ],
    params: [
      {
        param: 'data',
        description: () => Fragment([
          Pg({}, __('The data object contains all the necessary information.', 'groundhogg')),
        ]),
        type: 'object',
        subParams: [
          {
            param: 'tag_name',
            description: () => Pg({}, __('The name of the tag.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'tag_description',
            description: () => Pg({},
              __('Describe how the tag is supposed to be used. This will only be visible in the admin unless the tag is used as a preference.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'tag_slug',
            description: () => Pg({}, __('A unique slug for the tag. It will be auto generated from the tag_name unless supplied.', 'groundhogg')),
            type: 'string',
          },
        ],
      },
    ],
    repeater: true,
    request: [{
      data: {
        tag_name: '',
        tag_description: '',
        tag_slug: '',
      },
    }],
    response: {},
  })

  ApiRegistry.tags.endpoints.add('create-single', {
    name: __('Create a tag', 'groundhogg'),
    description: () => Pg({}, __('Create a new tag.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/tags/`,
    required: [
      'data',
      'data.tag_name',
    ],
    params: ApiRegistry.tags.endpoints.create.params,
    request: {
      data: {
        tag_name: '',
        tag_description: '',
        tag_slug: '',
      },
    },
    response: {},
  })

  ApiRegistry.tags.endpoints.add('update-single', {
    name: __('Update a tag', 'groundhogg'),
    description: () => Pg({}, __('Update a tag.', 'groundhogg')),
    method: 'PATCH',
    endpoint: `${ apiRoot }/tags/<id>`,
    identifiers: [
      CommonParams.id('tag'),
    ],
    required: [
      'data'
    ],
    params: ApiRegistry.tags.endpoints.create.params,
    request: {
      data: {
        tag_name: '',
        tag_description: '',
        tag_slug: '',
      },
    },
    response: {},
  })

  ApiRegistry.tags.endpoints.add('delete-single', {
    name: __('Delete a tag', 'groundhogg'),
    description: () => Pg({}, __('Delete a tag.', 'groundhogg')),
    method: 'DELETE',
    endpoint: `${ apiRoot }/tags/<id>`,
    identifiers: [
      CommonParams.id('tag'),
    ],
    request: {},
    response: {},
  })

})()
