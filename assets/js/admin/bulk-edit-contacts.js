(($) => {

  const { propertiesEditor } = Groundhogg
  const { createFilters } = Groundhogg.filters.functions
  const {
    searchOptionsWidget,
    regexp,
    specialChars,
    modal,
    input,
    loadingDots,
    copyObject,
    objectEquals,
    toggle,
    moreMenu,
    select,
    dangerConfirmationModal,
    confirmationModal,
    clickInsideElement,
    progressBar,
    dialog,
    bold,
    tooltip,
    adminPageURL
  } = Groundhogg.element
  const {
    betterTagPicker
  } = Groundhogg.components
  const { post, get, patch, routes, ajax } = Groundhogg.api
  const { searches: SearchesStore, contacts: ContactsStore, tags: TagsStore, funnels: FunnelsStore } = Groundhogg.stores
  const { tagPicker, funnelPicker } = Groundhogg.pickers
  const { userHasCap } = Groundhogg.user
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { sprintf, __, _x, _n } = wp.i18n

  const fieldSection = ({
    title = '',
    fields = ''
  }) => {

    // language=HTML
    return `
		<div class="gh-panel">
			<div class="gh-panel-header">
				<h2>${title}</h2>
				<button type="button" class="toggle-indicator" aria-expanded="true"></button>
			</div>
			<div class="inside">
				${fields}
			</div>
		</div>`

  }

  let sections = [

    {
      title: __('General', 'groundhogg'),
      // language=HTML
      fields: `
		  <div class="gh-rows-and-columns">
			  <div class="gh-row">
				  <div class="gh-col">
					  <label for="email">${__('Optin Status', 'groundhogg')}</label>
					  ${select({
						  id: `optin-status`,
						  name: 'optin_status'
					  }, Groundhogg.filters.optin_status)}
				  </div>
				  <div class="gh-col">
					  <label for="owner">${__('Owner', 'noun the contact owner', 'groundhogg')}</label>
					  ${select({
						  id: `owner`,
						  name: 'owner_id'
					  }, Groundhogg.filters.owners.map(u => ({
						  text: u.data.user_email,
						  value: u.ID
					  })))}
				  </div>
			  </div>
		  </div>`,
      onMount: () => {

        //

      }
    },
    {
      title: '<span class=" dashicons dashicons-tag"></span>' + __('Apply Tags', 'groundhogg'),
      // language=HTML
      fields: `
		  <div id="apply-tags"></div>`,
      onMount: () => {
        betterTagPicker('#apply-tags', {
          onChange: ({ addTags }) => {
            console.log(addTags)
          }
        })
      }
    },
    {
      title: '<span class=" dashicons dashicons-tag"></span>' + __('Remove Tags', 'groundhogg'),
      // language=HTML
      fields: `
		  <div id="remove-tags"></div>`,
      onMount: ({}) => {
        betterTagPicker('#remove-tags', {
          onChange: ({ addTags }) => {
            console.log(addTags)
          }
        })
      }
    }

  ]

  BulkEdit.gh_contact_custom_properties.tabs.forEach(t => {

    // Groups belonging to this tab
    let groups = BulkEdit.gh_contact_custom_properties.groups.filter(g => g.tab === t.id)
    // Fields belonging to the groups of this tab
    let fields = BulkEdit.gh_contact_custom_properties.fields.filter(f => groups.find(g => g.id === f.group))

    sections.push({
      title: t.name,
      fields: `<div id="${t.id}"></div>`,
      onMount: ({ updateMeta }) => {
        propertiesEditor(`#${t.id}`, {
          values: {},
          properties: {
            groups,
            fields
          },
          onChange: (meta) => {
            updateMeta(meta)
          },
          canEdit: () => false

        })
      }
    })

  })

  $(() => {

    let data, meta, add_tags, remove_tags

    const updateData = (_data) => {
      data = {
        ...data,
        ..._data
      }
    }

    const updateMeta = (_meta) => {
      meta = {
        ...meta,
        ..._meta
      }
    }

    $('#bulk-edit').html(sections.map(({ title, fields }) => fieldSection({ title, fields })))
    sections.forEach(s => s.onMount({ updateData, updateMeta }))

  })

})(jQuery)