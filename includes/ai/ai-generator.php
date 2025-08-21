<?php

namespace Groundhogg\AI;

interface AI_Generator {

	/**
	 * Generates a simple string of text, up to a few sentences of text without formatting.
	 *
	 * @param string $prompt a description of the text to create
	 *
	 * @return string
	 */
	public function simple_text( string $prompt ): string;

	/**
	 * Given existing text, tweak it based on the given prompt
	 *
	 * @param string $text the text to tweak
	 * @param string $prompt a description of the desired tweaks, like "shorter, longer, funnier"
	 *
	 * @return string
	 */
	public function tweak_simple_text( string $text, string $prompt ): string;

	/**
	 * Generates HTML formatted text, using <p>, <h1>, <strong> etc...
	 *
	 * @param string $prompt a description of the text to generate
	 *
	 * @return string
	 */
	public function formatted_text( string $prompt ): string;

	/**
	 * Given existing text, tweak it based on the given prompt
	 *
	 * @param string $text the text to tweak
	 * @param string $prompt a description of the tweaks to make
	 *
	 * @return string
	 */
	public function tweak_formatted_text( string $text, string $prompt ): string;

	/**
	 * Should return the image contents (base64 encoded? to be determined later).
	 *
	 * @param string $prompt a description of the image to make, should include dimensions and other constraints.
	 *
	 * @return string
	 */
	public function image( string $prompt ): string;

}
