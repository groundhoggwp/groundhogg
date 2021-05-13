import React from 'react';

import { DynamicForm } from './';
import { Textfield } from '../text-field';

export default {
  title: 'Groundhogg Core UI/Dynamic Form',
  component: DynamicForm,
  argTypes: {
    // value: { text: 'color' },
  },
};

const Template = (args) => <DynamicForm {...args} />;

// const [formData, setFormData] = React.useState({});
// const hanldeFormChange =
let formData = {
  text: 'test'
}
export const Default = Template.bind({});
Default.args = {
  // formData,
  children : [<Textfield value={formData['text']} />],
  // hanldeFormChange : (e) => {
  //   console.log(e.target.name, e.target.value)
  //   formData[e.target.id] = e.target.value
  //   // setFormData(formData)
  // }
  // primary: true,
  // label: 'Toggle On',
};
