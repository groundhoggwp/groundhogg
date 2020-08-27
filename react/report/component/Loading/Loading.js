import React from 'react' ;
import {Card, Spinner} from "react-bootstrap";

export const Loading = (props) => {

    return (
        // <Card className="groundhogg-report-card">
        //     <Card.Body className={"owlytik-card-body"}>
                <div className={"owlytik-chart-wrapper"}>
                    <Spinner animation="grow"/>
                </div>
            // </Card.Body>
        // </Card>
    );
}