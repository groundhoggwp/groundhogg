import React from 'react';

import { DatePicker } from './';

export default {
  title: 'Groundhogg Core UI/Date Picker',
  component: DatePicker,
  argTypes: {
    // backgroundColor: { control: 'text' },
  },
};

const Template = (args) => <DatePicker {...args} />;

export const Default = Template.bind({});


Default.args = {
  options
};
