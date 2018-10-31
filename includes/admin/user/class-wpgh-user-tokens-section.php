<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 10/30/2018
 * Time: 1:48 PM
 */


class WPGH_User_Token_Section
{

    public function __construct()
    {

        add_action( 'show_user_profile', array( $this, 'profile_section' ) );
        add_action( 'edit_user_profile', array( $this, 'profile_section' ) );

    }

    /**
     * @param $user WP_User
     */
    function profile_section( $user )
    {

        $tokens = WPGH()->tokens->get_tokens( array( 'user_id' => $user->ID ) )

        ?>

        <h2><?php _e( 'API Tokens' ); ?></h2>
        <table class="form-table">
            <tbody>
            <?php foreach ( $tokens as $token ): ?>

            <tr>
                <td>
                    <?php echo $token->domain; ?>
                </td>
                <td>
                    <?php echo $token->token; ?>
                </td>
            </tr>

            <?php endforeach; ?>
            </tbody>
        </table>

        <?php

    }

}