<?php

namespace Groundhogg\Reporting\New_Reports;


class Table_Broadcast_Link_Clicked extends Table_Email_Links_Clicked {

	protected function contact_filters( $url ) {
		return [
			[
				[
					'type'         => 'broadcast_link_clicked',
					'broadcast_id' => $this->get_broadcast_id(),
					'link'         => $url
				]
			]
		];
	}
}
