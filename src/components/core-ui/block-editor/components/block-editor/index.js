/**
 * WordPress dependencies
 */
import "@wordpress/format-library";
import { __ } from "@wordpress/i18n";
import { useSelect, useDispatch } from "@wordpress/data";
import { useEffect, useState, useMemo, useRef } from "@wordpress/element";
import { serialize, parse } from "@wordpress/blocks";
import { uploadMedia } from "@wordpress/media-utils";
import {
  BlockEditorKeyboardShortcuts,
  BlockEditorProvider,
  BlockList,
  BlockInspector,
  WritingFlow,
  ObserveTyping,
  Typewriter,
  CopyHandler,
  BlockSelectionClearer,
  MultiSelectScrollIntoView,
} from "@wordpress/block-editor";

import { VisualEditorGlobalKeyboardShortcuts } from "@wordpress/editor";

import { Popover } from "@wordpress/components";

/**
 * External dependencies
 */
import Card from "@material-ui/core/Card";
import Paper from "@material-ui/core/Paper";
import Grid from "@material-ui/core/Grid";
import TextField from "@material-ui/core/TextField";
import { makeStyles } from "@material-ui/core/styles";

/**
 * Internal dependencies
 */
import BlocksPanel from "../sidebar/components/blocks-panel.js";
import ExpandablePanel from "../expandable-panel/";
import { createTheme } from "../../../../../theme";
const theme = createTheme({});

export default function ({
  settings: _settings,
  subject,
  handleSubjectChange,
  preHeader,
  handlePreHeaderChange,
  viewType,
  handleUpdateBlocks,
  blocks,
  editorType,
}) {
  const useStyles = makeStyles((theme) => ({
    root: {
      position: "absolute",
      top: "385px",
      right: "0",
      bottom: "0",
      left: "0",
      width: "calc(100%)",
      minHeight: "calc(100vh - 32px)",
      paddingBottom: "280px",
      overflowY: "auto",
      overflowX: "hidden",
    },
    emailHeader: {},
    subjectHeaderRow: {
      display: "block",
      width: "calc(100% - 75px)",
      margin: "0 auto 0 auto",
      fontSize: "16px",
      padding: "20px",

      borderBottom: "1px solid rgba(16, 38, 64, 0.15)",
      "& label": {
        fontWeight: "500",
      },
    },
    subjectInputs: {
      display: "inline-block",
      width: "calc(100% - 140px)",
      fontSize: "16px",
      outline: "none",
      border: "none",
      boxShadow: "none",
      marginLeft: "10px",
    },
    emailContainer: {
      width:
        editorType === "email" ? "calc(100% - 360px)" : "calc(100% - 680px)",
      marginLeft: editorType === "email" ? "0px" : "305px",
    },
    emailContent: {
      position: "relative",
      display: "block",
      width: viewType === "desktop" ? "600px" : "320px",
      minHeight: "300px", // Needed for new emails so drag & drop works
      margin: "10px auto 30px auto",
      padding: "10px",
    },
  }));

  const { createInfoNotice } = useDispatch("core/notices");
  const classes = useStyles();
  const canUserCreateMedia = useSelect((select) => {
    const _canUserCreateMedia = select("core").canUser("create", "media");
    return _canUserCreateMedia || _canUserCreateMedia !== false;
  }, []);

  const settings = useMemo(() => {
    if (!canUserCreateMedia) {
      return _settings;
    }
    return {
      ..._settings,
      mediaUpload({ onError, ...rest }) {
        uploadMedia({
          wpAllowedMimeTypes: _settings.allowedMimeTypes,
          onError: ({ message }) => onError(message),
          ...rest,
        });
      },
    };
  }, [canUserCreateMedia, _settings]);

  if (!settings.hasOwnProperty("__experimentalBlockPatterns")) {
    settings.__experimentalBlockPatterns = [];
  }

  const blockEditorEl = useRef(null);

  return (
    <div className={classes.root} ref={blockEditorEl}>
      <BlockEditorProvider
        value={blocks}
        settings={settings}
        onInput={handleUpdateBlocks}
        onChange={handleUpdateBlocks}
      >
        <div className={classes.emailContainer}>
          <Card>
            <form noValidate autoComplete="off" className={classes.emailHeader}>
              <div className={classes.subjectHeaderRow}>
                <label>Subject:</label>
                <input
                  className={classes.subjectInputs}
                  onChange={handleSubjectChange}
                  label={"Subject"}
                  value={subject}
                />
                <br />
              </div>
              <div className={classes.subjectHeaderRow}>
                <label>Pre Header Text:</label>
                <input
                  className={classes.subjectInputs}
                  onChange={handlePreHeaderChange}
                  label={"Pre Header"}
                  value={preHeader === "" ? "Pre Header" : preHeader}
                />
              </div>
            </form>

            <div className={classes.emailContent}>
              <BlockSelectionClearer className={classes}>
                <VisualEditorGlobalKeyboardShortcuts />
                <MultiSelectScrollIntoView />
                {/* Add Block Button */}
                <BlockEditorKeyboardShortcuts.Register />
                <Popover.Slot left={300} top={550} />
                <Popover.Slot name="block-toolbar" left={300} top={550} />
                <Typewriter>
                  <CopyHandler>
                    <WritingFlow>
                      <ObserveTyping>
                        {/* Rendered blocks */}
                        <BlockList />
                      </ObserveTyping>
                    </WritingFlow>
                  </CopyHandler>
                </Typewriter>
              </BlockSelectionClearer>
            </div>
          </Card>

          <ExpandablePanel
            title={"Advanced"}
            fontSize={"16px"}
            width={"calc(100%)"}
            margin={"20px 0px 0px 0px"}
            contents={<div>contents</div>}
          />
        </div>

        <BlocksPanel.InspectorFill>
          <BlockInspector />
        </BlocksPanel.InspectorFill>
      </BlockEditorProvider>
    </div>
  );
}
