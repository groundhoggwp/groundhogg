import {Fragment, useState} from "@wordpress/element";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";
import {} from "@material-ui/core";
import { Redirect } from "react-router";
import Button from "@material-ui/core/Button";

export const Map = (props) => {

    const {handleBack, handleNext } = props;

    const handleMap =() => {
        handleNext();
    }


    return (
        <Fragment>
            <div style={{
                padding: 24,
                background: '#fff',
            }}>
                <h1> Hello </h1>
            </div>

            <div style={{
                padding: 24,
                background: '#fff',
                marginTop: 10
            }}>
                <Button variant="contained" color="secondary" onClick={handleBack}>
                    {__('Back', 'groundhogg')}
                </Button>
                <Button variant="contained" color="primary" onClick={handleMap} >
                {__('next', 'groundhogg')}
                </Button>
            </div>
        </Fragment>
    );

}