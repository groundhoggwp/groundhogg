(()=>{

  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
  } = MakeEl

  ApiRegistry.add('campaigns', {
    name: __('Campaigns'),
    description: () => Fragment([
      Pg({}, __('Add, update, or manage campaigns remotely.', 'groundhogg'))
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  ApiRegistry.campaigns.endpoints.add('list', {
    name: __('List campaigns', 'groundhogg'),
    description: () => Pg({}, __('Retrieve a list of campaigns.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/campaigns`,
    params: [
      CommonParams.search('campaigns', ['name', 'description', 'slug']),
      CommonParams.limit('campaigns'),
      CommonParams.offset('campaigns'),
      CommonParams.order('campaigns'),
      CommonParams.orderby('campaigns', ['ID', 'name', 'slug']),

    ],
    request: {
      search: 'Nurture',
      limit: 20,
    },
    response: {
      "total_items": 1,
      "items": [
        {
          "ID": 22,
          "data": {
            "ID": 22,
            "slug": "nuture",
            "name": "Nurture",
            "description": "Emails sent to nurture leads.",
            "visibility": "public"
          },
        }
      ],
      "status": "success"
    },
  })

  ApiRegistry.campaigns.endpoints.add('create', {
    name: __('Create multiple campaigns', 'groundhogg'),
    description: () => Pg({}, __('Create multiple new campaigns at once.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/campaigns/`,
    required: [
      'data',
      'data.name'
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
            param: 'name',
            description: () => Pg({}, __('The name of the campaign.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'description',
            description: () => Pg({},
              __('Describe the type of assets associated with the campaign. This will only be visible in the admin unless the campaign is public.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'slug',
            description: () => Pg({}, __('A unique slug for the campaign. It will be auto generated from the name unless supplied.', 'groundhogg')),
            type: 'string',
          },
          {
            param: 'visibility',
            description: () => Pg({}, __('Whether the campaign can be viewed in a public archive.', 'groundhogg')),
            type: 'string',
            options: [
              'hidden',
              'public'
            ]
          },
        ],
      },
    ],
    repeater: true,
    request: [{
      data: {
        name: 'Nurture',
        description: 'Emails sent to nurture leads.',
        slug: 'nurture',
        visibility: 'public',
      },
    }],
    response: {},
  })

  ApiRegistry.campaigns.endpoints.add('create-single', {
    name: __('Create a campaign', 'groundhogg'),
    description: () => Pg({}, __('Create a new campaign.', 'groundhogg')),
    method: 'POST',
    endpoint: `${ apiRoot }/campaigns/`,
    required: [
      'data',
      'data.name',
    ],
    params: ApiRegistry.campaigns.endpoints.create.params,
    request: {
      data: {
        name: 'Nurture',
        description: 'Emails sent to nurture leads.',
        slug: 'nurture',
        visibility: 'public',
      },
    },
    response: {},
  })

  ApiRegistry.campaigns.endpoints.add('update-single', {
    name: __('Update a campaign', 'groundhogg'),
    description: () => Pg({}, __('Update a campaign.', 'groundhogg')),
    method: 'PATCH',
    endpoint: `${ apiRoot }/campaigns/:id`,
    identifiers: [
      CommonParams.id('campaign'),
    ],
    required: [
      'data'
    ],
    params: ApiRegistry.campaigns.endpoints.create.params,
    request: {
      data: {
        name: 'Nurture-1',
        slug: 'nurture-1',
      },
    },
    response: {},
  })

  ApiRegistry.campaigns.endpoints.add('delete-single', {
    name: __('Delete a campaign', 'groundhogg'),
    description: () => Pg({}, __('Delete a campaign.', 'groundhogg')),
    method: 'DELETE',
    endpoint: `${ apiRoot }/campaigns/:id`,
    identifiers: [
      CommonParams.id('campaign'),
    ],
    request: {},
    response: {},
  })

  ApiRegistry.campaigns.endpoints.add('archive', {
    name: __('List campaigns archive', 'groundhogg'),
    description: () => Pg({}, __('List campaigns that are publicly visible for the archive.', 'groundhogg')),
    method: 'GET',
    endpoint: `${ apiRoot }/campaigns/archive`,
    params: [
      CommonParams.per_page('campaigns'),
      CommonParams.page('campaigns'),
      CommonParams.search('campaigns', [ 'name', 'description' ] ),
    ],
    request: {},
    response: {
      items:  ApiRegistry.campaigns.endpoints.list.response.items,
      total_items: 10,
      total_pages: 1,
      status:'success'
    }
  })

})()
