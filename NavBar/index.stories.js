import React from 'react';

import { NavBar } from './';

export default {
  title: 'Example/Nav-Bar',
  component: NavBar,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <NavBar {...args} />;


export const NavBarOpen = Template.bind({});
Emails.args = {
  size: 'small',
  label: 'Button',
};

export const NavBarClosed = Template.bind({});
Funnels.args = {
  size: 'small',
  label: 'Button',
};
