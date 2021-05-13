import React from 'react';

import { DynamicForm } from './';

export default {
  title: 'Groundhogg Core UI/Dynamic Form',
  component: DynamicForm,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <DynamicForm {...args} />;

export const Default = Template.bind({});
Default.args = {
  // primary: true,
  // label: 'Toggle On',
};
