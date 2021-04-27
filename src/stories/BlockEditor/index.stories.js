import React from 'react';

import { Button } from './';

export default {
  title: 'Example/Block-Editor',
  component: Button,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <Button {...args} />;


export const Emails = Template.bind({});
Emails.args = {
  size: 'small',
  label: 'Button',
};

export const Funnels = Template.bind({});
Funnels.args = {
  size: 'small',
  label: 'Button',
};
