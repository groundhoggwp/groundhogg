import React from 'react';

import { TimePicker } from './';

export default {
  title: 'Groundhogg Core UI/Time Picker',
  component: TimePicker,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <TimePicker {...args} />;

export const Default = Template.bind({});
Default.args = {
  text: 'zsd'
};
