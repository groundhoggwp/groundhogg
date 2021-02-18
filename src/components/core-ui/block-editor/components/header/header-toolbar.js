/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { NavigableToolbar } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";

const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100%)",
    overflow: "visible",
    borderRadius: "5px",
    padding: "20px",
  },
  button:{
    color: '#fff'
  },
  inserterBtn: {
    backgroundColor: theme.palette.secondary.main,
    height: "36px",
    marginLeft: "20px",
    marginTop: "6px",
    borderRadius: "4px",
  },
}));

export default function HeaderToolbar({ children }) {
  const classes = useStyles();
  const displayBlockToolbar = true; // May connect to GH core state.

  const toolbarAriaLabel = displayBlockToolbar
    ? /* translators: accessibility text for the editor toolbar when Top Toolbar is on */
      __("Document and block tools")
    : /* translators: accessibility text for the editor toolbar when Top Toolbar is off */
      __("Document tools");

  return (
    <NavigableToolbar
      className={classes.root}
      aria-label={toolbarAriaLabel}
    >
      {children}
    </NavigableToolbar>
  );
}
