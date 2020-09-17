import React from "react";
import ReactDOM from "react-dom";
import { BrowserRouter, Route, NavLink } from "react-router-dom";
import App from "./components/App/App";
import registerServiceWorker from "./registerServiceWorker";

import "./index.css";

ReactDOM.render(
  <BrowserRouter>
    <App />
  </BrowserRouter>,
  document.getElementById("reactseed-app")
);
registerServiceWorker();
