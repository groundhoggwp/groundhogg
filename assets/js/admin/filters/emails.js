( () => {

  const { createFilter } = Groundhogg.filters

  const {
    funnels: FunnelsStore,
    campaigns: CampaignsStore,
  } = Groundhogg.stores
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    ItemPicker,
  } = MakeEl

  const {
    bold,
    orList,
    andList,
  } = Groundhogg.element

  const {
    getOwner,
  } = Groundhogg.user

  const OwnerPicker = (userIds, updateFilter) => ItemPicker({
    id: `select-users`,
    noneSelected: __('Select a user...', 'groundhogg'),
    selected: userIds.map(user_id => {

      if (user_id == 0) {
        return { id: 0, text: __('The contact owner', 'groundhogg') }
      }

      return { id: user_id, text: getOwner(user_id).data.user_email }
    }),
    multiple: true,
    style: {
      flexGrow: 1,
    },
    isValidSelection: id => id === 0 || getOwner(id),
    fetchOptions: (search) => {
      search = new RegExp(search, 'i')

      let options = [
        ...Groundhogg.filters.owners.map(u => ( { id: u.ID, text: u.data.display_name } )),
        { id: 0, text: __('The contact owner', 'groundhogg') },
      ].filter(({ text }) => text.match(search))

      return Promise.resolve(options)
    },
    onChange: items => {
      updateFilter({
        users: items.map(({ id }) => id),
      })
    },
  })

  const registerEmailFilters = (Registry, group = 'table') => {

    Registry.registerFilter(createFilter('from_user', 'From User', group, {
      display: ({ users = [] }) => {

        if (!users.length) {
          return 'Any user'
        }

        return sprintf('From %s', orList(users.map(user_id => {

          if (user_id == 0) {
            return bold(__('The contact owner', 'groundhogg'))
          }

          return bold(getOwner(user_id).data.user_email)
        })))
      },
      edit: ({ users = [], updateFilter }) => Fragment([
        OwnerPicker(users, updateFilter),
      ]),
    }))

    Registry.registerFilter(createFilter('author', 'Author', group, {
      display: ({ users = [] }) => {

        if (!users.length) {
          return 'Any author'
        }

        return sprintf('Author is %s', orList(users.map(user_id => bold(getOwner(user_id).data.display_name))))
      },
      edit: ({ users = [], updateFilter }) => Fragment([
        OwnerPicker(users, updateFilter),
      ]),
    }))

    Registry.registerFilter(createFilter('funnel', 'Funnel', group, {
      display: ({ funnel_id = false }) => {

        if (!funnel_id) {
          return 'Any funnel'
        }

        return sprintf('In funnel %s', bold( FunnelsStore.get(funnel_id).data.title ) )
      },
      edit: ({ funnel_id = false, updateFilter }) => Fragment([
        ItemPicker({
          id: `select-a-funnel`,
          noneSelected: __('Select a funnel...', 'groundhogg'),
          selected: funnel_id ? { id: funnel_id, text: FunnelsStore.get(funnel_id).data.title } : [],
          multiple: false,
          style: {
            flexGrow: 1,
          },
          fetchOptions: (search) => {
            return FunnelsStore.fetchItems({
              search,
            }).then(funnels => funnels.map(({ ID, data }) => ( { id: ID, text: data.title } )))
          },
          onChange: item => {
            if (!item) {
              updateFilter({
                funnel_id: null,
              })
              return
            }

            updateFilter({
              funnel_id: item.id,
            })
          },
        }),
      ]),
      preload: ({ funnel_id }) => {
        if (funnel_id) {
          return FunnelsStore.maybeFetchItem(funnel_id)
        }
      },
    }))


    Registry.registerFilter(createFilter('campaigns', 'Campaigns', group, {
      display: ({ campaigns = [] }) => {

        if (!campaigns.length) {
          return 'Any campaign'
        }

        return sprintf('Campaigns are %s', andList(campaigns.map(id => bold(CampaignsStore.get(id).data.name))))
      },
      edit: ({ campaigns = [], updateFilter }) => Fragment([
        ItemPicker({
          id: `select-campaigns`,
          noneSelected: __('Select campaigns...', 'groundhogg'),
          selected: campaigns.map(id => ( { id, text: CampaignsStore.get(id).data.name } )),
          multiple: true,
          style: {
            flexGrow: 1,
          },
          fetchOptions: async (search) => {
            let items = await CampaignsStore.fetchItems({
              search,
            })

            return items.map(({ ID: id, data: { name } }) => ( { id, text: name } ))
          },
          onChange: items => {
            updateFilter({
              campaigns: items.map(({ id }) => id),
            })
          },
        }),
      ]),
      preload: ({ campaigns = [] }) => {
        if (campaigns && campaigns.length) {
          return CampaignsStore.maybeFetchItems(campaigns)
        }
      },
    }))
  }

  if ( window.GroundhoggTableFilters ){
    registerEmailFilters( GroundhoggTableFilters.FilterRegistry )
  }

  Groundhogg.filters.registerEmailFilters = registerEmailFilters
})()
