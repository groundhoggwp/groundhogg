import { createMuiTheme } from '@material-ui/core/styles';
import blue from '@material-ui/core/colors/blue';

const theme = createMuiTheme({
  palette: {
    primary: {
      main: '#DB741A',
    },
    secondary: {
      main: blue[500],
    },
  },
});

export default theme;
