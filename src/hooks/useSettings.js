import { useContext } from 'react'; 
import SettingsContext from 'src/contexts/SettingsContext';

const useSettings = () => useContext(SettingsContext);

export default useSettings;
