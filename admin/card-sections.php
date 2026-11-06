<?php

namespace Groundhogg\Admin;

use function Groundhogg\kses_e;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Settings laid out as cards. Related fields share a card with a title, every field is a row with its label on the left
 * and its control on the right.
 *
 * Used by the settings page and the fields of a user, the styles are in elements.scss under .gh-settings-sections
 */
class Card_Sections {

	/**
	 * Open a card holding a group of related fields
	 *
	 * @param string $title
	 * @param string $description a line on what the section is for
	 * @param string $badge       HTML allowed in posts, shown next to the title, like a pill
	 * @param string $class       'danger' makes the card red, for settings that destroy data
	 * @param string $id          the id of the card, so that a link can go to it
	 */
	public static function section_start( $title, $description = '', $badge = '', $class = '', $id = '' ) {
		?>
        <div <?php echo $id ? 'id="' . esc_attr( $id ) . '"' : ''; ?> class="gh-panel gh-settings-section <?php echo esc_attr( trim( $class ) ); ?>">
            <div class="gh-panel-header gh-settings-section-header">
                <div>
                    <h2><?php echo esc_html( $title ); ?><?php if ( $badge ) { echo ' ' . wp_kses_post( $badge ); } ?></h2>
					<?php if ( $description ): ?>
                        <p class="description"><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
                </div>
            </div>
            <div class="inside no-padding">
		<?php
	}

	/**
	 * Close the card opened by section_start()
	 */
	public static function section_end() {
		?>
            </div>
        </div>
		<?php
	}

	/**
	 * Something that is part of the section, but isn't a field, like a notice or a component that fills its own space
	 *
	 * @param string $html
	 */
	public static function intro( $html ) {
		?>
        <div class="gh-settings-section-intro"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- printed by the section itself ?></div>
		<?php
	}

	/**
	 * A field in a section, the label on the left and the control with its description on the right
	 *
	 * @param string   $label       HTML allowed in posts, if empty the control takes the whole row
	 * @param string   $for         the id of the input the label belongs to, empty for a group of inputs
	 * @param callable $control     prints the control
	 * @param string   $description printed below the control, accepts the HTML allowed in posts
	 * @param string   $class       'wide' lets the control take the whole row
	 */
	public static function row( $label, $for, callable $control, $description = '', $class = '' ) {

		if ( ! $label ) {
			$class .= ' wide';
		}

		?>
        <div class="gh-settings-row <?php echo esc_attr( trim( $class ) ); ?>">
			<?php if ( $label ): ?>
                <div class="gh-settings-row-label">
					<?php if ( $for ): ?>
                        <label for="<?php echo esc_attr( $for ); ?>"><?php echo wp_kses_post( $label ); ?></label>
					<?php else: ?>
                        <span><?php echo wp_kses_post( $label ); ?></span>
					<?php endif; ?>
                </div>
			<?php endif; ?>
            <div class="gh-settings-row-control">
				<?php call_user_func( $control ); ?>
				<?php if ( $description ): ?>
                    <p class="description"><?php kses_e( $description ); ?></p>
				<?php endif; ?>
            </div>
        </div>
		<?php
	}
}
