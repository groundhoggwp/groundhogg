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

export const Default = Template.bind({});
Default.args = {
  label: 'Logo',
};
