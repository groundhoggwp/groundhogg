import {applyFilters} from "@wordpress/hooks";
import {Fragment, useState} from "@wordpress/element";
import {ToolsGrid} from "../../../core-ui/tools-grid";
import './tabs';
import Box from "@material-ui/core/Box";
import Grid from "@material-ui/core/Grid";
import {Route, Switch, useRouteMatch} from "react-router-dom";
import Paper from "@material-ui/core/Paper";
import TextField from "@material-ui/core/TextField";

const Tiles = ({tools, path}) => {
    const [search, setSearch] = useState('');

    const find = [];
    tools.forEach((tool) => {

        if (tool.title.toLowerCase().search(search.toLowerCase()) > -1 || tool.description.toLowerCase().search(search.toLowerCase()) > -1) {
            find.push(tool);
        }
    })

    const searchChange = (event) => {
        setSearch(event.target.value);
    }


    return (
        <Fragment>
            <Box>
                <Paper>
                    <TextField
                        style={{margin: 8, padding: 20}}
                        placeholder="Placeholder"
                        elperText="Full width!"
                        fullWidth
                        margin="normal"
                        InputLabelProps={{
                            shrink: true,
                        }}
                        variant="outlined"
                        onChange={searchChange}
                    />
                </Paper>
            </Box>
            <Box style={{marginTop: 20}}>
                <Grid container spacing={2}>
                    {find.map((tool) => {
                        let t = {
                            ...tool,
                            ...{
                                path: path + tool.path
                            }
                        }
                        return (<ToolsGrid {...t}  />)
                    })}
                </Grid>
            </Box>
        </Fragment>
    );
}


export const Tools = (props) => {
    var tools = [];
    tools = applyFilters('groundhogg.tools.tabs', tools);
    let {path} = useRouteMatch();
    return (
        <Switch>
            <Route exact path={path}>
                <Tiles tools={tools} path={path}/>
            </Route>
            {tools.map((tool) => {
                return (
                    <Route path={path + tool.path}>
                        <tool.component {...tool} />
                    </Route>
                );
            })}
        </Switch>
    )

};