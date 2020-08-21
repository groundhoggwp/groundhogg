import React from 'react' ;
import {Card, Table} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import './style.scss';
import {Helmet} from "react-helmet";


//
// const StyledTableCell = withStyles((theme) => ({
//     head: {
//         backgroundColor: "#ccc",
//         color: theme.palette.common.white,
//     },
//     body: {
//         fontSize: 14,
//     },
// }))(TableCell);
//
// const StyledTableRow = withStyles((theme) => ({
//     root: {
//         '&:nth-of-type(odd)': {
//             backgroundColor: theme.palette.action.hover,
//         },
//     },
// }))(TableRow);
//
//
// const useStyles = makeStyles({
//     table: {
//         paddingBottom: 10
//
//     },
// });
//
//
// export default function CustomizedTables() {
//
//     const classes = useStyles();
//
//     var data = {
//         label: ["Funnel", "Conversion Rate"],
//         title: "Table",
//         data: [{
//             label: "<a >Onboarding Series<\/a>",
//             data: "100%"
//         }, {
//             label: "<a  >Fall Back Email Confirmation<\/a>",
//             data: "50%"
//         }, {
//             label: "<a >flunet form test<\/a>",
//             data: "25%"
//         }, {
//             label: "<a  >Start From Scratch - Copy<\/a>",
//             data: "0%"
//         }, {
//             label: "<a  >Refund request<\/a>",
//             data: "0%"
//         }, {
//             label: "<a  >14 Day Demo<\/a>",
//             data: "0%"
//         }, {
//             label: "<a>Higher tier plan upsell.<\/a>",
//             data: "0%"
//         }, {
//             label: "<a  >Affiliate On-boarding!<\/a>",
//             data: "0%"
//         }, {
//             label: "<a  >Support followup<\/a>",
//             data: "0%"
//         }, {
//             label: "<a  >Agency Plan Followup<\/a>",
//             data: "0%"
//         }],
//
//     };
//
//
//     return (
//
//         <Card className="owlytik-card">
//             <Card.Header className="owlytik-card-header">
//                 <h6>{data.title}</h6>
//             </Card.Header>
//             <Card.Body className="owlytik-card-body">
//                 {/*<TableContainer>*/}
//                 {/*    <Table className={classes.table} aria-label="customized table">*/}
//                 {/*        <TableHead>*/}
//                 {/*            <TableRow>*/}
//                 {/*                /!*{ data.label.map(  (label) => {(<StyledTableCell> {label} </StyledTableCell>) })}*!/*/}
//                 {/*                <StyledTableCell> Hello </StyledTableCell>*/}
//                 {/*                <StyledTableCell> Title </StyledTableCell>*/}
//                 {/*            </TableRow>*/}
//                 {/*        </TableHead>*/}
//                 {/*        <TableBody>*/}
//                 {/*            {data.data.map(row =>*/}
//                 {/*                <StyledTableRow>*/}
//                 {/*                    {Object.keys(row).map(key => <StyledTableCell>{row[key]}</StyledTableCell>)}*/}
//                 {/*                </StyledTableRow>)}*/}
//                 {/*        </TableBody>*/}
//                 {/*    </Table>*/}
//                 {/*</TableContainer>*/}
//
//
//
//                 <Table { ...tableProps } className={ 'list-table' }>
//                     <thead>
//                     <tr>
//                         { columns.map((column,i) => <ListTableTH key={i} { ...column } />) }
//                     </tr>
//                     </thead>
//                     <tbody>
//                     {
//
//                         items.length > 0 ?
//                             items.map(
//                                 (item,i) => <ListTableItemRow key={i} item={ item } columns={ columns }/>)
//                             : ! isLoading ? <ListTableRowEmpty
//                                 colSpan={columns.length}
//                                 noItems={noItems}
//                             /> : <></>
//                     }
//                     { isLoading &&
//                     range(10).map(i => <ListTableRowLoading key={i} columns={ columns }/>) }
//                     </tbody>
//                     <tfoot>
//                     <tr>
//                         { columns.map((column,i) => <ListTableTH key={i} { ...column } />) }
//                     </tr>
//                     </tfoot>
//                 </Table>
//             </Card.Body>
//         </Card>
//
//     );
// }
//
//
// const StyledTableCell = withStyles((theme) => ({
//     head: {
//         backgroundColor: "#ccc",
//         color: theme.palette.common.white,
//     },
//     body: {
//         fontSize: 14,
//     },
// }))(TableCell);
//
// const StyledTableRow = withStyles((theme) => ({
//     root: {
//         '&:nth-of-type(odd)': {
//             backgroundColor: theme.palette.action.hover,
//         },
//     },
// }))(TableRow);


class CustomizedTables extends React.Component {

    constructor(props) {
        super(props);
    }

    componentDidMount() {
        // get the data for the line chart from the id
        this.props.fetchReport(this.props.id, this.props.start, this.props.end);
    }

    // const classes = useStyles();

    // var data = {
    //     label: ["Funnel", "Conversion Rate"],
    //     title: "Table",
    //     data: [{
    //         label: "<a >Onboarding Series<\/a>",
    //         data: "100%"
    //     }, {
    //         label: "<a  >Fall Back Email Confirmation<\/a>",
    //         data: "50%"
    //     }, {
    //         label: "<a >flunet form test<\/a>",
    //         data: "25%"
    //     }, {
    //         label: "<a  >Start From Scratch - Copy<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a  >Refund request<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a  >14 Day Demo<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a>Higher tier plan upsell.<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a  >Affiliate On-boarding!<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a  >Support followup<\/a>",
    //         data: "0%"
    //     }, {
    //         label: "<a  >Agency Plan Followup<\/a>",
    //         data: "0%"
    //     }],
    //
    // };

    render() {

        let reportId = this.props.id;

        if ((!this.props.reports || !this.props.reports[reportId]) || this.props.reports[reportId].isLoading) {
            return <Loading/>;
        }

        let report = this.props.reports[reportId];
        if (report.isFailed) {
            // return <h1>Chart not found</h1>;
            return <NotFound/>;
        } else {

            if (!report.data.chart.data.length) {

                return (
                    <Card className="groundhogg-report-card"style={{padding:0}}>
                        <Card.Header className="groundhogg-report-card-header">
                            <h6>{(report.data.title) ? report.title : "No title"}</h6>
                        </Card.Header>
                        <Card.Body className={"groundhogg-report-card-body"} >
                            <Table className={'list-table'}>
                                <tbody>
                                <div className={"groundhogg-report-nav navbar navbar-expand-lg navbar-light"}>
                                {require('html-react-parser')(report.data.no_data)}
                                </div>
                                </tbody>
                            </Table>
                        </Card.Body>
                    </Card>
                );

            }

            return (
                <Card className="groundhogg-report-card" style={{padding:0}}>
                    <Card.Header className="groundhogg-report-card-header">
                        <h6>{(report.data.title) ? report.title : "No title"}</h6>
                    </Card.Header>
                    <Card.Body className={"groundhogg-report-card-body"}>
                        <Table className={'groundhogg-report-table'}>
                            <thead>
                            <tr>
                                {report.data.chart.label.map((label) => {
                                    return <th> {label} </th>;
                                })}
                            </tr>
                            </thead>
                            <tbody>
                            {report.data.chart.data.map(row =>
                                <tr >
                                    {Object.keys(row).map(key => <td
                                        key={key}>{require('html-react-parser')(row[key])}</td>)}
                                </tr>)
                            }
                            </tbody>
                        </Table>
                    </Card.Body>
                </Card>
            );
        }
    }
}


const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(CustomizedTables);
