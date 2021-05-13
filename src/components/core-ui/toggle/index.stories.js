/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { Toggle } from './';
import  { createTheme }   from "../../../theme";

const theme = createTheme({});

export default {
  title: 'Groundhogg Core UI/Toggle',
  component: Toggle,
  argTypes: {
    on: { control: 'boolean' },
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <Toggle {...args} />;

export const On = Template.bind({});
On.args = {
  label: 'Toggle On',
  backgroundColor: theme.palette.primary.main
};
export const Off = Template.bind({});
Off.args = {
  label: 'Toggle Off',
  backgroundColor: theme.palette.primary.main
};
