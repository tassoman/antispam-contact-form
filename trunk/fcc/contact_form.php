<?php
/*
Plugin Name: Antispam contact form
Plugin URI: http://code.google.com/p/antispam-contact-form
Description: This contact form accept any data from your site and send as email to you in a customized way. It uses easy templates.
Version: 0.4.5
Author: Francesco Fullone and Tassoman
Author URI: http://code.google.com/p/antispam-contact-form
*/

define(FCC_PATH, ABSPATH.'/wp-content/plugins/fcc/');
define(FCC_FORM_PATH,FCC_PATH.'forms/');
define(FCC_SUCCESS_PATH,FCC_PATH.'success/');
define(FCC_ERROR_PATH,FCC_PATH.'errors/');
define(FCC_EXEC_PATH,FCC_PATH.'exec/');

add_action('admin_menu', 'fcc_config_page');
add_filter('the_content', 'fcc_replace');

if(function_exists('load_plugin_textdomain'))
	load_plugin_textdomain('fcc','wp-content/plugins/fcc');



/**********************************************
* HERE START THE WP FRONTEND CONFIG FUNCTIONS *
**********************************************/

function fcc_replace($content) {
	return preg_replace_callback("/<!--fcc:(.*)-->/", "fcc_loader", $content);
}

function fcc_loader($data)
{
	$output = '';

	$param = split(':',$data[1]);

	if (($data[1]=='') OR (!file_exists(FCC_FORM_PATH.$param[0].'.php')))
	{
		// creare il file di default
		$output .= "missed form file<br/>";
		$output .= __('form template is missing','fcc') . "<br/>";
		$output .=  FCC_FORM_PATH.$data[1].'.php';
	}
	else
	{
		require FCC_PATH.'custom_form.php';
		
		$custom_form = new fcc_custom_form();
		$custom_form->init($param);
			
		// parse the POST data and start the input validation
		if (count($_POST) > 0)
		{			
			if ($custom_form->honeypot)
				$custom_form->checkHoneypot();
			
			// invoco tutti i parser a mia disposizione
			foreach ($_POST['check'] as $parser => $value)
			{
				if (is_array($value))
					$custom_form->execParser($parser,$value);
				else
					$custom_form->execParser($parser,explode(',',$value));
			}
			
			$custom_form->show = false;
			$custom_form->setForm($_POST['fcc']);
			$custom_form->setExecute();
			
			if (!$custom_form->error)
				return $custom_form->compose_mail();
			
		}

		// error output and generate javascript for effects
		if (($custom_form->error) or ($custom_form->show))
		{
			$js = '';

			if ($custom_form->error_msg != '')
			{
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
						</div>" , __('There are problems with the form', 'fcc'),  $custom_form->error_msg);
			}

			$output .=  "<div>";
			//include_once FCC_FORM_PATH.$data[1].'.php';
			$output .=  $custom_form->getTemplateForm();
			$output .=  "<br/></div>$js";			
		}
	}
	
	return $output;
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
		require_once(FCC_PATH . 'custom_form.php');
		$data = new fcc_custom_form();
		$data->setForm($_POST);

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
			if ($handle = opendir(FCC_FORM_PATH))
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
		<p><h3 style="color:red"><?php _e('AKISMET IS DEACTIVATED, SO THE FOLLOWING MESSAGE CAN`T BE USED','fcc');?></h3></p>
		<?php } ?>
		<p>
			<h3><label for="msg"><?php echo _e('Spam message','fcc');?></label></h3>
			<textarea id="spam" name="spam" style="font-family: 'Courier New', Courier, monospace; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message_spam']); ?></textarea>
		</p>

		<p>
			<input type="submit" name="submit" value="<?php _e('Update', 'fcc');?>" />
		</p>
	</form>
</div>
<?php
}

?>