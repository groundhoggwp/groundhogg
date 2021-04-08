/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
  SlotFillProvider,
  DropZoneProvider,
  FocusReturnProvider,
  Panel,
  PanelBody,
  PanelRow
} from "@wordpress/components";
import { useEffect, useState, useRef, createElement } from "@wordpress/element";
import { useSelect, useDispatch } from "@wordpress/data";
import {
  serialize,
  parse,
  pasteHandler,
  rawHandler,
  createBlock,
  getBlockTypes,
  getBlockInsertionPoint,
  getBlocksByClientId,
  setDefaultBlockName
} from "@wordpress/blocks";
import {
  insertBlock,
  insertDefaultBlock
} from "@wordpress/block-editor";


/**
 * External dependencies
 */
 import { Card, TextField, FormControl, FormHelperText, FormControlLabel, Select, Switch, MenuItem } from "@material-ui/core";
 import { makeStyles } from "@material-ui/core/styles";
import {
  InterfaceSkeleton,
  FullscreenMode,
  ComplementaryArea,
} from "@wordpress/interface";
import interact from "interactjs";
import { DateTime } from "luxon";
import { withStyles } from '@material-ui/core/styles';

/**
 * Internal dependencies
 */
import Notices from "./components/notices";
import Header from "./components/header";
import Sidebar from "./components/sidebar";
import BlockEditor from "./components/block-editor";
import TextEditor from "./components/text-editor";
import EditorSteps from "./components/editor-steps";
import SimpleModal from "./components/Modal";
import { getLuxonDate, matchEmailRegex } from "utils/index";
import EditPen from "components/svg/EditPen/";

import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

import "./index.scss";
import "./components/blocks";

let draggedBlockIndex = {};
let draggedBlock = {};


// Most Complete Documentation can be found here
// https://developer.wordpress.org/block-editor/reference-guides/data/data-core-block-editor/
// Works in the console! and in the code
// wp.data.dispatch( 'core/block-editor' ).insertBlock( wp.blocks.createBlock( 'groundhogg/paragraph' ) );
export default ({ editorItem, history, ...rest }) => {
  const useStyles = makeStyles((theme) => ({
    root: {

    },
    skipEmail:{
      fontSize: '14px',
      fontWeight: '300',
      margin: '10px 0px 0px 20px',
      "& label": {
        fontSize: "14px",
        fontWeight: '300'
      },
    },
    contentSideBar:{

    },
    contentFooter:{
      posiion: 'absolute',
      bottom: '0'
    },
    addBlock:{
      display: "block",
      width: "200px",
      height: "200px",
      position: "absolute",
      top: "0px",
      left: "0px"
    }

  }));
  const classes = useStyles();

  const dispatch = useDispatch(EMAILS_STORE_NAME);

  const { sendEmailById, sendEmailRaw } = useDispatch(EMAILS_STORE_NAME);
  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
    notes: defaultNotes,
    editorType,
  } = editorItem.data;

  // Global States
  const [blocksVersionTracker, setBlocksVersionTracker] = useState(0);
  const [blockVersionHistory, setBlockVersionHistory] = useState([parse(defaultContentValue)]);
  const [noticeText, setNoticeText] = useState('');

  // Header States
  const [altBodyContent, setAltBodyContent] = useState('');
  const [altBodyEnable, setAltBodyEnable] = useState('');
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);

  // Editor Contents
  const [title, setTitle] = useState(defaultTitleValue);
  const [content, setContent] = useState(defaultContentValue);
  const [blocks, setBlocks] = useState(parse(defaultContentValue).length === 0 ? [createBlock("groundhogg/paragraph", {content: defaultContentValue})] : parse(defaultContentValue)); // Old emails need to be converted to blocks
  const [subTitle, setSubTitle] = useState(defaultTitleValue);
  const [disableSubTitle, setDisableSubTitle] = useState(false);

  // Modal
  const [open, setOpen] = useState(false);

  // Side Bar States
  const [replyTo, setReplyTo] = useState("");
  const [from, setFrom] = useState("");
  const [viewType, setViewType] = useState("desktop");
  const [messageType, setMessageType] = useState("marketing");
  const [emailAlignment, setEmailAlignment] = useState("left")
  const [notes, setNotes] = useState(defaultNotes);

  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(editorItem.ID),
    }),
    []
  );

  /*
   Header Handlers
  */
  const handleTitleChange = (e) => {
    setTitle(e.target.value);
  };

  const handleOpen = () => {
    setOpen(true);
  };

  const handleClose = () => {
    setOpen(false);
  };

  const emailStepBackward = () => {
    const newBlocksVersionTracker = blocksVersionTracker-1
    if(!blockVersionHistory[newBlocksVersionTracker]){
      return;
    }
    setBlocksVersionTracker(newBlocksVersionTracker)
    handleUpdateBlocks(blockVersionHistory[newBlocksVersionTracker], {}, true);

  }
  const emailStepForward = () => {
    const newBlocksVersionTracker = blocksVersionTracker+1
    if(!blockVersionHistory[newBlocksVersionTracker]){
      return;
    }
    setBlocksVersionTracker(newBlocksVersionTracker)
    handleUpdateBlocks(blockVersionHistory[newBlocksVersionTracker], {}, true);
  }


  /*
    Saves Funnel or Email
  */
  const updateItem = (e) => {
    dispatch.updateItem(editorItem.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "ready",
        content: serialize(blocks),
        last_updated: getLuxonDate("last_updated"),
        notes //API isn't accepting these
      },
    });

    setNoticeText("Email Updated")
  };

  /*
    Email Content Handlers
  */
  const handleSubTitleChange = (e) => {
    setSubTitle(e.target.value)
  }
  const handleInsertReplacement = (e) => {
    console.log(e)
    // setSubTitle(e.target.value)
  }

  const toggleSubTitleDisable = () => {
    setDisableSubTitle(disableSubTitle ? false : true)
  }

  const handleSubjectChange = (e) => {
    setSubject(e.target.value);
  };
  const handlePreHeaderChange = (e) => {
    setPreHeader(e.target.value);
  };


  /*
    Side Bar
  */
  const handleSetFrom = (e) => {
    setFrom(e.target.value.trim());
  };
  const handleSetReplyTo = (e) => {
    setReplyTo(e.target.value.trim());
  };
  const handleEmailAlignmentChange = (alignment) => {
    setEmailAlignment(alignment);
  };
  const handleMessageType = (e) => {
    setMessageType(e.target.value);
  };


  /*
    Block Handlers
  */
  const handleUpdateBlocks = (blocks, object, updateFromHistory) => {
    console.log(blocks, object, updateFromHistory)
    // Standard calls for the block editor
    setBlocks(blocks);


    if(!updateFromHistory){
      // Build up the history tracker, except when we're going back and forth
      const newBlocksVersionTracker = blockVersionHistory.length;
      const newBlockVersionHistory = blockVersionHistory
      newBlockVersionHistory.push(blocks);

      setBlocksVersionTracker(newBlocksVersionTracker)
      setBlockVersionHistory(newBlockVersionHistory)
    }
  };

  /*
    Drag Handlers
  */
  const dragMoveListener = (event) => {
    const target = event.target;
    event.target.classList.add("drop-active");

    draggedBlock = JSON.parse(target.getAttribute("data-block"));

    // keep the dragged position in the data-x/data-y attributes
    const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
    const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

    // translate the element
    target.style.webkitTransform = target.style.transform =
      "translate(" + x + "px, " + y + "px)";

    // update the posiion attributes
    target.setAttribute("data-x", x);
    target.setAttribute("data-y", y);


    if(!y){return;}
    draggedBlockIndex = 0;
    let adjustedY = y + 505+55; //Offset by header and block size
    let classToApply = '';
    let saveBlock = false

    document.querySelectorAll('.wp-block').forEach((block, i)=>{
      block.style.borderTop = ''
      block.style.borderBottom = ''


      if(adjustedY >= block.getBoundingClientRect().top && adjustedY <= block.getBoundingClientRect().bottom ){
        draggedBlockIndex = i
        saveBlock = block
        // console.log(block.getBoundingClientRect().bottom, block.getBoundingClientRect().top)
      }

      if(draggedBlock === 0){
        // console.log(block, y, block.getBoundingClientRect().bottom)
      }

    })
    // document.querySelectorAll('.wp-block')[draggedBlockIndex].style.borderBottom = '4px solid #0075FF';
  };

  const dragEndListener = (event) => {
    document.querySelectorAll('.wp-block').forEach((block, i)=>{
      block.style.borderTop = ''
      block.style.borderBottom = ''
    });

    // keep the dragged position in the data-x/data-y attributes
    const target = event.target;
    const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
    const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

    // translate the element
    target.style.webkitTransform = target.style.transform =
      "translate(" + 0 + "px, " + 0 + "px)";

    // update the posiion attributes
    target.setAttribute("data-x", 0);
    target.setAttribute("data-y", 0);
  };
  const setupDragNDrop = (createBlock, setBlocks) =>{
      interact(".block-editor__typewriter").unset()
      interact(".block-editor__typewriter").dropzone({
        overlap: 0.75,
        ondrop: (event) => {
          let newBlocks = blocks;
          newBlocks.push(draggedBlock)
          handleUpdateBlocks(newBlocks)
        },
        ondropactivate: (event) => {},
        ondragenter: (event) => {},
        ondragleave: (event) => {},
        ondropdeactivate: (event) => {},

      })

      interact(".side-bar-drag-drop-block").unset()
      interact(".side-bar-drag-drop-block").draggable({
        cursorChecker(action, interactable, element, interacting) {
          return "grab";
        },
        // onstart: dragStartListener,
        onend: dragEndListener,
        listeners: { move: dragMoveListener },
        modifiers: [
          interact.modifiers.restrict({
            restriction: interact(".groundhogg-email-editor__email-content"),
            elementRect: { top: 0, left: 0, bottom: 1, right: 1 },
            endOnly: true,
          }),
        ],
      });
      }

  /*
    Sidebar Handlers
  */
  const handleViewTypeChange = (type) => {
    setViewType(type);
  };

  const sendTestEmail = (e) => {
    if (!matchEmailRegex(replyTo)) {
      return;
    };

    sendEmailRaw({
      to: replyTo,
      from,
      from_name: "TEST D",
      content: content,
      subject: subject,
    });

    setNoticeText(`Test Email Sent`)
  };

  const handleAltBodyContent = (e) => {
    setAltBodyContent(e.target.value);
  };

  const handleAltBodyEnable = (e) => {
    setAltBodyContent(e.target.value);
  };

  const handleChangeNotes = (e) => {
    setNotes(e.target.value);
  };

  const toggleSubTitle = () =>{
    setDisableTitle(disableSubTitle ? false : true )
  }

  useEffect(() => {
    setupDragNDrop(createBlock, setBlocks)
  }, []);
  // }, [blocks]);


  // let editorPanel;
  // switch (editorMode) {
  //   case "text":
  //   editorPanel = (
  //     <TextEditor
  //       settings={window.Groundhogg.preloadSettings}
  //       subject={subject}
  //       handleSubjectChange={handleSubjectChange}
  //       preHeader={preHeader}
  //       handlePreHeaderChange={handlePreHeaderChange}
  //       viewType={viewType}
  //       handleUpdateBlocks={handleUpdateBlocks}
  //       blocks={blocks}
  //     />
  //   );
  //
  //     break;
  //   default:
  //     editorPanel = (
  //
  //     );
  // }

  let steps = <div/>
  if(editorType === 'funnel'){
    steps = <div className={classes.contentSideBar}>
      <EditorSteps/>
    </div>
  }


  // TESTING GROUNDS
  // TESTING GROUNDS
  // TESTING GROUNDS
  // TESTING GROUNDS
  // TESTING GROUNDS
  const handleOnDrop = () => {
    console.log(blocks)
    blocks.push(createBlock('groundhogg/paragraph'))
    setBlocks(blocks)
  }

  const addBlock = () => {
    const newBlocksVersionTracker = blocksVersionTracker-1
    if(!blockVersionHistory[newBlocksVersionTracker]){
      return;
    }
    // This Works
    // let newBlocks = [blockVersionHistory[newBlocksVersionTracker][0], blockVersionHistory[newBlocksVersionTracker][1]]



    // This works
    console.log(createBlock('groundhogg/paragraph'))
    let newBlocks = blockVersionHistory[newBlocksVersionTracker].concat([blockVersionHistory[newBlocksVersionTracker][1]])

    // let newBlocks = blockVersionHistory[newBlocksVersionTracker].concat([createBlock('groundhogg/paragraph')])

    // This throws a bizarre JS error for what reason I do not know
    // let newBlocks = blockVersionHistory[newBlocksVersionTracker].push(blockVersionHistory[newBlocksVersionTracker][1])

    setBlocksVersionTracker(newBlocksVersionTracker)
    handleUpdateBlocks(newBlocks, {}, true);
  }

  return (
    <>
      <img src={require('./webpack-test.jpg').default}/>
      <div className="Groundhogg-BlockEditor">
        {steps}
        <SimpleModal open={open}/>

        <div className={classes.addBlock} onClick={addBlock}>Add blocks </div>
        <FullscreenMode isActive={false} />
        <Notices text={noticeText}/>
        <SlotFillProvider>
          <DropZoneProvider>
            <FocusReturnProvider>
              <Header
                editorItem={editorItem}
                history={history}
                updateItem={updateItem}
                closeEditor={() => {}}
                isSaving={isSaving}
                title={title}
                handleTitleChange={handleTitleChange}
                editorType={editorType}
                handleOpen={handleOpen}
                emailStepBackward={emailStepBackward}
                emailStepForward={emailStepForward}
              />


              <div className={classes.content}>

                  {/*
                  <SendEmailComponent/>

                  <BlocksPreview blocks={blocks}/>
                  */}

                  <BlockEditor
                    settings={window.Groundhogg.preloadSettings}
                    subject={subject}
                    handleSubjectChange={handleSubjectChange}
                    preHeader={preHeader}
                    handlePreHeaderChange={handlePreHeaderChange}
                    viewType={viewType}
                    handleUpdateBlocks={handleUpdateBlocks}
                    blocks={blocks}
                    editorType={editorType}
                  />
              </div>

              <Sidebar sendTestEmail={sendTestEmail} handleViewTypeChange={handleViewTypeChange} handleSetFrom={handleSetFrom} handleSetReplyTo={handleSetReplyTo}  messageType={messageType} handleMessageType={handleMessageType} emailAlignment={emailAlignment} handleEmailAlignmentChange={handleEmailAlignmentChange} notes={notes} handleChangeNotes={handleChangeNotes}/>

              <ComplementaryArea.Slot scope="gh/v4/core" />

            </FocusReturnProvider>
          </DropZoneProvider>
        </SlotFillProvider>


      </div>
    </>
  );
};
