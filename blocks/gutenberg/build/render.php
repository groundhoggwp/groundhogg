<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$form_id = isset( $attributes['formId'] ) ? $attributes['formId'] : '';
$theme = isset( $attributes['theme'] ) ? $attributes['theme'] : 'default';
$accent_color = isset( $attributes['accentColor'] ) ? $attributes['accentColor'] : '#0073aa';

if ( empty( $form_id ) ) {
	return;
}

$shortcode = '[gh_form id="' . esc_attr( $form_id ) . '" theme="' . esc_attr( $theme ) . '" accent-color="' . esc_attr( $accent_color ) . '"]';
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo do_shortcode( $shortcode ); ?>
</div>
