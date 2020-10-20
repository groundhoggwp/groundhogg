import { useState } from 'react'
import Box from '@material-ui/core/Box'
import TextField from '@material-ui/core/TextField/TextField'
import Grid from '@material-ui/core/Grid'
import makeStyles from '@material-ui/core/styles/makeStyles'
import Paper from '@material-ui/core/Paper/Paper'
import { select, useDispatch, useSelect } from '@wordpress/data'
import { STEPS_STORE_NAME } from 'data/steps'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import Button from '@material-ui/core/Button'
import { useEffect } from '@wordpress/element'

const useStyles = makeStyles((theme) => ( {
  box: {
    padding: theme.spacing(1),
  },
  stepPaper: {
    padding: theme.spacing(2),
    textAlign: 'center',
    color: theme.palette.text.secondary,
  },
} ))

const SelectStepButton = ({ type, onSelect }) => {

  const { name, icon } = type
  return (
    <Button
      size={ 'medium' }
      variant={ 'outlined' }
      onClick={ () => onSelect(type.type) }
      startIcon={ icon }
    >
      { name }
    </Button>
  )
}

export default (props) => {

  const {
    steps,
    stepGroup,
    parentSteps,
    childSteps,
    stepOrder,
  } = props

  const classes = useStyles()
  const [search, setSearch] = useState('')
  const { createItems, updateItems } = useDispatch(STEPS_STORE_NAME)

  // Get the current If of the funnel
  const { ID } = select(FUNNELS_STORE_NAME).getItem()

  // Get the current state of the steps
  const { items, createdItems } = useSelect((select) => {
    return {
      items: select(STEPS_STORE_NAME).getItems(),
      createdItems: select(STEPS_STORE_NAME).getCreatedItems(),
    }
  }, [])

  // List for when items change
  useEffect(() => {

    if ( ! createdItems ){
      return;
    }

    const itemsToUpdate = [];
    const newStepId = createdItems.pop().ID

    parentSteps.forEach( parent => {

      const { child_steps } = select( STEPS_STORE_NAME ).getItem( parent ).data;

      itemsToUpdate.push( {
        ID: parent,
        data: {
          // remove edge from parent to child
          // add new edge from parent to new child
          child_steps: child_steps.filter( child => ! childSteps.includes( child ) ).push( newStepId ),
        }
      } )
    } )

    childSteps.forEach( child => {

      const { parent_steps } = select( STEPS_STORE_NAME ).getItem( child ).data;

      itemsToUpdate.push( {
        ID: child,
        data: {
          // remove edge from child to parent
          // add new edge from child to new parent
          parent_steps: parent_steps.filter( parent => ! parentSteps.includes( parent ) ).push( newStepId ),
        }
      } )
    } )

    updateItems( itemsToUpdate );

  }, [createdItems])

  // reduce step types into rows of three
  const reducer = (acc, curr) => {
    if (acc.length > 0 && acc[acc.length - 1].length < 3) {
      acc[acc.length - 1].push(curr)
    }
    else {
      acc.push([curr])
    }
    return acc
  }

  const handleTypeChosen = (type) => {

    // Create the step
    const newStepData = {
      step_type: type,
      step_order: stepOrder || 1,
      step_group: stepGroup,
      child_steps: childSteps || [],
      parent_steps: parentSteps || [],
      funnel_id: ID,
    }

    createItems([
      {
        data: newStepData,
      },
    ])
  }

  return (
    <Box className={ classes.box }>
      <Box className={ classes.box }>
        <TextField
          value={ search }
          onChange={ (e) => setSearch(e.target.value) }
          label={ 'Search' }
          type={ 'search' }
          variant={ 'outlined' }
          size={ 'small' }
          fullWidth
        />
      </Box>
      <Box className={ classes.box }>
        <Grid container spacing={ 2 }>
          {
            steps.filter(item => item.name.match(new RegExp(search, 'i'))).
              reduce(reducer, []).
              map(row => {
                return (
                  <Grid container item xs={ 12 } spacing={ 2 }>
                    {
                      row.map(item => {
                        return (
                          <Grid item xs={ 4 }>
                            <SelectStepButton
                              type={ item }
                              onSelect={ handleTypeChosen }
                            />
                          </Grid>
                        )
                      })
                    }
                  </Grid>
                )
              })
          }
        </Grid>
      </Box>
    </Box>
  )
}