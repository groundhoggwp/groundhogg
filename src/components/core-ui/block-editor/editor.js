/**
 * WordPress dependencies
 */
import {
  Popover,
  SlotFillProvider,
  DropZoneProvider,
  FocusReturnProvider,
} from "@wordpress/components";

import {
  InterfaceSkeleton,
  FullscreenMode,
  ComplementaryArea,
} from "@wordpress/interface";

import { PostTextEditor } from "@wordpress/editor";
import { useEffect, useState } from "@wordpress/element";
import { select, useSelect, useDispatch } from "@wordpress/data";
import { serialize, parse, pasteHandler, rawHandler, createBlock, insertBlock, insertBlocks, insertDefaultBlock, getBlockTypes, getBlockInsertionPoint } from "@wordpress/blocks";

/**
 * External dependencies
 */
import interact from "interactjs";
import { DateTime } from "luxon";

/**
 * Internal dependencies
 */
import Notices from "./components/notices";
import Header from "./components/header";
import Sidebar from "./components/sidebar";
import BlockEditor from "./components/block-editor";
import { getLuxonDate } from "utils/index";
import { CORE_STORE_NAME, EMAILS_STORE_NAME } from "data";

let setup = false;
let draggedBlock = {}
export default ({ settings, email, history }) => {
  const dispatch = useDispatch(EMAILS_STORE_NAME);

  const {
    title: defaultTitleValue,
    subject: defaultSubjectValue,
    pre_header: defaultPreHeaderValue,
    content: defaultContentValue,
  } = email.data;

  const [title, setTitle] = useState(defaultTitleValue);
  // const [draggedBlock, setDraggedBlock] = useState(null);
  const [subject, setSubject] = useState(defaultSubjectValue);
  const [preHeader, setPreHeader] = useState(defaultPreHeaderValue);
  const [content, setContent] = useState(defaultContentValue);
  const [viewType, setViewType] = useState("desktop");
  const [blocks, updateBlocks] = useState(parse(defaultContentValue));

  const { editorMode, isSaving, item } = useSelect(
    (select) => ({
      editorMode: select(CORE_STORE_NAME).getEditorMode(),
      isSaving: select(CORE_STORE_NAME).isItemsUpdating(),
      item: select(EMAILS_STORE_NAME).getItem(email.ID),
    }),
    []
  );

  if (!item.hasOwnProperty("ID")) {
    return null;
  }

  const handleTitleChange = (e) => {
    setTitle(e.target.value);
  };

  const handleSubjectChange = (e) => {
    setSubject(e.target.value);
  };
  const handlePreHeaderChange = (e) => {
    setPreHeader(e.target.value);
  };
  const handleContentChange = (blocks) => {
    console.log('handleconten', blocks)
    if (!Array.isArray(blocks)) {
      return;
    }
    setContent(serialize(blocks));
  };



  const handleContentChangeDraggedBlock = () => {


    // const blockData = {"name":"groundhogg/divider","icon":{"src":"shield"},"keywords":["Groundhogg - Divider"],"attributes":{"height":{"type":"number","default":2},"width":{"type":"number","default":80},"color":{"type":"string"},"className":{"type":"string"}},"providesContext":{},"usesContext":[],"supports":{},"styles":[],"title":"Groundhogg - Divider","category":"text","description":"Add Space in your email","variations":[]}
    let newBlock = createBlock(draggedBlock.name)
    // let newBlock = createBlock(blockData.name, blockData.attributes)



    const newBlocks = blocks

    console.log(newBlock, newBlocks)

    newBlocks.push(newBlock)


    handleUpdateBlocks(newBlocks)

    //
    // let insertionPoint = select( 'core/block-editor' ).getBlockInsertionPoint();
    // const dispatch123 = useDispatch('core/block-editor');
    // console.log(newBlock, insertionPoint)
    // dispatch123.insertBlock( newBlock, insertionPoint.index, newBlock.clientId);
    //       const newBlock = JSON.parse(target.getAttribute("data-block"))
    //       console.log(target.getAttribute("data-block"), insertionPoint, newBlock)
          // let insertedBlock = createBlock( name, atts );
    //

          // dispatch( 'core/block-editor' ).selectBlock( insertedBlock.clientId );

          // const newBlock = createBlock(JSON.parse(target.getAttribute("data-block")).name);
          // console.log(newBlock)
          // insertBlock(newBlock);
          // Otal API resource for core block calls
          // https://developer.wordpress.org/block-editor/data/data-core-block-editor/
          // https://developer.wordpress.org/block-editor/data/data-core-block-editor/#insertBlock

          // const newBlock2 = createBlock(newBlock.name, {}, [{attributes: newBlock.attributes}])
          // console.log(newBlock)
          // // let insertionPoint = getBlockInsertionPoint();
          // console.log(insertionPoint)
          // insertBlock(createBlock(newBlock))
          // insertBlock(newBlock);
          // // insertBlock(newBlock, 1, newBlock.clientId);
          // insertBlocks([newBlock]);
    // console.log(parse(block))
    // console.log(serialize(block))
    // setContent(serialize(blocks));
    // setDraggedBlock(null);
  };

  const handleUpdateBlocks = (blocks) => {
    console.log("update", blocks);
    updateBlocks(blocks);
    handleContentChange(blocks);
  };

  const saveDraft = (e) => {
    dispatch.updateItem(email.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "draft",
        content,
        last_updated: getLuxonDate("last_updated"),
      },
    });
  };

  const publishEmail = (e) => {
    dispatch.updateItem(email.ID, {
      data: {
        subject,
        title,
        pre_header: preHeader,
        status: "ready",
        content,
        last_updated: getLuxonDate("last_updated"),
      },
    });
  };

  const closeEditor = (e) => {
    history.goBack();
  };

  const setupInteractJS = async () => {
    const dragMoveListener = (event) => {

      const target = event.target;
      event.target.classList.add("drop-active");
      draggedBlock = (JSON.parse(target.getAttribute("data-block")));
      // setDraggedBlock(JSON.parse(target.getAttribute("data-block")));

      // keep the dragged position in the data-x/data-y attributes
      const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
      const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

      // translate the element
      target.style.webkitTransform = target.style.transform =
        "translate(" + x + "px, " + y + "px)";

      // update the posiion attributes
      target.setAttribute("data-x", x);
      target.setAttribute("data-y", y);
    };
    const dragEndListener = (event) => {
      const target = event.target;

      // keep the dragged position in the data-x/data-y attributes
      const x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
      const y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;

      // translate the element
      target.style.webkitTransform = target.style.transform =
        "translate(" + 0 + "px, " + 0 + "px)";

      // update the posiion attributes
      target.setAttribute("data-x", 0);
      target.setAttribute("data-y", 0);
    };


    interact(".groundhogg-email-editor__email-content").dropzone({
      overlap: 0.75,
      ondropactivate: (event) => {
        // event.target.style.border = "1px solid #005a87";
        // event.target.style.background = "#bfe4ff";
        // console.log("started drag", event.target, event.target.classList);
      },
      ondragenter: (event) => {
        var draggableElement = event.relatedTarget;
        var dropzoneElement = event.target;
        // console.log("entered the drag zone, still holding");
      },
      ondragleave: (event) => {
        // Probably dont need this one
      },
      ondrop: (event) => {
        // Add block here
        event.stopImmediatePropagation();
        // event.stopPropagation();
        console.log("dropped");

        handleContentChangeDraggedBlock();
        // event.relatedTarget.textContent = "Dropped";
      },
      ondropdeactivate: (event) => {
        // remove active dropzone feedback
        // console.log("ended drag", event.target);
        event.target.style.border = "";
        event.target.style.background = "";
        event.target.classList.remove("drop-active");
      },
    });


    let ele = ".side-bar-drag-drop-block, .wp-block"
    // if(interact.isSet(ele)){
    //   interact(ele).unset();
    // }
    interact(ele).draggable({
      cursorChecker(action, interactable, element, interacting) {
        return "grab";
      },
      autoScroll: true,
      onend: dragEndListener,
      listeners: { move: dragMoveListener },
    });
  };

  const handleViewTypeChange = (type) => {
    setViewType(type);
  };
  const sendTestEmail = (type) => {
    // console.log("email");
    // setViewType(type);
  };

  useEffect(() => {
    if (content?.length) {
      handleUpdateBlocks(() => parse(content));
    }



    if(!setup){
      setup = true;
      console.log('hiii', blocks)

    }
    setupInteractJS()
  }, [draggedBlock]);

  let editorPanel;
  switch (editorMode) {
    case "visual":
      editorPanel = (
        <BlockEditor
          settings={settings}
          subject={subject}
          handleSubjectChange={handleSubjectChange}
          preHeader={preHeader}
          handlePreHeaderChange={handlePreHeaderChange}
          content={content}
          handleContentChange={handleContentChange}
          viewType={viewType}
          handleUpdateBlocks={handleUpdateBlocks}
          blocks={blocks}
        />
      );
      break;
    case "text":
      editorPanel = <PostTextEditor />;
      break;
    default:
      editorPanel = (
        <BlockEditor
          settings={settings}
          subject={subject}
          handleSubjectChange={handleSubjectChange}
          preHeader={preHeader}
          handlePreHeaderChange={handlePreHeaderChange}
          content={content}
          handleContentChange={handleContentChange}
          viewType={viewType}
          handleUpdateBlocks={handleUpdateBlocks}
          blocks={blocks}
        />
      );
  }

  return (
    <div className="Groundhogg-BlockEditor">
      <FullscreenMode isActive={false} />
      <SlotFillProvider>
        <DropZoneProvider>
          <FocusReturnProvider>
            <InterfaceSkeleton
              header={
                <Header
                  email={email}
                  history={history}
                  saveDraft={saveDraft}
                  publishEmail={publishEmail}
                  closeEditor={closeEditor}
                  isSaving={isSaving}
                  handleTitleChange={handleTitleChange}
                  handleViewTypeChange={handleViewTypeChange}
                  sendTestEmail={sendTestEmail}
                  title={title}
                />
              }
              sidebar={
                <>
                  <Sidebar />
                  <ComplementaryArea.Slot scope="gh/v4/core" />
                </>
              }
              content={
                <>
                  <Notices />
                  {editorPanel}
                </>
              }
            />
            <Popover.Slot />
          </FocusReturnProvider>
        </DropZoneProvider>
      </SlotFillProvider>
    </div>
  );
};
