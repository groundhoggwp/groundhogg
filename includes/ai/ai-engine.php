<?php

namespace Groundhogg\AI;

class AI_Engine implements AI_Generator {

	public function ai() {
		global $mwai;

		return $mwai;
	}

	public function json( ) {

	}

	public function simple_text( string $prompt ): string {
		/* translators: 1: the prompt */
		return $this->ai()->simpleTextQuery( $prompt );
	}

	public function tweak_simple_text( string $text, string $prompt ): string {
		/* translators: 1: the text to modify, 2: the prompt on how to modify it */
		return $this->ai()->simpleTextQuery( sprintf( __( 'Modify the given text, "%1$s": %2$s', 'groundhogg' ), $text, $prompt ) );
	}

	public function formatted_text( string $prompt ): string {
		return $this->ai()->simpleTextQuery( sprintf( __( 'Modify the text, "%1$s": %2$s', 'groundhogg' ), $text, $prompt ) );
	}

	public function tweak_formatted_text( string $text, string $prompt ): string {
		// TODO: Implement tweak_formatted_text() method.
	}

	public function image( string $prompt ): string {
		// TODO: Implement image() method.
	}
}
