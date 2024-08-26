( () => {

  if (!window.GroundhoggTableFilters) {
    console.log('here')
    return
  }

  const { FilterRegistry } = GroundhoggTableFilters
  const {
    createFilter,
    ContactFilters,
    ContactFilterDisplay,
  } = Groundhogg.filters

  const {
    funnels   : FunnelsStore,
    broadcasts: BroadcastsStore,
    contacts  : ContactsStore,
  } = Groundhogg.stores
  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const {
    Fragment,
    ItemPicker,
    Div,
  } = MakeEl

  const {
    bold,
    orList,
  } = Groundhogg.element

  const broadcastTitle = ({
    date_sent_pretty,
    object: { data: { title } },
  }) => `${ title } ${ date_sent_pretty }`

  const params = new URLSearchParams(location.search.substring(1))

  FilterRegistry.registerFilter(createFilter('broadcast', 'Broadcast', 'table', {
    display: ({ broadcast_id = false }) => {

      if (!broadcast_id) {
        return 'Any Broadcast'
      }

      return sprintf('Broadcast is %s', bold(broadcastTitle(BroadcastsStore.get(broadcast_id))))
    },
    edit   : ({
      broadcast_id = false,
      updateFilter,
    }) => Fragment([
      ItemPicker({
        id          : `select-a-broadcast`,
        noneSelected: __('Select a broadcast...', 'groundhogg'),
        selected    : broadcast_id ? {
          id  : broadcast_id,
          text: broadcastTitle(BroadcastsStore.get(broadcast_id)),
        } : [],
        multiple    : false,
        style       : {
          flexGrow: 1,
        },
        fetchOptions: (search) => {
          return BroadcastsStore.fetchItems({
              search,
              status: [
                        'complete',
                        'cancelled',
                        'failed',
                        'skipped',
                      ].includes(params.get('status')) ? 'sent' : 'scheduled',
            }).
            then(broadcasts => broadcasts.map(broadcast => ( {
              id  : broadcast.ID,
              text: broadcastTitle(broadcast),
            } )))
        },
        onChange    : item => {
          if (!item) {
            updateFilter({
              broadcast_id: null,
            })
            return
          }

          updateFilter({
            broadcast_id: item.id,
          })
        },
      }),
    ]),
    preload: ({ broadcast_id }) => {
      if (broadcast_id) {
        return BroadcastsStore.maybeFetchItem(broadcast_id)
      }
    },
  }))

  FilterRegistry.registerFilter(createFilter('funnel', 'Funnel', 'table', {
    display: ({
      funnel_id = false,
      step_id = false,
    }) => {

      if (!funnel_id) {
        return 'Any funnel'
      }

      if (funnel_id && !step_id) {
        return sprintf('Funnel is %s', FunnelsStore.get(funnel_id).data.title)
      }

      return sprintf('Funnel is %s and step is %s', bold(FunnelsStore.get(funnel_id).data.title),
        bold(FunnelsStore.get(funnel_id).steps.find(s => s.ID == step_id).data.step_title))
    },
    edit   : ({
      funnel_id = false,
      step_id = false,
      updateFilter,
    }) => Fragment([
      ItemPicker({
        id          : `select-a-funnel`,
        noneSelected: __('Select a funnel...', 'groundhogg'),
        selected    : funnel_id ? {
          id  : funnel_id,
          text: FunnelsStore.get(funnel_id).data.title,
        } : [],
        multiple    : false,
        style       : {
          flexGrow: 1,
        },
        fetchOptions: (search) => {
          return FunnelsStore.fetchItems({
              search,
            }).
            then(funnels => funnels.map(({
              ID,
              data,
            }) => ( {
              id  : ID,
              text: data.title,
            } )))
        },
        onChange    : item => {
          if (!item) {
            updateFilter({
              funnel_id: null,
              step_id  : null,
            }, true)
            return
          }

          updateFilter({
            funnel_id: item.id,
            step_id  : FunnelsStore.get(item.id).steps[0].ID,
          }, true)
        },
      }),
      funnel_id ? ItemPicker({
        id          : `select-step-from-${ funnel_id }`,
        noneSelected: __('Select a step...', 'groundhogg'),
        selected    : step_id ? {
          id  : step_id,
          text: FunnelsStore.get(funnel_id).steps.find(s => s.ID === step_id).data.step_title,
        } : [],
        multiple    : false,
        style       : {
          flexGrow: 1,
        },
        fetchOptions: async (search) => FunnelsStore.get(funnel_id).
          steps.
          map(({
            ID,
            data,
          }) => ( {
            id  : ID,
            text: data.step_title,
          } )).
          filter(opt => opt.text.match(new RegExp(search, 'i'))),
        onChange    : item => {
          if (!item) {
            updateFilter({
              step_id: null,
            })
            return
          }

          updateFilter({
            step_id: item.id,
          })
        },
      }) : null,
    ]),
    preload: ({ funnel_id }) => {
      if (funnel_id) {
        return FunnelsStore.maybeFetchItem(funnel_id)
      }
    },
  }))

  FilterRegistry.registerFilter(createFilter('contacts', 'Contacts', 'table', {
    display: ({ contacts = [] }) => {

      if (!contacts.length) {
        return 'Any contact'
      }

      return sprintf('Contact is %s', orList(contacts.map(id => bold(ContactsStore.get(id).data.email))))
    },
    edit   : ({
      contacts = [],
      updateFilter,
    }) => Fragment([
      ItemPicker({
        id          : `select-a-contact`,
        noneSelected: __('Select a contact...', 'groundhogg'),
        selected    : contacts.map(id => ( {
          id,
          text: ContactsStore.get(id).data.email,
        } )),
        multiple    : true,
        style       : {
          flexGrow: 1,
        },
        fetchOptions: (search) => {
          return ContactsStore.fetchItems({
              search,
            }).
            then(contacts => contacts.map(({
              ID,
              data: { email },
            }) => ( {
              id  : ID,
              text: email,
            } )))
        },
        onChange    : items => {
          updateFilter({
            contacts: items.map(({ id }) => id),
          })
        },
      }),
    ]),
    preload: ({ contacts = [] }) => {
      if (contacts.length) {
        return ContactsStore.maybeFetchItems(contacts)
      }
    },
  }))

  // console.log('here')

  FilterRegistry.registerFilter(createFilter('contact_query', 'Contact Query', 'table', {
    display: ({
      include_filters = [],
      exclude_filters = [],
    }) => {

      let include = ContactFilterDisplay(include_filters)
      let exclude = ContactFilterDisplay(exclude_filters)

      if (include_filters.length && exclude_filters.length) {
        return Fragment([
          include,
          ' <abbr title="exclude">and exclude</abbr> ',
          exclude,
        ])
      }

      if (exclude_filters.length) {
        return Fragment([
          ' <abbr title="exclude">Exclude</abbr> ',
          exclude,
        ])
      }

      if (include_filters.length) {
        return include
      }

      throw new Error('No filters defined.')

    },
    edit   : ({
      include_filters = [],
      exclude_filters = [],
      updateFilter,
    }) => {

      return Fragment([
        Div({
          className: 'include-search-filters',
        }, [
          ContactFilters('sub-query-filters', include_filters, include_filters => updateFilter({
            include_filters,
          })),
        ]),
        Div({
          className: 'exclude-search-filters',
        }, [
          ContactFilters('sub-query-exclude-filters', exclude_filters, exclude_filters => updateFilter({
            exclude_filters,
          })),
        ]),
      ])
    },
    preload: ({
      include_filters = [],
      exclude_filters = [],
    }) => {
      return Promise.all([
        // ContactFilterRegistry.preloadFilters(include_filters),
        // ContactFilterRegistry.preloadFilters(exclude_filters),
      ])
    },
  }, {}))

} )()
