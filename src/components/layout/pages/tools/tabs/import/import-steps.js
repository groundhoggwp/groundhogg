import {__} from '@wordpress/i18n';
import {Fragment, useState} from '@wordpress/element';
import {makeStyles} from '@material-ui/core/styles';
import Stepper from '@material-ui/core/Stepper';
import Step from '@material-ui/core/Step';
import StepLabel from '@material-ui/core/StepLabel';
import Button from '@material-ui/core/Button';
import Typography from '@material-ui/core/Typography';
import {Map} from './steps/map'
import {Import} from './steps/import'
import {Upload} from './steps/upload'


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
        __('Upload File', 'goundhogg'),
        __('Map Fields', 'goundhogg'),
        __('Import Contacts', 'goundhogg'),
    ];
}

function getStepContent(stepIndex, handleNext, handleBack, data, setData) {
    switch (stepIndex) {
        case 0:
            return <Upload
                handleNext={handleNext}
                handleBack={handleBack}
                data={data}
                setData={setData}

            />;
        case 1:
            return <Map
                handleNext={handleNext}
                handleBack={handleBack}
                data={data}
                setData={setData}
            />;
        case 2:
            return <Import
                handleNext={handleNext}
                handleBack={handleBack}
                data={data}
                setData={setData}
            />;
        default:
            return 'Unknown stepIndex';
    }
}

export const ImportSteps = (props) => {


    const classes = useStyles();
    const steps = getSteps();

    const [activeStep, setActiveStep] = useState(0);
    const [data, setData] = useState({
        file: null,
        delimiter : ';',
        map : {}
    });

    const handleNext = () => {
        setActiveStep((prevActiveStep) => prevActiveStep + 1);
    };

    const handleBack = () => {
        setActiveStep((prevActiveStep) => prevActiveStep - 1);
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
                    </div>
                ) : (
                    <div>
                        <Typography
                            className={classes.instructions}>{
                            getStepContent(activeStep, handleNext, handleBack, data, setData)
                        }
                        </Typography>
                    </div>
                )}
            </div>
        </div>
    );

}