( $ => {

  const {
    modal,
    errorDialog,
    loadingDots,
    select,
    uuid,
    addMediaToBasicTinyMCE,
    specialChars,
    tinymceElement,
    searchOptionsWidget,
    input,
    isNumeric,
    icons,
    dialog,
    tooltip,
    regexp,
    isValidEmail,
    loadingModal,
    textarea,
    spinner,
    skeleton,
    adminPageURL,
  } = Groundhogg.element

  const {
    Div,
    H2,
    H4,
    Toggle,
    Img,
    An,
    Span,
    ModalFrame,
    Iframe,
    makeEl,
    Button,
    Modal,
    ModalWithHeader,
    Dashicon,
    ToolTip,
    Input,
    TinyMCE,
    Label,
    Fragment,
    ItemPicker,
    Skeleton,
    Pg,
    Form,
    Textarea,
    InputGroup,
  } = MakeEl

  const {
    contacts: ContactsStore,
    tags    : TagsStore,
    forms   : FormsStore,
    emails  : EmailsStore,
  } = Groundhogg.stores

  const {
    post,
    routes,
    postFormData,
  } = Groundhogg.api
  const { tagPicker } = Groundhogg.pickers

  const {
    userHasCap,
    getOwner,
  } = Groundhogg.user

  const {
    sprintf,
    __,
    _x,
    _n,
  } = wp.i18n

  const {
    formatDateTime,
  } = Groundhogg.formatting

  const { currentUser } = Groundhogg

  const {
    maybeCall,
    debounce,
    jsonCopy,
  } = Groundhogg.functions

  const selectContactModal = ({
    onSelect = () => {},
    onClose = () => {},
    exclude = [],
  }) => {

    Modal({
        dialogClasses: 'no-padding',
        width        : '400px',
        onOpen       : e => {
          document.getElementById('quick-search-input').focus()
        },

      },
      ({ close }) => QuickSearch({
        itemProps     : contact => ( {
          onClick: e => {
            onSelect(contact)
            close()
          },
        } ),
        queryOverrides: {
          limit: 15,
          exclude,
        },
      }),
    )
  }

  const betterTagPicker = (el, {
    selected = [],
    removeTags = [],
    addTags = [],
    onChange = (changes) => {

    },
  }) => {

    const $el = $(el)

    let timeout

    const template = () => {
      // language=HTML
      return `
          <div class="gh-tags">
              ${ selected.map(tag => `<span class="gh-tag${ removeTags.includes(tag.ID)
                                                            ? ' remove'
                                                            : '' }">${ tag.data.tag_name } <span data-id="${ tag.ID }" class="remove-tag dashicons dashicons-no-alt"></span></span>`).
                      join('') }
              ${ addTags.map(id => TagsStore.get(id)).
                      map(tag => `<span class="gh-tag adding">${ tag.data.tag_name } <span data-id="${ tag.ID }" class="remove-adding-tag dashicons dashicons-no-alt"></span></span>`).
                      join('') }
              <button class="add-tag">
                  <span class="dashicons dashicons-plus-alt2"></span>
              </button>
          </div>`
    }

    const mount = () => {
      $el.html(template())
      onMount()
    }

    const informChanges = () => {
      onChange({
        removeTags,
        addTags,
      })
    }

    const onMount = () => {

      tooltip($el.find('.add-tag'), {
        content: __('Add a tag', 'groundhogg'),
      })

      $el.find('.gh-tag .remove-tag').on('click', (e) => {
        let tagId = parseInt(e.currentTarget.dataset.id)

        if (removeTags.includes(tagId)) {
          removeTags.splice(removeTags.indexOf(tagId), 1)
        }
        else {
          removeTags.push(tagId)
        }

        informChanges()

        mount()
      })

      $el.find('.gh-tag .remove-adding-tag').on('click', (e) => {
        let tagId = parseInt(e.currentTarget.dataset.id)

        if (addTags.includes(tagId)) {
          addTags.splice(addTags.indexOf(tagId), 1)
        }

        informChanges()

        mount()
      })

      $el.find('.add-tag').on('click', (e) => {

        const filterTags = (tags) => tags.filter(t => !selected.map(_t => _t.ID).includes(t.ID) && !addTags.includes(t.ID)).sort((a, b) => b.ID - a.ID)

        let initialOptions = filterTags(TagsStore.getItems())

        searchOptionsWidget({
          target       : e.currentTarget,
          position     : 'fixed',
          noOptions    : __('No tags found...', 'groundhogg'),
          options      : initialOptions,
          filterOption : ({ data }, search) => data.tag_name.match(regexp(search)),
          filterOptions: (opts, search) => {
            if (!search) {
              return opts
            }

            if (userHasCap('add_tags')) {
              opts.unshift({
                ID  : search,
                data: {
                  tag_name: sprintf(__('Add "%s"', 'groundhogg'), search),
                },
              })
            }

            return opts
          },
          renderOption : ({ data }) => data.tag_name,
          onClose      : () => {
            mount()
          },
          onInput      : (search, widget) => {

            if (timeout) {
              clearTimeout(timeout)
            }

            timeout = setTimeout(() => {
              TagsStore.fetchItems({
                search,
              }).then(() => {
                widget.options = filterTags(TagsStore.getItems())
                widget.mountOptions()
              })
            }, 1500)

          },
          onSelect     : (tag) => {

            let { ID } = tag

            // Created a new tag
            if (!isNumeric(ID)) {
              TagsStore.post({
                data: {
                  tag_name: ID,
                },
              }).then(t => {
                addTags.push(t.ID)
                informChanges()
                mount()
              })
              return
            }

            addTags.push(ID)
            informChanges()
          },
          onOpen       : (widget) => {

            if (!initialOptions.length) {
              TagsStore.fetchItems().then(() => {
                widget.options = filterTags(TagsStore.getItems())
                widget.mountOptions()
              })
            }

          },
        }).mount()
      })
    }

    TagsStore.itemsFetched(selected)

    mount()
  }

  const quickEditContactModal = ({
    contact,
    prefix = 'quick-edit',
    onEdit = (contact) => {},
    additionalFields = () => '',
    additionalFieldsOnMount = () => {},
  }) => {

    if (contact && contact.tags) {
      TagsStore.itemsFetched(contact.tags)
    }

    const getContact = () => {
      return ContactsStore.get(contact.ID)
    }

    const quickEdit = (contact) => {

      // language=HTML
      return `
          <div class="contact-quick-edit" tabindex="0">
              <div class="gh-header space-between">
                  <div class="align-left-space-between">
                      <img class="border-radius-5" height="40" width="40" src="${ contact.data.gravatar }" alt="avatar"/>
                      <h3 class="contact-name">
                          ${ specialChars(`${ contact.data.first_name } ${ contact.data.last_name }`) }</h3>
                  </div>
                  <div class="actions align-right-space-between">
                      <a class="gh-button secondary"
                         href="${ contact.admin }">${ __('Edit Full Profile', 'groundhogg') }</a>
                      <button class="gh-button dashicon no-border icon text ${ prefix }-cancel"><span
                              class="dashicons dashicons-no-alt"></span></button>
                  </div>
              </div>
              <div class="contact-quick-edit-fields gh-rows-and-columns">
                  <div class="gh-row">
                      <div class="gh-col">
                          <label for="${ prefix }-first-name">${ __('First Name', 'groundhogg') }</label>
                          ${ input({
                              id   : `${ prefix }-first-name`,
                              name : 'first_name',
                              value: contact.data.first_name,
                          }) }
                      </div>
                      <div class="gh-col">
                          <label for="${ prefix }-last-name">${ __('Last Name', 'groundhogg') }</label>
                          ${ input({
                              id   : `${ prefix }-last-name`,
                              name : 'last_name',
                              value: contact.data.last_name,
                          }) }
                      </div>
                  </div>
                  <div class="gh-row">
                      <div class="gh-col">
                          <label for="${ prefix }-email">${ __('Email Address', 'groundhogg') }</label>
                          ${ input({
                              type : 'email',
                              name : 'email',
                              id   : `${ prefix }-email`,
                              value: contact.data.email,
                          }) }
                      </div>
                      <div class="gh-col">
                          <div class="gh-row phone">
                              <div class="gh-col">
                                  <label for="${ prefix }-primary-phone">${ __('Primary Phone', 'groundhogg') }</label>
                                  ${ input({
                                      type : 'tel',
                                      id   : `${ prefix }-primary-phone`,
                                      name : 'primary_phone',
                                      value: contact.meta.primary_phone,
                                  }) }
                              </div>
                              <div class="primary-phone-ext">
                                  <label
                                          for="${ prefix }-primary-phone-extension">${ _x('Ext.',
                                          'phone number extension', 'groundhogg') }</label>
                                  ${ input({
                                      type : 'number',
                                      id   : `${ prefix }-primary-phone-extension`,
                                      name : 'primary_phone_extension',
                                      value: contact.meta.primary_phone_extension,
                                  }) }
                              </div>
                          </div>
                      </div>
                  </div>
                  <div class="gh-row">
                      <div class="gh-col">
                          <label for="${ prefix }-email">${ __('Opt-in Status', 'groundhogg') }</label>
                          ${ select({
                              id  : `${ prefix }-optin-status`,
                              name: 'optin_status',
                          }, Groundhogg.filters.optin_status, contact.data.optin_status) }
                      </div>
                      <div class="gh-col">
                          <label for="${ prefix }-mobile-phone">${ __('Mobile Phone', 'groundhogg') }</label>
                          ${ input({
                              type : 'tel',
                              id   : `${ prefix }-mobile-phone`,
                              name : 'mobile_phone',
                              value: contact.meta.mobile_phone,
                          }) }
                      </div>
                  </div>
                  <div class="gh-row">
                      <div class="gh-col">
                          <label for="${ prefix }-owner">${ __('Owner', 'noun the contact owner',
                                  'groundhogg') }</label>
                          ${ select({
                              id  : `${ prefix }-owner`,
                              name: 'owner_id',
                          }, Groundhogg.filters.owners.map(u => ( {
                              text : u.data.user_email,
                              value: u.ID,
                          } )), contact.data.owner_id) }
                      </div>
                      <div class="gh-col"></div>
                  </div>
                  <div class="gh-row">
                      <div class="gh-col">
                          <label for="${ prefix }-tags">${ __('Tags', 'groundhogg') }</label>
                          <div id="${ prefix }-tags-here"></div>
                      </div>
                  </div>
                  ${ additionalFields({
                      prefix,
                      contact,
                  }) }
              </div>
              <div class="align-right-space-between" style="margin-top: 20px">
                  <button class="gh-button text danger ${ prefix }-cancel">${ __('Cancel', 'groundhogg') }</button>
                  <button class="gh-button primary" id="${ prefix }-save">${ __('Save Changes', 'groundhogg') }</button>
              </div>
          </div>`
    }

    const quickEditMounted = ({
      close,
      setContent,
    }) => {

      let payload

      const clearPayload = () => {
        payload = {
          data       : {},
          meta       : {},
          add_tags   : [],
          remove_tags: [],
        }
      }

      clearPayload()

      const updateContact = (data) => {

        payload = {
          ...data,
          data: {
            ...payload.data,
            ...data.data,
          },
          meta: {
            ...payload.meta,
            ...data.meta,
          },
        }
      }

      const $quickEdit = $('.contact-quick-edit')

      $quickEdit.focus()

      $(`#${ prefix }-save`).on('click', (e) => {

        const $btn = $(e.target)

        $btn.prop('disabled', true)
        $btn.text(__('Saving', 'groundhogg'))
        const { stop } = loadingDots(`#${ prefix }-save`)

        ContactsStore.patch(contact.ID, payload).then(c => {
          stop()
          clearPayload()
          onEdit(c)

          setContent(quickEdit(getContact()))
          quickEditMounted({
            close,
            setContent,
          })
        }).catch(e => {

          stop()
          clearPayload()
          setContent(quickEdit(getContact()))
          quickEditMounted({
            close,
            setContent,
          })

          console.log(e)

          dialog({
            type   : 'error',
            message: e.message,
          })
        })

      })

      $(`.${ prefix }-cancel`).on('click', (e) => {
        clearPayload()
        close()
      })

      betterTagPicker(`#${ prefix }-tags-here`, {
        selected: getContact().tags,
        onChange: ({
          addTags,
          removeTags,
        }) => {
          updateContact({
            add_tags   : addTags,
            remove_tags: removeTags,
          })
        },
      })

      $(`#${ prefix }-first-name, #${ prefix }-last-name, #${ prefix }-email, #${ prefix }-optin-status, #${ prefix }-owner`).
        on('change', (e) => {
          updateContact({
            data: {
              [e.target.name]: e.target.value,
            },
          })
        })

      $(`#${ prefix }-primary-phone, #${ prefix }-primary-phone-extension, #${ prefix }-mobile-phone`).
        on('change', (e) => {
          updateContact({
            meta: {
              [e.target.name]: e.target.value,
            },
          })
        })

      additionalFieldsOnMount({
        prefix,
        contact,
        setPayload: updateContact,
        getPayload: () => payload,
      })
    }

    const {
      close,
      setContent,
    } = modal({
      // dialogClasses: 'overflow-visible',
      content: quickEdit(getContact()),
      onOpen : quickEditMounted,
    })
  }

  const quickAddForm = (selector, {
    prefix = 'quick-add',
    onCreate = () => {},
    additionalFields = ({ prefix }) => '',
    additionalFieldsOnMount = () => {},
  }) => {

    const quickAddForm = () => {
      //language=HTML
      return `
          <div class="gh-rows-and-columns">
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="${ prefix }-first-name">${ __('First Name', 'groundhogg') }</label>
                      ${ input({
                          id         : `${ prefix }-first-name`,
                          name       : 'first_name',
                          placeholder: 'John',
                      }) }
                  </div>
                  <div class="gh-col">
                      <label for="${ prefix }-last-name">${ __('Last Name', 'groundhogg') }</label>
                      ${ input({
                          id         : `${ prefix }-last-name`,
                          name       : 'last_name',
                          placeholder: 'Doe',
                      }) }
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="${ prefix }-email">${ __('Email Address', 'groundhogg') }</label>
                      ${ input({
                          id         : `${ prefix }-email`,
                          name       : 'email',
                          placeholder: 'john@example.com',
                          required   : true,
                      }) }
                  </div>
                  <div class="gh-col">
                      <div class="gh-row phone">
                          <div class="gh-col">
                              <label for="${ prefix }-primary-phone">${ __('Primary Phone', 'groundhogg') }</label>
                              ${ input({
                                  type: 'tel',
                                  id  : `${ prefix }-primary-phone`,
                                  name: 'primary_phone',
                              }) }
                          </div>
                          <div class="primary-phone-ext">
                              <label
                                      for="${ prefix }-primary-phone-extension">${ _x('Ext.', 'phone number extension',
                                      'groundhogg') }</label>
                              ${ input({
                                  type: 'number',
                                  id  : `${ prefix }-primary-phone-ext`,
                                  name: 'primary_phone_extension',
                              }) }
                          </div>
                      </div>
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="${ prefix }-email">${ __('Opt-in Status', 'groundhogg') }</label>
                      ${ select({
                          id  : `${ prefix }-optin-status`,
                          name: 'optin_status',
                      }, Groundhogg.filters.optin_status) }
                  </div>
                  <div class="gh-col">
                      <label for="${ prefix }-mobile-phone">${ __('Mobile Phone', 'groundhogg') }</label>
                      ${ input({
                          type: 'tel',
                          id  : `${ prefix }-mobile-phone`,
                          name: 'mobile_phone',
                      }) }
                  </div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="${ prefix }-owner">${ __('Owner', 'noun the contact owner', 'groundhogg') }</label>
                      ${ select({
                          id      : `${ prefix }-owner`,
                          name    : 'owner_id',
                          options : Groundhogg.filters.owners.map(u => ( {
                              text : `${ u.data.display_name } &lt;${ u.data.user_email }&gt;`,
                              value: u.ID,
                          } )),
                          selected: Groundhogg.user.getCurrentUser().ID,
                      }) }
                  </div>
                  <div class="gh-col"></div>
              </div>
              <div class="gh-row">
                  <div class="gh-col">
                      <label for="${ prefix }-tags">${ __('Tags', 'groundhogg') }</label>
                      <div id="${ prefix }-tags-here"></div>
                  </div>
              </div>
              ${ additionalFields({ prefix }) }
              <div class="gh-row">
                  <div class="gh-col">
                      <div>
                          <label
                                  for="${ prefix }-terms">${ input({
                              id   : `${ prefix }-terms`,
                              type : 'checkbox',
                              name : 'terms_agreement',
                              value: 'yes',
                          }) }
                              ${ __('Agreed to the terms and conditions?', 'groundhogg') }</label>
                      </div>
                      <div>
                          <label
                                  for="${ prefix }-data-consent">${ input({
                              id   : `${ prefix }-data-consent`,
                              type : 'checkbox',
                              name : 'data_consent',
                              value: 'yes',
                          }) }
                              ${ __('Agreed to data processing and storage? (GDPR)', 'groundhogg') }</label>
                      </div>
                      <div>
                          <label
                                  for="${ prefix }-marketing-consent">${ input({
                              id   : `${ prefix }-marketing-consent`,
                              type : 'checkbox',
                              name : 'marketing_consent',
                              value: 'yes',
                          }) }
                              ${ __('Agreed to receive marketing? (GDPR)', 'groundhogg') }</label>
                      </div>
                  </div>
              </div>
              <div class="align-right-space-between">
                  <button id="${ prefix }-create" class="gh-button primary">
                      ${ __('Create Contact', 'groundhogg') }
                  </button>
              </div>
          </div>`
    }

    $(selector).html(quickAddForm())

    let _payload = {
      data: {
        owner_id: currentUser.ID,
      },
      meta: {},
    }

    const getPayload = () => {
      return _payload
    }

    const setPayload = (data) => {
      _payload = {
        ..._payload,
        ...data,
      }
    }

    $(`#${ prefix }-create`).on('click', ({ target }) => {

      if (!_payload.data.email || !isValidEmail(_payload.data.email)) {
        errorDialog({
          message: __('A valid email is required!', 'groundhogg'),
        })
        return
      }

      $(target).prop('disabled', true)
      const { stop } = loadingDots(`#${ prefix }-quick-add-button`)
      ContactsStore.post(_payload).then(c => {
        stop()
        onCreate(c)
      })
    })

    $(`
    #${ prefix }-first-name,
    #${ prefix }-last-name,
    #${ prefix }-owner,
    #${ prefix }-optin-status,
    #${ prefix }-email`).on('change input', ({ target }) => {
      setPayload({
        data: {
          ..._payload.data,
          [target.name]: target.value,
        },
      })
    })

    $(`
    #${ prefix }-primary-phone,
    #${ prefix }-primary-phone-ext,
    #${ prefix }-mobile-phone`).on('change input', ({ target }) => {
      setPayload({
        meta: {
          ..._payload.meta,
          [target.name]: target.value,
        },
      })
    })

    $(`#${ prefix }-owner`).select2()

    $(`
    #${ prefix }-terms,
    #${ prefix }-data-consent,
    #${ prefix }-marketing-consent`).on('change', ({ target }) => {
      setPayload({
        meta: {
          ..._payload.meta,
          [target.name]: target.checked,
        },
      })
    })

    betterTagPicker(`   #${ prefix }-tags-here`, {
      selected: [],
      onChange: ({ addTags }) => {
        setPayload({
          tags: addTags,
        })
      },
    })

    additionalFieldsOnMount({
      prefix,
      setPayload,
      getPayload,
    })

  }

  const internalForm = ({
    contact = false,
    onSubmit = () => {},
  }) => {

    let selectedForm

    const ui = () => {
      //language=HTML
      return `
          <div class="gh-header">
              <div class="display-flex gap-20 full-width">
                  ${ select({
                      id  : `select-form`,
                      name: 'select_form',
                  }) }
                  <button id="cancel" class="gh-button secondary text icon"><span
                          class="dashicons dashicons-no-alt"></span></button>
              </div>
          </div>
          <div class="form-wrap">
              ${ selectedForm ? selectedForm.rendered : `<p>${ __('Select a form using the dropdown',
                      'groundhogg') }</p>` }
          </div>`
    }

    return modal({
      width        : 500,
      content      : ui(),
      dialogClasses: 'internal-form-wrap',
      onOpen       : ({
        setContent,
        close,
      }) => {

        const reMount = () => {
          setContent(ui())
          onMount()
        }

        const onMount = () => {
          $('#cancel').on('click', () => close())
          $(`#select-form`).ghPicker({
            endpoint   : FormsStore.route,
            width      : '100%',
            placeholder: __('Type to select a form...', 'groundhogg'),
            data       : [
              {
                id  : '',
                text: '',
              },
              ...FormsStore.getItems().map(f => ( {
                id      : f.ID,
                text    : f.name,
                selected: selectedForm && f.ID == selectedForm.ID,
              } )),
            ],
            getParams  : (q) => ( {
              ...q,
              search : q.term,
              active : true,
              contact: contact.ID,
            } ),
            getResults : ({ items }) => {
              FormsStore.itemsFetched(items)
              return items.map(f => ( {
                id  : f.ID,
                text: f.name,
              } ))
            },
          }).on('select2:select', (e) => {
            selectedForm = FormsStore.get(e.params.data.id)
            reMount()
          })

          if (selectedForm) {
            $('.internal-form-wrap form.gh-form').on('submit', (e) => {

              e.preventDefault()

              const $form = $(e.currentTarget)

              handleInternalFormSubmit(selectedForm.ID, $form, c => {
                close()
                onSubmit(c)
              })

            })
          }
        }

        onMount()
      },
    })

  }

  const handleInternalFormSubmit = (formId, $form, onSubmit) => {

    let $btn = $form.find('.gh-submit')
    let origTxt = $btn.text()

    $btn.prop('disabled', true)
    $btn.text(__('Submitting', 'groundhogg'))
    const { stop } = loadingDots($btn)
    var data = new FormData($form[0])

    if ($form.is('.gh-form-v2')) {

      postFormData(`${ FormsStore.route }/${ formId }/admin`, data).then(r => {

        $btn.prop('disabled', false)
        $btn.text(origTxt)

        if (r.status && r.status === 'success') {
          dialog({
            message: __('Form submitted!'),
          })

          ContactsStore.itemsFetched([
            r.contact,
          ])

          onSubmit(r.contact)
          return
        }

        dialog({
          message: r.additional_errors[0].message,
          type   : 'error',
        })

      })

    }
    else {

      data.append('action', 'groundhogg_ajax_form_submit')

      $.ajax({
        method: 'POST',
        // dataType: 'json',
        url        : ajaxurl,
        data       : data,
        processData: false,
        contentType: false,
        cache      : false,
        timeout    : 600000,
        enctype    : 'multipart/form-data',
        success    : (r) => {

          stop()
          $btn.prop('disabled', false)
          $btn.text(origTxt)

          if (!r.success) {

            dialog({
              message: r.data[0].message,
              type   : 'error',
            })

          }
          else {
            dialog({
              message: __('Form submitted!'),
            })

            ContactsStore.itemsFetched([
              r.data.contact,
            ])

            onSubmit(r.data.contact)
          }

        },
        error      : (e) => {
          dialog({
            message: __('Something went wrong...', 'groundhogg'),
            type   : 'error',
          })
        },
      })

    }
  }

  const addContactModal = ({
    prefix = 'quick-add',
    onCreate = () => {},
    additionalFields = () => '',
    additionalFieldsOnMount = () => {},
  }) => {

    let method = 'quick-add'
    let selectedForm

    const form = () => {

      const quickAddForm = () => {
        //language=HTML
        return `
            <div id="${ prefix }-quick-add-form"></div>`
      }

      const useForm = () => {
        //language=HTML
        return `
            <div class="gh-rows-and-columns">
                <div class="gh-row">
                    <div class="gh-col">
                        <label for="${ prefix }-select-form">${ __('Select a form', 'groundhogg') }</label>
                        ${ select({
                            id  : `${ prefix }-select-form`,
                            name: 'select_form',
                        }) }
                    </div>
                </div>
            </div>
            <div style="margin-top: 20px">
                ${ selectedForm ? selectedForm.rendered : '' }
            </div>`
      }

      // language=HTML
      return `
          <div class="quick-add-wrap" style="width: 500px">
              <div class="gh-header modal-header">
                  <h3>${ __('Add Contact', 'groundhogg') }</h3>
                  <div class="actions align-right-space-between">
                      <button
                              class="gh-button dashicon no-border icon ${ method == 'quick-add'
                                                                          ? 'filled'
                                                                          : '' } use-quick-add">
                          ${ icons.createContact }</span></button>
                      <button class="gh-button dashicon no-border icon ${ method == 'form' ? 'filled' : '' } use-form">
                          ${ icons.form }</span></button>
                      <button class="gh-button dashicon no-border icon text ${ prefix }-cancel"><span
                              class="dashicons dashicons-no-alt"></span></button>
                  </div>
              </div>
              ${ method == 'form' ? useForm() : quickAddForm() }
          </div>
      `
    }

    const onMount = ({
      close,
      setContent,
    }) => {

      const reMount = () => {
        setContent(form())
        onMount({
          close,
          setContent,
        })
      }

      tooltip('.use-quick-add', {
        content: __('Use quick-add form', 'groundhogg'),
      })

      tooltip('.use-form', {
        content: __('Use internal form', 'groundhogg'),
      })

      $('.use-form').on('click', (e) => {

        method = 'form'
        reMount()
      })

      $('.use-quick-add').on('click', (e) => {

        method = 'quick-add'
        reMount()
      })

      $(`.${ prefix }-cancel`).on('click', close)

      if (method == 'quick-add') {

        quickAddForm(`#${ prefix }-quick-add-form`, {
          prefix,
          additionalFields,
          additionalFieldsOnMount,
          onCreate: (c) => {
            close()
            onCreate(c)
          },
        })

      }
      else {
        $(`#${ prefix }-select-form`).ghPicker({
          endpoint   : FormsStore.route,
          width      : '100%',
          placeholder: __('Type to search...', 'groundhogg'),
          data       : [
            {
              id  : '',
              text: '',
            },
            ...FormsStore.getItems().map(f => ( {
              id      : f.ID,
              text    : f.name,
              selected: selectedForm && f.ID == selectedForm.ID,
            } )),
          ],
          getParams  : (q) => ( {
            ...q,
            search: q.term,
            active: true,
          } ),
          getResults : ({ items }) => {
            FormsStore.itemsFetched(items)
            return items.map(f => ( {
              id  : f.ID,
              text: f.name,
            } ))
          },
        }).on('select2:select', (e) => {
          selectedForm = FormsStore.get(e.params.data.id)
          reMount()
        })

        if (selectedForm) {
          $('.quick-add-wrap form.gh-form').on('submit', (e) => {

            e.preventDefault()

            var $form = $(e.currentTarget)

            handleInternalFormSubmit(selectedForm.ID, $form, c => {
              close()
              onCreate(c)
            })

          })
        }
      }

    }

    return modal({
      // dialogClasses: 'overflow-visible',
      content: form(),
      onOpen : onMount,
    })

  }

  const EmailModal = (props, onSend = () => {}) => {

    const State = Groundhogg.createState({
      to        : [],
      from_user : currentUser.ID,
      from_name : currentUser.data.display_name,
      from_email: currentUser.data.user_email,
      cc        : [],
      showCC    : false,
      bcc       : [],
      showBCC   : false,
      subject   : '',
      content   : '',
      sending   : false,
      ...props,
    })

    const EmailAddressPicker = ({
      label = '',
      key = '',
    }) => ItemPicker({
      id              : `composed-${ key }`,
      label           : `${ label }:`,
      style           : {
        flexGrow: 1,
      },
      noneSelected    : `${ label }...`,
      tags            : true,
      selected        : State[key].map(v => ( {
        id  : v,
        text: v,
      } )),
      fetchOptions    : search => ContactsStore.fetchItems({
        search,
      }).then(items => items.map(item => ( {
        id  : item.data.email,
        text: item.data.email,
      } ))),
      isValidSelection: isValidEmail,
      onChange        : items => State.set({
        [key]: items.map(item => item.id),
      }),
    })

    return Modal({
        className    : 'send-email',
        dialogClasses: 'gh-panel',
        overlay      : false,
        onClose      : () => {
          wp.editor.remove('composed-content')
        },
      }, ({
        close,
        morph: realMorph,
      }) => {

        const morph = () => realMorph({
          // onBeforeNodeDiscarded: node => {
          //   return ! node.classList.contains( 'mce-tinymce' )
          // }
        })

        return Form({
          id       : 'compose-email-form',
          className: 'display-flex column gap-10',
          onSubmit : e => {

            e.preventDefault()

            const {
              to,
              from_email,
              from_name,
              cc,
              bcc,
              subject,
              content,
            } = State

            if (!content) {
              errorDialog({
                message: 'Please add a message.',
              })
              return false
            }

            if (!to.length) {
              errorDialog({
                message: 'Please add a least one recipient.',
              })
              return false
            }

            State.set({ sending: true })
            morph()

            post(`${ routes.v4.emails }/send`, {
              to,
              from_email,
              from_name,
              cc,
              bcc,
              subject,
              content,
            }).then((r) => {

              if (r.status !== 'success') {

                dialog({
                  message: r.message,
                  type   : 'error',
                })

                return
              }

              dialog({
                message: __('Message sent!', 'groundhogg'),
              })

              onSend({
                ...r,
                email: {
                  to,
                  from_email,
                  from_name,
                  cc,
                  bcc,
                  subject,
                  content,
                },
              })

              close()

            }).catch(e => {

              dialog({
                message: e.message,
                type   : 'error',
              })

              State.set({ sending: false })
              morph()

            })

            return false
          },
        }, [

          // from
          OwnerPicker({
            label      : 'From:',
            id         : 'composed-from',
            selected   : [State.from_user],
            multiple   : false,
            allow0     : false,
            itemDisplay: u => `${ u.data.display_name } &lt;${ u.data.user_email }&gt;`,
            onChange   : item => {
              State.set({
                form_user : item.id,
                from_email: getOwner(item.id).data.user_email,
                from_name : getOwner(item.id).data.display_name,
              })
            },
          }),

          Div({
            className: 'display-flex gap-5 align-center',
          }, [

            // to
            EmailAddressPicker({
              label: 'To',
              key  : 'to',
            }),

            State.cc.length || State.showCC ? null : An({
              id     : 'show-cc',
              onClick: e => {
                State.set({ showCC: true })
                morph()
              },
            }, 'CC'),
            State.bcc.length || State.showBCC ? null : An({
              id     : 'show-bcc',
              onClick: e => {
                State.set({ showBCC: true })
                morph()
              },
            }, 'BCC'),

          ]),

          // cc
          State.cc.length || State.showCC ? EmailAddressPicker({
            label: 'CC',
            key  : 'cc',
          }) : null,

          // bcc
          State.bcc.length || State.showBCC ? EmailAddressPicker({
            label: 'BCC',
            key  : 'bcc',
          }) : null,

          // subject
          Input({
            id         : 'composed-subject-line',
            name       : 'composed_subject_line',
            required   : true,
            placeholder: __('Subject line...'),
            value      : State.subject,
            onInput    : e => State.set({ subject: e.target.value }),
          }),

          // content
          TinyMCE({
            id      : 'composed-content',
            value   : State.content,
            config  : {
              replacements: true,
              savedReplies: true,
              media       : true,
              quicktags   : false,
              tinymce     : {
                height: 300,
              },
            },
            onChange: content => State.set({
              content,
            }),
          }),

          // discard/send
          Div({
            className: 'display-flex gap-5 flex-end',
          }, [
            Button({
              className: 'gh-button danger text',
              disabled : State.sending,
              onClick  : e => close(),
            }, __('Discard')),
            Button({
              type     : 'submit',
              className: 'gh-button primary',
              disabled : State.sending,
            }, State.sending ? Span({ className: 'gh-spinner' }) : __('Send')),
          ]),

        ])
      },
    )

  }

  const emailModal = (props, onSend = () => {}) => {
    return EmailModal(props, onSend)
  }

  const EmailTemplateModal = async (contactId, onSend = () => {}) => {

    let contactIds, title

    // array of contacts provided
    if (Array.isArray(contactId)) {
      contactIds = contactId
      title = sprintf(__('Select an email to send to %s contacts', 'groundhogg'), contactIds.length)
    }
    else if (typeof contactId === 'object') { // contact object provide
      let contact = contactId
      contactIds = [contact.ID]
      title = sprintf(__('Select an email to send to %s', 'groundhogg'), contact.data.full_name.trim() || contact.data.email)
    }
    else { // contact ID provided
      let contact = await Groundhogg.stores.contacts.maybeFetchItem(contactId)
      contactIds = [contact.ID]
      title = sprintf(__('Select an email to send to %s', 'groundhogg'), contact.data.full_name.trim() || contact.data.email)
    }

    const State = Groundhogg.createState({
      email: null,
    })

    MakeEl.Modal({}, ({
      morph,
      close,
    }) => MakeEl.Div({
      id: 'send-email-dialog',
    }, [
      `<h3>${ title }</h3>`,
      MakeEl.ItemPicker({
        id          : `select-email`,
        noneSelected: __('Select an email to send...', 'groundhogg'),
        selected    : State.email ? {
          id  : State.email.ID,
          text: State.email.data.title,
        } : [],
        multiple    : false,
        style       : {
          flexGrow: 1,
        },
        fetchOptions: (search) => {
          return EmailsStore.fetchItems({
              search,
              status: 'ready',
            }).
            then(emails => emails.map(({
              ID,
              data,
            }) => ( {
              id  : ID,
              text: data.title,
            } )))
        },
        onChange    : item => {
          if (!item) {
            State.set({
              email: null,
            })
          }
          else {
            let email = EmailsStore.get(item.id)

            State.set({
              email,
            })
          }

          morph()
        },
      }),

      State.email ? MakeEl.Div({
          className: 'gh-panel outlined',
        },
        Groundhogg.components.EmailPreview({
          ...State.email.context,
          content: State.email.context.built,
        }),
      ) : null,

      State.email ? MakeEl.Button({
        id       : 'send-email',
        className: 'gh-button primary medium',
        onClick  : e => {

          e.currentTarget.disabled = true
          e.currentTarget.innerHTML = `<span class="gh-spinner"></span>`

          EmailsStore.send(State.email.ID, {
            to: contactIds,
          }).then(r => {

            dialog({
              message: __('Email sent!'),
            })

            close()
            onSend(r)

          }).catch(e => {
            dialog({
              type   : 'error',
              message: e.message,
            })
            morph()
          })

        },
      }, __('Send email now!', 'groundhogg')) : null,
    ]))
  }

  const makeInput = (selector, {
    inputProps = {},
    value = '',
    onChange = () => {},
    replaceWith = () => {},
  }) => {

    inputProps = {
      id: uuid(),
      value,
      ...inputProps,
    }

    $(selector).replaceWith(input(inputProps))

    $(`#${ inputProps.id }`).focus().on('blur keydown', e => {

      if (e.type === 'keydown' && e.key !== 'Enter') {
        return
      }

      value = e.target.value
      onChange(value)

      $(`#${ inputProps.id }`).replaceWith(replaceWith(value))
    })

  }

  const fileUploader = ({
    action = '',
    nonce = '',
    accept = '',
    multiple = true,
    fileName = 'file-upload',
    beforeUpload = () => {},
    onUpload = () => {},
  }) => {
    return modal({
      // language=HTML
      width        : 600,
      dialogClasses: 'gh-media-uploader',
      content      : `
		  ${ input({
        type     : 'file',
        id       : 'upload-file-input',
        name     : 'files' + ( multiple ? '[]' : '' ),
        className: 'hidden',
        accept,
        multiple,
      }) }
		  <div class="droppable-handler">
			  <h2>${ __('Drag files to upload') }</h2>
			  <button class="gh-button primary" id="select-files">${ __('Select Files') }</button>
		  </div>
      <div id="uploading-files"></div>
      <div id="uploaded-files"></div>
      `,
      onOpen       : ({ close }) => {

        let file = null
        let filesToUpload = []
        let filesUploaded = []
        let uploading = false

        const pushFiles = () => {

          renderUploadingFiles()

          file = filesToUpload.pop()

          if (!file) {

            uploading = false

            return
          }

          uploading = true

          let fd = new FormData()

          fd.append(fileName, file, file.name)
          fd.append('gh_admin_ajax_nonce', Groundhogg.nonces._adminajax)
          fd.append('action', action)

          beforeUpload(fd)

          setTimeout(() => {

            fetch(ajaxurl, {
              method     : 'POST',
              credentials: 'same-origin',
              body       : fd,
            }).then(r => {

              if (!r.ok) {

                dialog({
                  message: __('Something when wrong...'),
                  type   : 'error',
                })

                return
              }

              return r.json()
            }).then(r => {

              if (!r.success) {
                dialog({
                  message: r.data[0].message,
                  type   : 'error',
                })

                pushFiles()

                return
              }

              onUpload(r, file)

              filesUploaded.unshift(file)

              renderUploadedFiles()

              pushFiles()

            })

          }, 2000)
        }

        const renderUploadingFiles = () => {
          $('#uploading-files').
            html(filesToUpload.map(f => `<div class="file"><span class="hourglass">⌛</span> ${ f.name }</div>`))
        }

        const renderUploadedFiles = () => {
          $('#uploaded-files').html(filesUploaded.map(f => `<div class="file">✅ ${ f.name }</div>`))
        }

        const addFiles = (files) => {
          filesToUpload.push(...files)

          if (!uploading) {
            pushFiles()
          }
        }

        const $input = $('#upload-file-input')

        $input.on('change', (e) => {
          addFiles(e.target.files)
        })

        $('#select-files').on('click', (e) => {
          e.preventDefault()
          $input.click()
        })

        const $droppable = $('.droppable-handler')

        $droppable.on('dragover', (e) => {
          e.preventDefault()
          $droppable.addClass('dragover')
        }).on('dragleave', (e) => {
          $droppable.removeClass('dragover')
        }).on('drop', e => {
          e.preventDefault()
          $droppable.removeClass('dragover')

          let { dataTransfer } = e.originalEvent

          addFiles(dataTransfer.files)
        })
      },
    })
  }

  const EmailPreviewModal = async (emailId, {
    height = window.innerHeight * 0.85,
    width = 900,
  }) => {

    const { close } = loadingModal()

    let email

    try {
      email = await EmailsStore.maybeFetchItem(emailId)
    }
    catch (err) {
      close()
      throw err
    }

    const {
      from_avatar,
      from_email,
      from_name,
      subject,
      built: content,
    } = email.context

    close()

    return ModalFrame({
      frameAttributes: {
        className: 'gh-modal-frame gh-email-preview-modal',
      },
    }, ({ close }) => Div({
      style: {
        width : `${ width }px`,
        height: `${ height }px`,
      },
    }, EmailPreview({
      close,
      from_avatar,
      from_email,
      from_name,
      subject,
      content,
    })))
  }

  const EmailPreview = ({
    close = false,
    from_avatar,
    from_email,
    from_name,
    subject,
    content,
  }) => {

    return Div({
      className: 'email-preview',
    }, [
      Div({
        className: 'from-preview display-flex gap-20 has-box-shadow',
      }, [
        makeEl('img', {
          src      : from_avatar,
          className: 'from-avatar',
          height   : 40,
          width    : 40,
          style    : {
            borderRadius: '50%',
          },
        }),
        Div({
          className: 'subject-and-from',
        }, [
          // Subject Line
          `<h2>${ subject }</h2>`,
          // From Name & Email
          `<span class="from-name">${ from_name }</span> <span class="from-email">&lt;${ from_email }&gt;</span>`,
        ]),
        close !== false ? Button({
          className: 'gh-button secondary icon text',
          style    : {
            marginLeft: 'auto',
          },
          onClick  : close,
        }, Dashicon('no-alt')) : null,
      ]),
      Iframe({
        id: 'desktop-preview-iframe',
      }, content),
    ])
  }

  $(() => {
    $(document).on('click', 'table.wp-list-table .gh-email-preview', e => {
      e.preventDefault()

      EmailPreviewModal(parseInt($(e.currentTarget).closest('tr').attr('id')), {})
    })
  })

  const ImagePicker = ({
    multiple = false,
    title = __('Select a image to upload'),
    selectText = __('Use this image'),
    onChange = attachment => {},
  }) => {

    // Create the media frame.
    let file_frame = wp.media({
      title,
      button: {
        text: selectText,
      },
      multiple,	// Set to true to allow multiple files to be selected
    })

    // When an image is selected, run a callback.
    file_frame.on('select', function () {
      // We set multiple to false so only get one image from the uploader
      let attachment = file_frame.state().get('selection').first().toJSON()
      onChange(attachment)
    })

    file_frame.open()
  }

  const ImageInput = ({
    id,
    name = 'src',
    onChange,
    value = '',
  }) => {

    const handleChange = (value, attachment = null) => {
      onChange(value, attachment)
      morphdom(document.getElementById(id), ImageInput({
        id,
        name,
        onChange,
        value,
      }))
    }

    return Div({
      id,
      className: 'image-picker',
    }, [
      value ? Div({
        id       : `${ id }-preview`,
        className: 'image-input-preview',
        style    : {
          backgroundImage: `url(${ value })`,
        },
        onClick  : e => {
          e.preventDefault()
          ImagePicker({
            multiple: false,
            onChange: attachment => handleChange(attachment.url, attachment),
          })
        },
      }) : null,
      InputGroup([
        Input({
          type     : 'text',
          id       : `${ id }-src`,
          value,
          className: 'control full-width',
          name,
          onChange : e => {
            handleChange(e.target.value, null)
          },
        }),
        Button({
            id       : `${ id }-select`,
            className: 'gh-button secondary icon',
            onClick  : e => {
              e.preventDefault()
              ImagePicker({
                multiple: false,
                onChange: attachment => handleChange(attachment.url, attachment),
              })
            },
          },
          icons.image),
      ]),
    ])
  }

  const FeedbackModal = ({
    subject = '',
    message = '',
    onSubmit = r => {},
  }) => {

    const State = Groundhogg.createState({
      subject,
      message,
      submitting: false,
    })

    ModalWithHeader({
      width: '400px',
      header: 'Send Feedback'
    }, ({
      close,
      morph,
    }) => Form({
      className: 'display-flex column gap-5',
      onSubmit : e => {
        e.preventDefault()

        State.set({
          submitting: true,
        })

        morph()

        Groundhogg.api.ajax({
          action : 'gh_plugin_feedback',
          subject: State.subject,
          message: State.message,
        }).then(r => {
          onSubmit(r)
          dialog({
            message: 'Thanks for your feedback!',
          })
          close()
        })

        return false
      },
    }, [

      Label({
        for: 'feedback-subject',
      }, ['What feature are you submitting feedback for?']),
      Input({
        id      : 'feedback-subject',
        value   : State.subject,
        required: true,
        onInput : e => State.set({
          subject: e.target.value,
        }),
      }),
      Div(),
      Label({
        for: 'feedback-message',
      }, ['What is your feedback? Be as descriptive as possible.']),
      Textarea({
        id      : 'feedback-message',
        value   : State.message,
        required: true,
        rows    : 4,
        onInput : e => State.set({
          message: e.target.value,
        }),
      }),
      Button({
        className: 'gh-button primary',
        type     : 'submit',
        disabled : State.submitting,
      }, 'Send feedback'),
      Pg({}, 'Your email address will be collected to validate your feedback, but will not be used beyond that.'),
    ]))

  }

  $(document).on('click', 'a.feedback-modal', e => {
    e.preventDefault()
    const {
      subject = '',
      message = '',
    } = e.currentTarget.dataset
    FeedbackModal({
      subject,
      message,
    })
  })

  const ContactPhone = (icon, number, extension = '') => number ? Span({
    className: 'contact-phone',
  }, [
    icon,
    An({ href: `tel:${ number }` }, number),
    extension ? Span({
      className: 'ext',
    }, ` x${ extension }`) : null,
  ]) : null

  /**
   *
   * @param item
   * @param extra
   * @param props
   * @returns {*}
   * @constructor
   */
  const ContactListItem = (item, {
    extra = item => null,
    ...props
  } = {}) => {

    let allTags = jsonCopy(item.tags)
    let showTags = allTags.splice(0, 10)

    const {
      ID,
    } = item

    const {
      full_name,
      gravatar,
      date_created,
      email,
    } = item.data

    const {
      primary_phone = '',
      primary_phone_extension = '',
      mobile_phone = '',
      company_phone = '',
      company_phone_extension = '',
    } = item.meta

    // top level item container
    return Div({
      className: `contact-list-item`,
      id       : `contact-list-item-${ ID }`,
      dataId   : ID,
      ...props,
    }, [
      // Contact info
      Div({
        className: 'display-flex gap-10',
      }, [
        Img({
          className: 'avatar',
          src      : gravatar,
          alt      : 'avatar',
        }),
        Div({ className: 'display-flex column' }, [
          Div({}, [
            makeEl('h4', {
              style: {
                margin: 0,
              },
            }, full_name),
            Span({
              className: 'subscribed',
            }, `&nbsp;— ${ sprintf(
              __('Subscribed %s'),
              `<abbr title="${ formatDateTime(date_created) }">${ sprintf(__('%s ago '),
                item.i18n.created) }</abbr>`) }`),
          ]),
          Div({}, [
            An({
              href: `mailto:${ email }`,
            }, email),
            Span({}, [
              ' — ',
              Span({
                className: `gh-text ${ item.is_marketable ? 'green' : 'red' }`,
              }, Groundhogg.filters.optin_status[item.data.optin_status]),
            ]),
          ]),
        ]),
      ]),
      Div({
        className: 'show-on-hover',
      }, [
        // Phones
        primary_phone || company_phone || mobile_phone ? Div({
          className: 'contact-phones',
        }, [
          ContactPhone(icons.mobile, mobile_phone),
          ContactPhone(icons.phone, primary_phone, primary_phone_extension),
          ContactPhone(icons.phone, company_phone, company_phone_extension),
        ]) : null,
        // Tags
        Div({ className: 'gh-tags' }, [
          ...showTags.map(tag => Span({ className: 'gh-tag' }, tag.data.tag_name)),
          allTags.length ? Span({}, sprintf('and %d more...', allTags.length)) : null,
        ]),
        maybeCall(extra, item),
      ]),
    ])
  }

  const ContactList = (contacts = [], {
    noContacts = () => null,
    itemProps = {},
  } = {}) => {

    if (!contacts.length) {
      return maybeCall(noContacts)
    }

    return Div({
      className: 'contact-list',
    }, contacts.map(contact => ContactListItem(contact, maybeCall(itemProps, contact))))
  }

  const QuickSearch = ({
    itemProps = {},
    queryOverrides = {},
  } = {}) => {

    const State = Groundhogg.createState({
      search  : '',
      searched: false,
      results : [],
      loaded  : false,
    })

    const fetchResults = async () => {
      let results = await ContactsStore.fetchItems({
        search : State.search,
        orderby: 'date_created',
        order  : 'DESC',
        limit  : 5,
        ...queryOverrides,
      })

      State.set({
        results,
        searched: true,
        loaded  : true,
      })
    }

    return Div({
      id: 'quick-search-wrap',
    }, morph => {

      if (!State.loaded) {
        fetchResults().then(morph)
      }

      const updateResults = debounce(async () => {
        await fetchResults()
        morph()
      }, 300)

      return Fragment([
        Form({
          action: adminPageURL('gh_contacts'),
        }, [
          Input({
            type : 'hidden',
            name : 'page',
            value: 'gh_contacts',
          }),
          Input({
            id         : 'quick-search-input',
            placeholder: __('Search by name or email...', 'groundhogg'),
            type       : 'search',
            name       : 's',
            value      : State.search,
            onInput    : e => {
              State.set({
                search: e.target.value,
              })
              updateResults()
            },
          }),
        ]),
        State.loaded ? null : Skeleton({}, [
          'full',
          'full',
          'full',
        ]),
        State.results.length ? ContactList(State.results, {
          itemProps: item => ( {
            className: 'contact-list-item clickable',
            onClick  : e => {
              window.open(item.admin, '_self')
            },
            ...maybeCall(itemProps, item),
          } ),
        }) : null,
        State.results.length === 0 && State.searched ? Pg({
          style: {
            textAlign: 'center',
          },
        }, __('No contacts found for the current search', 'groundhogg')) : null,
      ])
    })
  }

  const Panel = ({
    id,
    name,
    collapsed = false,
    hidden = false,
    onCollapse = id => {},
  }, content) => {

    if (hidden) {
      return null
    }

    return Div({
      id       : `${ id }-panel`,
      className: `gh-panel ${ collapsed ? 'closed' : '' }`,
    }, [
      Div({ className: `gh-panel-header` }, [
        H2({}, name),
        Button({
          className: 'toggle-indicator',
          onClick  : e => {
            onCollapse(id)
          },
        }),
      ]),
      collapsed ? null : maybeCall(content),
    ])
  }

  const Panels = (overrides) => ( {
    ...Groundhogg.createRegistry({}),
    storagePrefix: 'gh-panels',
    collapse (id) {
      if (!this.isCollapsed(id)) {
        this.toggleCollapse(id)
      }
    },
    expand (id) {
      if (this.isCollapsed(id)) {
        this.toggleCollapse(id)
      }
    },
    hide (id) {
      if (!this.isHidden(id)) {
        this.toggleHidden(id)
      }
    },
    show (id) {
      if (this.isHidden(id)) {
        this.toggleHidden(id)
      }
    },

    togglePanel (id, suffix) {
      let panels = this.getPanelIds(suffix)

      if (panels.includes(id)) {
        panels.splice(panels.indexOf(id), 1)
      }
      else {
        panels.push(id)
      }

      localStorage.setItem(`${ this.storagePrefix }-${ suffix }`, JSON.stringify(panels))
    },

    toggleHidden (id) {
      this.togglePanel(id, 'hidden')
    },

    toggleCollapse (id) {
      this.togglePanel(id, 'collapsed')
    },

    getPanelIds (suffix) {
      return JSON.parse(localStorage.getItem(`${ this.storagePrefix }-${ suffix }`)) || []
    },

    getHiddenPanelIds () {
      return this.getPanelIds('hidden')
    },
    getCollapsedPanelIds () {
      return this.getPanelIds('collapsed')
    },
    isHidden (id) {
      return this.getHiddenPanelIds().includes(id)
    },
    isCollapsed (id) {
      return this.getCollapsedPanelIds().includes(id)
    },

    PanelControls () {
      return Div({}, [
        ...this.map((item, id) => Div({
          className: 'display-flex gap-10',
          style    : {
            marginBottom: '10px',
          },
        }, [
          Toggle({
            checked : !this.isHidden(id),
            id      : `toggle-${ id }`,
            onChange: e => {
              this.toggleHidden(id)

            },
          }),
          Label({
            for: `toggle-${ id }`,
          }, item.name),
        ])),
      ])
    },

    Panel (id) {

      let {
        content,
        ...panel
      } = this.get(id)

      return Panel({
        id,
        ...panel,
        collapsed : this.isCollapsed(id),
        hidden    : this.isHidden(id),
        onCollapse: id => {
          this.toggleCollapse(id)
          morphdom(document.getElementById(`${ id }-panel`), this.Panel(id))
        },
      }, content)
    },

    Panels () {
      return Div({
        className: 'display-flex column gap-20',
        id       : this.storagePrefix,
      }, this.keys().map(id => this.Panel(id)))
    },

    ...overrides,
  } )

  const Relationships = ({
    title = '',
    id,
    store,
    child_type = '',
    parent_type = '',
    renderItem = item => {},
    onAddItem = (r, j) => {},
  }) => {

    const rel_type_key = child_type ? 'child_type' : 'parent_type'
    const rel_type = child_type || parent_type
    const rel_id_key = child_type ? 'child_id' : 'parent_id'

    const State = Groundhogg.createState({
      loaded: false,
      items : [],
    })

    const fetchRelationships = () => store.fetchRelationships(id, {
        [rel_type_key]: rel_type,
      }).
      then(items => State.set({
        items,
        loaded: true,
      }))

    const deleteRelationship = itemId => store.deleteRelationships(id, {
      [rel_type_key]: rel_type,
      [rel_id_key]  : itemId,
    }).then(() => State.set({
      items: State.items.filter(item => item.ID !== itemId),
    }))

    const createRelationship = item => store.createRelationships(id, {
      [rel_type_key]: rel_type,
      [rel_id_key]  : item.ID,
    }).then(() => State.set({
      items: [
        ...State.items,
        item,
      ],
    }))

    return Div({
      id       : `${ rel_type_key }-${ rel_type }-rel-of-${ id }`,
      className: `display-flex column relationship-editor ${ rel_type_key }-${ rel_type }`,
    }, morph => {

      const handleDeleteRelationship = itemId => deleteRelationship(itemId).then(morph)

      if (!State.loaded) {

        fetchRelationships().then(morph)

        return Skeleton({}, [
          'full',
          'full',
          'full',
        ])
      }

      const AddRelButton = () => Button({
        id       : `add-${ rel_type_key }-${ rel_type }-rel-for-${ id }`,
        className: 'gh-button secondary text icon',
        onClick  : e => {
          let promise = new Promise((resolve, reject) => onAddItem(resolve, reject, State))

          promise.then(item => createRelationship(item).then(morph))
        },
      }, [
        Dashicon('plus-alt2'),
        ToolTip(__('Add relationship', 'groundhogg'), 'left'),
      ])

      return Fragment([

        title ? Div({ className: 'space-between' }, [
          H4({}, title),
          AddRelButton(),
        ]) : null,

        ...State.items.map(item => renderItem({
          ...item,
          onDelete: handleDeleteRelationship,
        })),
        title ? null : Div({
          className: 'display-flex flex-end',
        }, AddRelButton()),

      ])

    })

  }

  const OwnerPicker = ({
    id = 'select-owners',
    selected = [], // list of user ids,
    onChange = ids => {}, // list of user ids,
    allow0 = true,
    itemDisplay = user => user.data.display_name,
    ...overrides
  }) => ItemPicker({
    id              : `select-users`,
    noneSelected    : __('Select a user...', 'groundhogg'),
    selected        : selected.map(user_id => {

      if (user_id == 0 && allow0) {
        return {
          id  : 0,
          text: __('The contact owner', 'groundhogg'),
        }
      }

      return {
        id  : user_id,
        text: itemDisplay(getOwner(user_id)),
      }
    }),
    multiple        : true,
    style           : {
      flexGrow: 1,
    },
    isValidSelection: id => id === 0 || getOwner(id),
    fetchOptions    : (search) => {
      search = new RegExp(search, 'i')

      let options = Groundhogg.filters.owners.map(u => ( {
        id  : u.ID,
        text: itemDisplay(u),
      } ))

      if (allow0) {
        options.push({
          id  : 0,
          text: __('The contact owner', 'groundhogg'),
        })
      }

      options = options.filter(({ text }) => text.match(search))

      return Promise.resolve(options)
    },
    onChange        : items => onChange(items.map(({ id }) => id)),
    ...overrides,
  })

  function getClosestRelativeAncestor (element) {
    let parent = element.parentElement

    while (parent) {
      if (window.getComputedStyle(parent).position === 'relative') {
        return parent // Found the closest relative ancestor
      }
      parent = parent.parentElement // Move up the tree
    }

    return null // No relative ancestor found
  }

  const Tour = (steps, {
    onFinish = () => {},
    beforeDismiss = ({dismiss}) => dismiss(),
    onDismiss = () => {},
    fixed = false,
  }) => {

    const State = Groundhogg.createState({
      current : 0,
      step    : null,
      target  : null,
      relative: null,
    })

    const currentStep = () => steps[State.current]
    const removeSteps = () => {
      document.querySelectorAll('.tour-prompt-container').forEach(el => el.remove())
      document.querySelectorAll('.tour-prompt').forEach(el => el.remove())
      document.querySelectorAll('.tour-highlighted').forEach(el => el.classList.remove('tour-highlighted'))
    }

    const remove = () => {
      removeSteps()
      document.removeEventListener('resize', rePositionStep)
      document.removeEventListener('scroll', rePositionStep)
    }

    const dismiss = async () => {
      remove()
      onDismiss()
    }

    const next = () => {

      const { onNext = () => {} } = currentStep()
      onNext()

      if (State.current + 1 >= steps.length) {
        remove()
        onFinish(true)
        return
      }

      State.current++
      showStep()
    }

    const prev = () => {

      const { onPrev = () => {} } = currentStep()
      onPrev()

      if (State.current <= 0) {
        // can't go back
        return
      }

      State.current--
      showStep()
    }

    const showStep = () => {

      removeSteps() // remove other prompts

      // create a new prompt
      positionStep()
    }

    function rePositionStep () {

      let {
        position,
      } = currentStep()

      let {
        target,
        relative,
        step,
        windowEl
      } = State

      const targetPos = target.getBoundingClientRect()
      const relativePos = relative.getBoundingClientRect()
      const stepPos = step.getBoundingClientRect()

      const gap = 20;

      if (fixed) {

        windowEl.style.height = `${ targetPos.height + gap*2 }px`
        windowEl.style.width = `${ targetPos.width + gap*2 }px`
        windowEl.style.borderWidth = `${ Math.round(targetPos.y) - gap }px ${ Math.round(window.innerWidth - targetPos.x) - gap }px ${ Math.round(
          window.innerHeight - targetPos.y) - gap }px ${ Math.round(targetPos.x) - gap }px`

        switch (position) {
          case 'right':
            step.style.left = `${ targetPos.x + targetPos.width + gap }px`
            step.style.top = `${ targetPos.y }px`
            break
          case 'left':
            step.style.left = `${ targetPos.x - stepPos.width - gap }px`
            step.style.top = `${ targetPos.y }px`
            break
          case 'above':
            step.style.left = `${ targetPos.x }px`
            step.style.top = `${ targetPos.y - stepPos.height - gap }px`
            break
          case 'below':
            step.style.left = `${ targetPos.x }px`
            step.style.top = `${ targetPos.y + targetPos.height + gap }px`
            break
          case 'below-left':
            step.style.left = `${ targetPos.x + targetPos.width - stepPos.width }px`
            step.style.top = `${ targetPos.y + targetPos.height + gap }px`
            break
        }

        return
      }

      switch (position) {
        case 'right':
          step.style.left = `${ targetPos.right - relativePos.left + gap }px`
          step.style.top = `${ targetPos.top - relativePos.top }px`
          break
        case 'left':
          step.style.left = `${ targetPos.left - relativePos.left - stepPos.width - gap }px`
          step.style.top = `${ targetPos.top - relativePos.top }px`
          break
        case 'above':
          step.style.left = `${ targetPos.left - relativePos.left }px`
          step.style.top = `${ targetPos.top - relativePos.top - stepPos.height - gap }px`
          break
        case 'below':
          step.style.left = `${ targetPos.left - relativePos.left }px`
          step.style.top = `${ targetPos.bottom - relativePos.top + gap }px`
          break
        case 'below-left':
          step.style.left = `${ targetPos.right - relativePos.left - stepPos.width }px`
          step.style.top = `${ targetPos.bottom - relativePos.top + gap }px`
          break
      }
    }

    function positionStep () {

      let tourEl = TourStep()
      let windowEl = tourEl.querySelector('.tour-window')
      let stepEl = tourEl.querySelector('.tour-prompt')
      let {
        target,
        relative,
        onInit = () => {},
        onBefore = () => {},
      } = currentStep()

      target = document.querySelector(target)

      if (!target) {
        next()
        return
      }

      if (fixed) {
        relative = document.body
      }
      else if (relative) {
        relative = target.closest(relative)
      }
      else {
        relative = getClosestRelativeAncestor(target)
      }

      stepEl.style.position = fixed ? 'fixed' : 'absolute'

      if ( fixed ){
        relative.append(tourEl)
      } else {
        target.classList.add('tour-highlighted')
        relative.append(stepEl)
      }

      target.scrollIntoView({
        behavior: 'instant',
        block   : 'center',
        inline  : 'center',
      })

      onBefore({
        next,
        prev,
        target,
        relative,
        step: stepEl,
        windowEl,
        currentStep,
      })

      State.set({
        step: stepEl,
        windowEl,
        target,
        relative,
      })

      rePositionStep()

      onInit({
        next,
        prev,
        target,
        relative,
        step: stepEl,
        windowEl,
        currentStep,
      })

      stepEl.querySelector('#tour-next').focus()
    }

    const TourStep = () => MakeEl.Div({
      className: 'tour-prompt-container',
    }, [
      fixed ? MakeEl.Div({
        className: 'tour-window',
      }, [
        MakeEl.Div({
          className: 'tour-window-shadow',
          onClick: next
        })
      ]) : null,
      MakeEl.Div({
        className: `tour-prompt ${ currentStep().position }`,
        style    : {
          padding: '10px',
          width  : '200px',
        },
      }, [
        MakeEl.Button({
          className: 'dismiss',
          onClick  : e => {
            beforeDismiss({ dismiss, State })
          },
        }, MakeEl.Dashicon('no-alt')),
        MakeEl.Div({}, currentStep().prompt),
        MakeEl.Div({ className: 'display-flex flex-end gap-5 space-above-10' }, [
          State.current > 0 ? MakeEl.Button({
            id       : 'tour-prev',
            className: 'gh-button small secondary text prev-step',
            onClick  : () => prev(),
          }, 'Prev') : null,
          currentStep().showNext === false ? null : MakeEl.Button({
            id       : 'tour-next',
            className: `gh-button small ${State.current < steps.length - 1 ? 'secondary' : 'primary'} next-step`,
            onClick  : () => next(),
          }, State.current < steps.length - 1 ? 'Next' : 'Finish'),
        ]),
      ]),
    ])

    document.addEventListener('resize', rePositionStep)
    document.addEventListener('scroll', rePositionStep)

    positionStep()
  }

  Groundhogg.components = {
    QuickSearch,
    addContactModal,
    internalForm,
    betterTagPicker,
    quickAddForm,
    selectContactModal,
    quickEditContactModal,
    makeInput,
    emailModal,
    EmailTemplateModal,
    fileUploader,
    EmailPreview,
    EmailPreviewModal,
    ImageInput,
    ImagePicker,
    FeedbackModal,
    ContactList,
    ContactListItem,
    Panel,
    Panels,
    Relationships,
    OwnerPicker,
    Tour,
  }

} )(jQuery)
