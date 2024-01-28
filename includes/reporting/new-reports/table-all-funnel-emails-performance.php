<?php

namespace Groundhogg\Reporting\New_Reports;

use Groundhogg\Email;
use function Groundhogg\admin_page_url;
use function Groundhogg\base64_json_encode;
use function Groundhogg\html;
use function Groundhogg\Ymd_His;

class Table_All_Funnel_Emails_Performance extends Base_Funnel_Email_Performance_Table_Report {

	protected function should_include( $sent, $opened, $clicked ) {
		return true;
	}
}
