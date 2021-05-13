import React from 'react';

import { Toggle } from './';

export default {
  title: 'Example/Toggle',
  component: Toggle,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <Toggle {...args} />;

export const ToggleOn = Template.bind({});
ToggleOn.args = {
  primary: true,
  label: 'Toggle On',
};
export const ToggleOff = Template.bind({});
ToggleOff.args = {
  primary: false,
  label: 'Toggle Off',
};
