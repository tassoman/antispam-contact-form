<?php
/*
Plugin Name: Contact Form Generator
Plugin URI: http://www.fullo.net
Description: This contact form accept any data from your site and send as email to you
Version: 0.4
Author: Francesco Fullone
Author URI: http://www.fullo.net/
*/

add_action('admin_menu', 'fcc_config_page');
add_filter('the_content', 'fcc_replace');

if(function_exists('load_plugin_textdomain'))
	load_plugin_textdomain('fcc','wp-content/plugins/fcc');

class fcc_custom_form
{
	var $error = false;
	var $error_msg = '';
	var $error_input = array();
	var $show = true;
	var $form = array();

	var $mailto = '';
	var $title = '';

	/**
	 * controllo che il campo non sia vuoto
	 *
	 * @param mixed $required
	 * @return text
	 */
	function parse_required($required)
	{
		foreach ($required as $k => $v)
		{
			if ($_POST['fcc'][$v] == '')
			{
				$this->error_msg .= __(sprintf("<li>field %s is empty</li>", $v) , 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}

	/**
	 * check if the input is an integer
	 *
	 * @param mixed $integer
	 * @return text
	 */
	function parse_integer($integer)
	{
		foreach ($integer as $k => $v)
		{
			$num = $_POST['fcc'][$v];
			if (!is_int($num))
			{
				$this->error_msg .= __( sprintf("<li>field %s is not numeric</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}

	/**
	 * check if the input is a valid date (dd-mm-aaaa)
	 *
	 * @param mixed $date
	 */
	function parse_date($date)
	{
		foreach ($date as $k => $v)
		{
			if (ereg('^(0?[1-9]|[1-2][0-9]|3[01])[[:blank:]/\.\\-](0?[1-9]|1[0-2])[[:blank:]/\.\\-](19[3-9][0-9]|20[01][0-9])$|^$',$_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf( "<li>field %s is not a valid date (dd-mm-yyyy)</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}

	/**
	 * check if the input is a valid telephone number
	 *
	 * @param mixed $tel
	 */
	function parse_telephone($tel)
	{
		foreach ($tel as $k => $v)
		{
			if (ereg("^[00[1-9]{1,4}|\+[1-9]{1,4}]?[[:blank:]\./-]?(3[2-9][0-9]|0[2-9][0-9]{1,2})[[:blank:]\./-]?[0-9]{6,9}$|^$",$_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf("<li>field %s is not a valid telephone number (+xx-xxxx-xxxxxxxx)</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}

	/**
	 * check if the value is > of the input check
	 *
	 * @param mixed $max
	 */
	function parse_max($max)
	{
		foreach ($max as $num => $v)
		{
			$campo = explode(',',$v);
			foreach ($campo as $id => $valore)
			{
				if ($_POST['fcc'][$valore] > $num)
				{
					$this->error_msg .= __( sprintf("<li>value %d is greater than max allowed value (%d)</li>", $valore, $num), 'fcc');
					$this->error_input[] = $valore;
					$this->error = true;
				}
			}
		}
	}

	/**
	 * check if the value is < of the input check
	 *
	 * @param mixed $min
	 */
	function parse_min($min)
	{
		foreach ($min as $num => $v)
		{
			$campo = explode(',',$v);
			foreach ($campo as $id => $valore)
			{
				if ($_POST['fcc'][$valore] < $num)
				{
					$this->error_msg .= __( sprintf("<li>value %d is lesser than min allowed value (%d)</li>", $valore, $num), 'fcc');
					$this->error_input[] = $valore;
					$this->error = true;
				}
			}
		}
	}

	/**
	 * check if the value is a correct email
	 *
	 * @param mixed $mail
	 */
	function parse_mail($mail)
	{
		foreach ($mail as $k => $v)
		{
			if (!$this->check_email_address($_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf("<li>field %s is not a valid email</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
			}		
		}
	}
	
	/**
	 * parse the email and check if is correct
	 * http://www.ilovejackdaniels.com/php/email-address-validation/
	 *
	 * @param string $email
	 * @return boolean
	 */
	function check_email_address($email) 
	{
 		// First, we check that there's one @ symbol, and that the lengths are right
 		
	 	// Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
 		if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email)) 
 		{		
			return false;
		}
		
		// Split it into sections to make life easier
		$email_array = explode("@", $email);
		$local_array = explode(".", $email_array[0]);
		
		for ($i = 0; $i < sizeof($local_array); $i++) 
		{
			if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i])) 
			{
				return false;
			}
		}
		
		// Check if domain is IP. If not, it should be valid domain name
		if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1])) 
		{ 
			$domain_array = explode(".", $email_array[1]);
			if (sizeof($domain_array) < 2) 
			{
				return false; // Not enough parts to domain
			}

			for ($i = 0; $i < sizeof($domain_array); $i++) 
			{
				if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i])) 
				{
					return false;
				}
			}
		}
		
		return true;
	 }
	
	
	/**
	 * Parse reserved words data, removes \n and \r and validate some input
	 *
	 * @param mixed $form
	 */
	function parse_data($form = array())
	{
		if (count($form) > 0)
		foreach ($form as $key => $value)
		{
			$form[$key] = strip_tags($value);
			if (eregi('email',$key))
			{
				$form[$key] = preg_replace("|[^a-z0-9@.]|i", "", urldecode($value));
				$form[$key] = preg_replace("[\n]",'',$value);
			}
			elseif (eregi('name',$key))
			{
				$form[$key] = preg_replace("|[^a-z0-9 \-.,]|i", "", urldecode($value));
				$form[$key] = preg_replace("[\n]",'', $value);
			}
			else
			{
				$form[$key] = preg_replace("[\n]",'',$value);
			}
		}

		$this->form = $form;
	}

	/**
	 * Generate the email and check for reserved words
	 *
	 * @param mixed $form
	 * @uses sendmail
	 * @uses akismet_sendmail
	 * @todo ricontrollare il funzionamento delle reserved words
	 */
	function compose_mail($form)
	{
		$from = $title = $message = $email = '';

		$this->parse_data($form);


		foreach ($this->form as $key => $value)
		{
			$message .= $key.' = '.$value."\n";
		}

		// reserved words
		if (isset($this->form['email']) != '') 	{ $email .= $this->form['email'].' ';	}
		if (isset($this->form['name']) != '') 	{ $from  .= $this->form['name'].' ';	}
		if (isset($this->form['title']) != '')	{ $title .= $this->form['title'].' ';	}

		if ((get_option('wordpress_api_key') != '') AND (file_exists('/wp-content/plugins/fcc/akismet.php'))) $this->akismet_sendmail($message,$from,$email,$title);
		else $this->sendmail($message,$from,$email,$title);

	}

	/**
	 * send the email
	 *
	 * @param string $message 	message text
	 * @param string $from  	message from
	 * @param string $email 	email to
	 * @param string $title 	title of the email
	 */
	function sendmail($message='',$from='',$email='',$title='')
	{
		$fcconfig = get_option('fcc_settings');

		// get data from parameters
		if ($from != '') $header = 'From: '.$from.' <'.$email.">\r\nX-Mailer: PHP/BeS";

		if ($this->title != '') $title = ' - '.$this->title;
		elseif ($title != '') $title = ' - '.$title;

		// overwrite data if is passed from page
		if ($this->mailto != '') $mailto = $this->mailto;
		else $mailto = $fcconfig['mailto'];

		$message = stripslashes($message);

		if (!mail($mailto,'['.$fcconfig['subject'].$title.']',$message, $header))
			$this->error_message();
		else
			echo stripcslashes($fcconfig['message']);
	}

	/**
	 * check with akismet if the message has spam
	 *
	 * @param string $message 	message text
	 * @param string $from  	message from
	 * @param string $email 	email to
	 * @param string $title 	title of the email
	 *
	 * @uses sendmail
	 */
	function akismet_sendmail($message='',$from='',$email='',$title='')
	{
		require_once '/wp-content/plugins/fcc/akismet.php';

		$akismet = new Akismet( bloginfo('blog_url') , get_option('wordpress_api_key') );
		$akismet->setCommentAuthor($from);
		$akismet->setCommentAuthorEmail($email);
		$akismet->setCommentAuthorURL('');
		$akismet->setCommentContent($message);
		$akismet->setPermalink(bloginfo('blog_url').'/contatti');

		if($akismet->isSpam())
		{
			$this->error_message('spam');
		}
		else
		{
			$this->sendmail($message,$from,$email,$title);
		}

	}

	/**
	 * generate error output
	 *
	 * @param string $type can be 'mail' or 'spam'
	 */
	function error_message($type = 'mail')
	{
		$fcconfig = get_option('fcc_settings');

		if ($type == 'spam')
			echo $fcconfig['message_spam'];
		elseif ($type == 'mail')
			echo $fcconfig['message_error'];
	}

}



/**********************************************
* HERE START THE WP FRONTEND CONFIG FUNCTIONS *
**********************************************/

/**
 * Parse the content to search the fcc placer
 *
 * @param string $content
 * @return string $content
 */
function fcc_replace($content) {
	return preg_replace_callback("/<!--fcc:(.*)-->/", "fcc_loader", $content);
}

/**
 * The main plugin loader
 *
 * @param string $data is the old content of the post
 * @return string $output is the new content
 */
function fcc_loader($data)
{
	$output = '';

	$param = split(':',$data[1]);

	if (($data[1]=='') OR (!file_exists(ABSPATH.'/wp-content/plugins/fcc/forms/'.$param[0].'.php')))
	{
		// creare il file di default
		$output .= "missed form file<br/>";
		$output .= __('form template is missing','fcc') . "<br/>";
		$output .=  ABSPATH.'/wp-content/plugins/fcc/forms/'.$data[1].'.php';
	}
	else
	{
		// format filename:title:mailto

		$custom_form = new fcc_custom_form();

		if ((isset($param[1])) and ($param[1] != '')) {$custom_form->title = $param[1]; }
		if ((isset($param[2])) and ($param[2] != '')) {$custom_form->mailto = $param[2]; }

		// parse the POST data and start the input validation
		// move all in a custom_form init method 
		if (count($_POST) > 0)
		{
			if (isset($_POST['check']['required']) != '') $custom_form->parse_required(explode(',',$_POST['check']['required']));
			if (isset($_POST['check']['integer']) != '') $custom_form->parse_integer(explode(',',$_POST['check']['integer']));
			if (isset($_POST['check']['date']) != '') $custom_form->parse_date(explode(',',$_POST['check']['date']));
			if (isset($_POST['check']['telephone']) != '') $custom_form->parse_telephone(explode(',',$_POST['check']['telephone']));
			if (isset($_POST['check']['min'])) $custom_form->parse_min($_POST['check']['min']);
			if (isset($_POST['check']['max'])) $custom_form->parse_max($_POST['check']['max']);
			if (isset($_POST['check']['email'])) $custom_form->parse_mail(explode(',',$_POST['check']['email']));

			// this is a simple spambot blocker
			if (isset($_POST['email']) && ($_POST['email']!='')) $custom_form->error = true;
			if (isset($_POST['mail']) && ($_POST['mail']!='')) $custom_form->error = true;
			if (isset($_POST['name']) && ($_POST['name']!='')) $custom_form->error = true;
			if (isset($_POST['subject']) && ($_POST['subject']!='')) $custom_form->error = true;
			
			$custom_form->show = false;

			if (!$custom_form->error) $custom_form->compose_mail($_POST['fcc']);
			else $custom_form->parse_data($_POST['fcc']);
		}

		// error output and generate javascript for effects
		if (($custom_form->error) or ($custom_form->show))
		{
			$js = '';

			if ($custom_form->error_msg != '')
			{
				// load of the js
				$js .= "<script src='".get_bloginfo('url')."/wp-content/plugins/fcc/mootools.js' type='text/javascript' ></script>
					<script type='text/javascript'>

				";

				foreach ($custom_form->error_input as $k => $v)
				{
					$js .= "
						var e_$v = $('fcc[$v]');
						new Fx.Color(e_$v, 'background-color').custom('#ffffff', '#ff0000');
					";
				}

				foreach ($custom_form->form as $k1 => $v1)
				{
					$js .= "
						var e_$k1 = $('fcc[$k1]');
						e_$k1.value = '$v1';
					";
				}

				$js .= "
	  					</script>";

				$output .=  sprintf("	<div class='fcc_error'>
							<h2>%s</h2>
							<ul>%s</ul>
						</div>" , __('There are problems with the form', $custom_form->error_msg), 'fcc');
			}

			// this include the form file in the post
			$output .=  "<div>";
			$output .= file_get_contents(ABSPATH.'/wp-content/plugins/fcc/forms/'.$param[0].'.php',false);
			$output .=  "<br/></div>$js";

			$output = str_ireplace('</form>','<input type="hidden" name="subject" value="" /><input type="hidden" name="name" value="" /><input type="hidden" name="email" value="" /><input type="hidden" name="mail" value="" /></form>',$output);
			
			return $output;
		}
	}
	//print_r($data);
	//print_r($_POST);
}


/*********************************************
* HERE START THE WP BACKEND CONFIG FUNCTIONS *
**********************************************/


if ( ! function_exists('wp_nonce_field') )
{
	function fcc_nonce_field($action = -1)
	{
		return;
	}
	$fcc_nonce = -1;
}
else
{
	function fcc_nonce_field($action = -1)
	{
		return wp_nonce_field($action);
	}
	$fcc_nonce = 'fcg-save-option';
}

function fcc_config_page()
{
	global $wpdb;
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('Contact Form Generator','fcc'), __('Contact Form Generator','fcc'), 'manage_options', 'fcc-conf', 'fcc_conf');
}

function fcc_conf()
{
	global $fcc_nonce;

	if ( isset($_POST['submit']) )
	{
		if ( function_exists('current_user_can') && !current_user_can('manage_options') )
			die(__('Cheatin&#8217; uh?'));

		check_admin_referer($fcc_nonce);

		$data = new fcc_custom_form();
		$data->parse_data($_POST);

		update_option('fcc_settings', array(
											'mailto' => $data->form['email'],
											'message' => $data->form['msg'],
											'message_spam' => $data->form['spam'],
											'message_error' => $data->form['error'],
											'subject' => $data->form['subject'],
											)
						);
	}

?>

<div class="wrap">
	<h2><?php _e('Contact Form Generator','fcc'); ?></h2>
	<div class="">
		<p><?php _e('Please add &lt;!--fcc:<em>filename</em>--&gt; in the content of the post/page you want show the form.', 'fcc');?></p>
		<p><?php _e('If you want to change default subject or default address you must write as following: &lt;!--fcc:<em>filename:new title:email</em>--&gt;','fcc');?></p>
		<ul>
			<li><?php _e('To send mails to me@mail.com: &lt;!--fcc:<em>filename::me@mail.com</em>--&gt;','fcc');?></li>
			<li><?php _e('To send mails to me@mail.com with subject "hello world": &lt;!--fcc:<em>filename:hello world:me@mail.com</em>--&gt;', 'fcc');?></li>
			<li><?php _e('To send mails with subject "hello world": &lt;!--fcc:<em>filename:hello world</em>--&gt;','fcc');?></li>
		</ul>
		</p>
		<h3><?php _e('Form lists','fcc');?></h3>
		<?php
			$fcconfig = get_option('fcc_settings');
			if ($handle = opendir(ABSPATH.'/wp-content/plugins/fcc/forms/'))
			{
				echo "<ul>";
   				while (false !== ($file = readdir($handle))) {
       				if ($file != "." && $file != "..")
       				{
           				echo sprintf("<li>
           							&lt;!--fcc:%s--&gt; --
           							<a href='%s/wp-admin/templates.php?file=wp-content/plugins/fcc/forms/%s'>[%s]</a>
           					  </li>", str_replace('.php','',$file), get_bloginfo('url'), $file, __('edit') );
       				}
   				}
   				closedir($handle);
   				echo "</ul>";
			}
		?>
	</div>
	<hr/>

	<form action="" method="post" id="fcc-conf" style="width: 400px; ">

		<?php fcc_nonce_field($fcc_nonce) ?>
		<p>
			<h3><label for="email"><?php _e('Email','fcc'); ?></label></h3>
			<input id="email" name="email" type="text" size="24" maxlength="128" value="<?php echo $fcconfig['mailto']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;" />
		</p>
		<p>
			<h3><label for="subject"><?php _e('Subject','fcc');?></label></h3>
			<input id="subject" name="subject" type="text" size="24" maxlength="64" value="<?php echo $fcconfig['subject']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;" />
		</p>
		<p>
			<h3><label for="msg"><?php echo _e('Default message','fcc');?></label></h3>
			<textarea id="msg" name="msg" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message']); ?></textarea>
		</p>
		<p>
			<h3><label for="msg"><?php echo _e('Error message','fcc');?></label></h3>
			<textarea id="error" name="error" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message_error']); ?></textarea>
		</p>
		<?php if (get_option('wordpress_api_key') == '') { ?>
		<p><h3 style="color:red"><?php _e('AKISMET IS DEACTIVATED, SO THIS MESSAGE CAN`T BE USED','fcc');?></h3></p>
		<?php } ?>
		<p>
			<h3><label for="msg"><?php echo _e('Spam message','fcc');?></label></h3>
			<textarea id="spam" name="spam" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message_spam']); ?></textarea>
		</p>

		<p>
			<input type="submit" name="submit" value="<?php _e('update');?>" />
		</p>

	</form>
</div>
<?php
}

?>