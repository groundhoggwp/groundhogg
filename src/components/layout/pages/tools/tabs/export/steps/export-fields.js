import { Fragment, render, useState } from '@wordpress/element'

import FormControlLabel from '@material-ui/core/FormControlLabel'

import Checkbox from '@material-ui/core/Checkbox'
import { FieldMap } from 'components/core-ui/field-map'
import Button from '@material-ui/core/Button'
import { __ } from '@wordpress/i18n'

export const ExportFields = (props) => {

  // console.log(window.Groundhogg.export_default_keys)
  // console.log(window.Groundhogg.export_meta_keys)

  const [fields, setFields] = useState([])
  const { data, setData ,handleNext } = props

  const toggleCheckbox = (event) => {
    let selected = fields
    if (selected.includes(event.target.value)) {
      let arr = selected.filter((ele) => ele !== event.target.value)
      setFields(arr)
    } else {
      setFields(selected.concat(event.target.value))

    }
  }

  const handleExport = () => {
    setData({
      ...data,
      ...{
        fields: fields
      }
    })
    handleNext();
  }

  return (

    <Fragment>
      <div style={{
        padding: 24,
        background: '#fff',
        maxHeight: 500,
        overflow: 'scroll'
      }}>
        <div>
          <h1> Basic Contact info </h1>

          <table>

            {window.Groundhogg.export_default_keys.map((key) => {
              return (
                <tr>
                  <td>
                    <FormControlLabel
                      control={<Checkbox color="primary" value={key} checked={fields.includes(key)}
                                         onChange={(event) => {
                                           toggleCheckbox(event)
                                         }}/>}
                      label={key}
                      labelPlacement="end"
                    />
                  </td>
                </tr>
              )
            })}
          </table>
          <h1> meta fields </h1>
          <table>
            {window.Groundhogg.export_meta_keys.map((key) => {
              return (
                <tr>
                  <td>
                    <FormControlLabel
                      control={<Checkbox color="primary" value={key} checked={fields.includes(key)}
                                         onChange={(event) => {
                                           toggleCheckbox(event)
                                         }}/>}
                      label={key}
                      labelPlacement="end"
                    />
                  </td>
                </tr>
              )
            })}
          </table>

        </div>
      </div>
      <div style={{
        padding: 24,
        background: '#fff',
        marginTop: 10
      }}>

        <Button variant="contained" color="primary" onClick={handleExport}>
          {__('Export', 'groundhogg')}
        </Button>
      </div>
    </Fragment>

  )

}
//
// import React from 'react'
// import { makeStyles } from '@material-ui/core/styles'
// import Grid from '@material-ui/core/Grid'
// import List from '@material-ui/core/List'
// import ListItem from '@material-ui/core/ListItem'
// import ListItemIcon from '@material-ui/core/ListItemIcon'
// import ListItemText from '@material-ui/core/ListItemText'
// import Checkbox from '@material-ui/core/Checkbox'
// import Button from '@material-ui/core/Button'
// import Paper from '@material-ui/core/Paper'
//
// const useStyles = makeStyles((theme) => ({
//   root: {
//     margin: 'auto',
//   },
//   paper: {
//     width: 200,
//     height: 230,
//     overflow: 'auto',
//   },
//   button: {
//     margin: theme.spacing(0.5, 0),
//   },
// }))
//
// function not (a, b) {
//   return a.filter((value) => b.indexOf(value) === -1)
// }
//
// function intersection (a, b) {
//   return a.filter((value) => b.indexOf(value) !== -1)
// }
//
// export const ExportFields = (props) => {
//
//   let arr =window.Groundhogg.export_default_keys.concat(window.Groundhogg.export_meta_keys);
//   const classes = useStyles()
//   const [checked, setChecked] = React.useState([])
//   const [left, setLeft] = React.useState(arr)
//   const [right, setRight] = React.useState([])
//
//   const leftChecked = intersection(checked, left)
//   const rightChecked = intersection(checked, right)
//
//   const handleToggle = (value) => () => {
//     const currentIndex = checked.indexOf(value)
//     const newChecked = [...checked]
//
//     if (currentIndex === -1) {
//       newChecked.push(value)
//     } else {
//       newChecked.splice(currentIndex, 1)
//     }
//
//     setChecked(newChecked)
//   }
//
//   const handleAllRight = () => {
//     setRight(right.concat(left))
//     setLeft([])
//   }
//
//   const handleCheckedRight = () => {
//     setRight(right.concat(leftChecked))
//     setLeft(not(left, leftChecked))
//     setChecked(not(checked, leftChecked))
//   }
//
//   const handleCheckedLeft = () => {
//     setLeft(left.concat(rightChecked))
//     setRight(not(right, rightChecked))
//     setChecked(not(checked, rightChecked))
//   }
//
//   const handleAllLeft = () => {
//     setLeft(left.concat(right))
//     setRight([])
//   }
//
//   const customList = (items) => (
//     <Paper className={classes.paper}>
//       <List dense component="div" role="list">
//         {items.map((value) => {
//           const labelId = `transfer-list-item-${value}-label`
//
//           return (
//             <ListItem key={value} role="listitem" button onClick={handleToggle(value)}>
//               <ListItemIcon>
//                 <Checkbox
//                   checked={checked.indexOf(value) !== -1}
//                   tabIndex={-1}
//                   disableRipple
//                   inputProps={{ 'aria-labelledby': labelId }}
//                 />
//               </ListItemIcon>
//               <ListItemText id={labelId} primary={value}/>
//             </ListItem>
//           )
//         })}
//         <ListItem/>
//       </List>
//     </Paper>
//   )
//
//   return (
//     <div>
//       <Grid container spacing={2} justify="center" alignItems="center" className={classes.root}>
//         <Grid item>{customList(left)}</Grid>
//         <Grid item>
//           <Grid container direction="column" alignItems="center">
//             <Button
//               variant="outlined"
//               size="small"
//               className={classes.button}
//               onClick={handleAllRight}
//               disabled={left.length === 0}
//               aria-label="move all right"
//             >
//               ≫
//             </Button>
//             <Button
//               variant="outlined"
//               size="small"
//               className={classes.button}
//               onClick={handleCheckedRight}
//               disabled={leftChecked.length === 0}
//               aria-label="move selected right"
//             >
//               &gt;
//             </Button>
//             <Button
//               variant="outlined"
//               size="small"
//               className={classes.button}
//               onClick={handleCheckedLeft}
//               disabled={rightChecked.length === 0}
//               aria-label="move selected left"
//             >
//               &lt;
//             </Button>
//             <Button
//               variant="outlined"
//               size="small"
//               className={classes.button}
//               onClick={handleAllLeft}
//               disabled={right.length === 0}
//               aria-label="move all left"
//             >
//               ≪
//             </Button>
//           </Grid>
//         </Grid>
//         <Grid item>{customList(right)}</Grid>
//       </Grid>
//     </div>
//   )
// }
