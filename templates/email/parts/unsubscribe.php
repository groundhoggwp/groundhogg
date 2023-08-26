<?php

use function Groundhogg\html;
use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( ! $email->is_transactional() ) {

}