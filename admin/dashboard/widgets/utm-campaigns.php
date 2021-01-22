<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;

class UTM_Campaigns extends Table_Widget
{

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_utm_campaign';
    }

    /**
     * Any additional information needed for the widget.
     *
     * @return void
     */
    protected function extra_widget_info()
    {
        // TODO: Implement extra_widget_info() method.
    }

    /**
     * Normalize a datum
     *
     * @param $item_key
     * @param $item_data
     * @return array
     */
    protected function normalize_datum($item_key, $item_data)
    {
        return [
            'label' => $item_key,
            'data' => $item_data,
            'url'  => admin_url( 'admin.php?page=gh_contacts&meta_value=utm_campaign&meta_value=' . urlencode( $item_key ) )
        ];
    }

    /**
     * @return string
     */
    function column_title()
    {
        return __( 'UTM Campaign', 'groundhogg' );
    }
}