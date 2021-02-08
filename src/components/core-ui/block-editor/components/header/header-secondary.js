/**
 * External dependencies
 */
import Checkbox from "@material-ui/core/Checkbox";
import Button from "@material-ui/core/Button";
import CodeIcon from "@material-ui/icons/Code";
import LineStyleIcon from "@material-ui/icons/LineStyle";
import SurroundSoundIcon from "@material-ui/icons/SurroundSound";
import FindReplaceIcon from "@material-ui/icons/FindReplace";
import ChromeReaderModeIcon from "@material-ui/icons/ChromeReaderMode";
import DesktopMacIcon from "@material-ui/icons/DesktopMac";
import SmartphoneIcon from "@material-ui/icons/Smartphone";
import UpdateIcon from "@material-ui/icons/Update";
import { makeStyles } from "@material-ui/core/styles";
import TextField from "@material-ui/core/TextField";
import Typography from "@material-ui/core/Typography";

/**
 * WordPress dependencies
 */
import { __, _x } from "@wordpress/i18n";
import { useSelect, useDispatch } from "@wordpress/data";
import { Card } from "@material-ui/core";
import { Fragment, useEffect, useState, useMemo } from "@wordpress/element";

/**
 * Internal dependencies
 */
import ToolbarItem from "./toolbar-item"; // Stop-gap while WP catches up.
import Dialog from "../dialog/";
import { CORE_STORE_NAME } from "data/core";

const useStyles = makeStyles({
  root: {
    width: "100%",
    display: "flex",
    justifyContent: "flex-start",
    paddingTop: "10px",
  },
  button: {
    color: '#fff',
    marginRight: "8px",
  },
});
export default ({
  handleViewTypeChange,
  sendTestEmail,
  testEmail,
  handleTestEmailChange,
  altBodyContent,
  handleAltBodyContent,
  altBodyEnable,
  handleAltBodyEnable,
}) => {
  const classes = useStyles();

  const { editorMode, isInserterEnabled } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isInserterOpened: select(CORE_STORE_NAME).isInserterOpened(),
    }),
    []
  );

  const { switchEditorMode, setIsInserterOpened } = useDispatch(
    CORE_STORE_NAME
  );

  const isTextModeEnabled = editorMode === "text";

  return (
    <div className={classes.root}>
      <ToolbarItem
        as={Button}
        className={classes.button + " groundhogg-header-toolbar__mode-toggle"}
        variant="contained"
        color="primary"
        size="small"
        onClick={() => switchEditorMode(isTextModeEnabled ? "visual" : "text")}
        startIcon={isTextModeEnabled ? <LineStyleIcon /> : <CodeIcon />}
        /* translators: button label text should, if possible, be under 16
		characters. */
        label={_x(
          "Toggle between HTML and Visual Mode",
          "Generic label for mode toggle button"
        )}
      >
        {__("Editor Mode")}
      </ToolbarItem>
      <ToolbarItem
        as={Button}
        className={
          classes.button + " groundhogg-header-toolbar__broadcast-link"
        }
        variant="contained"
        color="primary"
        size="small"
        onClick={() => {}}
        onMouseDown={(event) => {
          event.preventDefault();
        }}
        startIcon={<SurroundSoundIcon />}
        /* translators: button label text should, if possible, be under 16
		characters. */
        label={_x("Link to Broadcast", "Generic label for link to broadcasts")}
      >
        {__("Broadcast")}
      </ToolbarItem>
      <ToolbarItem
        as={Dialog}
        className={
          classes.button + " groundhogg-header-toolbar__replacements-modal"
        }
        variant="contained"
        color="primary"
        size="md"
        buttonIcon={<FindReplaceIcon />}
        buttonTitle={__("Replacements")}
        title={__("Replacements")}
        content={__(
          "Replacements Table. TBD on how we want to parse this in here."
        )}
        dialogButtons={[{ color: "primary", label: __("Insert") }]}
        /* translators: button label text should, if possible, be under 16
		characters. */
        label={_x(
          "Open replacements list",
          "Generic label for replacements button"
        )}
        dialogAction={() => {}}
      />
      <ToolbarItem
        as={Dialog}
        buttonIcon={<ChromeReaderModeIcon />}
        className={
          classes.button + " groundhogg-header-toolbar__alt-body-modal"
        }
        variant="contained"
        color="primary"
        size="small"
        buttonTitle={__("Email Alt-Body")}
        title={__("Email Alt-Body")}
        content={__(
          <Fragment>
            <Typography variant="p" component="p">
              Use this custom Alt Body text
            </Typography>
            <Typography variant="span" component="span">
              Enable
            </Typography>
            <Checkbox
              checked={altBodyEnable}
              onChange={handleAltBodyEnable}
              name=""
              color="primary"
            />
            <Typography variant="p" component="p">
              If left un-enabled an alt-body will be auot-generated.
            </Typography>
            <br />
            <TextField
              onChange={handleAltBodyContent}
              label={__("Alt Body Content")}
              value={altBodyContent}
              multiline
              fullWidth={true}
              placeholder={__(
                "Alt Body Content. Will need to build out custom component here."
              )}
            />
          </Fragment>
        )}
        dialogButtons={[{ color: "primary", label: __("Done") }]}
        label={_x(
          "Open replacements list",
          "Generic label for replacements button"
        )}
        dialogAction={() => {}}
      />

      <ToolbarItem
        as={Dialog}
        buttonIcon={<UpdateIcon />}
        className={
          classes.button + " groundhogg-header-toolbar__update-and-test"
        }
        variant="contained"
        color="primary"
        size="small"
        buttonTitle={__("Test")}
        title={__("Test")}
        content={__(
          <TextField
            onChange={handleTestEmailChange}
            label={__("Send Test Email to ...")}
            value={testEmail}
            placeholder={__(
              "Pre Header Text: Used to summarize the content of the email."
            )}
          />
        )}
        dialogButtons={[
          { color: "secondary", label: __("Cancel") },
          { color: "primary", label: __("Done") },
        ]}
        label={_x("Test Link", "Generic label for replacements button")}
        dialogAction={sendTestEmail}
      />

      <ToolbarItem
        as={Button}
        className={
          classes.button + " groundhogg-header-toolbar__mobile-device-toggle"
        }
        variant="contained"
        color="secondary"
        size="small"
        onMouseDown={(event) => {
          event.preventDefault();
          handleViewTypeChange("mobile");
        }}
        startIcon={<SmartphoneIcon />}
        /* translators: button label text should, if possible, be under 16
		characters. */
        label={_x(
          "Mobile Device Toggle",
          "Generic label for mobile device toggle button"
        )}
      ></ToolbarItem>
      <ToolbarItem
        as={Button}
        className={
          classes.button + " groundhogg-header-toolbar__large-device-toggle"
        }
        variant="contained"
        color="secondary"
        size="small"
        onMouseDown={(event) => {
          event.preventDefault();
          handleViewTypeChange("desktop");
        }}
        startIcon={<DesktopMacIcon />}
        size="small"
        /* translators: button label text should, if possible, be under 16
		characters. */
        label={_x(
          "Desktop Preview Toggle",
          "Generic label for desktop preview button"
        )}
      ></ToolbarItem>
    </div>
  );
};
