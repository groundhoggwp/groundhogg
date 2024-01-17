( () => {

  if (!window.GroundhoggTableFilters) {
    console.log('here')
    return
  }

  const { FilterRegistry } = GroundhoggTableFilters
  const { createFilter } = Groundhogg.filters

  const {
    contacts: ContactsStore,
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

  const OwnerPicker = (users, updateFilter) => ItemPicker({
    id: `select-users`,
    noneSelected: __('Select a user...', 'groundhogg'),
    selected: users.map(user_id => ( { id: user_id, text: getOwner(user_id).data.user_email } )),
    multiple: true,
    style: {
      flexGrow: 1,
    },
    fetchOptions: (search) => {
      search = new RegExp(search, 'i')

      let options = [
        ...Groundhogg.filters.owners.map(u => ( { id: u.ID, text: u.data.display_name } )),
        { id: 0, text: 'The contact owner' },
      ].filter(({ text }) => text.match(search))

      return Promise.resolve(options)
    },
    onChange: items => {
      updateFilter({
        users: items.map(({ id }) => id),
      })
    },
  })

  FilterRegistry.registerFilter(createFilter('from_user', 'From User', 'table', {
    display: ({ users = [] }) => {

      if (!users.length) {
        return 'Any user'
      }

      return sprintf('From %s', orList(users.map(user_id => bold(getOwner(user_id).data.user_email))))
    },
    edit: ({ users = [], updateFilter }) => Fragment([
      OwnerPicker(users, updateFilter),
    ]),
  }))

  FilterRegistry.registerFilter(createFilter('author', 'Author', 'table', {
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

  FilterRegistry.registerFilter(createFilter('campaigns', 'Campaigns', 'table', {
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

} )()
