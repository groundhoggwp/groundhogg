( function (wp) {
  const {
    createElement: el,
    Component,
  } = wp.element

  const {
    ToggleControl,
    Panel,
    PanelBody,
    PanelRow,
  } = wp.components

  const { ContactFilters } = Groundhogg.filters

  const InspectorControls = wp.blockEditor.InspectorControls
  const Fragment = wp.element.Fragment

  const {
    isContentRestrictionInstalled
  } = GroundhoggGutenberg

  const isSupportedBlock = name => [
    'core/paragraph',
    'core/heading',
    'core/list-item',
    'core/list',
    'core/image',
    'core/buttons',
    'core/button',
    'core/group',
    'core/shortcode',
    'core/columns',
    'core/column',
    'groundhogg/forms',
  ].includes(name)

  // Define a new attribute for the paragraph block to store the custom setting
  function addCustomAttributes (settings, name) {

    if (typeof settings.attributes == 'undefined') {
      return settings
    }

    // Check if the block is the paragraph block
    if (!isSupportedBlock(name)) {
      return settings
    }

    // Add new attributes for the custom setting
    settings.attributes = Object.assign(settings.attributes, {
      ghReplacements  : {
        type   : 'boolean',
        default: false,
      },
      ghRestrictContent  : {
        type   : 'boolean',
        default: false,
      },
      ghIncludeFilters: {
        type   : 'string',
        default: '[]',
      },
      ghExcludeFilters: {
        type   : 'string',
        default: '[]',
      },
    })

    return settings
  }

  function htmlStringToReact (string, props = {}) {
    return domElementToReact(MakeEl.Div({}, string).firstElementChild, props)
  }

  function domElementToReact (element, props = {}) {
    // Get the tag name
    let tagName = element.tagName.toLowerCase()

    // Gather attributes (excluding event listeners)
    let attributes = {}
    for (let i = 0; i < element.attributes.length; i++) {
      let attr = element.attributes[i]
      // Skip event listeners (e.g., onclick, onmouseover)
      if (!attr.name.startsWith('on')) {
        attributes[attr.name] = attr.value
      }
    }

    // Parse child nodes recursively (text nodes or elements)
    let children = []
    for (let j = 0; j < element.childNodes.length; j++) {
      let child = element.childNodes[j]
      if (child.nodeType === Node.ELEMENT_NODE) {
        // Recursively parse child elements
        children.push(domElementToReact(child))
      }
      else if (child.nodeType === Node.TEXT_NODE) {
        // Add text nodes directly
        children.push(child.nodeValue)
      }
    }

    // Return a React element using createElement
    return el(tagName, {
      ...attributes,
      ...props,
    }, children.length > 0 ? children : null)
  }

  class Filters extends Component {
    componentDidMount () {

      // Call MakeEl to create the DOM element
      let filtersElement = ContactFilters(`block-${ this.props.type }-filters`, this.props.filters || [], this.props.onChange)

      // Append the raw DOM element to the container after the component mounts
      if (this.container) {
        this.container.appendChild(filtersElement)
      }

      // Store the new element for later removal in componentWillUnmount
      this.filtersElement = filtersElement
    }

    componentWillUnmount () {
      // Cleanup: remove the custom DOM element from the container
      if (this.container && this.filtersElement) {
        this.container.removeChild(this.filtersElement)
      }
    }

    render () {
      return el('div', {
        style: {
          flexGrow: 1,
        },
        ref  : el => {
          this.container = el
        },
      })
    }
  }

  let openWithGroundhogg = false

  const groundhoggControls = wp.compose.createHigherOrderComponent(BlockEdit => {

    return props => {

      if (!isSupportedBlock(props.name)) {
        return el(BlockEdit, props)
      }

      // Get the current value of the customSetting attribute
      let {
        ghReplacements = false,
        ghRestrictContent = false,
        ghIncludeFilters = [],
        ghExcludeFilters = [],
      } = props.attributes

      let Edit = el(BlockEdit, props)

      if ( ghRestrictContent && isContentRestrictionInstalled ){
        Edit = el( 'div', {
          className: 'gh-block-edit-restricted-content'
        }, [
          Edit,
          el('div', {
            className: 'filters-enabled',
            onClick: e => {
              openWithGroundhogg = true
              wp.data.dispatch( 'core/block-editor' ).selectBlock( props.clientId )
              setTimeout( ( ) => {
                openWithGroundhogg = false
              }, 100 )
            }
          }, [
            htmlStringToReact( Groundhogg.element.icons.eye ),
            domElementToReact( MakeEl.ToolTip( 'Restricted content', 'left' ) )
          ])
        ] )
      }

      return el(Fragment, null, [

        Edit,

        el(InspectorControls, null, [
          el(PanelBody, {
            title      : Groundhogg.whiteLabelName,
            icon       : Groundhogg.isWhiteLabeled ? null : htmlStringToReact(Groundhogg.element.icons.groundhogg, {
              style: {
                fill: 'black',
              },
            }),
            initialOpen: openWithGroundhogg,
          }, [
            el(PanelRow, {}, [
              el(ToggleControl, {
                label   : 'Enable replacements',
                value   : 1,
                help    : ghReplacements ? 'Replacement codes will be replaced with data from the current contact.' : 'Replacement codes will be ignored.',
                checked : ghReplacements,
                onChange: value => props.setAttributes({ ghReplacements: value }),
              }),
            ]),
            isContentRestrictionInstalled ? el(Fragment, null, [
              el(PanelRow, {}, [
                el(ToggleControl, {
                  label   : 'Enable content restriction',
                  value   : 1,
                  help    : ghRestrictContent ? 'Only contacts that match the filters will be able to view this block.' : 'Anyone can view this block.',
                  checked : ghRestrictContent,
                  onChange: value => props.setAttributes({ ghRestrictContent: value }),
                }),
              ]),
              ghRestrictContent ? el(PanelRow, {}, [
                el(Filters, {
                  type    : 'include',
                  filters : JSON.parse(ghIncludeFilters) || [],
                  onChange: filters => props.setAttributes({ ghIncludeFilters: JSON.stringify(filters) }),
                }),
              ]) : null,
              ghRestrictContent ? el(PanelRow, {}, [
                el(Filters, {
                  type    : 'exclude',
                  filters : JSON.parse(ghExcludeFilters) || [],
                  onChange: filters => props.setAttributes({ ghExcludeFilters: JSON.stringify(filters) }),
                }),
              ]) : null,
            ]) : null,
          ]),
        ]),

      ])

    }
  }, 'groundhoggInspectorControls')

  // Filter to add new attributes to the paragraph block
  wp.hooks.addFilter(
    'blocks.registerBlockType',
    'groundhogg/attributes',
    addCustomAttributes,
  )

  // Filter to add the custom settings panel to the paragraph block
  wp.hooks.addFilter(
    'editor.BlockEdit',
    'groundhogg/panel',
    groundhoggControls,
  )

} )(window.wp)
