import React from "react";
import "./component.scss";
import Spinner from "react-bootstrap/Spinner";

export class AddStepControl extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            loading: false
        };

        this.handleOnClick = this.handleOnClick.bind(this);
    }

    handleOnClick(e) {
        this.setState({
            loading: true
        });

        this.props.stepChosen( this.props.step.type );
    }

    render() {

        const step = this.props.step;
        const classes = [
        	"add-step-control",
			"gh-box",
			step.type,
			step.group,
			this.state.loading ? 'active' : 'inactive'
		].join(" ");

        return (
            <div className={classes} onClick={this.handleOnClick}>
                <div className={"step-icon-wrap"}>
                    <img alt={step.name} className={"step-icon"} src={step.icon}/>
                    {this.state.loading && <Spinner animation={"border"} variant={"white"}/>}
                </div>
                <div className={"details"}>
                    <h3 className={"step-name"}>{step.name}</h3>
                    <p className={"description"}>{step.description}</p>
                </div>
            </div>
        );
    }

}