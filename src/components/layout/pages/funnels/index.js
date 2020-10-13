/**
 * External dependencies
 */
import Table from './table';
import Single from './single';
import {
  useRouteMatch,
  Switch,
  Route
} from "react-router-dom";

export const Funnels = () => {

  let { path } = useRouteMatch();

  return (
    <Switch>
      <Route exact path={path}>
        <Table/>
      </Route>
      <Route path={`${path}/:id`}>
        <Single/>
      </Route>
    </Switch>
  )
}