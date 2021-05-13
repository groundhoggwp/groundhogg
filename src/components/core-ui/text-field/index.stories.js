import React from 'react';

import { Textfield } from './';

export default {
  title: 'Groundhogg Core UI/Textfield',
  component: Textfield,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <Textfield {...args} />;

export const Default = Template.bind({});
Default.args = {
  text: ''
};

export const Filled = Template.bind({});
Filled.args = {
  text: 'Filled',
};

export const DefaultMultiline = Template.bind({});
DefaultMultiline.args = {
  text: 'Multi-line',
};

export const FilledMultiline = Template.bind({});
FilledMultiline.args = {
  text: 'Multi-line filled',
};
