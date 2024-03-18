<?php

namespace Groundhogg\Cli;

function doing_cli() {
	return defined( 'WP_CLI' ) && WP_CLI;
}

if ( ! class_exists( 'WP_CLI' ) ){
	return;
}

\WP_CLI::add_command( 'groundhogg-faker', __NAMESPACE__ . '\Faker' );
\WP_CLI::add_command( 'groundhogg-table', __NAMESPACE__ . '\Table' );

