import _ from 'lodash';
import {
  colors,
  createMuiTheme,
  responsiveFontSizes
} from '@material-ui/core';
import { THEMES } from '../constants';
import { softShadows, strongShadows } from './shadows';
import typography from './typography';

import Montserrat100 from '../fonts/montserrat-v15-latin-100.woff2';

const monsterrat100 = {
  fontFamily: 'Montserrat',
  fontStyle: 'normal',
  fontDisplay: 'swap',
  fontWeight: 100,
  src: `
    local('Montserrat'),
    local('Montserrat-Regular'),
    url(${Montserrat100}) format('woff2')
  `,
  unicodeRange:
    'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+2000-206F, U+2074, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF',
};
const baseOptions = {
  direction: 'ltr',
  typography,
  overrides: {
    MuiCssBaseline: {
      '@global': {
        '@font-face': [monsterrat100]
      },
    },
    MuiLinearProgress: {
      root: {
        borderRadius: 3,
        overflow: 'hidden'
      }
    },
    MuiListItemIcon: {
      root: {
        minWidth: 32
      }
    },
    MuiChip: {
      root: {
        backgroundColor: 'rgba(0,0,0,0.075)'
      }
    }
  }
};

const themesOptions = [
  {
    name: THEMES.LIGHT,
    overrides: {
      MuiCssBaseline: {
        '@global': {
          '@font-face': [monsterrat100],
        },
      },
      MuiInputBase: {
        input: {
          '&::placeholder': {
            opacity: 1,
            color: colors.blueGrey[600]
          }
        }
      }
    },
    palette: {
      type: 'light',
      action: {
        active: colors.blueGrey[600]
      },
      background: {
        default: colors.common.white,
        dark: '#f4f6f8',
        paper: colors.common.white
      },
      primary: {
        main: '#f18f01'
      },
      secondary: {
        main: '#006e90'
      },
      text: {
        primary: colors.blueGrey[900],
        secondary: colors.blueGrey[600]
      }
    },
    shadows: softShadows
  }
];

export const createTheme = (config = {}) => {
  let themeOptions = themesOptions.find((theme) => theme.name === 'LIGHT');
  // let themeOptions = themesOptions.find((theme) => theme.name === config.theme);

  if (!themeOptions) {
    console.warn(new Error(`The theme ${config.theme} is not valid`));
    [themeOptions] = themesOptions;
  }

  let theme = createMuiTheme(
    _.merge(
      {},
      baseOptions,
      themeOptions,
      { direction: config.direction }
    )
  );

  if (config.responsiveFontSizes) {
    theme = responsiveFontSizes(theme);
  }

  return theme;
}
