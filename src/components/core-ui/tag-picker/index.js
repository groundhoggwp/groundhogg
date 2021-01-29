import { parseArgs } from 'utils/core'
import Autocomplete, { createFilterOptions } from '@material-ui/lab/Autocomplete'
import TextField from '@material-ui/core/TextField'
import { useDispatch, useSelect } from '@wordpress/data'
import { TAGS_STORE_NAME } from 'data/tags'
import { useEffect, useState } from '@wordpress/element'
import { useDebounce } from 'utils/index'
import CircularProgress from '@material-ui/core/CircularProgress'
import { isString } from '@material-ui/data-grid'

const filter = createFilterOptions()

const TagPicker = ({ selectProps, onChange, selected, isCreatable }) => {

  selectProps = parseArgs(selectProps || {}, {
    multiple: true,
    fullWidth: true
  })

  const { options, isLoading } = useSelect((select) => {
    const store = select(TAGS_STORE_NAME)
    return {
      options: store.getItemsCache(),
      isLoading: store.isItemsRequesting()
    }
  }, [])

  const { fetchItems, createItem, createItems } = useDispatch(TAGS_STORE_NAME)
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search, 250)

  const __fetchItems = () => {
    fetchItems({
      search: search
    })
  }

  // Load new search results
  useEffect(() => {
      if (open) {
        __fetchItems()
      }
    },
    [debouncedSearch]
  )

  // Load selected
  useEffect(() => {
      __fetchItems({
        where: selected.length > 0 ? [['tag_id', 'IN', selected]] : {}
      })
    },
    []
  )

  /**
   * Add any new text options that are not INT based
   *
   * @param e
   * @param value
   * @param reason
   * @returns {Promise<void>}
   */
  const handleOnChange = async (e, value, reason) => {

    const tagIds = value.map(tag => {
      return tag.ID
    })

    // new text option
    const newTags = tagIds.filter(tagId => isString(tagId))

    if ( newTags.length > 0 ){
      const result = await createItems(newTags.map(tagName => {
        return {
          data: {
            tag_name: tagName
          }
        }
      }))

      const newTagIds = result.items.map( tag => tag.ID )

      tagIds.push(...newTagIds)
    }

    onChange(tagIds.filter( id => ! isString( id ) ) )

    setSearch('')
  }

  const filterOptions = (options, params) => {
    const filtered = filter(options, params)

    if (params.inputValue !== '' && filtered.length === 0) {
      filtered.push({
        ID: params.inputValue,
        data: {
          tag_name: `Add "${params.inputValue}"`
        }
      })
    }

    return filtered
  }

  return (

    <Autocomplete
      key={'tag-picker'}
      {...selectProps}
      fullWidth
      open={open}
      onOpen={() => {
        setOpen(true)
      }}
      onClose={() => {
        setOpen(false)
      }}
      selectOnFocus
      clearOnBlur
      freeSolo
      handleHomeEndKeys
      filterSelectedOptions
      loading={isLoading}
      options={options}
      inputValue={search}
      onChange={handleOnChange}
      filterOptions={filterOptions}
      onInputChange={(e, value, reason) => { reason !== 'reset' && setSearch(value) }}
      getOptionLabel={(option) => option.data.tag_name}
      value={options.filter(option => selected.includes(option.ID))}
      renderInput={(params) => (
        <TextField
          {...params}
          variant="outlined"
          label="Select Tags"
          placeholder="Tag Name"
          InputProps={{
            ...params.InputProps,
            endAdornment: (
              <>
                {isLoading ? <CircularProgress color="inherit" size={20}/> : null}
                {params.InputProps.endAdornment}
              </>
            )
          }}
        />
      )}
    />
  )
}

export default TagPicker
