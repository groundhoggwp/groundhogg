/**
 * Edit campaigns in an editor beside the campaigns table
 */
( ($) => {

  const {
    SidebarEditor,
  } = Groundhogg

  const {
    Panel,
    Field,
    CopyField,
  } = SidebarEditor

  const {
    dialog,
    dangerConfirmationModal,
  } = Groundhogg.element

  const { campaigns: CampaignsStore } = Groundhogg.stores
  const { ajax } = Groundhogg.api

  const {
    Div,
    Input,
    Textarea,
    Pg,
    An,
    Dashicon,
  } = MakeEl

  const { __ } = wp.i18n

  /**
   * Post to admin-ajax, throwing the error message when it fails
   */
  const request = async data => {

    let response = await ajax(data)

    if (!response.success) {
      throw new Error(( response.data && response.data.message ) || '')
    }

    return response.data
  }

  // like the slug the add form makes from the name
  const slugify = name => name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')

  const deleteCampaign = item => dangerConfirmationModal({
    alert    : `<p>${ __('Are you sure you want to delete this campaign? Its flows, emails and broadcasts are kept, they just won\'t be in it anymore.', 'groundhogg') }</p>`,
    onConfirm: () => CampaignsStore.delete(item.ID).then(() => {
      dialog({
        message: __('Campaign deleted!', 'groundhogg'),
      })
      editor.removed(item.ID)
    }),
  })

  const editor = SidebarEditor({
    id         : 'campaign',
    label      : __('Edit campaign', 'groundhogg'),
    editLinks  : '.edit-campaign',
    load       : campaign => request({
      action: 'groundhogg_campaign_editor',
      campaign,
    }),
    draft      : item => ( {
      name       : item.name,
      slug       : item.slug,
      description: item.description,
      visibility : item.visibility,
    } ),
    name       : {
      key        : 'name',
      placeholder: __('Campaign name', 'groundhogg'),
    },
    toggle     : {
      key     : 'visibility',
      on      : 'public',
      off     : 'hidden',
      onLabel : __('Public', 'groundhogg'),
      offLabel: __('Hidden', 'groundhogg'),
      title   : __('Anyone can see the archive of a public campaign\'s emails and broadcasts.', 'groundhogg'),
    },
    panels     : ({
      item,
      draft,
      changed,
    }) => [
      Panel({
        id   : 'campaign-details',
        title: __('Details', 'groundhogg'),
      }, [
        Field(__('Slug', 'groundhogg'), Input({
          id         : 'campaign-editor-slug',
          className  : 'full-width',
          value      : draft.slug,
          placeholder: slugify(draft.name),
          onInput    : e => {
            draft.slug = e.target.value
            changed()
          },
        }), __('The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'groundhogg')),
        Field(__('Description', 'groundhogg'), Textarea({
          id       : 'campaign-editor-description',
          className: 'full-width',
          rows     : 4,
          value    : draft.description,
          onInput  : e => {
            draft.description = e.target.value
            changed()
          },
        }), __('The description is not prominent by default; However it may be shown if the campaign archive is public.', 'groundhogg')),
      ]),
      Panel({
        id     : 'campaign-archive',
        title  : __('Archive', 'groundhogg'),
        actions: An({
          className: 'gh-button secondary text icon',
          href     : item.archive_url,
          target   : '_blank',
          title    : __('View archive', 'groundhogg'),
        }, Dashicon('external')),
      }, [
        Field(__('Link', 'groundhogg'), CopyField('campaign-archive-url', item.archive_url),
          item.visibility === 'public'
          ? __('Anyone with this link can see the campaign\'s emails and broadcasts.', 'groundhogg')
          : __('The campaign is hidden, so only you can see its archive. Make it public to share it.', 'groundhogg')),
      ]),
      Panel({
        id   : 'campaign-assets',
        title: __('In this campaign', 'groundhogg'),
      }, [
        Div({
          className: 'display-flex column gap-5',
          onCreate : el => {
            // links from PHP, add-ons add their own
            el.innerHTML = ( item.assets ?? [] ).join('')
          },
        }),
      ]),
    ],
    save       : ({
      item,
      draft,
    }) => request({
      action     : 'groundhogg_save_campaign',
      campaign   : item.ID,
      name       : draft.name,
      slug       : draft.slug,
      description: draft.description,
      public     : draft.visibility === 'public' ? 1 : '',
    }),
    moreActions: item => [
      {
        key     : 'archive',
        text    : __('View archive', 'groundhogg'),
        onSelect: item => window.open(item.archive_url, '_blank'),
      },
      {
        key     : 'delete',
        text    : `<span class="gh-text danger">${ __('Delete', 'groundhogg') }</span>`,
        onSelect: deleteCampaign,
      },
    ],
    messages   : {
      saved     : __('Campaign saved!', 'groundhogg'),
      saveFailed: __('The campaign could not be saved.', 'groundhogg'),
      discard   : __('You have unsaved changes to this campaign. Discard them?', 'groundhogg'),
    },
  })

  Groundhogg.campaignEditor = editor

} )(jQuery)
