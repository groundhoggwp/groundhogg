import React from "react";
import { Route } from "react-router-dom";
import { withRouter } from "react-router";
import { Button, GridList, GridListTile } from '@material-ui/core';
// import Button from '@material-ui/core/Button';


class Home extends React.Component {
  constructor(props) {
    super(props);

    this.state = {};
  }
  render() {
    const tileData =[
      {
      title: 'asdf',
      cols: 1,
      img: 'https://material-ui.com/static/images/grid-list/morning.jpg'
    },
      {
      title: 'asdf',
      cols: 3,
      img: 'https://material-ui.com/static/images/grid-list/morning.jpg'
    },
      {
      title: 'asdf',
      cols: 1,
      img: 'https://material-ui.com/static/images/grid-list/morning.jpg'
    },
      {
      title: 'asdf',
      cols: 1,
      img: 'https://material-ui.com/static/images/grid-list/morning.jpg'
    },
      {
      title: 'asdf',
      cols: 1,
      img: 'https://material-ui.com/static/images/grid-list/morning.jpg'
    }
  ]
    return (
      <div className="Home">
        <h1>My React Seed</h1>
         <Button color="primary">Hello World</Button>

         <GridList cellHeight={160} className={'class'} cols={3}>
          {tileData.map((tile) => (
            <GridListTile key={tile.img} cols={tile.cols || 1}>
              <img src={tile.img} alt={tile.title} />
            </GridListTile>
          ))}
        </GridList>
      </div>
    );
  }
}

export default withRouter(Home);
