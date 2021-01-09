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

/**
 * Internal dependencies
 */

import HeaderToolbar from "./header-toolbar";
// import HeaderPrimary from './header-primary';<HeaderPrimary />
import HeaderSecondary from "./header-secondary";
import { Spinner } from "components";

const useStyles = makeStyles((theme) => ({
  root: {
    width: "calc(100% - 19px)",
    overflow: "visible",
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
  saveDraft,
  publishEmail,
  closeEditor,
  isSaving,
  handleTitleChange,
  title,
  handleViewTypeChange,
  sendTestEmail,
  handleTestEmailChange,
  testEmail,
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
          {/* This is totally undocumented, it pops up and import blocks fine but the add doesn't work at all without documentation I can't figure out how to use the functions without a ton of digging
          <div className={classes.inserterBtn}>
            <Inserter />
          </div>*/}
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
          <div className="groundhogg-header__settings edit-post-header__settings">
            {isSaving && <Spinner />}
            <Button onClick={saveDraft} variant="contained" color="secondary">
              {__("Save Draft")}
            </Button>
            <Button onClick={publishEmail} variant="contained" color="primary">
              {__("Publish")}
            </Button>
            <Button onClick={closeEditor} variant="contained" color="secondary">
              {__("Close")}
            </Button>
            <PinnedItems.Slot scope="gh/v4/core" />
          </div>
        </HeaderToolbar>
      </div>

      <div
        className="groundhogg-header secondary-header edit-post-header"
        role="region"
        aria-label={__("Email Editor secondary top bar.", "groundhogg")}
        tabIndex="-1"
      >
        <HeaderToolbar>
          <HeaderSecondary
            handleViewTypeChange={handleViewTypeChange}
            sendTestEmail={sendTestEmail}
            handleTestEmailChange={handleTestEmailChange}
            testEmail={testEmail}
          />
        </HeaderToolbar>
      </div>
    </Card>
  );
}
