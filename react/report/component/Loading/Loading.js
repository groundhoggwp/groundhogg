import React from 'react' ;
import {Card, Spinner} from "react-bootstrap";
import './style.scss';

export const Loading = (props) => {
    return (
        // <Card className="groundhogg-report-card">
        //     <Card.Body className={"loading-animation"}>
                <div className={"loading-animation"} style={{height: props.height ? props.height:30}}>
                    {/*<Spinner animation="grow"/>*/}
                </div>
            // </Card.Body>
        // </Card>
    );
}
