import React from 'react';

import { NavItem } from './';

export default {
  title: 'Example/NavItem',
  component: NavItem,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <NavItem {...args} />;


export const NavItemTemplate = Template.bind({});
NavItemTemplate.args = {
  size: 'small',
  label: 'Button',
};
