<?php

namespace Groundhogg\Utils;

class Safe_WP_User extends \WP_User implements \JsonSerializable {

	public function get_meta( $key, $default = false ) {
		return get_user_meta( $this->ID, $key, true ) ?: $default;
	}

	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'ID' => $this->ID,
			'caps' => $this->caps,
			'allcaps' => $this->allcaps,
			'roles' => $this->roles,
			'data' => [
				'display_name' => $this->display_name,
				'user_login' => $this->user_login,
				'user_email' => $this->user_email,
				'user_url' => $this->user_url,
				'user_nicename' => $this->user_nicename,
				'first_name' => $this->first_name,
				'last_name' => $this->last_name
			],
			'from_name'  => $this->get_meta( 'gh_from_name', $this->display_name ),
			'from_email' => $this->get_meta( 'gh_from_email', $this->user_email ),
		];
	}
}
