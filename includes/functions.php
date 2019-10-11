<?php

namespace Groundhogg;

use Groundhogg\Lib\Mobile\Mobile_Validator;
use WP_Error;

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Wrapper function for Utils function.
 *
 * @param $contact_id_or_email
 * @param $by_user_id
 * @return false|Contact
 */
function get_contactdata($contact_id_or_email = false, $by_user_id = false)
{
    if (!$contact_id_or_email) {
        return Plugin::$instance->tracking->get_current_contact();
    }

    return Plugin::$instance->utils->get_contact($contact_id_or_email, $by_user_id);
}

/**
 * Check whether the current use is the given role.
 *
 * @param string $role
 * @return bool
 */
function current_user_is($role = 'subscriber')
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $roles = ( array )$user->roles;
        return in_array($role, $roles);
    } else {
        return false;
    }
}

/**
 * Internal URL builder.
 *
 * @param string $page
 * @param array $args
 * @return string
 */
function groundhogg_url($page = '', $args = [])
{
    return Plugin::$instance->admin->get_page($page)->admin_url($args);
}

/**
 * Easier url builder.
 *
 * @param $page
 * @param $args
 * @return string
 */
function admin_page_url( $page, $args )
{
    $args = wp_parse_args( $args, [ 'page' => $page ] );
    return add_query_arg( $args, admin_url( 'admin.php' ) );
}

/**
 * Similar to wp_list_pluck in that we take the ID and the title and match them up.
 *
 * @param array $data array[]
 * @param string $id_col string
 * @param string $title_col string
 * @return array
 */
function parse_select2_results($data = [], $id_col = 'ID', $title_col = 'title')
{
    $ids = wp_parse_id_list(wp_list_pluck($data, $id_col));
    $names = wp_list_pluck($data, $title_col);
    $results = array_combine($ids, $names);
    return $results;
}

/**
 * Get DB
 *
 * @param $name
 * @return DB\DB|DB\Meta_DB|DB\Tags
 */
function get_db($name)
{
    return Plugin::$instance->dbs->get_db($name);
}

/**
 * Wrapper
 *
 * @param string $option
 * @return bool
 */
function is_option_enabled($option = '')
{
    return Plugin::$instance->settings->is_option_enabled($option);
}

/**
 * Shorthand;
 *
 * @return HTML
 */
function html()
{
    return Plugin::$instance->utils->html;
}

/**
 * Return if a value in an array isset and is not empty
 *
 * @param $array
 * @param $key
 *
 * @return bool
 */
function isset_not_empty($array, $key = '')
{
    if (is_object($array)) {
        return isset($array->$key) && !empty($array->$key);
    } elseif (is_array($array)) {
        return isset($array[$key]) && !empty($array[$key]);
    }

    return false;
}

/**
 * Get a variable from the $_REQUEST global
 *
 * @param string $key
 * @param bool $default
 * @param bool $post_only
 * @return mixed
 */
function get_request_var($key = '', $default = false, $post_only = false)
{
    $global = $post_only ? $_POST : $_REQUEST;
    return wp_unslash(get_array_var($global, $key, $default));
}

/**
 * Set the $_REQUEST param
 *
 * @param $key
 * @param $value
 */
function set_request_var( $key, $value ){
    $_REQUEST[ $key ] = $value;
}

/**
 * Get a variable from the $_POST global
 *
 * @param string $key
 * @param bool $default
 * @return mixed
 */
function get_post_var($key = '', $default = false)
{
    return wp_unslash(get_array_var($_POST, $key, $default));
}

/**
 * Get a variable from the $_GET global
 *
 * @param string $key
 * @param bool $default
 * @return mixed
 */
function get_url_var($key = '', $default = false)
{
    return map_deep(get_array_var($_GET, $key, $default), 'urldecode');
}

/**
 * Get a variable from the $_GET global
 *
 * @param string $key
 * @param bool $default
 * @return mixed
 */
function get_url_param($key = '', $default = false)
{
    return get_url_var($key, $default);
}

/**
 * Get a db query from the URL.
 *
 * @param array $default a default query if the given is empty
 * @param array $force for the query to include the given
 * @param array $accepted_keys for the query to include the given
 * @return array|string
 */
function get_request_query($default = [], $force = [], $accepted_keys = [])
{
    $query = $_GET;

    $ignore = apply_filters('groundhogg/get_request_query/ignore', [
        'page',
        'paged',
        'ids',
        'tab',
        'action',
        'bulk_action',
        '_wpnonce'
    ]);

    foreach ($ignore as $key) {
        unset($query[$key]);
    }

    $query = urldecode_deep($query);

    if ($search = get_request_var('s')) {
        $query['search'] = $search;
    }

    $query = array_merge($query, $force);
    $query = wp_parse_args($query, $default);

    if (!empty($accepted_keys)) {

        $new_query = [];

        foreach ($accepted_keys as $key) {
            $val = get_array_var($query, $key);
            $new_query[$key] = $val;
        }

        $query = $new_query;
    }

    $query = map_deep($query, 'sanitize_text_field');

    return wp_unslash(array_filter($query));
}

/**
 * Ensures an array
 *
 * @param $array
 * @return array
 */
function ensure_array($array)
{
    if (is_array($array)) {
        return $array;
    }

    return [$array];
}

/**
 * Wrapper for validating tags...
 *
 * @param $maybe_tags
 * @return array
 */
function validate_tags($maybe_tags)
{
    return get_db('tags')->validate($maybe_tags);
}

/**
 * Replacements Wrapper.
 *
 * @param string $content
 * @param int $contact_id
 * @return string
 */
function do_replacements($content = '', $contact_id = 0)
{
    return Plugin::$instance->replacements->process($content, $contact_id);
}

/**
 * Encrypt a string.
 *
 * @param $data
 * @return bool|string
 */
function encrypt($data)
{
    return Plugin::$instance->utils->encrypt_decrypt($data, 'e');
}

/**
 * If WordPress is executing a REST request
 *
 * @return bool
 */
function doing_rest()
{
    return (defined('REST_REQUEST') && REST_REQUEST);
}

/**
 * Decrypt a string
 *
 * @param $data
 * @return bool|string
 */
function decrypt($data)
{
    return Plugin::$instance->utils->encrypt_decrypt($data, 'd');
}

/**
 * Get a variable from an array or default if it doesn't exist.
 *
 * @param $array
 * @param string $key
 * @param bool $default
 * @return mixed
 */
function get_array_var($array, $key = '', $default = false)
{
    if (isset_not_empty($array, $key)) {
        if (is_object($array)) {
            return $array->$key;
        } elseif (is_array($array)) {
            return $array[$key];
        }
    }

    return $default;
}

/**
 * convert a key to words.
 *
 * @param $key
 * @return string
 */
function key_to_words($key)
{
    return ucwords(preg_replace('/[-_]/', ' ', $key));
}

/**
 * Term parser helper.
 *
 * @param $term
 * @return array
 */
function get_terms_for_select($term)
{
    $terms = get_terms($term);
    $options = [];

    foreach ($terms as $term) {
        $options[absint($term->term_id)] = esc_html($term->name);
    }

    return $options;
}

/**
 * @param $post_type string|array
 * @return array
 */
function get_posts_for_select($post_type)
{
    $posts = get_posts(array(
        'post_type' => $post_type,
        'post_status' => 'publish',
        'numberposts' => -1
    ));

    $options = [];

    foreach ($posts as $i => $post) {
        $options[$post->ID] = $post->post_title;
    }

    return $options;
}

/**
 * Convert words to a key
 *
 * @param $words
 * @return string
 */
function words_to_key($words)
{
    return sanitize_key(str_replace(' ', '_', $words));
}

/**
 * Return the percentage to the second degree.
 *
 * @param $a
 * @param $b
 * @return float
 */
function percentage($a, $b)
{
    $a = intval($a);
    $b = intval($b);

    if (!$a) {
        return 0;
    }

    return round(($b / $a) * 100, 2);
}

function sort_by_string_in_array($key)
{
    return function ($a, $b) use ($key) {
        return strnatcmp(get_array_var($a, $key), get_array_var($b, $key));
    };
}

/**
 * If the JSON is your typical error response
 *
 * @param $json
 * @return bool
 */
function is_json_error($json)
{
    return isset($json->code) && isset($json->message) && $json->code !== 'success';
}

/**
 * Convert JSON to a WP_Error
 *
 * @param $json
 * @return bool|WP_Error
 */
function get_json_error($json)
{

    if (is_json_error($json)) {
        return new WP_Error($json->code, $json->message, $json->data);
    }

    return false;
}

/**
 * Normalize multiple files.
 *
 * @param $files
 * @return array
 */
function normalize_files(&$files)
{
    $_files = [];
    $_files_count = count($files['name']);
    $_files_keys = array_keys($files);

    for ($i = 0; $i < $_files_count; $i++)
        foreach ($_files_keys as $key)
            $_files[$i][$key] = $files[$key][$i];

    return $_files;
}

/**
 * Dequeue Theme styles for compatibility
 */
function dequeue_theme_css_compat()
{
    $theme_name = basename(get_stylesheet_directory());

    // Dequeue Theme Support.
    wp_dequeue_style($theme_name . '-style');
    wp_dequeue_style($theme_name);
    wp_dequeue_style('style');

    // Extra compat.
    global $wp_styles;
    $maybe_dequeue = $wp_styles->queue;
    foreach ($maybe_dequeue as $style) {
        if (strpos($style, $theme_name) !== false) {
            wp_dequeue_style($style);
        }
    }
}

/**
 * Dequeue WooCommerce style for compatibility
 */
function dequeue_wc_css_compat()
{
    global $wp_styles;
    $maybe_dequeue = $wp_styles->queue;
    foreach ($maybe_dequeue as $style) {
        if (strpos($style, 'woocommerce') !== false) {
            wp_dequeue_style($style);
        }
    }
}

/**
 * Enqueues the modal scripts
 *
 * @return Modal
 *
 * @since 1.0.5
 */
function enqueue_groundhogg_modal()
{
    return Modal::instance();
}

/**
 * Replace any other domain name with the one of the website.
 *
 * @param $string
 * @return string|string[]|null
 */
function search_and_replace_domain($string)
{
    return preg_replace('#https?:\/\/[^\\/\s]+#', site_url(), $string);
}

/**
 * Convert array to HTML tag attributes
 *
 * @param $atts
 * @return string
 */
function array_to_atts($atts)
{
    $tag = '';

    foreach ($atts as $key => $value) {

        if (empty($value)) {
            continue;
        }

        $key = strtolower($key);

        switch ($key) {
            case 'style':
                $value = array_to_css($value);
                break;
            case 'href':
            case 'action':
            case 'src':
                $value = esc_url($value);
                break;
            default:
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }

                $value = esc_attr($value);
                break;

        }

        $tag .= sanitize_key($key) . '="' . $value . '" ';
    }

    return $tag;
}

/**
 * Convert array to CSS style attributes
 *
 * @param $atts
 * @return string
 */
function array_to_css($atts)
{
    $css = '';
    foreach ($atts as $key => $value) {
        $css .= sanitize_key($key) . ':' . esc_attr($value) . ';';
    }
    return $css;
}

/**
 * Get a cookie value
 *
 * @param string $cookie
 * @param bool $default
 * @return mixed
 */
function get_cookie($cookie = '', $default = false)
{
    return get_array_var($_COOKIE, $cookie, $default);
}

/**
 * Set a cookie the WP way
 *
 * @param string $cookie
 * @param mixed $value
 * @param int $expiration
 * @return bool
 */
function set_cookie($cookie = '', $value = '', $expiration = 3600)
{
    return setcookie($cookie, $value, time() + $expiration, COOKIEPATH, COOKIE_DOMAIN);
}

/**
 * Delete a cookie
 *
 * @param string $cookie
 * @return bool
 */
function delete_cookie($cookie = '')
{
    unset($_COOKIE[$cookie]);
    // empty value and expiration one hour before
    return setcookie($cookie, '', time() - 3600);
}

/**
 * Overwrite the regular WP_Mail with an identical function but use our modified PHPMailer class instead
 * which sends the email to the Groundhogg Sending Service.
 *
 * @param string|array $to Array or comma-separated list of email addresses to send message.
 * @param string $subject Email subject
 * @param string $message Message contents
 * @param string|array $headers Optional. Additional headers.
 * @param string|array $attachments Optional. Files to attach.
 *
 * @return bool Whether the email contents were sent successfully.
 * @throws \Exception
 *
 * @since 1.2.10
 **
 */
function gh_ss_mail($to, $subject, $message, $headers = '', $attachments = array())
{
    // Compact the input, apply the filters, and extract them back out

    /**
     * Filters the wp_mail() arguments.
     *
     * @param array $args A compacted array of wp_mail() arguments, including the "to" email,
     *                    subject, message, headers, and attachments values.
     * @since 2.2.0
     *
     */
    $atts = apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments'));

    if (isset($atts['to'])) {
        $to = $atts['to'];
    }

    if (!is_array($to)) {
        $to = explode(',', $to);
    }

    if (isset($atts['subject'])) {
        $subject = $atts['subject'];
    }

    if (isset($atts['message'])) {
        $message = $atts['message'];
    }

    if (isset($atts['headers'])) {
        $headers = $atts['headers'];
    }

    if (isset($atts['attachments'])) {
        $attachments = $atts['attachments'];
    }

    if (!is_array($attachments)) {
        $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
    }

    global $phpmailer;

    /* Use the GH SS Mailer class instead */
    if (!($phpmailer instanceof GH_SS_Mailer)) {
//        require_once dirname(__FILE__) . '/gh-ss-mailer.php';
        $phpmailer = new GH_SS_Mailer(true);
    }

    // Headers
    $cc = $bcc = $reply_to = array();

    if (empty($headers)) {
        $headers = array();
    } else {
        if (!is_array($headers)) {
            // Explode the headers out, so this function can take both
            // string headers and an array of headers.
            $tempheaders = explode("\n", str_replace("\r\n", "\n", $headers));
        } else {
            $tempheaders = $headers;
        }
        $headers = array();

        // If it's actually got contents
        if (!empty($tempheaders)) {
            // Iterate through the raw headers
            foreach ((array)$tempheaders as $header) {
                if (strpos($header, ':') === false) {
                    if (false !== stripos($header, 'boundary=')) {
                        $parts = preg_split('/boundary=/i', trim($header));
                        $boundary = trim(str_replace(array("'", '"'), '', $parts[1]));
                    }
                    continue;
                }
                // Explode them out
                list($name, $content) = explode(':', trim($header), 2);

                // Cleanup crew
                $name = trim($name);
                $content = trim($content);

                switch (strtolower($name)) {
                    // Mainly for legacy -- process a From: header if it's there
                    case 'from':
                        $bracket_pos = strpos($content, '<');
                        if ($bracket_pos !== false) {
                            // Text before the bracketed email is the "From" name.
                            if ($bracket_pos > 0) {
                                $from_name = substr($content, 0, $bracket_pos - 1);
                                $from_name = str_replace('"', '', $from_name);
                                $from_name = trim($from_name);
                            }

                            $from_email = substr($content, $bracket_pos + 1);
                            $from_email = str_replace('>', '', $from_email);
                            $from_email = trim($from_email);

                            // Avoid setting an empty $from_email.
                        } elseif ('' !== trim($content)) {
                            $from_email = trim($content);
                        }
                        break;
                    case 'mime-version':
                        // Ensure mime-version does not survive do avoid duplicate header.
                        break;
                    case 'content-type':
                        if (strpos($content, ';') !== false) {
                            list($type, $charset_content) = explode(';', $content);
                            $content_type = trim($type);
                            if (false !== stripos($charset_content, 'charset=')) {
                                $charset = trim(str_replace(array('charset=', '"'), '', $charset_content));
                            } elseif (false !== stripos($charset_content, 'boundary=')) {
                                $boundary = trim(str_replace(array('BOUNDARY=', 'boundary=', '"'), '', $charset_content));
                                $charset = '';
                            }

                            // Avoid setting an empty $content_type.
                        } elseif ('' !== trim($content)) {
                            $content_type = trim($content);
                        }
                        break;
                    case 'cc':
                        $cc = array_merge((array)$cc, explode(',', $content));
                        break;
                    case 'bcc':
                        $bcc = array_merge((array)$bcc, explode(',', $content));
                        break;
                    case 'reply-to':
                        $reply_to = array_merge((array)$reply_to, explode(',', $content));
                        break;
                    default:
                        // Add it to our grand headers array
                        $headers[trim($name)] = trim($content);
                        break;
                }
            }
        }
    }

    // Empty out the values that may be set
    $phpmailer->clearAllRecipients();
    $phpmailer->clearAttachments();
    $phpmailer->clearCustomHeaders();
    $phpmailer->clearReplyTos();
    $phpmailer->AltBody = null;

    // From email and name
    // If we don't have a name from the input headers
    if (!isset($from_name)) {
        $from_name = 'WordPress';
    }

    /* If we don't have an email from the input headers default to wordpress@$sitename
     * Some hosts will block outgoing mail from this address if it doesn't exist but
     * there's no easy alternative. Defaulting to admin_email might appear to be another
     * option but some hosts may refuse to relay mail from an unknown domain. See
     * https://core.trac.wordpress.org/ticket/5007.
     */

    if (!isset($from_email)) {
        // Get the site domain and get rid of www.
        $sitename = strtolower($_SERVER['SERVER_NAME']);
        if (substr($sitename, 0, 4) == 'www.') {
            $sitename = substr($sitename, 4);
        }

        $from_email = 'wordpress@' . $sitename;
    }

    /**
     * Filters the email address to send from.
     *
     * @param string $from_email Email address to send from.
     * @since 2.2.0
     *
     */
    $from_email = apply_filters('wp_mail_from', $from_email);

    /**
     * Filters the name to associate with the "from" email address.
     *
     * @param string $from_name Name associated with the "from" email address.
     * @since 2.3.0
     *
     */
    $from_name = apply_filters('wp_mail_from_name', $from_name);

    try {
        $phpmailer->setFrom($from_email, $from_name, false);
    } catch (\phpmailerException $e) {
        $mail_error_data = compact('to', 'subject', 'message', 'headers', 'attachments');
        $mail_error_data['set_from_name'] = $from_name;
        $mail_error_data['set_from_email'] = $from_email;
        $mail_error_data['phpmailer_exception_code'] = $e->getCode();

        /** This filter is documented in wp-includes/pluggable.php */
        do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_error_data));

        return false;
    }

    // Set destination addresses, using appropriate methods for handling addresses
    $address_headers = compact('to', 'cc', 'bcc', 'reply_to');

    foreach ($address_headers as $address_header => $addresses) {
        if (empty($addresses)) {
            continue;
        }

        foreach ((array)$addresses as $address) {
            try {
                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>"
                $recipient_name = '';

                if (preg_match('/(.*)<(.+)>/', $address, $matches)) {
                    if (count($matches) == 3) {
                        $recipient_name = $matches[1];
                        $address = $matches[2];
                    }
                }

                switch ($address_header) {
                    case 'to':
                        $phpmailer->addAddress($address, $recipient_name);
                        break;
                    case 'cc':
                        $phpmailer->addCc($address, $recipient_name);
                        break;
                    case 'bcc':
                        $phpmailer->addBcc($address, $recipient_name);
                        break;
                    case 'reply_to':
                        $phpmailer->addReplyTo($address, $recipient_name);
                        break;
                }
            } catch (\phpmailerException $e) {
                continue;
            }
        }
    }

    // Set Content-Type and charset
    // If we don't have a content-type from the input headers
    if (!isset($content_type) || empty($content_type)) {
        $content_type = 'text/plain';
    }

    /**
     * Filters the wp_mail() content type.
     *
     * @param string $content_type Default wp_mail() content type.
     * @since 2.3.0
     *
     */
    $content_type = apply_filters('wp_mail_content_type', $content_type);

    $phpmailer->ContentType = $content_type;

    // Set the content-type and charset
    // Set whether it's plaintext, depending on $content_type
    // GHSS can only send HTML emails apparently. So convert all emails to HTML
    if ('text/html' == $content_type) {
        $phpmailer->isHTML(true);
    }

    // Set mail's subject and body
    $phpmailer->Subject = $subject;
    $phpmailer->Body = $message;

    // If we don't have a charset from the input headers
    if (!isset($charset)) {
        $charset = get_bloginfo('charset');
    }


    /**
     * Filters the default wp_mail() charset.
     *
     * @param string $charset Default email charset.
     * @since 2.3.0
     *
     */
    $phpmailer->CharSet = apply_filters('wp_mail_charset', $charset);

    // Set custom headers
    if (!empty($headers)) {
        foreach ((array)$headers as $name => $content) {
            $phpmailer->addCustomHeader(sprintf('%1$s: %2$s', $name, $content));
        }

        if (false !== stripos($content_type, 'multipart') && !empty($boundary)) {
            $phpmailer->addCustomHeader(sprintf("Content-Type: %s;\n\t boundary=\"%s\"", $content_type, $boundary));
        }
    }

    if (!empty($attachments)) {
        foreach ($attachments as $attachment) {
            try {
                $phpmailer->addAttachment($attachment);
            } catch (\phpmailerException $e) {
                continue;
            }
        }
    }

    /**
     * Fires after PHPMailer is initialized.
     *
     * @param \PHPMailer $phpmailer The PHPMailer instance (passed by reference).
     * @since 2.2.0
     *
     */
    do_action_ref_array('phpmailer_init', array(&$phpmailer));

    // Hard set X-Mailer cuz we taking credit for this.
    $phpmailer->XMailer = sprintf('Groundhogg %s (https://www.groundhogg.io)', GROUNDHOGG_VERSION);

    if ($content_type === 'text/html' && empty($phpmailer->AltBody)) {
        $phpmailer->AltBody = wp_strip_all_tags($message);
    }

    // Send!
    try {

        return $phpmailer->send();

    } catch (\phpmailerException $e) {

        $mail_error_data = compact('to', 'subject', 'message', 'headers', 'attachments');
        $mail_error_data['phpmailer_exception_code'] = $e->getCode();
        $mail_error_data['mime_message'] = $phpmailer->getSentMIMEMessage();
        $mail_error_data['set_from_name'] = $from_name;
        $mail_error_data['set_from_email'] = $from_email;

        if (Plugin::$instance->sending_service->has_errors()) {
            $mail_error_data['orig_error_data'] = Plugin::$instance->sending_service->get_last_error()->get_error_data();
            $mail_error_data['orig_error_message'] = Plugin::$instance->sending_service->get_last_error()->get_error_message();
            $mail_error_data['orig_error_code'] = Plugin::$instance->sending_service->get_last_error()->get_error_code();
        }

        /**
         * Fires after a phpmailerException is caught.
         *
         * @param WP_Error $error A WP_Error object with the phpmailerException message, and an array
         *                        containing the mail recipient, subject, message, headers, and attachments.
         * @since 4.4.0
         *
         */
        do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $mail_error_data));

        return false;
    }
}

/**
 * handle a wp_mail_failed event.
 *
 * @param $error WP_Error
 */
function listen_for_complaint_and_bounce_emails($error)
{
    $data = (array)$error->get_error_data();

    if (!isset_not_empty($data, 'orig_error_data')) {
        return;
    }

    $code = $data['orig_error_code'];
    $data = $data['orig_error_data'];

    if ($code === 'invalid_recipients') {

        /* handle bounces */
        $bounces = isset_not_empty($data, 'bounces') ? $data['bounces'] : [];

        if (!empty($bounces)) {
            foreach ($bounces as $email) {
                if ($contact = get_contactdata($email)) {
                    $contact->change_marketing_preference(Preferences::HARD_BOUNCE);
                }
            }

        }

        $complaints = isset_not_empty($data, 'complaints') ? $data['complaints'] : [];

        if (!empty($complaints)) {
            foreach ($complaints as $email) {
                if ($contact = get_contactdata($email)) {
                    $contact->change_marketing_preference(Preferences::COMPLAINED);
                }
            }
        }
    }
}

add_action('wp_mail_failed', __NAMESPACE__ . '\listen_for_complaint_and_bounce_emails');

/**
 * Return the FULL URI from wp_get_referer for string comparisons
 *
 * @return string
 */
function wpgh_get_referer()
{
    if (!isset($_POST['_wp_http_referer']))
        return wp_get_referer();

    return (is_ssl() ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}" . $_REQUEST['_wp_http_referer'];
}

/**
 * Recount the contacts per tag...
 */
function recount_tag_contacts_count()
{
    /* Recount tag relationships */
    $tags = Plugin::$instance->dbs->get_db('tags')->query();

    if (!empty($tags)) {
        foreach ($tags as $tag) {
            $count = Plugin::$instance->dbs->get_db('tag_relationships')->count(['tag_id' => absint($tag->tag_id)]);
            Plugin::$instance->dbs->get_db('tags')->update(absint($tag->tag_id), ['contact_count' => $count]);
        }
    }
}

/**
 * Create a contact quickly from a user account.
 *
 * @param $user \WP_User|int
 * @param $sync_meta bool whether to copy the meta data over.
 * @return Contact|false|WP_Error the new contact, false on failure, or WP_Error on error
 */
function create_contact_from_user($user, $sync_meta = false)
{

    if (is_int($user)) {
        $user = get_userdata($user);
        if (!$user) {
            return false;
        }
    }

    if (!$user instanceof \WP_User) {
        return false;
    }

    $contact = get_contactdata($user->user_email);

    /**
     * Do not continue if the contact already exists. Just return it...
     */
    if ($contact && $contact->exists()) {
        $contact->update(['user_id' => $user->ID]);
        $contact->update_meta('user_login', $user->user_login);
        return $contact;
    }

    /**
     * Setup the initial args..
     */
    $args = array(
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->user_email,
        'user_id' => $user->ID,
        'optin_status' => Preferences::UNCONFIRMED
    );

    if (empty($args['first_name'])) {
        $args['first_name'] = $user->display_name;
    }

    $contact = new Contact();

    $id = $contact->create($args);

    if (!$id) {
        return new \WP_Error('db_error', __('Could not create contact.', 'groundhogg'));
    }

    // Save the login
    $contact->update_meta('user_login', $user->user_login);

    if ($sync_meta) {

        $user_meta = get_user_meta($user->ID);

        // Note: $values will be an array as single is false
        foreach ($user_meta as $key => $values) {
            $contact->update_meta($key, array_shift($values));
        }

    }

    return $contact;
}

/**
 * Provides a global hook not requireing the benchmark anymore.
 *
 * @param $userId int the Id of the user
 */
function convert_user_to_contact_when_user_registered($userId)
{
    $user = get_userdata($userId);
    $contact = create_contact_from_user($user);

    if (!$contact) {
        return;
    }

    if (!is_admin()) {

        /* register front end which is technically an optin */
        $contact->update_meta('last_optin', time());

    }

    /**
     * Provide hook for the Account Created benchmark and other functionality
     *
     * @param $user \WP_User
     * @param $contact Contact
     */
    do_action('groundhogg/contact_created_from_user', $user, $contact);
}

// Ensure runs before tag mapping stuff...
add_action('user_register', __NAMESPACE__ . '\convert_user_to_contact_when_user_registered');

/**
 * Used for blocks...
 *
 * @return array
 */
function get_form_list()
{

    $forms = Plugin::$instance->dbs->get_db('steps')->query([
        'step_type' => 'form_fill'
    ]);

    $form_options = array();
    $default = 0;
    foreach ($forms as $form) {
        if (!$default) {
            $default = $form->ID;
        }
        $step = Plugin::$instance->utils->get_step($form->ID);
        if ($step->is_active()) {
            $form_options[$form->ID] = $form->step_title;
        }
    }

    return $form_options;
}

/**
 * Schedule a 1 off email notification
 *
 * @param $email_id int the ID of the email to send
 * @param $contact_id_or_email int|string the ID of the contact to send to
 * @param int $time time time to send at, defaults to time()
 *
 * @return bool whether the scheduling was successful.
 */
function send_email_notification($email_id, $contact_id_or_email, $time = 0)
{
    $contact = Plugin::$instance->utils->get_contact($contact_id_or_email);
    $email = Plugin::$instance->utils->get_email($email_id);

    if (!$contact || !$email) {
        return false;
    }

    if (!$time) {
        $time = time();
    }

    $event = [
        'time' => $time,
        'funnel_id' => 0,
        'step_id' => $email->get_id(),
        'contact_id' => $contact->get_id(),
        'event_type' => Event::EMAIL_NOTIFICATION,
        'status' => 'waiting',
    ];

    if (Plugin::$instance->dbs->get_db('events')->add($event)) {
        return true;
    }

    return false;
}


/**
 * Parse the headers and return things like from/to etc...
 *
 * @param $headers string|string[]
 * @return array|false
 */
function parse_email_headers($headers)
{
    $headers = is_array($headers) ? implode(PHP_EOL, $headers) : $headers;
    if (!is_string($headers)) {
        return false;
    }

    $parsed = imap_rfc822_parse_headers($headers);

    if (!$parsed) {
        return false;
    }

    $map = [];

    if ($parsed->sender && !is_array($parsed->sender)) {
        $map['sender'] = sprintf('%s@%s', $parsed->sender->mailbox, $parsed->sender->host);
        $map['from'] = $parsed->sender->personal;
    } else if (is_array($parsed->sender)) {
        $map['sender'] = sprintf('%s@%s', $parsed->sender[0]->mailbox, $parsed->sender[0]->host);
        $map['from'] = $parsed->sender[0]->personal;
    }

    return $map;
}

/**
 * AWS Doesn't like special chars in the from name so we'll strip them out here.
 *
 * @param $name
 * @return string
 */
function sanitize_from_name($name)
{
    return sanitize_text_field(preg_replace('/[^A-z0-9 ]/', '', $name));
}

/**
 * This function is for use by any form or eccom extensions which is essentially a copy of the PROCESS method in the submission handler.
 *
 * @param $contact Contact
 */
function after_form_submit_handler(&$contact)
{

    if ($contact->update_meta('ip_address', Plugin::$instance->utils->location->get_real_ip())) {
        $contact->extrapolate_location();
    }

    if (!$contact->get_meta('lead_source')) {
        $contact->update_meta('lead_source', Plugin::$instance->tracking->get_leadsource());
    }

    if (!$contact->get_meta('source_page')) {
        $contact->update_meta('source_page', wpgh_get_referer());
    }

    if (!$contact->is_marketable()) {
        $contact->change_marketing_preference(Preferences::UNCONFIRMED);
    }

    $contact->update_meta('last_optin', time());

    /**
     * Helper function.
     *
     * @param $contact Contact
     */
    do_action('groundhogg/after_form_submit', $contact);
}

/**
 * Whether the given email address has the same hostname as the current site.
 *
 * @param $email
 * @return bool
 */
function email_is_same_domain($email)
{
    $email_domain = substr($email, strrpos($email, '@') + 1);
    $site_domain = site_url();
    $is_same = strpos($site_domain, $email_domain) !== false;
    return apply_filters( 'groundhogg/email_is_same_domain', $is_same, $email, $site_domain );
}

/**
 * Notify the admin when credits run low.
 *
 * @param $credits
 */
function gh_ss_notify_low_credit($credits)
{
    if ($credits > 1000) {
        return;
    }

    $message = false;
    $subject = false;

    switch ($credits) {
        case 1000:
        case 500:
        case 300:
        case 100:
        case 0:
            $subject = sprintf("Low on Email credits!");
            $message = sprintf("You are running low on credits! Only %s credits remaining. Top up on credits &rarr; https://www.groundhogg.io/downloads/credits/", $credits);
            break;
    }

    if ($message && $subject) {
        wp_mail(get_bloginfo('admin_email'), $subject, $message);
    }

}

add_action('groundhogg/ghss/credits_used', __NAMESPACE__ . '\gh_ss_notify_low_credit');


/**
 * Send event failure notification.
 *
 * @param $event Event
 */
function send_event_failure_notification($event)
{
    if ( ! is_option_enabled('gh_send_notifications_on_event_failure' ) || get_transient('gh_hold_failed_event_notification') ) {
        return;
    }

    $subject = sprintf("Event (%s) failed for %s on %s", $event->get_step_title(), $event->get_contact()->get_email(), esc_html(get_bloginfo('title')));
    $message = sprintf("This is to let you know that an event \"%s\" in funnel \"%s\" has failed for \"%s (%s)\"", $event->get_step_title(), $event->get_funnel_title(), $event->get_contact()->get_full_name(), $event->get_contact()->get_email());
    $message .= sprintf("\nFailure Reason: %s", $event->get_failure_reason());
    $message .= sprintf("\nManage Failed Events: %s", admin_url('admin.php?page=gh_events&view=status&status=failed'));
    $to = Plugin::$instance->settings->get_option('event_failure_notification_email', get_option('admin_email'));

    if (wp_mail($to, $subject, $message)) {
        set_transient('gh_hold_failed_event_notification', true, MINUTE_IN_SECONDS);
    }
}

add_action('groundhogg/event/failed', __NAMESPACE__ . '\send_event_failure_notification');


/**
 * Split a name into first and last.
 *
 * @param $name
 *
 * @return array
 */
function split_name($name)
{
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
    return array($first_name, $last_name);
}

/**
 * Get a list of items from a file path, if file does not exist of there are no items return an empty array.
 *
 * @param string $file_path
 * @return array
 */
function get_items_from_csv($file_path = '')
{

    if (!file_exists($file_path)) {
        return [];
    }

    $header = NULL;
    $header_count = 0;
    $data = array();
    if (($handle = fopen($file_path, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
            if (!$header) {
                $header = $row;
                $header_count = count($header);
            } else {

                if (count($row) > $header_count) {
                    $row = array_slice($row, 0, $header_count);
                } else if (count($row) < $header_count) {
                    $row = array_pad($row, $header_count - count($row) + 1, null);
                }

                $data[] = array_combine($header, $row);
            }
        }
        fclose($handle);
    }

    return $data;

}


/**
 * Get a list of mappable fields as well as extra fields
 *
 * @param array $extra
 * @return array
 */
function get_mappable_fields($extra = [])
{

    $defaults = [
        'full_name' => __('Full Name'),
        'first_name' => __('First Name'),
        'last_name' => __('Last Name'),
        'email' => __('Email Address'),
        'optin_status' => __('Optin Status'),
        'user_id' => __('User Id'),
        'owner_id' => __('Owner Id'),
        'date_created' => __('Date Created'),
        'birthday' => __('Birthday'),
        'primary_phone' => __('Phone Number'),
        'primary_phone_extension' => __('Phone Number Extension'),
        'street_address_1' => __('Street Address 1'),
        'street_address_2' => __('Street Address 2'),
        'city' => __('City'),
        'postal_zip' => __('Postal/Zip'),
        'region' => __('Province/State/Region'),
        'country' => __('Country'),
        'company_name' => __('Company Name'),
        'company_address' => __('Full Company Address'),
        'job_title' => __('Job Title'),
        'time_zone' => __('Time Zone'),
        'ip_address' => __('IP Address'),
        'lead_source' => __('Lead Source'),
        'source_page' => __('Source Page'),
        'terms_agreement' => __('Terms Agreement'),
        'gdpr_consent' => __('GDPR Consent'),
        'notes' => __('Add To Notes'),
        'tags' => __('Apply Value as Tag'),
        'meta' => __('Add as Custom Meta'),
        'file' => __('Upload File'),
        'utm_campaign' => __('UTM Campaign'),
        'utm_content' => __('UTM Content'),
        'utm_medium' => __('UTM Medium'),
        'utm_term' => __('UTM Term'),
        'utm_source' => __('UTM Source'),
    ];

    $fields = array_merge($defaults, $extra);

    return apply_filters('groundhogg/mappable_fields', $fields);

}

/**
 * Generate a contact from given associative array and a field map.
 *
 * @param $fields
 * @param $map
 *
 * @return Contact|false
 */
function generate_contact_with_map($fields, $map)
{
    $meta = [];
    $tags = [];
    $notes = [];
    $args = [];
    $files = [];

    foreach ($fields as $column => $value) {

        // ignore if we are not mapping it.
        if (!key_exists($column, $map)) {
            continue;
        }

        $value = wp_unslash($value);

        $field = $map[$column];

        switch ($field) {
            case 'full_name':
                $parts = split_name($value);
                $args['first_name'] = sanitize_text_field($parts[0]);
                $args['last_name'] = sanitize_text_field($parts[1]);
                break;
            case 'first_name':
            case 'last_name':
                $args[$field] = sanitize_text_field($value);
                break;
            case 'email':
                $args[$field] = sanitize_email($value);
                break;
            case 'date_created':
                $args[$field] = date('Y-m-d H:i:s', strtotime($value));
                break;
            case 'optin_status':
                $args[$field] = absint( $value );
                break;
            case 'user_id':
            case 'owner_id':

                // Email Passed
                if (is_email($value)) {
                    $by = 'email';
                // Username passed
                } elseif (is_string($value) && !is_numeric($value)) {
                    $by = 'login';
                // ID Passed
                } else {
                    $by = 'id';
                    $value = absint( $value );
                }

                $user = get_user_by($by, $value);

                // Make sure User exists
                if ( $user ){
                    // Check the mapped owner can actually own contacts.
                    if ( $field !== 'owner_id' || user_can( $user->ID, 'edit_contacts' ) ){
                        $args[$field] = $user->ID;
                    }
                }

            break;
            case 'primary_phone':
            case 'primary_phone_extension':
            case 'street_address_1' :
            case 'street_address_2':
            case 'city':
            case 'postal_zip':
            case 'region':
            case 'company_name':
            case 'company_address':
            case 'job_title':
            case 'lead_source':
            case 'source_page':
            case 'utm_campaign':
            case 'utm_medium':
            case 'utm_content':
            case 'utm_term':
            case 'utm_source':
                $meta[$field] = sanitize_text_field($value);
                break;
            // Only checks whether value is not empty.
            case 'terms_agreement':
                if (!empty($value)) {
                    $meta['terms_agreement'] = 'yes';
                    $meta['terms_agreement_date'] = date_i18n(get_date_time_format());
                }
                break;
            // Only checks whether value is not empty.
            case 'gdpr_consent':
                if (!empty($value)) {
                    $meta['gdpr_consent'] = 'yes';
                    $meta['gdpr_consent_date'] = date_i18n(get_date_time_format());
                }
                break;
            case 'country':
                if (strlen($value) !== 2) {
                    $countries = Plugin::$instance->utils->location->get_countries_list();
                    $code = array_search($value, $countries);
                    if ($code) {
                        $value = $code;
                    }
                }
                $meta[$field] = $value;
                break;
            case 'tags':
                $maybe_tags = explode(',', $value);
                $tags = array_merge($tags, $maybe_tags);
                break;
            case 'meta':
                $meta[get_key_from_column_label($column)] = sanitize_text_field($value);
                break;
            case 'files':
                if (isset_not_empty($_FILES, $column)) {
                    $files[$column] = wp_unslash(get_array_var($_FILES, $column));
                }
                break;
            case 'notes':
                $notes[] = sanitize_textarea_field($value);
                break;
            case 'time_zone':
                $zones = Plugin::$instance->utils->location->get_time_zones();
                $code = array_search($value, $zones);
                if ($code) {
                    $meta[$field] = $code;
                }
                break;
            case 'ip_address':
                $ip = filter_var($value, FILTER_VALIDATE_IP);
                if ($ip) {
                    $meta[$field] = $ip;
                }
                break;
            case 'birthday':

                $date = date('Y-m-d', strtotime($value));
                $parts = map_deep(explode('-', $date), 'absint');

                $meta['birthday_year'] = $parts[0];
                $meta['birthday_month'] = $parts[1];
                $meta['birthday_day'] = $parts[2];
                $meta['birthday'] = $date;
                break;
        }

    }

    $contact = new Contact();
    $id = $contact->create($args);

    if (!$id) {
        return false;
    }

    // Add Tags
    if (!empty($tags)) {
        $contact->apply_tag($tags);
    }

    // Add notes
    if (!empty($notes)) {
        foreach ($notes as $note) {
            $contact->add_note($note);
        }
    }

    // update meta data
    if (!empty($meta)) {
        foreach ($meta as $key => $value) {
            $contact->update_meta($key, $value);
        }
    }

    if (!empty($files)) {
        foreach ($files as $file) {
            $contact->upload_file($file);
        }
    }

    $contact->update_meta('last_optin', time());

    return $contact;
}

if (!function_exists('get_key_from_column_label')):

    /**
     * Get a key from a column label
     *
     * @param $column
     * @return string
     */
    function get_key_from_column_label($column)
    {
        return words_to_key($column);
    }

endif;

if (!function_exists('multi_implode')):
    function multi_implode($glue, $array)
    {
        $ret = '';

        foreach ($array as $item) {
            if (is_array($item)) {
                $ret .= multi_implode($glue, $item) . $glue;
            } else {
                $ret .= $item . $glue;
            }
        }

        $ret = substr($ret, 0, 0 - strlen($glue));

        return $ret;
    }
endif;

/**
 * Get a time string representing when something should be completed.
 *
 * @param $time
 * @return string
 */
function scheduled_time($time)
{
    // convert to local time.
    $p_time = Plugin::$instance->utils->date_time->convert_to_local_time($time);

    // Get the current time.
    $cur_time = (int)current_time('timestamp');

    $time_diff = $p_time - $cur_time;

    if (absint($time_diff) > DAY_IN_SECONDS) {
        $time = sprintf(_x("on %s", 'status', 'groundhogg'), date_i18n(get_date_time_format(), intval($p_time)));
    } else {
        $format = $time_diff <= 0 ? _x("%s ago", 'status', 'groundhogg') : _x("in %s", 'status', 'groundhogg');
        $time = sprintf($format, human_time_diff($p_time, $cur_time));
    }

    return $time;
}

function get_store_products($args = [])
{
    $args = wp_parse_args($args, array(
        //'category' => 'templates',
        'category' => '',
        'tag' => '',
        's' => '',
        'page' => '',
        'number' => '-1'
    ));

    $url = 'https://www.groundhogg.io/edd-api/v2/products/';

    $response = wp_remote_get(add_query_arg($args, $url));

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }

    $products = json_decode(wp_remote_retrieve_body($response));

    return $products;
}

/**
 * Whether to show Groundhogg Branding. Compat with white label options.
 *
 * @return bool
 */
function show_groundhogg_branding()
{
    return apply_filters('groundhogg/show_branding', true);
}

/**
 * Show a floating phil on the page!
 *
 * @return void;
 */
function floating_phil()
{
    ?><img style="position: fixed;bottom: -80px;right: -80px;transform: rotate(-20deg);" class="phil"
           src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" width="340" height="340"><?php
}

/**
 * Show the logo.
 *
 * @param string $color
 * @param int $width
 */
function groundhogg_logo($color = 'black', $width = 300)
{

    switch ($color) {
        default:
        case 'black':
            $link = 'logo-black-1000x182.png';
            break;
        case 'white':
            $link = 'logo-white-1000x182.png';
            break;
    }

    ?><img src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/' . $link; ?>" width="<?php echo $width; ?>"><?php
}

/**
 * Return form submission error html or echo it.
 *
 * @param bool $return
 * @return bool|string
 */
function form_errors($return = true)
{
    if (Plugin::$instance->submission_handler->has_errors()) {

        $errors = Plugin::$instance->submission_handler->get_errors();
        $err_html = "";

        foreach ($errors as $error) {
            $err_html .= sprintf('<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message());
        }

        $err_html = sprintf("<ul class='gh-form-errors'>%s</ul>", $err_html);
        $err_html = sprintf("<div class='gh-message-wrapper gh-form-errors-wrapper'>%s</div>", $err_html);

        if ($return) {
            return $err_html;
        }

        echo $err_html;

        return true;
    }

    return false;
}

/**
 * Get the email templates
 *
 * @return array
 */
function get_email_templates()
{
    include GROUNDHOGG_PATH . 'templates/assets/email-templates.php';
    /**
     * @var $email_templates array
     */
    return $email_templates;
}

/**
 * Checks data to see if it matches anything in the blacklist.
 *
 * @param string $data
 * @return bool
 */
function blacklist_check($data = '')
{

    if (!is_array($data) && !is_object($data)) {
        $mod_keys = trim(get_option('blacklist_keys'));
        if ('' == $mod_keys) {
            return false; // If moderation keys are empty
        }

        // Ensure HTML tags are not being used to bypass the blacklist.
        $data_no_html = wp_strip_all_tags($data);

        $words = explode("\n", $mod_keys);

        foreach ((array)$words as $word) {
            $word = trim($word);

            // Skip empty lines
            if (empty($word)) {
                continue;
            }

            // Do some escaping magic so that '#' chars in the
            // spam words don't break things:
            $word = preg_quote($word, '#');

            $pattern = "#$word#i";

            if (preg_match($pattern, $data) || preg_match($pattern, $data_no_html)) {
                return true;
            }
        }

        return false;
    }

    foreach ((array)$data as $datum) {
        if (blacklist_check($datum)) {
            return true;
        }
    }

    return false;
}

/**
 * @return mixed|void
 */
function get_managed_page_name()
{
    return apply_filters('groundhogg/managed_page_name', get_option('gh_managed_page_name_override', 'gh'));
}

/**
 * Return the URL markeup for the managed page
 *
 * @param string $url
 * @return string|void
 */
function managed_page_url($url = '')
{
    return trailingslashit(rtrim(site_url(get_managed_page_name()), '/') . '/' . ltrim($url, '/'));
}

/**
 * Setup the managed page
 */
function setup_managed_page()
{
    $managed_page_name = get_managed_page_name();

    $query = new \WP_Query();
    $posts = $query->query([
        'name' => $managed_page_name,
        'post_type' => 'page',
        'post_status' => 'publish'
    ]);

    if (empty($posts)) {
        $post_id = wp_insert_post([
            'post_title' => 'managed-page',
            'post_status' => 'publish',
            'post_name' => $managed_page_name,
            'post_type' => 'page',
            'post_content' => "Shhhh! This is a secret page. Go away!"
        ], true);

        if (is_wp_error($post_id)) {
            Plugin::$instance->notices->add($post_id);
        }
    }
}

/**
 * Add a managed rewrite rule
 *
 * @param string $regex
 * @param string $query
 * @param string $after
 */
function add_managed_rewrite_rule($regex = '', $query = '', $after = 'top')
{

    $managed_page_name = get_managed_page_name();

    if (strpos($query, 'index.php') === false) {
        $ahead = sprintf('index.php?pagename=%s&', $managed_page_name);
        $query = $ahead . $query;
    }

    if (strpos($regex, '^' . $managed_page_name) !== 0) {
        $regex = '^' . $managed_page_name . '/' . $regex;
    }

    add_rewrite_rule($regex, $query, $after);
}

/**
 * @param string $string
 * @return string
 * @deprecated since 2.0.9.2
 */
function managed_rewrite_rule($string = '')
{
    return sprintf('index.php?pagename=%s&', get_managed_page_name()) . $string;
}

/**
 * @return bool
 */
function is_managed_page()
{
    return get_query_var('pagename') === get_managed_page_name();
}

/**
 * Add the new rewrite rules.
 */
function install_custom_rewrites()
{
    setup_managed_page();

    Plugin::$instance->tracking->add_rewrite_rules();
    Plugin::$instance->rewrites->add_rewrite_rules();
    Plugin::$instance->preferences->add_rewrite_rules();

    flush_rewrite_rules();
}

/**
 * Retrieve URL with nonce added to URL query.
 *
 * @param string $actionurl URL to add nonce action.
 * @param int|string $action Optional. Nonce action name. Default -1.
 * @param string $name Optional. Nonce name. Default '_wpnonce'.
 * @return string
 * @since 2.0.4
 *
 */
function nonce_url_no_amp($actionurl, $action = -1, $name = '_wpnonce')
{
    return add_query_arg($name, wp_create_nonce($action), $actionurl);
}

/**
 * Return a dashicon
 *
 * @param $icon
 * @param string $wrap
 * @param array $atts
 *
 * @return string
 */
function dashicon($icon, $wrap = 'span', $atts = [], $echo = false)
{
    $atts = wp_parse_args($atts, [
        'class' => 'dashicons dashicons-'
    ]);

    $atts['class'] .= $icon;

    $html = html()->e($wrap, $atts, '', false);

    if ($echo) {
        echo $html;
    }

    return $html;
}

/**
 * Output a dashicon
 *
 * @param $icon
 * @param string $wrap
 * @param array $atts
 */
function dashicon_e( $icon, $wrap = 'span', $atts = [] )
{
    dashicon( $icon, $wrap, $atts, true );
}


/**
 * Whather the current admin page is a groundhogg page.
 *
 * @return bool
 */
function is_admin_groundhogg_page()
{
    $page = get_request_var('page');
    return is_admin() && $page && (preg_match('/^gh/', $page) || $page === 'groundhogg');
}


if (!function_exists(__NAMESPACE__ . '\is_white_labeled')) {

    /**
     * Whether the Groundhogg is white labeled or not.
     *
     * @return bool
     */
    function is_white_labeled()
    {
        return false; // todo make false
    }
}

if (!function_exists(__NAMESPACE__ . '\white_labeled_name')) {

    /**
     * Return replacement name form white label
     *
     * @return string
     */
    function white_labeled_name()
    {
        return 'Groundhogg';  // TODO
    }
}

/**
 * Gets the main blog ID.
 *
 * @return int
 */
function get_main_blog_id()
{
    if (is_multisite()) {
        return get_network()->site_id;
    }

    return false;
}

/**
 * Whether the current blog is the main blog.
 *
 * @return bool
 */
function is_main_blog()
{
    if (!is_multisite()) {
        return true;
    }

    return get_main_blog_id() === get_current_blog_id();
}

/**
 * Remote post json content
 * Glorified wp_remote_post wrapper
 *
 * @param string $url
 * @param array $body
 * @param string $method
 * @param array $headers
 * @return array|bool|WP_Error
 */
function remote_post_json($url = '', $body = [], $method = 'POST', $headers = [])
{
    $method = strtoupper($method);

    if (!isset_not_empty($headers, 'Content-type')) {
        $headers['Content-type'] = sprintf('application/json; charset=%s', get_bloginfo('charset'));
    }

    $body = is_array($body) ? wp_json_encode($body) : $body;

    $args = [
        'method' => $method,
        'headers' => $headers,
        'body' => $body,
        'data_format' => 'body',
        'sslverify' => true
    ];

    if ($method === 'GET') {
        $response = wp_remote_get($url, $args);
    } else {
        $response = wp_remote_post($url, $args);
    }

    if (!$response) {
        return new WP_Error('unknown_error', sprintf('Failed to initialize remote %s.', $method), $response);
    }

    if (is_wp_error($response)) {
        return $response;
    }

    $json = json_decode(wp_remote_retrieve_body($response));

    if (!$json) {
        return new WP_Error('unknown_error', sprintf('Failed to initialize remote %s.', $method), wp_remote_retrieve_body($response));
    }

    if (is_json_error($json)) {
        $error = get_json_error($json);

        $data = (array)$error->get_error_data();

        $data['url'] = $url;
        $data['method'] = $method;
        $data['headers'] = $headers;
        $data['body'] = json_decode($body);

        $error->add_data($data);

        return $error;
    }

    return $json;
}

/**
 * Returns the full format of dat time based on WP settings.
 *
 * @return string
 */
function get_date_time_format()
{
    return sprintf("%s %s", get_option('date_format'), get_option('time_format'));
}

/**
 * Url to access protected files in the Groundhogg uploads folder.
 *
 * @param $path string abspath to a file.
 * @param $download bool
 * @return string
 */
function file_access_url($path, $download = false)
{

    $url = managed_page_url('files/' . ltrim($path, '/'));

    if ($download) {
        $url = add_query_arg(['download' => true], $url);
    }

    return $url;
}

/**
 * Triggers the API benchmark
 *
 * @param string $call_name the name you wish to call
 * @param string $id_or_email id or email of the contact
 * @param bool $by_user_id whether the ID is the ID of a WP user
 */
function do_api_trigger($call_name = '', $id_or_email = '', $by_user_id = false)
{
    do_action('groundhogg/steps/benchmarks/api', $call_name, $id_or_email, $by_user_id);
}

/**
 * Wrapper for the do_api_trigger function.
 *
 * @param string $call_name
 * @param string $id_or_email
 * @param bool $by_user_id
 */
function do_api_benchmark($call_name = '', $id_or_email = '', $by_user_id = false)
{
    do_api_trigger($call_name, $id_or_email, $by_user_id);
}

/**
 * Get the value of an option.
 *
 * @param string $option
 * @return mixed|string
 */
function get_screen_option($option = '')
{
    $user = get_current_user_id();
    $screen = get_current_screen();
    $screen_option = $screen->get_option($option, 'option');
    $value = get_user_meta($user, $screen_option, true);

    if (empty($value) || (is_numeric($value) && $value < 1)) {
        $value = $screen->get_option($option, 'default');
    }

    return $value;
}

if (!function_exists(__NAMESPACE__ . '\get_email_top_image_url')):

    /**
     * Return the theme logo URL.
     *
     * @return mixed
     */
    function get_email_top_image_url()
    {
        $image = wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full');

        if (!$image) {
            return 'https://via.placeholder.com/350x150';
        }

        return $image[0];
    }

endif;

/**
 * Gets the ids of the magic tags.
 *
 * @return int[]
 */
function get_magic_tag_ids()
{
    return array_values(Plugin::$instance->tag_mapping->get_tag_map());
}

/**
 * Get the attribute of an HTML tag from the tag.
 * NOTE: This currently does NOT support attributes which do not have values in quotes.
 * Todo Support 'magic' attributes
 * @param $tag
 * @return array|false
 */
function get_tag_attributes($tag)
{
    if (!preg_match('/<[^>]+>/', $tag, $matches)) {
        return false;
    }

    $tag = $matches[0];

    preg_match_all("/([a-z\-]+)(=\"([^\"]+)\")/", $tag, $all_atts);

    $attributes = map_deep( $all_atts[ 1 ], 'sanitize_key' );
    $values = $all_atts[ 3 ];
    $attributes = array_combine( $attributes, $values );

    if ( isset_not_empty( $attributes, 'style' ) ){
        $attributes[ 'style' ] = parse_inline_styles( $attributes[ 'style' ] );
    }

    return $attributes;
}

/**
 * Gets the tag name given a tag
 *
 * @param $tag
 * @return bool|mixed
 */
function get_tag_name($tag)
{
    if (!preg_match('/<[^>]+>/', $tag)) {
        return false;
    }
    preg_match('/<([^\W]+)/', $tag, $matches);
    return $matches[1];
}

/**
 * Given a string of inline styles, parse it and return an array of [ attribute => value ]
 *
 * @param $style string
 * @return array
 */
function parse_inline_styles($style)
{
    $bits = explode(';', $style);

    $css = [];

    foreach ($bits as $bit) {

        $rule = explode(':', $bit);
        $attribute = sanitize_key($rule[0]);
        $value = trim($rule[1]);
        $css[$attribute] = $value;
    }

    return $css;
}

/**
 * echo an action input, similar to wp_nonce_field
 *
 * @param string $action
 * @param bool $echo
 * @return bool|string
 */
function action_input( $action='', $echo=true )
{
    $input = html()->input( [ 'value' => $action, 'type' => 'hidden', 'name' => 'action' ] );

    if ( $echo ){
        echo $input;
        return true;
    }

    return $input;
}

/**
 * Return an actionable url
 *
 * @param $action
 * @param array $args
 * @return string
 */
function action_url( $action, $args=[] )
{
    $url_args = [
        'page' => get_request_var( 'page' ),
        'tab' => get_request_var( 'tab' ),
        'action' => $action,
        '_wpnonce' => wp_create_nonce( $action )
    ];

    $url_args = array_filter( array_merge( $url_args, $args ) );

    return add_query_arg( urlencode_deep( $url_args ), admin_url( 'admin.php' ) );
}

global $groundhogg_mobile_validator;

/**
 * Get the default country code of the site.
 *
 * @return string the cc code of the site. US is default
 */
function get_default_country_code()
{
    // Is the CC already set?
    $cc = get_option( 'gh_default_country_code' );

    if ( $cc ){
        return $cc;
    }

    // Get the IP of the logged in user
    if ( is_user_logged_in() && current_user_can( 'manage_options' ) ){

        $cc = Plugin::instance()->utils->location->ip_info( null,'countrycode' );

        if ( $cc ){
            update_option( 'gh_default_country_code', $cc );
            return $cc;
        }

    }

    // Get the IP of the site wherever it's being hosted
    $parse_url = wp_parse_url( site_url(), PHP_URL_HOST );

    if ( $parse_url ){
        $ip = gethostbyname( $parse_url );
        $cc = Plugin::instance()->utils->location->ip_info( $ip,'countrycode' );

        if ( $cc ){
            update_option( 'gh_default_country_code', $cc );
            return $cc;
        }
    }

    return 'US';
}

/**
 * Validate a mobile number
 *
 * @param $number string
 * @param string $country_code the country code of the supposed contact
 * @param bool $with_plus whether to return with the + or not
 * @return bool|string
 */
function validate_mobile_number( $number, $country_code='', $with_plus=false )
{
    global $groundhogg_mobile_validator;

    if ( ! $groundhogg_mobile_validator instanceof Mobile_Validator ){
        $groundhogg_mobile_validator = new Mobile_Validator();
    }

    if ( ! $country_code ){
        $country_code = get_default_country_code();
    }

    $number = $groundhogg_mobile_validator->normalize( $number, $country_code );

    if ( empty( $number ) ){
        return false;
    }

    $number = $number[0];

    // Remove the plus from the string
    if ( $with_plus ){
        $number = str_replace( '+', '', $number );
    }

    return $number;
}

/**
 * Get an error from an uploaded file.
 *
 * @param $file
 * @return bool|WP_Error
 */
function get_upload_wp_error( $file )
{
    if ( ! is_array( $file ) ){
        return new WP_Error( 'not_a_file', 'No file was provided.' );
    }

    // no Error
    if ( absint( $file[ 'error' ] ) === UPLOAD_ERR_OK ){
        return false;
    }

    if ( ! is_uploaded_file( $file[ 'tmp_name' ] ) ){
        return new WP_Error( 'upload_error', 'File is not uploaded.' );
    }

    switch ( $file[ 'error' ] ) {
        case UPLOAD_ERR_INI_SIZE:
            $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
            break;
        case UPLOAD_ERR_FORM_SIZE:
            $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
            break;
        case UPLOAD_ERR_PARTIAL:
            $message = "The uploaded file was only partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            $message = "No file was uploaded";
            break;
        case UPLOAD_ERR_NO_TMP_DIR:
            $message = "Missing a temporary folder";
            break;
        case UPLOAD_ERR_CANT_WRITE:
            $message = "Failed to write file to disk";
            break;
        case UPLOAD_ERR_EXTENSION:
            $message = "File upload stopped by extension";
            break;
        default:
            $message = "Unknown upload error";
            break;
    }

    return new WP_Error( 'upload_error', $message, $file );
}

/**
 * Whether the guided setup is finished or not.
 *
 * @return mixed
 */
function guided_setup_finished()
{
    return (bool) Plugin::$instance->settings->get_option('gh_guided_setup_finished', false );
}

/**
 * Whether the SMS plugin is active. For backwards compatibility
 *
 * @return bool
 */
function is_sms_plugin_active(){
    return defined( 'GROUNDHOGG_SMS_VERSION' );
}

/**
 * Checks if Groundhogg pro features are installed.
 *
 * @return bool
 */
function is_pro_features_active()
{
    return defined( 'GROUNDHOGG_PRO_VERSION' );
}

add_action( 'admin_print_styles', function (){
    ?><style>
        #adminmenu #toplevel_page_groundhogg a[href="admin.php?page=gh_go_pro"] .dashicons{
            font-size: 18px;
            margin-right: 2px;
        }
        #adminmenu #toplevel_page_groundhogg a[href="admin.php?page=gh_go_pro"]{
            color: #DB741A;
            font-weight: bold;
        }
    </style>
    <?php
} );