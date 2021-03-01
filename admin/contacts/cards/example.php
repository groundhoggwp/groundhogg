<?php

/**
 * Display formatting examples for the new css design system for the sidebar info cards
 *
 * @var $contact \Groundhogg\Contact
 */


if ( $contact->get_user_id() ):


	$contact_metadata = $contact->get_meta();

	?>


    <ul id="sortable">

        <li class="ui-state-default" id="1">

            <div class="ic-section">

                <div class="ic-section-header">

                    <div class="ic-section-header-content">

                        <span class="dashicons dashicons-businessman"></span>User Basic Info
                    </div>

                </div>

                <div class="ic-section-content">

                    <ul>

                        <li>

                            <div class="label"><i class="dashicons dashicons-admin-users"></i>Username:</div>

                            <div class="content"><?php echo $contact->get_userdata()->user_login; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-phone"></i>Contact:</div>

                            <div class="content"><?php echo ( $contact_metadata['primary_phone'] ) ? $contact_metadata['primary_phone'] : ''; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-email-alt"></i>Email:</div>

                            <div class="content"><a
                                        href="mailto:<?php echo $contact->get_userdata()->user_email; ?>"><?php echo ( $contact->get_userdata()->user_email ) ? $contact->get_userdata()->user_email : ''; ?></a>
                            </div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-calendar"></i>Registered At:</div>

                            <div class="content orange"><?php echo ( $contact->get_userdata()->user_registered ) ? date( 'F j, Y', strtotime( $contact->get_userdata()->user_registered ) ) : ''; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-format-status"></i>Status:</div>

                            <div class="content"><?php echo( $contact->get_optin_status() == '2' ? '<span class="status green">Confirmed</span>' : '<span class="status red">Unconfirmed</span>' ); ?></div>

                        </li>

                    </ul>

                </div>

            </div>

        </li>

        <li class="ui-state-default" id="2">

            <div class="ic-section">

                <div class="ic-section-header">

                    <div class="ic-section-header-content">

                        <span class="dashicons dashicons-location"></span>Location
                    </div>


                </div>

                <div class="ic-section-content">

                    <ul>

                        <li>

                            <div class="label"><i class="dashicons dashicons-admin-site-alt"></i>Address#1</div>

                            <div class="content"><?php echo ( $contact_metadata['street_address_1'] ) ? $contact_metadata['street_address_1'] : '-'; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-admin-site-alt2"></i>Address#2</div>

                            <div class="content"><?php echo ( $contact_metadata['street_address_2'] ) ? $contact_metadata['street_address_2'] : '-'; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-admin-home"></i>City:</div>

                            <div class="content"><?php echo ( $contact_metadata['city'] ) ? $contact_metadata['city'] : '-'; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-calendar1"></i>State/Province:</div>

                            <div class="content"><?php echo ( $contact_metadata['region'] ) ? $contact_metadata['region'] : '-'; ?></div>

                        </li>

                        <li>

                            <div class="label"><i class="dashicons dashicons-calendar2"></i>Country:</div>

                            <div class="content"><?php echo ( $contact_metadata['country'] ) ? $contact_metadata['country'] : '-'; ?></div>

                        </li>

                    </ul>

                </div>

            </div>

        </li>

        <li class="ui-state-default" id="3">

            <div class="ic-section">

                <div class="ic-section-header">

                    <div class="ic-section-header-content">

                        <span class="dashicons dashicons-screenoptions"></span>User Activity
                    </div>


                </div>

                <div class="ic-section-content">

                    <ul class="timeline">

                        <li>

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li>

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li>

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li>

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li>

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                    </ul>

                </div>

            </div>

        </li>

        <li class="ui-state-default" id="4">

            <div class="ic-section">

                <div class="ic-section-header">

                    <div class="ic-section-header-content">

                        <span class="dashicons dashicons-networking"></span>User Actions
                    </div>


                </div>

                <div class="ic-section-content">

                    <ul class="timeline-second">

                        <li class="left">

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li class="right">

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li class="left">

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                        <li class="right">

                            <div class="label">Lorem ipsum</div>

                            <div class="content">Oct 10, 2017</div>

                        </li>

                    </ul>

                </div>

            </div>

        </li>


    </ul>


<?php else: ?>


<?php endif;

