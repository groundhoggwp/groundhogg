import React from 'react';

import { CustomNotes } from './';

export default {
  title: 'Groundhogg Core UI/Custom Notes',
  component: CustomNotes,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <CustomNotes {...args} />;

export const Default = Template.bind({});
Default.args = {
  text: 'zsd'
};
