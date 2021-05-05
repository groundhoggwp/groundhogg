import React from 'react';

import { Logo } from './';

export default {
  title: 'Example/Logo',
  component: Logo,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <Logo {...args} />;

export const Standard = Template.bind({});
Standard.args = {
  primary: true,
  label: 'Button',
};
export const Header = Template.bind({});
Header.args = {
  primary: true,
  label: 'Button',
};
