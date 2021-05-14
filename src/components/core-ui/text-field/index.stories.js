import React from 'react';

import { Textfield } from './';

export default {
  title: 'Groundhogg Core UI/Textfield',
  component: Textfield,
  argTypes: {
    // value: { control: 'string' },
  },
};

const Template = (args) => <Textfield {...args} />;

export const Default = Template.bind({});
Default.args = {
  value: '',
  placeholder: 'placeholder'
};

export const Filled = Template.bind({});
Filled.args = {
  value: 'Filled',
  placeholder: 'placeholder'
};

export const DefaultMultiline = Template.bind({});
DefaultMultiline.args = {
  value: 'Multi-line',
  placeholder: 'placeholder'
};

export const FilledMultiline = Template.bind({});
FilledMultiline.args = {
  value: 'Multi-line filled',
  placeholder: 'placeholder'
};
