<?php
namespace Groundhogg;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-04-29
 * Time: 9:31 AM
 */

abstract class Supports_Errors
{
    /**
     * @var \WP_Error[]
     */
    protected $errors = [];

    /**
     * @param $error \WP_Error
     */
    public function add_error( $error )
    {
        $this->errors[] = $error;
    }

    /**
     * @return bool
     */
    public function has_errors()
    {
        return ! empty( $this->errors );
    }

    /**
     * @return \WP_Error
     */
    public function get_last_error()
    {
        return $this->errors[ count( $this->errors ) - 1 ];
    }
}