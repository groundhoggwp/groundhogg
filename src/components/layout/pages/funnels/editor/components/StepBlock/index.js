import Box from '@material-ui/core/Box'
import Card from '@material-ui/core/Card'
import CardHeader from '@material-ui/core/CardHeader'
import CardActions from '@material-ui/core/CardActions';
import { select, useSelect } from '@wordpress/data'
import { STEP_TYPES_STORE_NAME } from '../../../../../../../data/step-type-registry'
import CardContent from '@material-ui/core/CardContent'
import DeleteIcon from '@material-ui/icons/Delete';
import EditIcon from '@material-ui/icons/Edit';
import IconButton from '@material-ui/core/IconButton'

export default ({ID, data, meta}) => {

  const { step_title, step_type, step_group, parent_steps, child_steps } = data;

  console.log( step_type );

  const { stepType } = useSelect( (select) => {
    return {
      stepType: select( STEP_TYPES_STORE_NAME ).getType( step_type )
    }
  }, [] )

  if ( ! stepType ){
    return 'loading...';
  }

  const classes = [
    step_type,
    step_group,
    ID
  ];

  return (
    <>
      <Box>
        <Card className={classes.join( ' ' )}>
          <CardHeader
            avatar={stepType.icon}
            title={step_title}
            subheader={stepType.name}
          />
          <CardContent>
            {/*<stepType.view data={data} meta={meta} />*/}
          </CardContent>
          <CardActions>
            <IconButton color={'primary'}>
              <EditIcon />
            </IconButton>
            <IconButton color={'secondary'}>
              <DeleteIcon />
            </IconButton>
          </CardActions>
        </Card>
      </Box>
    </>
  )
}