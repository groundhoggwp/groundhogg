import React from 'react';

import { BeaverIcon } from './';

export default {
  title: 'Example/BeaverIcon',
  component: BeaverIcon,
  argTypes: {
    // backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <BeaverIcon {...args} />;

export const Default = Template.bind({});
Default.args = {
  // label: 'BeaverIcon',
};

export const Round = Template.bind({});
Round.args = {
  // label: 'BeaverIcon',
};
