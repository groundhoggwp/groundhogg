import React from "react";
import { Router, Route, NavLink } from "react-router-dom";
import PropTypes from "prop-types";
import { withRouter } from "react-router";

// components

// pages
import Home from "../../pages/Home/Home.js";

class App extends React.Component {
  constructor(props) {
    super(props);

    // this.toggleNav = this.toggleNav.bind(this);
    this.state = {};
  }

  render() {
    return (
      <div className="App">
        <Route path="/" component={Home} />
      </div>
    );
  }
}

export default withRouter(App);
