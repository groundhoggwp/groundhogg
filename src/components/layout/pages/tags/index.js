/**
 * External dependencies
 */
import { Fragment, useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { ListTable } from '../../../core-ui/list-table/new'
import { TAGS_STORE_NAME } from '../../../../data/tags'

export const Tags = ( props ) => {

	return (
			<Fragment>
				<ListTable
					storeName={'gh/v4/tags'}
					columns={[
						{
							ID: 'tag_id',
							name: 'ID',
							orderBy: 'tag_id',
							align: 'right',
							cell: ({data, ID}) => {
								return data.tag_id
							}
						},
						{
							ID: 'name',
							name: 'Name',
							orderBy: 'tag_name',
							align: 'left',
							cell: ({data}) => {
								return data.tag_name
							}
						}
					]}
				/>
			</Fragment>
	);
}
