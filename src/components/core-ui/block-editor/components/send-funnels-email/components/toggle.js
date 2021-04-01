/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment, useState } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from "@material-ui/icons/ArrowBackIos";
import ReplayIcon from "@material-ui/icons/Replay";
/**
 * Internal dependencies
 */

 const Toggle = withStyles((theme) => ({
   root: {
     width: 34,
     height: 20,
     padding: 0,
     margin: theme.spacing(1),
   },
   switchBase: {
     padding: 1,
     '&$checked': {
       transform: 'translateX(14px)',
       color: theme.palette.common.white,
       '& + $track': {
         backgroundColor: theme.palette.primary.main,
         opacity: 1,
         border: 'none',
       },
     },
     '&$focusVisible $thumb': {
       color: '#52d869',
       border: '6px solid #fff',
     },
   },
   thumb: {
     width: 18,
     height: 18,
   },
   track: {
     borderRadius: 26 / 2,
     border: `1px solid ${theme.palette.grey[400]}`,
     backgroundColor: theme.palette.grey[50],
     opacity: 1,
     transition: theme.transitions.create(['background-color', 'border']),
   },
   checked: {},
   focusVisible: {},
 }))(({ classes, ...props }) => {
   return (
     <Switch
       focusVisibleClassName={classes.focusVisible}
       disableRipple
       classes={{
         root: classes.root,
         switchBase: classes.switchBase,
         thumb: classes.thumb,
         track: classes.track,
         checked: classes.checked,
       }}
       {...props}
     />
   );
 });

export default Toggle
