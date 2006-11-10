<?php
/*
Plugin Name: WP-ContactForm
Plugin URI: http://ryanduff.net/projects/wp-contactform/
Description: WP Contact Form is a drop in form for users to contact you. It can be implemented on a page or a post. It currently works with WordPress 2.0+
Author: Ryan Duff
Author URI: http://ryanduff.net
Version: 1.4.3a

Antispam securty improvements were added by Tassoman (tassoman@gmail.com),
Please visit my blog at: http://blog.tassoman.com

This software is released under the GNU General Public License.


*/

load_plugin_textdomain('wpcf',$path = 'wp-content/plugins/wp-contact-form');

/* Declare strings that change depending on input. This also resets them so errors clear on resubmission. */
$wpcf_strings = array(
    'name' => '<div class="contactright"><input type="text" name="wpcf_your_name" id="wpcf_your_name" size="30" maxlength="50" value="' . $_POST['wpcf_your_name'] . '" /> (' . __('required', 'wpcf') . ')</div>',
    'email' => '<div class="contactright"><input type="text" name="wpcf_email" id="wpcf_email" size="30" maxlength="50" value="' . $_POST['wpcf_email'] . '" /> (' . __('required', 'wpcf') . ')</div>',
    'msg' => '<div class="contactright"><textarea name="wpcf_msg" id="wpcf_msg" cols="35" rows="8" >' . $_POST['wpcf_msg'] . '</textarea></div>',
    'error' => '');

/*
This shows the quicktag on the write pages
Based off Buttonsnap Template
http://redalt.com/downloads
*/
if(get_option('wpcf_show_quicktag') == true) {
    include('buttonsnap.php');

    add_action('init', 'wpcf_button_init');
    add_action('marker_css', 'wpcf_marker_css');

    function wpcf_button_init() {
        $wpcf_button_url = buttonsnap_dirname(__FILE__) . '/wpcf_button.png';

        buttonsnap_textbutton($wpcf_button_url, __('Insert Contact Form', 'wpcf'), '<!--contact form-->');
        buttonsnap_register_marker('contact form', 'wpcf_marker');
    }

    function wpcf_marker_css() {
        $wpcf_marker_url = buttonsnap_dirname(__FILE__) . '/wpcf_marker.gif';
        echo "
            .wpcf_marker {
                    display: block;
                    height: 15px;
                    width: 155px
                    margin-top: 5px;
                    background-image: url({$wpcf_marker_url});
                    background-repeat: no-repeat;
                    background-position: center;
            }
        ";
    }
}

function wpcf_is_malicious($input) {
    $is_malicious = false;
    $bad_inputs = array("\r", "\n", "mime-version", "content-type", "cc:", "to:");
    foreach($bad_inputs as $bad_input) {
        if(strpos(strtolower($input), strtolower($bad_input)) !== false) {
            $is_malicious = true; break;
        }
    }
    return $is_malicious;
}

/* This function checks for errors on input and changes $wpcf_strings if there are any errors. Shortcircuits if there has not been a submission */
function wpcf_check_input()
{
    if(!(isset($_POST['wpcf_stage']))) {return false;} // Shortcircuit.

    $_POST['wpcf_your_name'] = stripslashes(trim($_POST['wpcf_your_name']));
    $_POST['wpcf_email'] = stripslashes(trim($_POST['wpcf_email']));
    $_POST['wpcf_website'] = stripslashes(trim($_POST['wpcf_website']));
    $_POST['wpcf_msg'] = stripslashes(trim($_POST['wpcf_msg']));

    global $wpcf_strings;
    $ok = true;

    if(empty($_POST['wpcf_your_name']))
    {
        $ok = false; $reason = 'empty';
        $wpcf_strings['name'] = '<div class="contactright"><input type="text" name="wpcf_your_name" id="wpcf_your_name" size="30" maxlength="50" value="' . $_POST['wpcf_your_name'] . '" class="contacterror" /> (' . __('required', 'wpcf') . ')</div>';
    }

    if(!is_email($_POST['wpcf_email']))
    {
        $ok = false; $reason = 'empty';
        $wpcf_strings['email'] = '<div class="contactright"><input type="text" name="wpcf_email" id="wpcf_email" size="30" maxlength="50" value="' . $_POST['wpcf_email'] . '" class="contacterror" /> (' . __('required', 'wpcf') . ')</div>';
    }
    else
    {
        if(function_exists('checkdnsrr')) {
            if(!checkdnsrr(strtolower(substr($_POST['wpcf_email'],strpos($_POST['wpcf_email'],'@')+1))))
            {
                $ok = false; $reason = 'notemail';
                $wpcf_strings['email'] = '<div class="contactright"><input type="text" name="wpcf_email" id="wpcf_email" size="30" maxlength="50" value="' . $_POST['wpcf_email'] . '" class="contacterror" /> (' . __('not exists', 'wpcf') . ')</div>';
            }
        }
    }

    if(empty($_POST['wpcf_msg']))
    {
        $ok = false; $reason = 'empty';
        $wpcf_strings['msg'] = '<div class="contactright"><textarea name="wpcf_msg" id="wpcf_message" cols="35" rows="8" class="contacterror">' . $_POST['wpcf_msg'] . '</textarea></div>';
    }

    if(wpcf_is_malicious($_POST['wpcf_your_name']) || wpcf_is_malicious($_POST['wpcf_email'])) {
        $ok = false; $reason = 'malicious';
    }
    if($ok == true)
    {
        return true;
    }
    else {
        switch ($reason) {
            case 'malicious':
                $wpcf_strings['error'] = '<div style="font-weight: bold;">You can not use any of the following in the Name or Email fields: a linebreak, or the phrases mime-version, content-type, cc: or to:.</div>';
            break;

            case 'empty':
                $wpcf_strings['error'] = '<div style="font-weight: bold;">' . stripslashes(get_option('wpcf_error_msg')) . '</div>';
            break;

            case 'notemail':
                $wpcf_strings['error'] = '<div style="font-weight: bold;">'. __("Your email address isn't correct") .'</div>';
            break;

            default:
        }
        return false;
    }
}

/*Wrapper function which calls the form.*/
function wpcf_callback( $content )
{
    global $wpcf_strings;

    /* Run the input check. */

        if(! preg_match('|<!--contact form-->|', $content)) {
        return $content;
        }

    if(wpcf_check_input()) // If the input check returns true (ie. there has been a submission & input is ok)
    {
            $recipient = get_option('wpcf_email');
            $subject = get_option('wpcf_subject');
            $success_msg = get_option('wpcf_success_msg');
            $success_msg = stripslashes($success_msg);

            $name = $_POST['wpcf_your_name'];
            $email = $_POST['wpcf_email'];
            $website = $_POST['wpcf_website'];
            $msg = $_POST['wpcf_msg'];

              $headers = "MIME-Version: 1.0\n";
            $headers .= "From: $name <$email>\n";
            $headers .= "Content-Type: text/plain; charset=\"" . get_settings('blog_charset') . "\"\n";
            $fullmsg = "$name wrote:\n";
            $fullmsg .= wordwrap($msg, 80, "\n") . "\n\n";
            $fullmsg .= "Website: " . $website . "\n";
            $fullmsg .= "IP: " . getip();

            if($yourWordPressAPIKey = get_option('wordpress_api_key')) {
                require_once(dirname(__FILE__) . '/../akismet/Akismet.class.php');
                $akismet = new Akismet(get_option('siteurl'), $yourWordPressAPIKey);
                $akismet->setAuthor($_POST['wpcf_your_name']);
                $akismet->setAuthorEmail($_POST['wpcf_email']);
                $akismet->setAuthorURL($_POST['wpcf_website']);
                $akismet->setContent($_POST['wpcf_msg']);
                $akismet->setPermalink(get_permalink());
                if($akismet->isSpam()) {
                    $ok = false; $reason = 'spam';
                    $akismet->submitSpam();
                    $results = '<div style="font-weight: bold;">We don\'t like spam messages. Your message was marked as spam.</div>';
                }
                else {
                    $akismet->submitHam();
                    mail($recipient, $subject, $fullmsg, $headers);
                    $results = '<div style="font-weight: bold;">' . $success_msg . '</div>';
                }
            }
            else {
                mail($recipient, $subject, $fullmsg, $headers);
                $results = '<div style="font-weight: bold;">' . $success_msg . '</div>';
            }
            echo $results;
    }
    else // Else show the form. If there are errors the strings will have updated during running the inputcheck.
    {
        echo $yourWordPressAPIKey;
        $form = '<div class="contactform">
        ' . $wpcf_strings['error'] . '
            <form action="' . get_permalink() . '" method="post">
                <div class="contactleft"><label for="wpcf_your_name">' . __('Your Name: ', 'wpcf') . '</label></div>' . $wpcf_strings['name']  . '
                <div class="contactleft"><label for="wpcf_email">' . __('Your Email:', 'wpcf') . '</label></div>' . $wpcf_strings['email'] . '
                <div class="contactleft"><label for="wpcf_website">' . __('Your Website:', 'wpcf') . '</label></div><div class="contactright"><input type="text" name="wpcf_website" id="wpcf_website" size="30" maxlength="100" value="' . $_POST['wpcf_website'] . '" /></div>
                <div class="contactleft"><label for="wpcf_msg">' . __('Your Message: ', 'wpcf') . '</label></div>' . $wpcf_strings['msg'] . '
                <div class="contactright"><input type="submit" name="Submit" value="' . __('Submit', 'wpcf') . '" id="contactsubmit" /><input type="hidden" name="wpcf_stage" value="process" /></div>
            </form>
        </div>
        <div style="clear:both; height:1px;">&nbsp;</div>';
        return str_replace('<!--contact form-->', $form, $content);
    }
}


/*Can't use WP's function here, so lets use our own*/
function getip()
{
    if (isset($_SERVER))
    {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
        {
              $ip_addr = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        elseif (isset($_SERVER["HTTP_CLIENT_IP"]))
        {
              $ip_addr = $_SERVER["HTTP_CLIENT_IP"];
        }
        else
        {
            $ip_addr = $_SERVER["REMOTE_ADDR"];
        }
    }
    else
    {
        if ( getenv( 'HTTP_X_FORWARDED_FOR' ) )
        {
              $ip_addr = getenv( 'HTTP_X_FORWARDED_FOR' );
        }
        elseif ( getenv( 'HTTP_CLIENT_IP' ) )
        {
              $ip_addr = getenv( 'HTTP_CLIENT_IP' );
        }
        else
        {
              $ip_addr = getenv( 'REMOTE_ADDR' );
        }
    }
return $ip_addr;
}


/*CSS Styling*/
function wpcf_css()
    {
    ?>
<style type="text/css" media="screen">

/* Begin Contact Form CSS */
.contactform {
    position: static;
    overflow: hidden;
}

.contactleft {
    width: 25%;
    text-align: right;
    clear: both;
    float: left;
    display: inline;
    padding: 4px;
    margin: 5px 0;
}

.contactright {
    width: 70%;
    text-align: left;
    float: right;
    display: inline;
    padding: 4px;
    margin: 5px 0;
}

.contacterror {
    border: 1px solid #ff0000;
}

.contactsubmit {
}
/* End Contact Form CSS */

    </style>

<?php

    }

function wpcf_add_options_page()
    {
        add_options_page('Contact Form Options', 'Contact Form', 'manage_options', 'wp-contact-form/options-contactform.php');
    }

/* Action calls for all functions */

//if(get_option('wpcf_show_quicktag') == true) {add_action('admin_footer', 'wpcf_add_quicktag');}

add_action('admin_head', 'wpcf_add_options_page');
add_filter('wp_head', 'wpcf_css');
add_filter('the_content', 'wpcf_callback', 7);

?>
