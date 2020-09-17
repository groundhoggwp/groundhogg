import React from "react";

import { NavLink } from "react-router-dom";

export default class Nav extends React.Component {
  constructor(props) {
    super(props);
    this.openAccordion = this.openAccordion.bind(this);
  }

  componentWillMount() {
    // JavaScript code​​​​​​‌​​​‌​​​‌​‌‌​​​‌‌‌​​​​​‌​ below
    // Use printErr(...) to debug your solution.

    function factorial(n) {
      if (n === 0) {
        return 1;
      } else {
        let nIncrement = n - 1;
        console.log(n);
        return n * factorial(nIncrement);
      }
    }

    console.log(factorial(4));

    return;
    ///#################################################################
    ///#################################################################
    ///#################################################################
    ///#################################################################
    ///#################################################################

    const checks = [
      {
        id: "1",
        value: 1
      },
      {
        id: "1",
        value: 1
      },
      {
        id: "1",
        value: 3
      },
      {
        id: "1",
        value: 2
      },
      {
        id: "1",
        value: 1
      },
      {
        id: "1",
        value: 1
      }
    ];

    function recurse(level, children) {
      console.log(level);
      if (level >= 100) {
        return children;
      } else {
        level++;
        children.push(level);
        return recurse(level, children);
      }
    }

    console.log(recurse(0, []));

    class helloJoe {
      constructor(one, twop) {
        this.one = one;
        this.twop = twop;
      }

      bark() {
        console.log(this.one, this.twop);
      }
    }

    const something = new helloJoe("anything");

    something.bark();

    class person extends helloJoe {}

    const josepph = new person("nein");

    josepph.bark();

    function createCheckDigit(membershipId) {
      // Write the code that goes here.
      var idArray = membershipId.split("");

      // v

      var idSum = 0;
      idArray.forEach((num, i) => {
        idSum += parseInt(num);
      });

      if (String(idSum).length > 1) {
        return createCheckDigit(String(idSum));
      } else {
        return idSum;
      }
    }

    console.log(createCheckDigit("55555"));
  }

  openAccordion(link, ele) {
    console.log(ele, link);
    if (link === "unity" || link === "gallery") {
      this.props.history.push(link);
    } else if (ele.target.parentNode.className.indexOf("open") !== -1) {
      ele.target.parentNode.className = "";
    } else {
      document.querySelectorAll(".Nav ul")[0].className = "";
      ele.target.parentNode.className += " open";
    }
  }

  render() {
    //<NavLink to="/github"><li>github</li></NavLink>

    //<li><a href="http://badsubject.ca" target="_blank" rel="noopener noreferrer">bad subject</a></li>

    // <div className="Nav__colorBar"></div>
    // <ul>
    //   <li onClick={this.openAccordion.bind(this, 'unity')}>Unity</li>
    //   <NavLink to="unity"><li>WIP</li></NavLink>
    // </ul>

    //
    // <NavLink to="/ubisoft"><li>ubisoft</li></NavLink>
    // <NavLink to="/ubisoft"><li>jamieson vitamins</li></NavLink>
    // <NavLink to="/jamieson"><li>lstfi</li></NavLink>
    return (
      <div className={this.props.navType + " Nav"}>
        <ul>
          <NavLink to="/">
            <li>Home</li>
          </NavLink>
        </ul>
        <ul>
          <li onClick={this.openAccordion.bind(this, false)}>Projects</li>

          <NavLink to="/carelnk">
            <li>carelnk</li>
          </NavLink>
          <li>
            <a href="http://tiff.net" target="_blank" rel="noopener noreferrer">
              tiff
            </a>
          </li>
          <li>
            <a
              href="http://www.jamesrobertdurant.com/"
              target="_blank"
              rel="noopener noreferrer"
            >
              james robert durant
            </a>
          </li>
        </ul>
        <div className="Nav__colorBar"></div>
        <ul>
          <li onClick={this.openAccordion.bind(this, false)}>Front End</li>
          <NavLink to="/special-request">
            <li>special re: quest</li>
          </NavLink>
          <NavLink to="tokyo">
            <li>tokyo</li>
          </NavLink>
        </ul>

        <div className="Nav__colorBar"></div>
        <ul>
          <li onClick={this.openAccordion.bind(this, "gallery")}>Design</li>
          <NavLink to="gallery">
            <li>gallery</li>
          </NavLink>
        </ul>
      </div>
    );
  }
}
