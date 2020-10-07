import { Fragment } from '@wordpress/element';
import ListItem from '@material-ui/core/ListItem';
import ListItemIcon from '@material-ui/core/ListItemIcon';
import ListItemText from '@material-ui/core/ListItemText';
import { getPages } from '../../layout/controller';

export default function NavListItems(props) {
  console.log(props);

  // This isn't working corrrectling for the path parameter
  const changeView = (view) => {
    // // setOpen(false);
    console.log(props, view)
    props.props.history.push(`${view}`)
  };

  return(
  <Fragment>
     { getPages().map((page) => {
          return(
          <ListItem button onClick={ () => { changeView( page.path ) } }>
            <ListItemIcon>
              <page.icon />
            </ListItemIcon>
            <ListItemText primary={page.name}  />
          </ListItem>
        )
      } ) }
    </Fragment>
  )
}
