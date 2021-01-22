import { parseArgs } from 'utils/core'
import Chip from '@material-ui/core/Chip'
import Autocomplete from '@material-ui/lab/Autocomplete'
import { makeStyles } from '@material-ui/core/styles'
import TextField from '@material-ui/core/TextField'
import { useDispatch, useSelect } from '@wordpress/data'
import { TAGS_STORE_NAME } from 'data/tags'
import { useEffect, useState } from '@wordpress/element'
import { useDebounce } from 'utils/index'
import CircularProgress from '@material-ui/core/CircularProgress'

const TagPicker = ({ selectProps, textFieldProps, onChange, selected, isCreatable }) => {

  selectProps = parseArgs(selectProps || {}, {
    multiple: true,
    fullWidth: true
  })

  const { options, isLoading } = useSelect((select) => {
    const store = select(TAGS_STORE_NAME)
    return {
      options: store.getItems(),
      isLoading: store.isItemsRequesting()
    }
  }, [])
  const { fetchItems } = useDispatch(TAGS_STORE_NAME)
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search, 250)

  const __fetchItems = () => {
    fetchItems({
      search: search
    })
  }

  useEffect(() => {
      if (open) {
        __fetchItems()
      }
    },
    [debouncedSearch]
  )

  return (

    <Autocomplete
      {...selectProps}
      fullWidth
      open={open}
      onOpen={() => {
        setOpen(true)
      }}
      onClose={() => {
        setOpen(false)
      }}
      loading={isLoading}
      options={options}
      onChange={onChange}
      getOptionLabel={(option) => option.data.tag_name}
      // getOptionSelected={(option, value) => {
      //   console.debug( option, value )
      //   return value.find( v => option.ID === v.ID )
      // }}
      onInputChange={(e, value) => setSearch(value)}
      inputValue={search}
      value={selected||[]}
      filterSelectedOptions
      renderInput={(params) => (
        <TextField
          {...params}
          {...textFieldProps}
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
