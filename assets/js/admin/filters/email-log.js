( () => {

  if (!window.GroundhoggTableFilters) {
    return
  }

  const { FilterRegistry } = GroundhoggTableFilters
  const { createFilter } = Groundhogg.filters

  const {
    contacts: ContactsStore,
  } = Groundhogg.stores
  const { sprintf, __, _x, _n } = wp.i18n

  const {
    Fragment,
    ItemPicker,
  } = MakeEl

  const {
    bold,
    orList,
  } = Groundhogg.element

  FilterRegistry.registerFilter(createFilter('recipients', 'Recipients', 'table', {
    display: ({ recipients = [] }) => {

      if (!recipients.length) {
        return 'Any recipient'
      }

      return sprintf('Recipient is %s', orList(recipients.map(bold)))
    },
    edit: ({ recipients = [], updateFilter }) => Fragment([
      ItemPicker({
        id: `select-recipients`,
        noneSelected: __('Select a recipient...', 'groundhogg'),
        selected: recipients.map(email => ( { id: email, text: email } )),
        multiple: true,
        style: {
          flexGrow: 1,
        },
        fetchOptions: (search) => {
          return ContactsStore.fetchItems({
            search,
          }).then(contacts => contacts.map(({ data: { email } }) => ( { id: email, text: email } )))
        },
        onChange: items => {
          updateFilter({
            recipients: items.map(({ id }) => id),
          })
        },
      }),
    ]),
  }))

} )()
