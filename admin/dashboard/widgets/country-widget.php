<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Plugin;

class Country_Widget extends Circle_Graph
{

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
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_country';
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

        $label = ! empty( $item_key ) ? Plugin::$instance->utils->location->get_countries_list( $item_key ): __( 'Unknown' );
        $data  = $item_data;
        $url   = ! empty( $item_key ) ? admin_url( sprintf( 'admin.php?page=gh_contacts&meta_key=country&meta_value=%s', $item_key ) ) : '#';

        return [
            'label' => $label,
            'data' => $data,
            'url'  => $url
        ];
    }
}