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
  // insertBlock,
  // insertBlocks,
  insertDefaultBlock,
  getBlockTypes,
  getBlockInsertionPoint,
  setDefaultBlockName
} from "@wordpress/blocks";

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
let startInteractJS = false;


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

  // Editor Contents
  const [title, setTitle] = useState(defaultTitleValue);
  const [content, setContent] = useState(defaultContentValue);
  const [blocks, setBlocks] = useState(parse(defaultContentValue).length === 0 ? [createBlock("groundhogg/paragraph", {content: defaultContentValue})] : parse(defaultContentValue));
  const [subTitle, setSubTitle] = useState(defaultTitleValue);
  const [disableSubTitle, setDisableSubTitle] = useState(false);

  // Modal
  const [open, setOpen] = useState(false);

  // Side Bar States
  const [replyTo, setReplyTo] = useState("");
  const [from, setFrom] = useState("");
  const [viewType, setViewType] = useState("desktop");
  const [messageType, setMessageType] = useState("marketing");
  const [emailAlignment, setEmailAlignment] = useState("left");

  const [notes, setNotes] = useState(defaultNotes);

  // Unused
  const [altBodyContent, setAltBodyContent] = useState('');
  const [altBodyEnable, setAltBodyEnable] = useState('');
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);


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

  const handleUpdateBlocks  = (blocks) => {
    setBlocks(blocks)
  }

  /*
    Saves Funnel or Email
  */
  const updateItem = (e) => {
    console.log(content)
    dispatch.updateItem(editorItem.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "ready",
        content,
        last_updated: getLuxonDate("last_updated"),
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
  const handleContentChangeDraggedBlock = () => {

  };

  const handlesetBlocks = (blocks, selectionObj, updateFromHistory) => {
    // Standard calls for the block editor
    setBlocks(blocks);
    setContent(serialize(blocks));


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
    document.querySelectorAll('.wp-block')[draggedBlockIndex].style.borderBottom = '1px solid #0075FF';
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
  const setupDragNDrop = () =>{
      interact(".block-editor__typewriter").unset()
      interact(".block-editor__typewriter").dropzone({
        overlap: 0.75,
        ondropactivate: (event) => {},

        ondragenter: (event) => {
          startInteractJS = true
          var dropzoneElement = event.target.classList.add("active");

        },
        ondragleave: (event) => {
          var dropzoneElement = event.target.classList.remove("active");
        },
        ondrop: (event) => {
          // console.log('dropped')
          console.log(blocks)
          x.a = '1123'
          var dropzoneElement = event.target.classList.remove("active");

          handleContentChangeDraggedBlock();
        },
        ondropdeactivate: (event) => {},
      });

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
      let x = {
          aInternal: 10,
          aListener: function(val) {},
          set a(val) {
            this.aInternal = val;
            this.aListener(val);
          },
          get a() {
            return this.aInternal;
          },
          registerListener: function(listener) {
            this.aListener = listener;
          }
        }
      x.registerListener(function(val) {
        emailStepBackward()
        emailStepForward()
        console.log("Someone changed the value of x.a to " + val);
      });



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
    console.log('usee effect this should hppaen 1')
    // setupDragNDrop()
  }, []);


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


  // const dispatchBlockEditor = useDispatch("core/block-editor");
  //
  //
  //
  // var content123 = "Test content";
  // var el = createElement();
  // var name = 'groundhogg/html';
  // let insertedBlock = createBlock(name, {
  //     content: "asdfasdfasdf",
  // });
  //
  // dispatchBlockEditor.insertBlock(insertedBlock);
  // // let newBlocks = blocks;
  // // newBlocks.splice(draggedBlockIndex, 0, createBlock(draggedBlock.name));
  // var name = 'core/paragraph';
  // // var name = 'core/html';
  // // insertedBlock = wp.blocks.createBlock(name, {
  // //     content: content,
  // // });
  // var content = "Test content";
  // const newBLock = createBlock(name, {
  //     content: content,
  // });
  // insertBlock(newBLock)
  // console.log('create Block')
  // // handleUpdateBlocks(newBlocks, {},false);

  // const { clientId } = ownProps;
  // const { replaceInnerBlocks, selectBlock, insertBlock } = useDispatch(CORE_STORE_NAME);

  // const {
//     insertBlock
//     // isDefaultColumns,
//     // innerColumns = [],
//     // hasParents,
//     // parentBlockAlignment,
//     // editorSidebarOpened,
//   } = useSelect(
//   (select) => ({
//     insertBlock: select(CORE_STORE_NAME).insertBlock(),
//     // isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
//     // item: select(CORE_STORE_NAME).getItem(editorItem.ID),
//   }),
//   []
// );

  // Get verticalAlignment from Columns block to set the same to new Column
  // const { verticalAlignment } = getBlockAttributes(clientId);

  // const innerBlocks = getBlocks(clientId);

  // const insertedBlock = createBlock("groundhogg/paragraph", {
  //   content: "asdfasdf"
  // });
  //
  // insertBlock(insertedBlock)

  // replaceInnerBlocks(clientId, [...innerBlocks, insertedBlock], true);
  // selectBlock(insertedBlock.clientId);

  // console.log('Re-render', blocks)

  return (
    <>
      <img src={require('./webpack-test.jpg').default}/>
      <div className="Groundhogg-BlockEditor">
        {steps}
        <SimpleModal open={open}/>


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
