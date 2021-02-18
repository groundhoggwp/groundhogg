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
  inserterBtn: {
    backgroundColor: theme.palette.secondary.main,
    height: "36px",
    marginLeft: "20px",
    marginTop: "6px",
    borderRadius: "4px",
  },
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
        className="groundhogg-header primary-header edit-post-header"
        role="region"
        aria-label={__("Email Editor primary top bar.", "groundhogg")}
        tabIndex="-1"
      >
        <HeaderToolbar>
          <Button className={classes.button} variant="contained" color="secondary" href="./admin.php?page=gh_emails">
            <ArrowBackIosIcon/>
          </Button>
          <h1 className="groundhogg-header__title">
            <form noValidate autoComplete="off">
              <TextField
                className="groundhogg-header__title"
                label="Email Title"
                value={title}
                onChange={handleTitleChange}
              />
            </form>
          </h1>
          <div className="groundhogg-header__reverseChangesBtns edit-post-header__settings">
            <Button><ReplayIcon/></Button>
            <Button><ReplayIcon/></Button>
          </div>
          <div className="groundhogg-header__settings edit-post-header__settings">
            {isSaving && <Spinner />}
            <Button className={classes.button} onClick={updateEmail} variant="contained" color="primary">
              {__("Update")}
            </Button>
            <PinnedItems.Slot scope="gh/v4/core" />
          </div>
        </HeaderToolbar>
      </div>
    </Card>
  );
}
