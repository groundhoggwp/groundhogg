<?php
namespace Groundhogg\Admin\Dashboard\Widgets;

use Groundhogg\Preferences;

/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:19 AM
 */

class Optin_Status_Widget extends Circle_Graph
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
     * Normalize a datum
     *
     * @param $item_key
     * @param $item_data
     * @return array
     */
    protected function normalize_datum($item_key, $item_data)
    {
        switch ( $item_key ){
            default:
            case Preferences::UNCONFIRMED:
                $label = __( 'Unconfirmed', 'groundhogg' );
                break;
            case Preferences::CONFIRMED:
                $label = __( 'Confirmed', 'groundhogg' );
                break;
            case Preferences::HARD_BOUNCE:
                $label = __( 'Bounced', 'groundhogg' );
                break;
            case Preferences::SPAM:
                $label = __( 'Spam', 'groundhogg' );
                break;
            case Preferences::UNSUBSCRIBED:
                $label = __( 'Unsubscribed', 'groundhogg' );
                break;
        }

        return [
            'label' => $label,
            'data' => $item_data,
            'url'  => admin_url( 'page=gh_contacts&optin_status=' . $item_key )
        ];
    }

    /**
     * Ge the report ID...
     *
     * @return string
     */
    protected function get_report_id()
    {
        return 'contacts_by_optin_status';
    }
}