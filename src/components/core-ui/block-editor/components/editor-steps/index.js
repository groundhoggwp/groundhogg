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
 import Step from "./step";
 import Mail from "components/svg/Mail/";

const useStyles = makeStyles((theme) => ({
  root: {
    width: '265px',
    position: 'absolute',
    top: '144px'
  },
  addStepBtn:{
    width: '265px',
    textAlign: 'center',
    textTransform: 'none'
  }
}));

export default function EditorSteps({}) {

  const classes = useStyles();


  const selectStep = () => {
    console.log('select step')
  }

  return (
    <div className={classes.root}>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#90C71C'}/>} title={'Confirmation Request Email'} type={'send email'} active={true} color={'#90C71C'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Step icon={<Mail fill={'#F58115'}/>} title={'Confirmation Request Email'} type={'send email'} active={false} color={'#F58115'} selectStep={selectStep}/>
      <Button className={classes.addStepBtn} color="secondary">+Add a new Step</Button>
    </div>
  );
}
