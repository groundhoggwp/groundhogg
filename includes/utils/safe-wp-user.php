<?php

namespace Groundhogg\Utils;

class Safe_WP_User extends \WP_User implements \JsonSerializable {

	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'ID' => $this->ID,
			'caps' => $this->caps,
			'allcaps' => $this->allcaps,
			'data' => [
				'display_name' => $this->display_name,
				'user_login' => $this->user_login,
				'user_email' => $this->user_email,
				'user_url' => $this->user_url,
				'user_nicename' => $this->user_nicename,
				'first_name' => $this->first_name,
				'last_name' => $this->last_name
			],
			'roles' => $this->roles,
		];
	}
}
