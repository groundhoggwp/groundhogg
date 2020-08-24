import React, {Component} from "react";
import PropType from 'prop-types';
import Pages from "../Pages/Pages";
import {Navbar, Nav} from "react-bootstrap";
import {connect} from 'react-redux';
import {fetchNavBar, changeSelectedNav} from "../../../actions/reportNavBarActions";
import './style.scss';
import {Loading} from "../Loading/Loading";

class NavBar extends Component {

    constructor(props) {
        super(props);
        this.handleSelected = this.handleSelected.bind(this);
    }

    componentDidMount() {
        this.props.fetchNavBar();
        // this.props.changeSelectedNav(this.props.navBar.pageSelected);
    }

    handleSelected(selectedKey) {
        this.props.changeSelectedNav(selectedKey);
    }

    render() {

        if (this.props.hasOwnProperty("navBar") &&
            this.props.navBar.hasOwnProperty("pageList") &&
            Object.keys(this.props.navBar.pageList).length !== 0 &&
            this.props.navBar.hasOwnProperty("pageSelected")) {
            return (
                <div>
                    <Navbar collapseOnSelect expand="lg" variant="light" className="groundhogg-report-nav">
                        <Navbar.Brand >{this.props.navBar.pageList[this.props.navBar.pageSelected]}</Navbar.Brand>
                        <Navbar.Toggle aria-controls="responsive-navbar-nav"/>
                        <Navbar.Collapse id="responsive-navbar-nav">
                            <Nav
                                className="justify-content-center"
                                onSelect={this.handleSelected}>
                                {Object.entries(this.props.navBar.pageList).map((value, key) => {
                                    return <Nav.Link eventKey={value[0]}>{value[1]}</Nav.Link>
                                })}
                            </Nav>
                        </Navbar.Collapse>
                    </Navbar>
                    <Pages page={this.props.navBar.pageSelected} />
                </div>
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

NavBar.propTypes = {
    fetchNavBar: PropType.func.isRequired,
    pageList: PropType.object,
    pageSelected: PropType.string
}

export default connect(mapStateToProps, {fetchNavBar ,changeSelectedNav})(NavBar)



