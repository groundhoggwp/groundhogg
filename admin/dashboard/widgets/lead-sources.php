<?php

namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;

class Lead_Sources extends Table_Widget
{

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_lead_source';
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
            'label' => Plugin::$instance->utils->html->wrap( $item_key, 'a', [ 'href' => $item_key, 'target' => '_blank' ] ),
            'data' => $item_data,
            'url'  => admin_url( 'admin.php?page=gh_contacts&meta_value=lead_source&meta_value=' . urlencode( $item_key ) )
        ];
    }

    /**
     * @return string
     */
    function column_title()
    {
        return __( 'Lead Source', 'groundhogg' );
    }
}