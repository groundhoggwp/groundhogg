<?php

namespace Groundhogg;

class Library extends Supports_Errors
{
    const PROXY_URL = 'https://library.groundhogg.io/wp-json/gh/v3/';

    public function request( $endpoint = '', $body = [], $method = 'GET', $headers = [] )
    {

        $url = self::PROXY_URL . $endpoint;

        $result = remote_post_json( $url, $body, $method, $headers );

        if ( is_wp_error( $result ) ) {
            $this->add_error( $result );
        }

        return $result;
    }

    public function get_funnel_templates()
    {
        $funnels = get_transient( 'groundhogg_funnel_templates' );

        if ( !empty( $funnels ) ) {
            return $funnels;
        }

        $response = $this->request( 'funnels/', [], 'GET' );

        $funnels = $response->funnels;

        set_transient( 'groundhogg_funnel_templates', $funnels, DAY_IN_SECONDS );

        return $funnels;
    }

    public function get_funnel_template( $id )
    {
        $response = $this->request( 'funnels/get', [ 'id' => $id ], 'GET' );
        return $response->funnel;
    }

    public function get_email_templates()
    {
        $emails = get_transient( 'groundhogg_email_templates' );

        if ( !empty( $emails ) ) {
            return $emails;
        }

        $response = $this->request( 'email/templates', [], 'GET' );

        $emails = $response->emails;

        set_transient( 'groundhogg_email_templates', $emails, DAY_IN_SECONDS );

        return $emails;
    }

    public function get_email_template( $id )
    {
        $response = $this->request( 'email/templates/get', [ 'id' => $id ], 'GET' );
        return $response->email;
    }
}
