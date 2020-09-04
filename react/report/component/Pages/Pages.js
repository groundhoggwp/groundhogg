import React, {useEffect} from 'react';
import {Card, Container} from "react-bootstrap";
import PropType from "prop-types";
import {connect} from "react-redux";
import {changeSelectedNav} from "../../../actions/reportNavBarActions";
import {Row, Col} from "react-bootstrap";
import LineChart from "../LineChart/LineChart";
import CustomizedTables from "../CustomizedTable/CustomizedTables";
import Stats from "../Stats/Stats";
import PieChart from "../PieChart/PieChart";
import {Loading} from "../Loading/Loading";
import BarChart from "../BarChart/BarChart";
import DropDown from "../DropDown/DropDown";
import {Line} from "react-chartjs-2";


export class Report extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        switch (this.props.type) {
            case 'table' :
                return (<CustomizedTables type={this.props.type} classes={this.props.classes} id={this.props.id}
                                          start={this.props.start}
                                          end={this.props.end}/>);
            case 'line-chart' :
                return (<LineChart type={this.props.type} classes={this.props.classes} id={this.props.id}
                                   start={this.props.start}
                                   end={this.props.end}/>);
            case 'stats' :
                return (<Stats type={this.props.type} classes={this.props.classes} id={this.props.id}
                               start={this.props.start}
                               end={this.props.end}/>);
            case  'pie' :
                return (<PieChart type={this.props.type} classes={this.props.classes} id={this.props.id}
                                  start={this.props.start}
                                  end={this.props.end}/>);
            case  'bar-chart' :
                return (<BarChart type={this.props.type} classes={this.props.classes} id={this.props.id}
                                  start={this.props.start}
                                  end={this.props.end}/>);
            case  'ddl' :
                return (<DropDown type={this.props.type} classes={this.props.classes} id={this.props.id}
                                  start={this.props.start}
                                  end={this.props.end}/>);
            default:
                console.log(this.props.type);
                return (<h1> default case</h1>);

        }
    }
}


export class ReportRows extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {

        const col = {
            marginBottom: 15
        };
        const {classes} = this.props;
        if (this.props.row instanceof Array) {
            return (
                <Row>
                    {this.props.row.map((objects, index) =>
                        <ReportRows key={index} row={objects}/>)}
                </Row>
            );
        } else {



            if (this.props.row.type === 'stats' || this.props.row.type === 'ddl') {


                return (<Col
                        lg={this.props.row.lg ? this.props.row.lg : 6}
                        md={this.props.row.md ? this.props.row.md : 12}
                        sm={this.props.row.sm ? this.props.row.sm : 12}
                        style={col}
                    >
                        <Report classes={classes} key={this.props.row.id} id={this.props.row.id}
                                type={this.props.row.type}/>

                    </Col>
                );

            } else if (this.props.row.type === 'multi-report') {

                return (<Col
                    lg={this.props.row.lg ? this.props.row.lg : 6}
                    md={this.props.row.md ? this.props.row.md : 12}
                    sm={this.props.row.sm ? this.props.row.sm : 12}
                    style={col}
                >
                    <Card className="groundhogg-report-card">
                        <Card.Header className="groundhogg-report-card-header">
                            <h6>{this.props.row.title ? this.props.row.title  : 'Define title in page'  }</h6>
                        </Card.Header>
                        <Card.Body className={"groundhogg-report-card-body"}>
                            {this.props.row.reports.map((report, index) =>
                                <Report classes={classes} key={index} id={report.id}
                                        type={report.type}/>)}
                        </Card.Body>
                    </Card>
                </Col>);

            } else {

                return (<Col
                    lg={this.props.row.lg ? this.props.row.lg : 6}
                    md={this.props.row.md ? this.props.row.md : 12}
                    sm={this.props.row.sm ? this.props.row.sm : 12}
                    style={col}
                >
                    <Card className="groundhogg-report-card">
                        <Card.Header className="groundhogg-report-card-header">
                            <h6>{this.props.row.title ? this.props.row.title  : 'Define title in page'  }</h6>
                        </Card.Header>
                        <Card.Body className={"groundhogg-report-card-body"}>
                            <Report classes={classes} key={this.props.row.id} id={this.props.row.id}
                                    type={this.props.row.type}/>
                        </Card.Body>
                    </Card>
                </Col>);

            }

        }
    }
}


export class Pages extends React.Component {

    // componentDidMount() {
    //     this.props.changeSelectedNav(this.props.navBar.pageSelected);
    // }

    render() {

        // console.log("page main render method") ;
        if (
            this.props.navBar.pageSelected &&
            this.props.navBar.hasOwnProperty("pages") &&
            this.props.navBar.pages.hasOwnProperty( this.props.navBar.pageSelected ) &&
            this.props.navBar.pages[ this.props.navBar.pageSelected].hasOwnProperty('rows')
        ) {
            let pageRows = this.props.navBar.pages[ this.props.navBar.pageSelected].rows ;
            return (
                <Container fluid style={{paddingTop: 15}}>
                    {pageRows.map((row, index) => {
                        // return (<div>{this.handleRow(row)}</div>);
                        return <ReportRows key={index} row={row}/>
                    })}
                </Container>
            );
        } else {
            return  <Card className="groundhogg-report-card" > <Loading height={ 500 }/> </Card> ;
        }
    }
}

const mapStateToProps = (state) => {
    return {
        navBar: state.reportNavBar,
        reports : state.reports
    };
};


export default connect(mapStateToProps, {changeSelectedNav})(Pages);
