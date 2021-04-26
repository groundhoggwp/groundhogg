import { useDispatch, useSelect } from '@wordpress/data'
import { FUNNELS_STORE_NAME } from 'data/funnels'
import Button from '@material-ui/core/Button'
import { useTempState } from 'utils/index'
import PauseIcon from '@material-ui/icons/Pause';

export default ({id, data}) => {

	const {updateItem} = useDispatch(FUNNELS_STORE_NAME)
	const {isUpdating} = useSelect((select) => {
		return {
			isUpdating: select(FUNNELS_STORE_NAME).isItemsUpdating()
		}
	}, [])

	const {tempState, setTempState} = useTempState(data);

	const handleClick = (status) => {
		setTempState({...tempState, status: status});
		updateItem(id, {
			data: {
				status: status,
			},
		})
	}

	return <>
		<>
			{tempState.status !== 'active' && (
				<Button
					disabled={isUpdating}
					onClick={() => handleClick('active')}
					variant={tempState.status === 'active'
						? 'contained'
						: 'outlined'}>
					Launch
					<svg width="25" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.888 7.513a21.621 21.621 0 017.22-4.782m-7.22 4.782a21.769 21.769 0 00-2.97 3.698m2.97-3.698c-1.444-.778-4.934-1.2-7.335 3.335l2.364 2.363 2-2m10.191-8.48a21.708 21.708 0 017.112-1.547 21.708 21.708 0 01-1.547 7.111m-5.565-5.564l5.565 5.564M5.918 11.211l1.885 4.056m9.087.248a21.621 21.621 0 004.783-7.22m-4.783 7.22a21.771 21.771 0 01-3.697 2.971m3.697-2.97c.778 1.444 1.2 4.934-3.334 7.334l-2.364-2.364 2-2m0 0l-4.055-1.885m0 0l-1.334-1.334m1.334 1.334l-2.71 2.71-.668-.667-.667-.666 2.71-2.71m6.42-5.087a1.886 1.886 0 112.669-2.668 1.886 1.886 0 01-2.668 2.668z" stroke="currentColor" stroke-width="1.5"/></svg>
				</Button>
			)}
			{tempState.status === 'active' && (
				<Button
					disabled={isUpdating}
					onClick={() => handleClick('inactive')}
					variant={tempState.status !== 'active'
						? 'contained'
						: 'outlined'}>
					<PauseIcon/>
				</Button>
			)}
		</>
	</>
}