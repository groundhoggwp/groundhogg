<?php

namespace Groundhogg;

class Proxy_Service extends Supports_Errors
{
    const PROXY_URL = 'https://proxy.groundho.gg/wp-json/proxy/';

    public function request( $endpoint='', $body=[], $method='POST', $headers=[] ){

        $url = self::PROXY_URL . $endpoint;

        $result = remote_post_json( $url, $body, $method, $headers );

        if ( is_wp_error( $result ) ){
            $this->add_error( $result );
        }

        return $result;
    }

}
