<?php

namespace Groundhogg\Form\Fields;

use function Groundhogg\html;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-05-09
 * Time: 4:25 PM
 */

/**
 * TODO Support for file types....
 *
 * Class File
 *
 * @package Groundhogg\Form\Fields
 */
class Date extends Input {

	public function get_default_args() {
		return [
			'type'        => 'text',
			'label'       => _x( 'Date *', 'form_default', 'groundhogg' ),
			'name'        => '',
			'id'          => '',
			'class'       => '',
			'max_date'    => '',
			'min_date'    => '',
			'date_format' => 'yy-mm-dd',
			'required'    => false,
			'attributes'  => '',
		];
	}

	/**
	 * Get the name of the shortcode
	 *
	 * @return string
	 */
	public function get_shortcode_name() {
		return 'date';
	}

	/**
	 * Get the min date...
	 */
	public function get_min_date() {
		return esc_attr( $this->get_att( 'min_date' ) );
	}

	/**
	 * Get the max date...
	 */
	public function get_max_date() {
		return esc_attr( $this->get_att( 'max_date' ) );
	}

	/**
	 * Get the max date...
	 */
	public function get_date_format() {
		return esc_attr( $this->get_att( 'date_format' ) );
	}

	public function render() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-ui' );

		$uniq_id = uniqid( 'date_' );

		ob_start();

		?>
		<script>
          (($) => {
            $(() => {
              $('.<?php echo $uniq_id ?>').datepicker({
                changeMonth: true,
                changeYear: true,
				  <?php echo $this->get_min_date() ? "minDate: '{$this->get_min_date()}'," : ''?>
				  <?php echo $this->get_max_date() ? "maxDate: '{$this->get_max_date()}'," : ''?>
				  <?php echo $this->get_date_format() ? "dateFormat: '{$this->get_date_format()}'," : ''?>
              })
            })
          })(jQuery)
		</script>
		<?php

		$script = ob_get_clean();

		$input = html()->input( [
			'type'        => $this->get_type(),
			'id'          => $this->get_id(),
			'name'        => $this->get_name(),
			'class'       => 'gh-input ' . $this->get_classes() . ' ' . $uniq_id,
			'placeholder' => $this->get_placeholder(),
			'title'       => $this->get_title(),
			'required'    => $this->is_required(),
		] );

		// No label, do not wrap in label element.
		if ( ! $this->has_label() ) {
			return $input;
		}

		$label = html()->e( 'label', [
			'class' => 'gh-input-label',
			'for'   => $this->get_id(),
		], $this->get_label() );

		return html()->e( 'div', [ 'class' => 'form-field-with-label' ], [
			$label,
			$input,
            $script
		] );
	}
}
