(() => {

  const { el } = Groundhogg.element
  const { formatNumber, formatTime, formatDate, formatDateTime } = Groundhogg.formatting
  const { __, _x, _n, _nx, sprintf } = wp.i18n


  const BlockRegistry = {

    collections: {
      core: 'Groundhogg'
    },
    blocks: {},

    /**
     * Register a new block
     *
     * @param type
     * @param name
     * @param block
     * @param collection
     */
    registerBlock (type, name, block, collection = 'core') {

      this.blocks[type] = {
        type,
        name,
        collection,
        ...block
      }

    },

    /**
     * Register a new block collection
     *
     * @param collection
     * @param name
     */
    registerBlockCollection (collection, name) {
      this.collections[collection] = name
    }



  }

  const { registerBlock } = BlockRegistry

  registerBlock( 'paragraph', __( 'Paragraph' ), {
    svg: '',
    controls: () => {},
    controlsOnMount: () => {},
    edit: () => {},
    editOnMount: () => {},
    html: ({id}, {fontSize, fontColor, fontFamily, content}) => {
      return el( 'p', {
        id,
        style: {
          fontSize,
          fontColor,
          fontFamily,
        }
      }, content )
    },
    plain: (props, {content}) => {
      return content
    }

  } )

  registerBlock( 'paragraph', __( 'Paragraph' ), {
    svg: '',
    controls: () => {},
    controlsOnMount: () => {},
    edit: () => {},
    editOnMount: () => {},
    html: ({id}, {fontSize, fontColor, fontFamily, content}) => {
      return el( 'p', {
        id,
        style: {
          fontSize,
          fontColor,
          fontFamily,
        }
      }, content )
    },
    plain: (props, {content}) => {
      return content
    }

  } )

})