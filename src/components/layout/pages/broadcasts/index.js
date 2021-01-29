/**
 * External dependencies
 */
import Table from './table';
import {AddBroadcast} from './add-broadcast';
import {
  useRouteMatch,
  Switch,
  Route
} from "react-router-dom";

export const Broadcasts = () => {
  let { path } = useRouteMatch();
  return (
    <Switch>
      <Route exact path={path}>
          <Table />
      </Route>
      <Route path={`${path}/schedule`}>
          <AddBroadcast />
      </Route>
    </Switch>
  )
}