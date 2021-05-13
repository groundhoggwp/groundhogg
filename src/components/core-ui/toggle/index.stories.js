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

export const ToggleOn = Template.bind({});
ToggleOn.args = {
  label: 'Toggle On',
  backgroundColor: theme.palette.primary.main
};
export const ToggleOff = Template.bind({});
ToggleOff.args = {
  label: 'Toggle Off',
  backgroundColor: theme.palette.primary.main
};
