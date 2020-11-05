import {Fragment, useState} from "@wordpress/element";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";
import {} from "@material-ui/core";
import {Redirect} from "react-router";
import Button from "@material-ui/core/Button";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import {FormFileUpload} from '@wordpress/components';

export const Upload = (props) => {


    const {handleBack, handleNext} = props;

    const handleUpload = () => {

        handleNext();

    }

    let selected = ';';

    return (
        <Fragment>
            <div style={{
                padding: 24,
                background: '#fff',
            }}>

                Select Your CSV Delimiter
                <Select
                    labelId="demo-customized-select-label"
                    id="demo-customized-select"
                    value={selected}
                    // onChange={}
                    // input={<BootstrapInput />}
                >
                    <MenuItem value={';'}>Semicolon Separated (;)</MenuItem>
                    <MenuItem value={','}>Comma Separated (,)</MenuItem>
                </Select>
                <FormFileUpload
                    accept="CSV/*"
                    onChange={() => console.log('new image')}
                >
                    Upload
                </FormFileUpload>

            </div>

            <div style={{
                padding: 24,
                background: '#fff',
                marginTop: 10

            }}>
                <Button
                    variant="contained"
                    color="primary"
                    onClick={handleUpload}>
                    {__('Upload & Next', 'groundhogg')}
                </Button>
            </div>
        </Fragment>

    );

}