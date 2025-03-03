<?php

namespace Groundhogg\Steps\Premium\Benchmarks;

use Groundhogg\Steps\Benchmarks\Benchmark;
use Groundhogg\Steps\Premium\Trait_Premium_Step;

class Webhook_Listener extends Benchmark {

	const TYPE = 'webhook_listener';

	use Trait_Premium_Step;

	public function get_name() {
		return __( 'Webhook Listener', 'groundhogg-pro' );
	}

	public function get_type() {
		return 'webhook_listener';
	}

	public function get_sub_group() {
		return 'developer';
	}

	public function get_description() {
		return __( 'Listen for requests from external webhooks.', 'groundhogg-pro' );
	}

	public function get_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/webhook-listener.svg';
	}
}
