( ($) => {

  const { sprintf, __, _x, _n } = wp.i18n

  const State = Groundhogg.createState({
    route: 'auth',
    endpoint: 'apikeys',
    request: {},
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
  } = MakeEl

  const { icons, andList, el, escHTML, copyObject } = Groundhogg.element

  const ApiRegistry = Groundhogg.createRegistry()

  const { root: apiRoot } = Groundhogg.api.routes.v4
  const { base64_json_encode, setNestedValue, getNestedValue } = Groundhogg.functions

  const CommonParams = {
    filters: (plural) => ( {
      param: 'filters',
      description: () => Fragment([
        Pg({}, sprintf(__('Filters are the most comprehensive way to search for %s that match your criteria.', 'groundhogg'), plural)),
        currEndpoint().method === 'GET' ? Pg({},
          sprintf(__('When using filters with <code class="get">GET</code> it is best to JSON encode and then base64 encode the filters.', 'groundhogg'),
            plural)) : '',
      ]),
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
    } ),
    search: (plural, columns = []) => ( {
      param: 'search',
      description: () => Pg({},
        sprintf(__('Search for %s using a search phrase. Will match %s.', 'groundhogg'), plural, andList(columns.map(col => el('code', {}, col))))),
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
      description: () => Pg({}, sprintf(__('The id of the %s to return.', 'groundhogg'), singular)),
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
        sprintf(__('Order %s by a specific column. Supported columns are %s.', 'groundhogg'), plural, andList(columns.map(col => el('code', {}, col))))),
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
          __('An array of tag names or tag IDs. If passing names, if the tag does not exist it will be created. The array can be a mix of strings and IDs.',
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

    if ( ! currEndpoint().required ){
      return false
    }

    if ( currEndpoint().repeater ){
      param = param.substring(param.indexOf('.')+1)
    }

    return currEndpoint().required.includes( param )
  }

  const ParamsList = (params, parentParam = '') => Fragment(params.map(({ param, description, required = false, type, ...props }) => {

    if (parentParam) {
      param = `${ parentParam }.${ param }`
    }

    return Div({
      className: 'parameter',
    }, [
      Div({ className: 'display-flex gap-10 align-center' }, [
        `<code class="param">${ escHTML(param) }</code>`,
        `<span class="type">${ escHTML(type) }</span>`,
        isParamRequired( param ) || required ? `<span class="required">${ __('Required', 'groundhogg') }</span>` : null,
      ]),
      description,
      typeof props.default !== 'undefined' && props.default !== null ? `<p>${ sprintf(__('Defaults to %s.'), `<code>${ props.default }</code>`) }</p>` : null,
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
          url = url.replace(`<${ param }>`, params[param])
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

    if (['DELETE', 'PATCH'].includes(METHOD)) {

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
            }, __('Are you sure you want to execute this API call? It will make real changes to the database.', 'groundhogg')),
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

  const ExampleRequest = () => {

    let { url, params, METHOD } = getGeneratedRequest()

    return Div({
      id: 'example-request',
      className: 'display-flex column gap-10',
    }, [
      makeEl('h2', {}, __('Example request')),

      METHOD === 'GET' ?
        // GET
        makeEl('pre', {}, escHTML([
          `curl -X ${ METHOD } ${ url }`,
        ].join('\n')))

        // Other methods
        : makeEl('pre', {}, escHTML([
          `curl -X ${ METHOD } ${ url } \\`,
          `-H 'Content-Type: application/json' \\`,
          Object.keys(params).length ? `-d '${ JSON.stringify(params, null, 2) }'` : '',
        ].join('\n'))),

      Button({
        id: `test-${ State.route }-${ State.endpoint }`,
        className: 'gh-button secondary full-width',
        onClick: sendTestRequest,
      }, __('Test Request')),
    ])
  }

  const ExampleResponse = () => Div({
    id: 'example-response',
    className: 'display-flex column gap-10',
  }, [
    makeEl('h2', {}, __('Example response')),
    makeEl('pre', {}, escHTML(JSON.stringify(State.response ?? currEndpoint().response, null, 2))),
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
      ]),
    ])

  }

  const morphEl = el => morphdom(document.getElementById(el.id), el, {
    // childrenOnly: true,
  })

  const morph = () => morphdom(document.getElementById('api-docs'), Docs(), {
    // childrenOnly: true,
  })

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

    morph()

  })

  $(() => {

    if (location.hash) {
      stateFromHash()
    }

    morph()

    document.getElementById('wpfooter').remove()
  })

  Groundhogg.apiDocs = {
    ApiRegistry,
    CommonParams,
    setInRequest,
    getFromRequest
  }

  ApiRegistry.add('auth', {
    name: __('Authentication'),
    description: () => Fragment([
      Pg({}, __('Groundhogg offers a variety of authentication methods for you to use to access the REST API.', 'groundhogg')),
      Ul({}, [
        Li({}, An({ href: '#auth/apikeys' }, __('Using API keys'))),
        Li({}, An({ href: '#auth/pswd' }, __('Using application passwords'))),
      ]),
      Pg({}, __(
        'The Groundhogg REST API is permission based. Regardless of the authentication method, only users accessing the API with the required permissions will be able to perform requests.',
        'groundhogg')),
    ]),
    endpoints: Groundhogg.createRegistry(),
  })

  // API Keys
  ApiRegistry.auth.endpoints.add('apikeys', {
    name: __('Using API keys', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __('Using API keys is an easy way to get started with the REST API. And is suitable for most backend applications.', 'groundhogg')),
      Pg({}, __('When creating an API key, you are provided with a <b>token</b> and a <b>public key</b>. Both are required to authenticate requests.',
        'groundhogg')),
      Pg({}, __('You must add both to the header of your request.', 'groundhogg')),
      makeEl('pre', {}, escHTML([
        `curl ${ apiRoot }/<endpoint> \\`,
        `\t-H "Gh-Token: <token>" \\`,
        `\t-H "Gh-Public-Key: <public-key>"`,
      ].join('\n'))),
    ]),
  })

  // Application Passwords
  ApiRegistry.auth.endpoints.add('pswd', {
    name: __('Using application passwords', 'groundhogg'),
    description: () => Fragment([
      Pg({}, __('Using application passwords is the <b>BEST WAY</b> to use the Groundhogg REST API from external applications.', 'groundhogg')),
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

} )(jQuery)
