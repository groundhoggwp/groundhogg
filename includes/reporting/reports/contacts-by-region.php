<?php
namespace Groundhogg\Reporting\Reports;

use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-01-03
 * Time: 3:24 PM
 */

class Contacts_By_Region extends Contacts_By_Meta
{

    public function __construct()
    {
        add_filter(  "groundhogg/reporting/reports/{$this->get_id()}/query", [ $this, 'by_country' ], 11 );
        parent::__construct();
    }

    public function by_country( $query )
    {
        // Intersect the IDs of the new contacts with the IDs of contacts in the country
        $query[ 'contact_id' ] = array_intersect( $query[ 'contact_id' ], $this->get_ids_in_country() );
        return $query;
    }

    /**
     * @return string
     */
    public function get_id()
    {
        return 'contacts_by_region';
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return __( 'Contacts By Region', 'groundhogg' );
    }

    /**
     * @return bool|mixed|string
     */
    public function get_country_code()
    {
        $country_code = Plugin::$instance->utils->location->site_country_code();
        $country_code = strtoupper( substr( get_request_var( 'country', $country_code ), 0, 2 ) );
        return $country_code;
    }

    /**
     * @return array
     */
    public function get_ids_in_country()
    {

        $contacts_in_country = wp_parse_id_list(
            wp_list_pluck(
                get_db( 'contactmeta' )->query( [
                    'meta_key' => 'country',
                    'meta_value' => $this->get_country_code() ]
                ),
                'contact_id' ) );

        return $contacts_in_country;
    }

    /**
     * Return the meta_key used to query the DB
     *
     * @return string
     */
    public function get_meta_key()
    {
        return 'region';
    }
}