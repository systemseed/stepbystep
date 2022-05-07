import { createTheme } from '@material-ui/core/styles';
import { theme as anuTheme } from '@anu/theme';

// Override default ANU LMS styles with a few things
// specific for SBS.
// Make the breakpoints match those from material base.
const theme = createTheme({
  ...anuTheme,
  palette: {
    ...anuTheme.palette,
    primary: {
      ...anuTheme.palette.primary,
      main: '#00599a',
    },
    washed: {
      green: '#e6f7f4',
      blue: '#e6f3fa',
    },
    who: {
      light: '#0288d1',
      dark: '#0071A2',
    },
  },
  breakpoints: {
    values: {
      lg: 1200,
      md: 992,
      sm: 768,
      xl: 1520,
      xs: 0,
    },
  },
  overrides: {
    MuiButton: {
      root: {
        ...anuTheme.overrides.MuiButton.root,
        [anuTheme.breakpoints.down('sm')]: {
          width: '100%',
        },
      },
    },
  },
});

export { theme };
