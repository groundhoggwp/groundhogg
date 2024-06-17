( ($) => {

  const { sprintf, __, _x, _n } = wp.i18n

  const State = Groundhogg.createState({
    route: 'auth',
    endpoint: 'apikeys',
    request: {},
    playground: true,
  })

  const {
    Div,
    Fragment,
    makeEl,
    Modal,
    Span,
    Pg,
    An,
    Ul,
    Ol,
    Li,
    Input,
    Select,
    Toggle,
    Button,
    Dashicon,
    InputRepeater,
    ItemPicker,
    ToolTip,
  } = MakeEl

  const { icons, andList, el, escHTML, copyObject, adminPageURL } = Groundhogg.element

  const ApiRegistry = Groundhogg.createRegistry()

  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { base64_json_encode, setNestedValue, getNestedValue, jsonCopy } = Groundhogg.functions

  const IdRepeater = ({ param, name, id }) => {

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
  }

  const CommonParams = {
    filters: (plural) => ( {
      param: 'filters',
      type: 'array',
      control: ({ param, id }) => {

        let filters = getFromRequest(param)

        if (!Array.isArray(filters)) {
          filters = []
        }

        return Groundhogg.filters.ContactFilters(id, filters, filters => {

          filters = filters.map(group => group.map(({ id, ...filter }) => filter))

          if (currEndpoint().method === 'GET') {
            filters = base64_json_encode(filters)
          }

          setInRequest(param, filters)
        })
      },
      description: () => Fragment([
        Pg({},
          sprintf(__('Filters are the most comprehensive way to search for %s that match your criteria.', 'groundhogg'),
            plural)),
        currEndpoint().method === 'GET' ? Pg({},
          sprintf(__(
              'When using filters with <code class="get">GET</code> it is best to JSON encode and then base64 encode the filters.',
              'groundhogg'),
            plural)) : '',
      ]),
    } ),
    include: (plural) => ( {
      param: 'include',
      type: 'int[]',
      control: IdRepeater,
      description: () => Pg({}, sprintf(__('IDs of %s to include.', 'groundhogg'), plural)),
    } ),
    exclude: (plural) => ( {
      param: 'exclude',
      type: 'int[]',
      control: IdRepeater,
      description: () => Pg({}, sprintf(__('IDs of %s to exclude.', 'groundhogg'), plural)),
    } ),
    search: (plural, columns = []) => ( {
      param: 'search',
      description: () => Pg({},
        sprintf(__('Search for %s using a search phrase. Will match %s.', 'groundhogg'), plural,
          andList(columns.map(col => el('code', {}, col))))),
      type: 'string',
    } ),
    limit: (plural) => ( {
      param: 'limit',
      description: () => Pg({}, sprintf(__('The number of %s to return.', 'groundhogg'), plural)),
      type: 'int',
      default: 20,
    } ),
    id: (singular) => ( {
      param: 'id',
      description: () => Pg({}, sprintf(__('The ID of the %s to return.', 'groundhogg'), singular)),
      type: 'int',
      required: true,
    } ),
    offset: (plural) => ( {
      param: 'offset',
      description: () => Pg({}, sprintf(__('Paginate through %s.', 'groundhogg'), plural)),
      type: 'int',
      default: 0,
    } ),
    order: (plural) => ( {
      param: 'order',
      description: () => Pg({}, sprintf(__('How to order %s.', 'groundhogg'), plural)),
      type: 'string',
      default: 'DESC',
      options: ['ASC', 'DESC'],
    } ),
    orderby: (plural, columns = []) => ( {
      param: 'orderby',
      description: () => Pg({},
        sprintf(__('Order %s by a specific column. Supported columns are %s.', 'groundhogg'), plural,
          andList(columns.map(col => el('code', {}, col))))),
      type: 'string',
      default: 'ID',
    } ),
    found_rows: (plural) => ( {
      param: 'found_rows',
      description: () => Pg({},
        sprintf(__('Whether to return the total number of %s matching the query.', 'groundhogg'), plural)),
      type: 'bool',
      default: 'true',
    } ),
    tags: (param) => ( {
      param,
      type: 'int[]|string[]',
      description: () => Fragment([
        Pg({},
          __(
            'An array of tag names or tag IDs. If passing names, if the tag does not exist it will be created. The array can be a mix of strings and IDs.',
            'groundhogg')),
      ]),
      control: ({ param, id }) => ItemPicker({
        id,
        noneSelected: 'Select tags...',
        selected: [],
        fetchOptions: async (search) => {
          let tags = await Groundhogg.stores.tags.fetchItems({
            search,
            limit: 30,
          })

          return tags.map(({ ID, data }) => ( { id: ID, text: data.tag_name } ))
        },
        onChange: items => setInRequest(param, items.map(({ id }) => id)),
      }),
    } ),
    meta: () => ( {
      param: 'meta',
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
        Pg({}, __('The meta object can contain any number of arbitrary key&rarr;value pairs.', 'groundhogg')),
      ]),
    } ),
  }

  const setInRequest = (param, value) => {
    setNestedValue(State.request, param, value)
    morphEl(ExampleRequest())
  }

  const getFromRequest = (param, def = '') => getNestedValue(State.request, param) ?? def

  const ControlFromParam = ({
    param,
    type,
    control = null,
    ...props
  }) => {

    if (!State.playground) {
      return null
    }

    let id = `param-${ param.replaceAll('.', '-') }`
    let name = param

    if (control) {
      return control({ param, id, name })
    }

    if (props.options) {
      return Select({
        name: param,
        id: param.replaceAll('.', '-'),
        selected: getFromRequest(param, props.default),
        options: props.options,
        onChange: e => setInRequest(param, e.target.value),
      })
    }

    switch (type) {
      case 'int':
      case 'string':
        return Input({
          type: type === 'int' ? 'number' : 'text',
          name,
          id,
          value: getFromRequest(param),
          className: 'code',
          onInput: e => setInRequest(param, type === 'int' ? parseInt(e.target.value) : e.target.value),
        })
      case 'bool':
      case 'boolean':
        return Toggle({
          name,
          id,
          checked: getFromRequest(param, props.default) === true,
          onChange: e => setInRequest(param, e.target.checked),
          onLabel: 'True',
          offLabel: 'False',
        })
    }
  }

  const isParamRequired = param => {

    if (!currEndpoint().required) {
      return false
    }

    if (currEndpoint().repeater) {
      param = param.substring(param.indexOf('.') + 1)
    }

    return currEndpoint().required.includes(param)
  }

  const ParamsList = (params, parentParam = '') => Fragment(
    params.map(({ param, description, required = false, type, ...props }) => {

      if (parentParam) {
        param = `${ parentParam }.${ param }`
      }

      if (typeof description === 'string') {
        description = Pg({}, description)
      }

      return Div({
        className: 'parameter',
      }, [
        Div({ className: 'display-flex gap-10 align-center' }, [
          `<code class="param">${ escHTML(param) }</code>`,
          `<span class="type">${ escHTML(type) }</span>`,
          isParamRequired(param) || required ? `<span class="required">${ __('Required', 'groundhogg') }</span>` : null,
        ]),
        description,
        typeof props.default !== 'undefined' && props.default !== null ? `<p>${ sprintf(__('Defaults to %s.'),
          `<code>${ props.default }</code>`) }</p>` : null,
        props.subParams ? null : ControlFromParam({ param, type, ...props }),
        props.subParams ? Div({ className: 'subparams gh-panel outlined' }, [
          Div({ className: 'gh-panel-header' }, `<h2>Child parameters</h2>`),
          ParamsList(props.subParams, param),
        ]) : null,
      ])
    }))

  const ParamsRepeater = (params) => {
    return Fragment([
      ...State.request.map((item, i) => {

        return Div({
          className: 'gh-panel outlined',
        }, [
          Div({
            className: 'gh-panel-header',
          }, [
            makeEl('h2', {}, `Index ${ i }`),
            Button({
              id: `delete-item-index-${ i }`,
              className: 'gh-button icon danger text small',
              onClick: e => {
                State.request.splice(i, 1)
                morph()
              },
            }, Dashicon('trash')),
          ]),
          ParamsList(params, `${ i }`),
        ])
      }),
      Button({
        className: 'gh-button secondary full-width',
        onClick: e => {
          State.request.push({})
          morph()
        },
      }, 'Add another item'),
    ])
  }

  const getGeneratedRequest = () => {
    const METHOD = currEndpoint().method.toUpperCase()

    let url = currEndpoint().endpoint
    let params = JSON.parse(JSON.stringify(State.request)) // make a copy of the object

    // Replace identifiers from request into the URL structure
    if (currEndpoint().identifiers && currEndpoint().identifiers.length) {
      currEndpoint().identifiers.forEach(({ param }) => {
        // replace it in the URL
        if (params[param]) {
          url = url.replace(`:${ param }`, params[param])
        }
        // remove it from the other request because it's in the URL
        delete params[param]
      })
    }

    // Method for GET is to use URL params
    if (METHOD === 'GET' && Object.keys(params).length) {
      url = `${ url }?${ $.param(params) }`
    }

    return {
      url,
      params,
      METHOD,
    }
  }

  const sendTestRequest = async () => {

    let { url, params, METHOD } = getGeneratedRequest()

    let response

    if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(METHOD)) {

      try {

        await new Promise((res, rej) => {

          Modal({
            onClose: rej,
            width: '400px',
          }, ({ close }) => Fragment([
            Pg({
              style: {
                fontSize: '16px',
                marginTop: 0,
              },
            }, __('Are you sure you want to execute this API call? It will make real changes to the database.',
              'groundhogg')),
            Div({
              className: 'display-flex flex-end gap-10',
            }, [
              Button({
                className: 'gh-button primary text',
                onClick: () => {
                  close()
                },
              }, 'No, cancel'),
              Button({
                className: 'gh-button danger',
                onClick: () => {
                  res()
                  close()
                },
              }, 'Yes, run anyway'),
            ]),
          ]))
        })

      }
      catch (err) {
        return
      }

    }

    if (METHOD === 'GET') {
      response = await fetch(url, {
        method: 'GET',
        headers: {
          'X-WP-Nonce': wpApiSettings.nonce,
        },
      })
    }
    else {
      response = await fetch(url, {
        method: METHOD,
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpApiSettings.nonce,
        },
        body: JSON.stringify(params),
      })
    }

    let json = await response.json()

    State.set({
      response: json,
    })

    morphEl(ExampleResponse())

  }

  const curlRequest = () => {
    let { url, params, METHOD } = getGeneratedRequest()

    return METHOD === 'GET' ?
      // GET
      [
        `curl -X GET ${ url }`,
      ].join('\n')

      // Other methods
      : [
        `curl -X ${ METHOD } ${ url } \\`,
        `-H 'Content-Type: application/json' \\`,
        Object.keys(params).length ? `-d '${ JSON.stringify(params, null, 2) }'` : '',
      ].join('\n')
  }

  const phpRequest = () => {

    return [
      '<?php',

    ].join('')
  }

  const ExampleRequest = () => {

    let request = ''

    if ( currEndpoint().examples?.hasOwnProperty( State.language ) ){
      request = currEndpoint().examples[State.language]()
    } else {
      switch ( State.language ){
        case 'curl':
        default:
          request = curlRequest()
          break;
        case 'php':
          request = phpRequest()
          break;
      }
    }

    return Div({
      id: 'example-request',
      className: 'gh-panel',
      style: {
        overflow: 'auto',
      },
    }, [
      Div({ className: 'gh-panel-header bg-dark-75' }, [
        makeEl('h2', {
          className: 'fc-white',
        }, __('Example request')),
      ]),

      makeEl('pre', {}, escHTML( request ) )
    ])
  }

  const TestButton = () => Button({
    id: `test-${ State.route }-${ State.endpoint }`,
    className: 'gh-button secondary',
    onClick: sendTestRequest,
  }, __('Test Request'))

  const ExampleResponse = () => Div({
    id: 'example-response',
    className: 'gh-panel',
    style: {
      marginTop: '20px',
    },
  }, [
    Div({ className: 'gh-panel-header' }, [
      makeEl('h2', {}, __('Example response')),
    ]),
    makeEl('pre', {
      className: 'light',
    }, escHTML(JSON.stringify(State.response ?? currEndpoint().response, null, 2))),
  ])

  const currEndpoint = () => State.endpoint ? currRoute().endpoints[State.endpoint] : undefined
  const currRoute = () => ApiRegistry[State.route]

  const Docs = () => {

    let currRoute = ApiRegistry[State.route]
    let currEndpoint

    if (State.endpoint) {
      currEndpoint = currRoute.endpoints[State.endpoint]
      State.set({
        request: currEndpoint.request,
        response: currEndpoint.response,
      })
    }

    return Div({
      id: 'api-docs',
      className: 'gh-fixed-ui',
    }, [

      Div({
        id: 'api-header',
        className: 'gh-header sticky',
      }, [
        Groundhogg.isWhiteLabeled ? Span() : icons.groundhogg,
        `<h1>${ __('Rest API', 'groundhogg') }</h1>`,
        Div({
          className: 'display-flex gap-10',
        }, [
          Span({}, __('Playground Features')),
          Toggle({
            id: 'enable-play',
            name: 'enable_playground',
            checked: State.playground,
            onChange: e => {
              State.set({
                playground: e.target.checked,
              })
              morph()
            },
          }),
        ]),
        State.playground ? TestButton() : null,
      ]),

      Div({
        id: 'docs-ui',
        className: 'display-flex',
      }, [
        // nav
        makeEl('nav', {}, makeEl('ul', {}, ApiRegistry.keys().map(key => {

          let item = ApiRegistry[key]
          let endpoints = item.endpoints

          return makeEl('li', {
            id: `route-${ key }`,
            className: State.route === key ? 'current' : '',
          }, [
            makeEl('a', { href: `#${ key }` }, item.name),
            makeEl('ul', {}, endpoints.keys().map(key2 => {

              let endpoint = endpoints[key2]

              return makeEl('li', {
                id: `route-${ key }-endpoint-${ key2 }`,
                className: State.endpoint === key2 ? 'current' : '',
              }, makeEl('a', {
                href: `#${ key }/${ key2 }`,
              }, [
                // endpoint.method ? `<code class="${ endpoint.method.toLowerCase() }">${ endpoint.method.toUpperCase() }</code>` : null,
                endpoint.name,
              ]))
            })),
          ])
        }))),
        // Display

        currEndpoint ? Div({
          id: 'endpoint-display',
          className: 'endpoint full-width',
        }, [
          makeEl('h1', {}, currEndpoint.name),
          currEndpoint.description,
          currEndpoint.endpoint ? makeEl('h2', {}, __('Endpoint', 'groundhogg')) : null,
          currEndpoint.endpoint
            ? `<pre><code class="${ currEndpoint.method.toLowerCase() }">${ currEndpoint.method.toUpperCase() }</code> ${ escHTML(
              currEndpoint.endpoint) }</pre>`
            : null,
          currEndpoint.identifiers?.length ? Fragment([
            makeEl('h2', {}, __('Identifiers', 'groundhogg')),
            ParamsList(currEndpoint.identifiers),
          ]) : null,
          currEndpoint.params?.length ? Fragment([
            makeEl('h2', {}, __('Parameters', 'groundhogg')),
            currEndpoint.repeater ? ParamsRepeater(currEndpoint.params) : ParamsList(currEndpoint.params),
          ]) : null,

        ]) : Div({
          id: 'endpoint-display',
          className: 'endpoint full-width',
        }, [
          makeEl('h1', {}, currRoute.name),
          currRoute.description,
        ]),

        currEndpoint && currEndpoint.request ? Div({
          id: 'request-display',
          className: 'request',
        }, [
          ExampleRequest(),
          currEndpoint.response ? ExampleResponse() : null,
        ]) : null,

        !currEndpoint && !currRoute.hideEndpoints ? Div({
          id: 'request-display',
          className: 'request',
        }, [
          Div({
            className: 'gh-panel',
            style: {
              overflow: 'auto',
            },
          }, [
            Div({
              className: 'gh-panel-header bg-dark-75',
            }, [
              makeEl('h2', {
                className: 'fc-white',
              }, __('Endpoints')),
            ]),

            makeEl('pre', {
              className: 'display-flex column gap-20 bg-dark',
            }, currRoute.endpoints.map(({ endpoint = '', method, name }, key) => {

              if (!endpoint || !method) {
                return null
              }

              return makeEl('a', {
                href: `#${ State.route }/${ key }`,
                style: {
                  display: 'block',
                },
                // className:'gh-has-tooltip'
              }, [
                `<code class="${ method.toLowerCase() }">${ method.toUpperCase() }</code>`,
                '&nbsp;',
                endpoint.replace(Groundhogg.url.home, ''),
                ToolTip(name, 'left'),
              ])
            })),
          ]),
        ]) : null,
      ]),
    ])

  }

  const morphEl = el => morphdom(document.getElementById(el.id), el, {
    // childrenOnly: true,
  })

  const morph = () => morphdom(document.getElementById('api-docs'), Docs(), {
    // childrenOnly: true,
  })

  const clear = () => {
    morphdom(document.getElementById('api-docs'), Div({}, Div({})), {
      childrenOnly: true,
    })
  }

  const stateFromHash = () => {
    let hash = location.hash
    let [route, endpoint = ''] = hash.substring(1).split('/')

    State.set({
      route,
      endpoint,
      request: {},
      response: {},
    })
  }

  window.addEventListener('hashchange', e => {
    stateFromHash()
    clear() // some weirdness happening here
    morph()
  })

  $(() => {

    if (location.hash) {
      stateFromHash()
    }

    morph()

    document.getElementById('wpfooter').remove()
  })

  const swv = word => ['a', 'e', 'i', 'o', 'u'].includes(word[0].toLowerCase())

  const addBaseObjectCRUDEndpoints = (registry, {
    plural = '',
    singular = '',
    route = '',
    columns = [],
    searchableColumns = [],
    orderByColumns = [],
    readParams = [],
    dataParams = [],
    metaParams = [],
    moreParams = [],
    meta = false,
    relationships = false,
    commonMeta = [],
    strings = {},
    exampleItem = {
      'ID': '1234',
      'data': {},
    },
    createExample = null,
    updateExample = null
  }) => {

    if ( ! createExample ){
      createExample = {
        data: {
          ...exampleItem.data
        }
      }

      if ( meta ){
        createExample.meta = {
          ...exampleItem.meta
        }
      }
    }

    if ( ! updateExample ){
      updateExample = {
        ...createExample
      }
    }

    strings = {
      read: sprintf(__('List %s', 'groundhogg'), plural),
      readDesc: sprintf(__('Retrieve multiple %s using a query.', 'groundhogg'), plural),
      create: sprintf(__('Create %s', 'groundhogg'), plural),
      createDesc: sprintf(__('Create multiple %s at once.', 'groundhogg'), plural),
      update: sprintf(__('Update %s', 'groundhogg'), plural),
      updateDesc: sprintf(__('Update multiple %s at once.', 'groundhogg'), plural),
      delete: sprintf(__('Delete %s', 'groundhogg'), plural),
      deleteDesc: sprintf(__('Delete multiple %s at once.', 'groundhogg'), plural),
      readSingle: sprintf(swv(singular) ? __('Retrieve an %s', 'groundhogg') : __('Retrieve a %s', 'groundhogg'),
        singular),
      readSingleDesc: sprintf(__('Retrieves a single %s.', 'groundhogg'), singular),
      createSingle: sprintf(swv(singular) ? __('Create an %s', 'groundhogg') : __('Create a %s', 'groundhogg'), singular),
      createSingleDesc: sprintf(__('Create a single %s.', 'groundhogg'), singular),
      updateSingle: sprintf(swv(singular) ? __('Update an %s', 'groundhogg') : __('Update a %s', 'groundhogg'), singular),
      updateSingleDesc: sprintf(__('Update a single %s.', 'groundhogg'), singular),
      deleteSingle: sprintf(swv(singular) ? __('Delete an %s', 'groundhogg') : __('Delete a %s', 'groundhogg'), singular),
      deleteSingleDesc: sprintf(__('Delete a single %s.', 'groundhogg'), singular),
      ...strings,
    }

    registry.add('read', {
      name: strings.read,
      description: () => Pg({}, strings.readDesc),
      method: 'GET',
      endpoint: route,
      params: [
        ...readParams,
        CommonParams.include(plural),
        CommonParams.exclude(plural),
        CommonParams.search(plural, searchableColumns),
        CommonParams.limit(plural),
        CommonParams.offset(plural),
        CommonParams.order(plural),
        CommonParams.orderby(plural, orderByColumns),
      ],
      request: {},
      response: {
        items: [
          exampleItem,
        ],
        total_items: 0,
      },
      examples: {
        php: () => [
          `$query = new Table_Query( '${plural}' );`,
          `$query->set_query_params([]);`,
          `$items = $query->get_objects();`,
          `$items = $query->found_rows();`,
        ].join('\n')
      }
    })

    registry.add('create', {
      name: strings.create,
      description: () => Pg({}, strings.createDesc),
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
            Pg({}, sprintf(__('The data object contains all the necessary information for a new %s.', 'groundhogg'), singular)),
          ]),
        },
        ...moreParams,
      ],
      request: [
        createExample
      ],
      response: {
        "items" : [
          exampleItem
        ],
        "status" : "success"
      },
    })

    if (meta) {

      const metaParam = CommonParams.meta()
      if (metaParams && metaParams.length) {
        metaParam.subParams.push(...metaParams)
      }

      registry.create.params.push(metaParam)
    }

    registry.add('update', {
      name: strings.update,
      description: () => Pg({}, strings.updateDesc),
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
        ...registry.create.params,
      ],
      request: [
        updateExample
      ],
      response: {
        item: exampleItem
      },
    })

    registry.add('delete', {
      name: strings.delete,
      description: () => Pg({}, strings.deleteDesc),
      method: 'DELETE',
      endpoint: route,
      params: [
        ...registry.read.params,
      ],
      request: {},
      response: {
        "success": "true"
      },
    })

    registry.add('create-single', {
      name: strings.createSingle,
      description: () => Pg({}, strings.createSingleDesc),
      method: 'POST',
      endpoint: route,
      params: [
        ...registry.create.params,
      ],
      request: createExample,
      response: {},
    })

    registry.add('read-single', {
      name: strings.readSingle,
      description: () => Pg({}, strings.readSingleDesc),
      method: 'GET',
      endpoint: `${ route }/:id`,
      identifiers: [
        CommonParams.id(singular),
      ],
      request: {},
      response: {},
    })

    registry.add('update-single', {
      name: strings.updateSingle,
      description: () => Pg({}, strings.updateSingleDesc),
      method: 'PATCH',
      endpoint: `${ route }/:id`,
      identifiers: [
        CommonParams.id(singular),
      ],
      params: [
        ...registry.create.params,
      ],
      request: updateExample,
      response: {},
    })

    registry.add('delete-single', {
      name: strings.deleteSingle,
      description: () => Pg({}, strings.deleteSingleDesc),
      method: 'DELETE',
      endpoint: `${ route }/:id`,
      identifiers: [
        CommonParams.id(singular),
      ],
      request: {},
      response: {},
    })
  }

  Groundhogg.apiDocs = {
    ApiRegistry,
    CommonParams,
    setInRequest,
    getFromRequest,
    addBaseObjectCRUDEndpoints,
    currEndpoint,
    currRoute,
  }

  ApiRegistry.add('auth', {
    name: __('Authentication'),
    hideEndpoints: true,
    endpoints: Groundhogg.createRegistry(),
    description: () => Fragment([
      Pg({}, __('Groundhogg offers a variety of authentication methods for you to use to access the REST API.',
        'groundhogg')),
      Ul({}, [
        Li({}, An({ href: '#auth/apikeys' }, __('Using API keys'))),
        Li({}, An({ href: '#auth/pswd' }, __('Using application passwords'))),
      ]),
      Pg({}, __(
        'The Groundhogg REST API is permission based. Regardless of the authentication method, only users accessing the API with the required permissions will be able to perform requests.',
        'groundhogg')),
    ]),
  })

  // API Keys
  ApiRegistry.auth.endpoints.add('apikeys', {
    name: __('Using API keys', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __(
        'Using API keys is an easy way to get started with the REST API. And is suitable for most backend applications.',
        'groundhogg')),
      Pg({}, __(
        'When creating an API key, you are provided with a <b>token</b> and a <b>public key</b>. Both are required to authenticate requests.',
        'groundhogg')),
      Pg({}, __('You must add both to the header of your request.', 'groundhogg')),
      makeEl('pre', {}, escHTML([
        `curl ${ apiRoot }/:endpoint \\`,
        `  -H "Gh-Token: :token" \\`,
        `  -H "Gh-Public-Key: :public-key"`,
      ].join('\n'))),
      Pg({}, An({
        href: adminPageURL('gh_settings', { tab: 'api_tab' }),
      }, __('Manage your API keys in the settings area.', 'groundhogg'))),
    ]),
  })

  // Application Passwords
  ApiRegistry.auth.endpoints.add('pswd', {
    name: __('Using application passwords', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __(
        'Using application passwords is the <b>BEST WAY</b> to use the Groundhogg REST API from external applications.',
        'groundhogg')),
      Pg({}, __(
        'Applications passwords use basic authentication, which is supported by most external applications that you might want to integrate with Groundhogg.',
        'groundhogg')),
      makeEl('pre', {}, escHTML([
        `curl --user "<username>:<application password>" ${ apiRoot }/<endpoint>`,
      ].join('\n'))),
      Pg({}, __(
        'For more on application passwords, see the <a href="https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/" target="_blank">WordPress application password integration guide</a>.',
        'groundhogg')),
    ]),

  })

  // Application Passwords
  ApiRegistry.auth.endpoints.add('nonce', {
    name: __('Using a Nonce', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __(
        'If you are using the API from a front-end application, such as in the WordPress admin dashboard, you can use a <b>nonce</b> so that application passwords and API keys are not exposed.',
        'groundhogg')),
      Pg({}, __('', 'groundhogg')),
      makeEl('pre', {}, escHTML([
        `curl -X GET ${ apiRoot }/<endpoint>`,
        `  -H "X-WP-Nonce: <nonce>"`,
      ].join('\n'))),
      Pg({}, __(
        'For more on authenticating API requests using a nonce, see the <a href="https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/" target="_blank">WordPress cookie authentication guide</a>.',
        'groundhogg')),
    ]),

  })

} )(jQuery)
