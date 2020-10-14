import Box from '@material-ui/core/Box'

export default ({data, meta}) => {

  const { step_type, step_group, parent_steps, child_steps } = data;

  return (
    <>
      <Box>
        { parent_steps.length > 1 }
      </Box>

    </>
  )
}