<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-29
 * Time: 4:54 PM
 */

?>

<table class="form-table">
    <tbody>
    <tr>

        <th>
            <?php _e( 'Meta Data' ); ?>
        </th>
        <td>

            <?php echo WPGH()->html->meta_key_picker(
                array(
                    'id'    => 'meta_data_1',
                    'name'  => 'meta_data_1',
                )
            ); ?>

        </td>
        <td>

            <?php $args = array(

            );

            echo WPGH()->html->input( $args );
            ?>

        </td>
    </tr>
    </tbody>
</table>
