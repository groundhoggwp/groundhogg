import {Fragment, useState} from "@wordpress/element";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";
import {} from "@material-ui/core";
import {Redirect} from "react-router";
import Button from "@material-ui/core/Button";
import {FieldMap} from "components/core-ui/field-map";

export const Map = (props) => {

    const {handleBack, handleNext, data, setData} = props;
    const {delimiter, file, map} = data;

    const handleMap = () => {
        handleNext();
    }

    /*   var reader = new FileReader();
        reader.readAsArrayBuffer(file) ;
        reader.onloadend  = (evt) => {

            // Get the Array Buffer
            var data = evt.target.result;

            // Grab our byte length
            var byteLength = data.byteLength;

            // Convert to conventional array, so we can iterate though it
            var ui8a = new Uint8Array(data, 0);

            // Used to store each character that makes up CSV header
            var headerString = '';

            // Iterate through each character in our Array
            for (var i = 0; i < byteLength; i++) {
                // Get the character for the current iteration
                var char = String.fromCharCode(ui8a[i]);

                // Check if the char is a new line
                if (char.match(/[^\r\n]+/g) !== null) {

                    // Not a new line so lets append it to our header string and keep processing
                    headerString += char;
                } else {
                    // We found a new line character, stop processing
                    break;
                }
            }

            // Split our header string into an array
            console.log( headerString.split(',') );



        }

   */

    const fields = [
        'fname',
        'lname',
        'email'

    ];

    console.log(map);

    const setMap = (key, value) => {
        setData({
            map: {
                ...map,
                ...{
                    [key] : value
                }
            }
        })
    }

    return (
        <Fragment>
            <div style={{
                padding: 24,
                background: '#fff',
            }}>
                <FieldMap fields={fields} setMap={setMap} map= {map} />
            </div>

            <div style={{
                padding: 24,
                background: '#fff',
                marginTop: 10
            }}>
                <Button variant="contained" color="secondary" onClick={handleBack}>
                    {__('Back', 'groundhogg')}
                </Button>
                <Button variant="contained" color="primary" onClick={handleMap}>
                    {__('next', 'groundhogg')}
                </Button>
            </div>
        </Fragment>
    );

}