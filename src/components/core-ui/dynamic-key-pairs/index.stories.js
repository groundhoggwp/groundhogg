import React from 'react';

import { DynamicKeyPairs } from './';

export default {
  title: 'Groundhogg Core UI/Dynamic Key Pairs',
  component: DynamicKeyPairs,
  argTypes: {
    backgroundColor: { control: 'color' },
  },
};

const Template = (args) => <DynamicKeyPairs {...args} />;

export const Default = Template.bind({});
Default.args = {
  rowData: [
    {
      'id' : '1',
      'label' : 'Dynamic Key Pairs',
    }
  ],
  label: 'Toggle On',
};
