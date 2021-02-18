/**
 * External dependencies
 */
import { Button, Card, TextField } from "@material-ui/core";

/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { Fragment } from "@wordpress/element";
import { PinnedItems } from "@wordpress/interface";
import { Inserter } from "@wordpress/block-editor";

/**
 * External dependencies
 */
import { makeStyles } from "@material-ui/core/styles";
import ArrowBackIosIcon from '@material-ui/icons/ArrowBackIos';
import ReplayIcon from '@material-ui/icons/Replay';
/**
 * Internal dependencies
 */

import HeaderToolbar from "./header-toolbar";
import { Spinner } from "components";

const useStyles = makeStyles((theme) => ({
  root: {
    // width: "calc(100% - 19px)",
    overflow: "visible",
    borderRadius: "5px",
    padding: "20px",
  },
  button:{
    color: '#fff'
  },
  titleContainer:{
    display: 'inline-block',
    width: 'calc(100% - 342px)',
    // '& .MuiTextField-root':{
    //   width: '100%'
    // }
  }
}));

export default function Header({
  email,
  history,
  updateEmail,
  isSaving,
  handleTitleChange,
  title,
}) {
  const classes = useStyles();
  return (
    <Card className={classes.root}>
      <div
        role="region"
        aria-label={__("Email Editor primary top bar.", "groundhogg")}
        tabIndex="-1"
      >
        <HeaderToolbar>
          <Button className={classes.button} variant="contained" color="secondary" href="./admin.php?page=gh_emails">
            <ArrowBackIosIcon/>
          </Button>
          <h1 className={classes.titleContainer}>
              <TextField
                fullWidth={true}
                label="Email Title"
                value={title}
                onChange={handleTitleChange}
              />
          </h1>
          <span>
            <Button><ReplayIcon/></Button>
            <Button><ReplayIcon/></Button>

            {isSaving && <Spinner />}
            <Button className={classes.button} onClick={updateEmail} variant="contained" color="primary">
              {__("Update")}
            </Button>
            <PinnedItems.Slot scope="gh/v4/core" />
          </span>
        </HeaderToolbar>
      </div>
    </Card>
  );
}
