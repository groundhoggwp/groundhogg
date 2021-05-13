import React from 'react';

import { DropDown } from './';

export default {
  title: 'Groundhogg Core UI/DropDown',
  component: DropDown,
  argTypes: {
    backgroundColor: { control: 'text' },
  },
};

const Template = (args) => <DropDown {...args} />;

export const DropDownDefault = Template.bind({});

const options = [
  { display: 'Marketing',
    value: 0
  },
  { display: 'Transactional',
    value: 0
  },
]
DropDownDefault.args = {
  options
};
// export const DropDownOpen = Template.bind({});
// DropDownOpen.args = {
//   options
// };
