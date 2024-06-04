(() => {

  // given an endpoint registry, register the standard CRUD endpoints for a base object
  const { ApiRegistry, CommonParams, setInRequest, getFromRequest } = Groundhogg.apiDocs
  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    Pg,
  } = MakeEl

  const addBaseObjectCRUDEndpoints = (registry, {
    plural = '',
    singular = '',
    route = '',
    columns = [],
    searchable = [],
    orderable = [],
    dataParams = [],
  }) => {

    registry.add('list', {
      name: sprintf(__('List %s', 'groundhogg'), plural),
      description: () => Pg({}, sprintf(__('Retrieve a list of %s.', 'groundhogg'), plural)),
      method: 'GET',
      endpoint: route,
      params: [
        CommonParams.search(plural, searchable),
        CommonParams.limit(plural),
        CommonParams.offset(plural),
        CommonParams.order(plural),
        CommonParams.orderby(plural, orderable),
      ],
      request: {},
      response: {},
    })

    registry.add('create', {
      name: sprintf(__('Create multiple %s', 'groundhogg'), plural),
      description: () => Pg({}, sprintf(__('Create multiple new %s at once.', 'groundhogg'), plural)),
      method: 'POST',
      endpoint: route,
      repeater: true,
      params: [
        {
          param: 'data',
          type: 'object',
          required: true,
          subParams: [
            ...dataParams,
          ],
          description: () => Fragment([
            Pg({}, sprintf(__('The data object contains all the necessary information for a new %s.', 'groundhogg'),
              singular)),
          ]),
        },
      ],
      request: [
        {
          data: {},
        }],
      response: {},
    })

    registry.add('update', {
      name: sprintf(__('Update multiple %s', 'groundhogg'), plural),
      description: () => Pg({}, sprintf(__('Update multiple new %s at once.', 'groundhogg'), plural)),
      method: 'PATCH',
      endpoint: route,
      repeater: true,
      params: [
        {
          param: 'ID',
          type: 'int',
          required: true,
          description: () => Fragment([
            Pg({}, sprintf(__('The ID of the %s to update.', 'groundhogg'), singular)),
          ]),
        },
        {
          param: 'data',
          type: 'object',
          subParams: [
            ...dataParams,
          ],
          description: () => Fragment([
            Pg({}, sprintf(__('The data object contains all the necessary information for a new %s.', 'groundhogg'),
              singular)),
          ]),
        },
      ],
      request: [
        {
          data: {},
        }],
      response: {},
    })

    registry.add('delete', {})

    registry.add('read-single', {
      name: sprintf( __('Retrieve a %s', 'groundhogg'), singular ),
      description: () => Pg({}, sprintf( __('Retrieves a %s by the ID.', 'groundhogg'), singular )),
      method: 'GET',
      endpoint: `${ route }/<id>`,
      identifiers: [
        CommonParams.id(singular ),
      ],
      request: {},
      response: {},
    })

    registry.add('update-single', {
      name: sprintf( __('Update a %s', 'groundhogg'), singular ),
      description: () => Pg({}, sprintf( __('Update a single %s based on the ID.', 'groundhogg'), singular )),
      method: 'PATCH',
      endpoint: `${route}/<id>`,
      identifiers: [
        CommonParams.id(singular ),
      ],
      params: registry.get('create').params,
      request: {},
      response: {},
    })

    registry.add('delete-single', {
      name: sprintf( __('Delete a %s', 'groundhogg'), singular ),
      description: () => Pg({}, sprintf( __('Delete a single %s based on the ID.', 'groundhogg'), singular )),
      method: 'DELETE',
      endpoint: `${route}/<id>`,
      identifiers: [
        CommonParams.id(singular ),
      ],
      request: {},
      response: {},
    })

  }

})()