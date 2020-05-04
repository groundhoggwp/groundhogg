<?php

namespace Groundhogg;
/**
 * How to Use:
 * Pointers are defined in an associative array and passed to the class upon instantiation.
 * First we hook into the 'admin_enqueue_scripts' hook with our function:
 *
 * @package WP_Help_Pointer
 * @version 0.1
 * @author Tim Debo <tim@rawcreativestudios.com>
 * @copyright Copyright (c) 2012, Raw Creative Studios
 * @link https://github.com/rawcreative/wp-help-pointers
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */
class Pointers {

	public $screen_id;
	public $valid;
	public $pointers;

	public function __construct( $pntrs = array() ) {
		// Don't run on WP < 3.3
		if ( get_bloginfo( 'version' ) < '3.3' ) {
			return;
		}

		$screen          = get_current_screen();
		$this->screen_id = $screen->id;

		$this->register_pointers( $pntrs );

		add_action( 'admin_enqueue_scripts', array( &$this, 'add_pointers' ), 1000 );
		add_action( 'admin_footer', array( &$this, 'add_scripts' ) );
	}

	public function register_pointers( $pntrs ) {
		$pointers = array();

		foreach ( $pntrs as $ptr ) {
			if ( $ptr['screen'] == $this->screen_id ) {
				$pointers[ $ptr['id'] ] = array(
					'screen'  => $ptr['screen'],
					'target'  => $ptr['target'],
					'options' => array(
						'content'  => sprintf( '<h3> %s </h3><p> %s </p>%s',
							__( $ptr['title'], 'plugindomain' ),
							__( $ptr['content'], 'plugindomain' ),
							! $ptr['show_next'] ? '' : html()->wrap( html()->wrap( __( 'Next' ), 'a', [
								'href'  => 'javascript:void(0)',
								'class' => 'pointer-next button button-primary',
								'style' => [ 'float' => 'right' ]
							] ), 'p' )
						),
						'position' => $ptr['position']
					)
				);
			}
		}

		$this->pointers = $pointers;
	}

	public function add_pointers() {
		$pointers = $this->pointers;
		if ( ! $pointers || ! is_array( $pointers ) ) {
			return;
		}
		// Get dismissed pointers
		$dismissed      = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		$valid_pointers = array();
		// Check pointers and remove dismissed ones.
		foreach ( $pointers as $pointer_id => $pointer ) {
			// Make sure we have pointers & check if they have been dismissed
			if ( in_array( $pointer_id, $dismissed ) || empty( $pointer ) || empty( $pointer_id ) || empty( $pointer['target'] ) || empty( $pointer['options'] ) ) {
				continue;
			}
			$pointer['pointer_id'] = $pointer_id;
			// Add the pointer to $valid_pointers array
			$valid_pointers['pointers'][] = $pointer;
		}
		// No valid pointers? Stop here.
		if ( empty( $valid_pointers ) ) {
			return;
		}

		$this->valid = $valid_pointers;

		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script( 'wp-pointer' );
	}

	/**
	 * Add our scripts
	 */
	public function add_scripts() {

//	    return;

		$pointers = get_array_var( $this->valid, 'pointers' );

		if ( empty( $pointers ) ) {
			return;
		}

		$pointers = wp_json_encode( $pointers );
		?>
        <script>
            GroundhoggPointers = {};

            (function ($, pointers, p) {

                $.extend(p, {

                    pointers: {},
                    pointer_id: 0,

                    init: function () {

                        var self = this;

                        $.each(pointers, function (i) {
                            self.help_pointer_open(i);
                        });

                        $(document).on('click', '.pointer-next', function (e) {
                            e.preventDefault();
                            self.open_next_pointer();
                        });
                    },

                    open_next_pointer: function () {
                        this.pointers[this.pointer_id].pointer('close');

                        this.pointer_id++;

                        if (this.pointer_id >= Object.keys(this.pointers).length) {
                            this.pointer_id = 0;
                        }

                        this.pointers[this.pointer_id].pointer('open');
                    },

                    help_pointer_open: function (i) {
                        var pointer = pointers[i];

                        options = $.extend(pointer.options, {
                            close: function () {
                                $.post(ajaxurl, {
                                    pointer: pointer.pointer_id,
                                    action: 'dismiss-wp-pointer'
                                });
                            }
                        });

                        if (typeof $.fn.pointer != 'function') {
                            return;
                        }

                        var $pointer = $(pointer.target).pointer(options);

                        this.pointers[i] = $pointer;

                        if (i === 0) {
                            this.pointer_id = i;
                            $pointer.pointer('open');
                        }
                    },

                });

                $(function () {
                    p.init();
                })

            })(jQuery, <?php echo $pointers; ?>, GroundhoggPointers);
        </script>
		<?php
	}
}