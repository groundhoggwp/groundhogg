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

  const AuthorPicker = (users, updateFilter) => ItemPicker({
    id: `select-users`,
    noneSelected: __('Select a user...', 'groundhogg'),
    selected: users.map(user_id => ( { id: user_id, text: getOwner(user_id).data.user_email } )),
    multiple: true,
    style: {
      flexGrow: 1,
    },
    fetchOptions: async (search) => {
      search = new RegExp(search, 'i')

      return Groundhogg.authors.map(id => ( { id, text: getOwner(id).data.display_name } )).filter(({ text }) => text.match(search))
    },
    onChange: items => {
      updateFilter({
        users: items.map(({ id }) => id),
      })
    },
  })

  FilterRegistry.registerFilter(createFilter('author', 'Author', 'table', {
    display: ({ users = [] }) => {

      if (!users.length) {
        return 'Any author'
      }

      return sprintf('Author is %s', orList(users.map(user_id => bold(getOwner(user_id).data.display_name))))
    },
    edit: ({ users = [], updateFilter }) => Fragment([
      AuthorPicker(users, updateFilter),
    ]),
  }))

  FilterRegistry.registerFilter(createFilter('step_type', 'Step types', 'table', {
    display: ({ types = [] }) => {

      if (!types.length) {
        throw new Error('You must choose at least 1 step type')
      }

      return sprintf('Has step type %s', orList(types.map(type => bold(Groundhogg.rawStepTypes[type].name))))
    },
    edit: ({ types = [], updateFilter }) => Fragment([
      ItemPicker({
        id: 'select-types',
        selected: types.map(type => ( { id: type, text: Groundhogg.rawStepTypes[type].name } )),
        multiple: true,
        fetchOptions: async (search) => {
          return Object.keys(Groundhogg.rawStepTypes).
            map(type => ( { id: type, text: Groundhogg.rawStepTypes[type].name } )).
            filter(opt => opt.text.match(new RegExp(search, 'i')))
        },
        onChange: items => {
          updateFilter({
            types: items.map( opt => opt.id )
          })
        }
      }),
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
