import {Box, Card, CardHeader, Divider, LinearProgress, makeStyles} from "@material-ui/core";
import clsx from "clsx";
import React from "react";


const useStyles = makeStyles((theme) => ({
}));

export const LoadingReport  = ({title ,className}) =>{

    const classes = useStyles();

    return(
        <Card className={clsx(classes.root, className)} >
            <CardHeader title={title} />
            <Divider />
            {/*<Box p={3}  minHeight={100}>*/}
                <LinearProgress />
            {/*</Box>*/}

        </Card>
    );
}