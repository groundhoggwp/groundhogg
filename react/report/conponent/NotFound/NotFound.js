import React, {Component} from 'react' ;
import {Card,Spinner} from "react-bootstrap";

export const NotFound = (props) => {
    return (
        <Card className="groundhogg-report-card">
            <Card.Header className="owlytik-card-header">
            </Card.Header>
            <Card.Body className={"owlytik-card-body"}>
                <div className={"owlytik-chart-wrapper"}>
                    <h3> Chart Not found!</h3>
                </div>
            </Card.Body>
        </Card>
    );
}