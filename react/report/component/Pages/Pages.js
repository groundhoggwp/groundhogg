import React, {useEffect} from 'react';
import {Container} from "react-bootstrap";
import PropType from "prop-types";
import {connect} from "react-redux";
import {changeSelectedNav} from "../../../actions/reportNavBarActions";
import  {Row,Col} from "react-bootstrap";
import LineChart from "../LineChart/LineChart";
import CustomizedTables from "../CustomizedTable/CustomizedTables";
import Stats from "../Stats/Stats";
import PieChart from "../PieChart/PieChart";
import {Loading} from "../Loading/Loading";




export class Report extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        switch (this.props.type) {
            case 'table' :
                return (<CustomizedTables classes={this.props.classes} id={this.props.id} start = {this.props.start} end = {this.props.end}  />);
            case 'line-chart' :
                return (<LineChart classes={this.props.classes} id={this.props.id} start = {this.props.start} end = {this.props.end}/>);
            case 'stats' :
                return (<Stats classes={this.props.classes} id={this.props.id} start = {this.props.start} end = {this.props.end}/>);
            case  'pie' :
                return  (<PieChart classes={this.props.classes} id={this.props.id} start = {this.props.start} end = {this.props.end}/>);
            default:
                return  (<h1> default case</h1>);

        }
    }
}


export class ReportRows extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {

        const  col  = {
            marginBottom: 15
        };
        const {classes} = this.props;
        if (this.props.row instanceof Array) {
            return (
                <Row >
                    {this.props.row.map((objects ,index) =>
                        <ReportRows key={index} row={objects} />)}
                </Row>
            );
        } else {
            return (
                <Col
                    lg={this.props.row.lg ? this.props.row.lg : 6}
                    md={this.props.row.md ? this.props.row.md : 12}
                    sm={this.props.row.sm ? this.props.row.sm : 12}
                    style = {col}
                >
                    <Report classes={classes} key={this.props.row.id} id={this.props.row.id} type={this.props.row.type}/>
                </Col>
            );
        }
    }
}


export class Pages extends React.Component {

    componentDidMount() {
        this.props.changeSelectedNav(this.props.navBar.pageSelected);
    }

    render() {

        if (
            this.props.navBar.hasOwnProperty("pages") &&
            this.props.navBar.pages.hasOwnProperty("rows")
        ) {
            return (
                <Container fluid style={{paddingTop : 15}}>
                    {this.props.navBar.pages.rows.map((row,index) => {
                        // return (<div>{this.handleRow(row)}</div>);
                        return <ReportRows key={index} row={row}/>
                    })}
                </Container>
            );
        } else {
            return <Loading />;
        }


    }
}

const mapStateToProps = (state) => {
    return {
        navBar: state.reportNavBar
    };
};


export default connect(mapStateToProps, {changeSelectedNav})(Pages);




