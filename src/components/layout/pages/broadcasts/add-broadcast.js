import {__} from '@wordpress/i18n';
import {Fragment, useState} from '@wordpress/element';
import {makeStyles} from '@material-ui/core/styles';
import Stepper from '@material-ui/core/Stepper';
import Step from '@material-ui/core/Step';
import StepLabel from '@material-ui/core/StepLabel';
import Button from '@material-ui/core/Button';
import Typography from '@material-ui/core/Typography';
import {ScheduleBroadcast} from './steps/schedule-broadcast';
import {ConfirmBroadcast} from './steps/confirm-broadcast';
import {ScheduleEvents} from "./steps/schedule-events";
import {use} from "@wordpress/data";


const useStyles = makeStyles((theme) => ({
    root: {
        width: '100%',
    },
    backButton: {
        marginRight: theme.spacing(1),
    },
    instructions: {
        marginTop: theme.spacing(1),
        marginBottom: theme.spacing(1),
    },
}));

function getSteps() {
    return [
        __('Schedule Broadcast', 'goundhogg'),
        __('Confirm Broadcast', 'goundhogg'),
        __('Send Broadcast', 'goundhogg'),
    ];
}

function getStepContent(stepIndex, handleNext, handleBack, data, setData, broadcast, setBroadcast) {
    switch (stepIndex) {
        case 0:
            return <ScheduleBroadcast
                handleNext={handleNext}
                handleBack={handleBack}
                data={data}
                setData={setData}
            />;
        case 1:
            return <ConfirmBroadcast
                handleNext={handleNext}
                handleBack={handleBack}
                setData={setData}
                data={data}
                broadcast = {broadcast}
                setBroadcast ={setBroadcast}
            />;
        case 2:
            return <ScheduleEvents
                handleNext={handleNext}
                handleBack={handleBack}
                setData={setData}
                data={data}
                broadcast = {broadcast}
                setBroadcast ={setBroadcast}
            />;
        default:
            return 'Unknown stepIndex';
    }
}

export const AddBroadcast = (props) => {


    const classes = useStyles();
    const [activeStep, setActiveStep] = useState(0);
    const [data, setData] = useState({
        email_or_sms_id: 0,
        tags: [],
        exclude_tags: [],
        date: '',
        time: '',
        send_now: false,
        send_in_timezone: false,
        type: '',
    });
    const [broadcast, setBroadcast] = useState(0);

    const steps = getSteps();

    const handleNext = () => {
        setActiveStep((prevActiveStep) => prevActiveStep + 1);
    };

    const handleBack = () => {
        setActiveStep((prevActiveStep) => prevActiveStep - 1);
    };

    const handleReset = () => {
        // setActiveStep(0);
        props.history.push('/broadcasts');
    };

    return (
        <div className={classes.root}>
            <Stepper activeStep={activeStep} alternativeLabel>
                {steps.map((label) => (
                    <Step key={label}>
                        <StepLabel>{label}</StepLabel>
                    </Step>
                ))}
            </Stepper>
            <div>
                {activeStep === steps.length ? (
                    <div>
                        <Typography className={classes.instructions}>All steps completed</Typography>
                        <Button onClick={handleReset}>Reset</Button>
                    </div>
                ) : (
                    <div>
                        <Typography
                            className={classes.instructions}>{
                            getStepContent(activeStep, handleNext, handleBack, data, setData ,broadcast, setBroadcast)
                        }
                        </Typography>
                        {/*<div>*/}
                        {/*    <Button*/}
                        {/*        disabled={activeStep === 0}*/}
                        {/*        onClick={handleBack}*/}
                        {/*        className={classes.backButton}*/}
                        {/*    >*/}
                        {/*        Back*/}
                        {/*    </Button>*/}
                        {/*    <Button variant="contained" color="primary" onClick={handleNext}>*/}
                        {/*        {activeStep === steps.length - 1 ? 'Finish' : 'Next'}*/}
                        {/*    </Button>*/}
                        {/*</div>*/}
                    </div>
                )}
            </div>
        </div>
    );

}