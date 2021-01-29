import {useState, Fragment} from "@wordpress/element";
import TextField from "@material-ui/core/TextField";
import Select from "@material-ui/core/Select";
import MenuItem from "@material-ui/core/MenuItem";
import {__} from "@wordpress/i18n";

export const FieldMap = (props) => {

    const {fields, map, setMap} = props;

    return (
        <Fragment>
            <table>
                {fields.map((field) => {
                    return (
                        <tr>
                            <TextField id="outlined-basic" label="Outlined" variant="outlined" value={field} disabled/>
                            <Select
                                labelId="demo-simple-select-outlined-label"
                                id="demo-simple-select-outlined"
                                value={map.hasOwnProperty(field) ? map[field] : ''}
                                onChange={((event) => {
                                    setMap(field, event.target.value);
                                })}
                                label="Age"
                                variant="outlined"
                                displayEmpty
                            >
                                <MenuItem value="">
                                    <em>{__('Do Not Map', 'groundhogg')}</em>
                                </MenuItem>
                                {Object.entries(window.Groundhogg.field_map).map(([key, value]) => {
                                    return <MenuItem value={key}>{value}</MenuItem>
                                })}
                            </Select>

                        </tr>
                    );
                })}
            </table>
        </Fragment>
    );
}
