import React from 'react';

import { Page } from './';
import * as HeaderStories from '../Header/';


export default {
  title: 'Example/Page',
  component: Page,
};

const Template = (args) => <Page {...args} />;

export const PageStory = Template.bind({});
// PageStory.args = {
//   ...HeaderStories.PageStory.args,
// };
//
// export const LoggedOut = Template.bind({});
// LoggedOut.args = {
//   ...HeaderStories.LoggedOut.args,
// };
