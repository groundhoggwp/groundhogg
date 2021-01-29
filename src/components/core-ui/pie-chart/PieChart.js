import React, {Component} from 'react' ;
import {Doughnut} from 'react-chartjs-2';
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import './style.scss';
//
//
// class Legend extends  Component {
//
//     constructor(props) {
//         super(props);
//     }
//
//
//     handleLegendClick(e, index, ref) {
//         const ci = ref.chartInstance;
//         var meta = Object.values(ci.data.datasets[0]._meta) [0];
//         var curr = meta.data[index];
//         curr.hidden = !curr.hidden;
//         ci.update();
//         this.forceUpdate();
//     }
//
//
//     render() {
//
//         let ref = this.props.chart;
//         console.log("here in the render method of legend ");
//         return (
//         <ul className="legend-scroll mt-8">
//             {legend.length && legend.map((item) => {
//                 return (
//                     <li key={item.text}
//                         className={item.hidden ? "groundhogg-report-pie-chart-listitem strike" : "groundhogg-report-pie-chart-listitem "}
//                         onClick={(e) => {
//                             this.handleLegendClick(e, item.index, ref)
//                         }}>
//                         <div
//                             style={{
//                                 marginRight: "8px",
//                                 width: "20px",
//                                 height: "20px",
//                                 backgroundColor: item.fillStyle
//                             }}
//                         />
//                         {item.text}
//                     </li>
//                 );
//             })}
//         </ul>
//         );
//
//     }
//
//
// }



/**
 * Renders a table
 */
class PieChart extends Component {

    constructor(props) {
        super(props);
        this.chartReference = React.createRef();
    }


    handleLegendClick(e, index, ref) {
        const ci = ref.chartInstance;
        var meta = Object.values(ci.data.datasets[0]._meta) [0];
        var curr = meta.data[index];
        curr.hidden = !curr.hidden;
        ci.update();
        this.forceUpdate();
    }

     draw(ref) {

        let legend = ref.chartInstance.legend.legendItems;
        return (
            <ul className="legend-scroll mt-8">
                {legend.length && legend.map((item) => {
                    return (
                        <li key={item.text}
                            className={item.hidden ? "groundhogg-report-pie-chart-listitem strike" : "groundhogg-report-pie-chart-listitem "}
                            onClick={(e) => {
                                this.handleLegendClick(e, item.index, ref)
                            }}>
                            <div
                                style={{
                                    marginRight: "8px",
                                    width: "20px",
                                    height: "20px",
                                    backgroundColor: item.fillStyle
                                }}
                            />
                            {item.text}
                        </li>
                    );
                })}
            </ul>
        );


    }

    render() {

        let reportId = this.props.id;

        if ((!this.props.reports || !this.props.reports[reportId]) || this.props.reports[reportId].isLoading) {
            return <Loading/>;
        }

        let report = this.props.reports[reportId];

        if (!report) {
            return <NotFound/>;
        } else {

            report.data =report ;
            if (!report.data.chart.data.datasets[0].data.length) {
                return (
                    <div className={"groundhogg-no-data-notice"}>
                        {require('html-react-parser')(String(report.data.chart.no_data))}
                    </div>
                );
            }

            return (
                // <Card className="groundhogg-report-card">
                //     <Card.Header className="groundhogg-report-card-header">
                //         <h6>{report.data.title}</h6>
                //     </Card.Header>
                //     <Card.Body className={"groundhogg-report-card-body"}>
                <div className={"groundhogg-report-chart-wrapper row"} style={{minHeight: 200 ,maxHeight: "auto"}}>
                    <div className={"groundhogg-report-pie-chart col-sm-12 col-md-4 col-lg-4"}>
                        <Doughnut
                            id={reportId}
                            data={report.data.chart.data}
                            options={report.data.chart.options}
                            ref={this.chartReference}
                            height={200}
                            width={200}
                            // ref = {(chart)=>{ return this.draw(chart) }}
                        />
                    </div>
                    <div className={"groundhogg-report-pie-chart-legend col-sm-12 col-md-8 col-lg-8"}>
                        {(this.chartReference.current !== null) ?  this.draw(this.chartReference.current )  : ''}
                        {/*{(this.chartReference.current !== null) ? <Legend chart={this.chartReference.current} />  : ''}*/}
                    </div>
                </div>
                // </Card.Body>
                // </Card>
            );
        }
    }

    // componentDidMount() {
    //     // get the data for the line chart from the id
    //     this.props.fetchReport(this.props.id, this.props.start, this.props.end);
    // }



}

const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(PieChart);