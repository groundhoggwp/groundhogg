import { createMuiTheme } from '@material-ui/core/styles';
import blue from '@material-ui/core/colors/blue';

export const theme = createMuiTheme({
  palette: {
    primary: {
      main: '#DB741A',
    },
    secondary: {
      main: blue[500],
    },
  },
});
