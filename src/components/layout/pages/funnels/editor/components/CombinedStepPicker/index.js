import Tabs from '@material-ui/core/Tabs'
import Tab from '@material-ui/core/Tab'
import AppBar from '@material-ui/core/AppBar'
import { useState } from '@wordpress/element'
import BenchmarkPicker from '../BenchmarkPicker';
import ActionPicker from '../ActionPicker';

export default (props) => {

  const [value, setValue] = useState('benchmarks')

  const handleChange = (event, newValue) => {
    setValue(newValue);
  };

  let Picker;

  switch (value) {
    case 'benchmarks':
      Picker = BenchmarkPicker
      break;
    case 'actions':
      Picker = ActionPicker
      break;
    case 'conditions':
      break;
  }

  return (
    <>
      <AppBar position={'static'}>
        <Tabs value={value}  onChange={handleChange}>
          <Tab
            value={'benchmarks'}
            label={'Benchmarks'}
          />
          <Tab
            value={'actions'}
            label={'actions'}
          />
          <Tab
            value={'conditions'}
            label={'Conditions'}
          />
        </Tabs>
      </AppBar>
      <Picker {...props}/>
    </>
  )
}