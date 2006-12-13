<?php
/*
Plugin Name: Contact Form Generator
Plugin URI: http://www.fullo.net
Description: This contact form accept any data from your site and send as email to you
Version: 0.3
Author: Francesco Fullone
Author URI: http://www.fullo.net/
*/

add_action('admin_menu', 'fcc_config_page');
add_filter('the_content', 'fcc_replace');

class fcc_custom_form
{
	var $error = false;
	var $error_msg = '';
	var $error_input = array();
	var $show = true;
	var $form = array();
	
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
				$this->error_msg .= "<li>il campo $v è vuoto</li>";
				$this->error_input[] = $v;
				$this->error = true;
			}
		}	
	}

	/**
	 * controllo che il campo sia maggiore di 0
	 *
	 * @param mixed $integer
	 * @return text
	 */
	function parse_integer($integer)
	{
		foreach ($integer as $k => $v)
		{
			if (!is_int($_POST['fcc'][$v]))
			{
				$this->error_msg .= "<li>il campo $v non è un numero</li>";
				$this->error_input[] = $v;
				$this->error = true;
			}
		}	
	}
	
	function parse_date($date)
	{
		foreach ($date as $k => $v)
		{
			if (ereg('^(0?[1-9]|[1-2][0-9]|3[01])[[:blank:]/\.\\-](0?[1-9]|1[0-2])[[:blank:]/\.\\-](19[3-9][0-9]|20[01][0-9])$|^$',$_POST['fcc'][$v]))
			{
				$this->error_msg .= "<li>il campo $v non è una data</li>";
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}
	
	
	function parse_telephone($tel)
	{
		foreach ($tel as $k => $v)
		{
			if (ereg("^[00[1-9]{1,4}|\+[1-9]{1,4}]?[[:blank:]\./-]?(3[2-9][0-9]|0[2-9][0-9]{1,2})[[:blank:]\./-]?[0-9]{6,9}$|^$",$_POST['fcc'][$v]))
			{
				$this->error_msg .= "<li>il campo $v non è un numero di telefono valido</li>";
				$this->error_input[] = $v;
				$this->error = true;
			}
		}
	}
	
	function parse_max($max)
	{
		foreach ($max as $num => $v)
		{
			$campo = explode(',',$v);
			foreach ($campo as $id => $valore)
			{
				if ($_POST['fcc'][$valore] > $num)
				{
					$this->error_msg .= "<li>il campo $valore supera il valore consentito ($num)</li>";
					$this->error_input[] = $valore;
					$this->error = true;
				}
			}
		}
	}
	
	function parse_min($min)
	{
		foreach ($min as $num => $v)
		{
			$campo = explode(',',$v);
			foreach ($campo as $id => $valore)
			{
				if ($_POST['fcc'][$valore] < $num)
				{
					$this->error_msg .= "<li>il campo $valore è inferiore al valore consentito ($num)</li>";
					$this->error_input[] = $valore;
					$this->error = true;
				}
			}
		}
	}	
	
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

	function sendmail($message='',$from='',$email='',$title='')
	{
		$fcconfig = get_option('fcc_settings');
		
		if ($from != '') $header = 'From: '.$from.' <'.$email.">\r\nX-Mailer: PHP/BeS";
		if ($title != '') $title = ' - '.$title;
		
		$message = stripslashes($message);
		
		if (!mail(get_option('fcc_to_mail'),'['.$fcconfig['subject'].$title.']',$message, $header))
			$this->error_message();
		else 
			echo stripcslashes($fcconfig['message']); 
	}	
	
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

function fcc_replace($content) {
	return preg_replace_callback("/<!--fcc:(.*)-->/", "fcc_loader", $content);
}

function fcc_loader($data)
{
	if (($data[1]=='') OR (!file_exists(ABSPATH.'/wp-content/plugins/fcc/forms/'.$data[1].'.php')))
	{
		// creare il file di default
		echo "missed form file<br/>";
		echo ABSPATH.'/wp-content/plugins/fcc/forms/'.$data[1].'.php';
	}

	
	$custom_form = new fcc_custom_form();	
	if (count($_POST) > 0)
	{
		if (isset($_POST['check']['required']) != '') $custom_form->parse_required(explode(',',$_POST['check']['required']));
		if (isset($_POST['check']['integer']) != '') $custom_form->parse_integer(explode(',',$_POST['check']['integer']));
		if (isset($_POST['check']['date']) != '') $custom_form->parse_date(explode(',',$_POST['check']['date']));
		if (isset($_POST['check']['telephone']) != '') $custom_form->parse_telephone(explode(',',$_POST['check']['telephone']));
		if (isset($_POST['check']['min'])) $custom_form->parse_min($_POST['check']['min']);
		if (isset($_POST['check']['max'])) $custom_form->parse_max($_POST['check']['max']);
		
		$custom_form->show = false;
					
		if (!$custom_form->error) $custom_form->compose_mail($_POST['fcc']);
		else $custom_form->parse_data($_POST['fcc']);
		
	}
	
	if (($custom_form->error) or ($custom_form->show))
	{
		if ($custom_form->error_msg != '')
		{
			$js = '';
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
			
			echo "	<div class='fcc_error'>
						<h2>Sono presenti errori nella form</h2>
						<ul>$custom_form->error_msg</ul>
					</div>";
		}
		
		echo "<div>";
		include_once ABSPATH.'/wp-content/plugins/fcc/forms/'.$data[1].'.php';
		echo "<br/></div>$js";
	}	
	
	//print_r($data);
	//print_r($_POST);
}


/*********************************************
* HERE START THE WP BACKEND CONFIG FUNCTIONS *
**********************************************/


if ( ! function_exists('wp_nonce_field') ) {
	function fcc_nonce_field($action = -1) {
		return;	
	}
	$fcc_nonce = -1;
} else {
	function fcc_nonce_field($action = -1) {
		return wp_nonce_field($action);
	}
	$fcc_nonce = 'fcg-save-option';
}

function fcc_config_page() {
	global $wpdb;
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('Contact Form Generator'), __('Contact Form Generator'), 'manage_options', 'fcc-conf', 'fcc_conf');
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
	<h2><?php _e('Contact Form Generator'); ?></h2>
	<div class="">
		<p>Add &lt;!--fcc:<em>filename</em>--&gt; in the post where you want to add the form</p>
		<h3>Form lists</h3>
		<?php 
			$fcconfig = get_option('fcc_settings');
			if ($handle = opendir(ABSPATH.'/wp-content/plugins/fcc/forms/')) 
			{
				echo "<ul>";
   				while (false !== ($file = readdir($handle))) {
       				if ($file != "." && $file != "..") 
       				{
           				echo "<li>&lt;!--fcc:".str_replace('.php','',$file)."--&gt;</li>";
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
			<h3><label for="email"><?php _e('Email'); ?></label></h3>
			<input id="email" name="email" type="text" size="24" maxlength="128" value="<?php echo $fcconfig['mailto']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;" />
		</p>
		<p>
			<h3>Subject</h3>
			<input id="subject" name="subject" type="text" size="24" maxlength="64" value="<?php echo $fcconfig['subject']; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;" />
		</p>
		<p>
			<h3>messaggio default</h3>
			<textarea id="msg" name="msg" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message']); ?></textarea>
		</p>
		<p>
			<h3>messaggio di errore</h3>
			<textarea id="error" name="error" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message_error']); ?></textarea>
		</p>
		<?php if (get_option('wordpress_api_key') != '') { ?>
		<p><h3 style="color:red">AKISMET E' DISATTIVATO IL MESSAGGIO NON VERRA' USATO!</h3></p>
		<?php } ?>		
		<p>
			<h3>messaggio di spam</h3>
			<textarea id="spam" name="spam" style="font-family: 'Courier New', Courier, mono; font-size: 0.9em;"><?php echo stripcslashes($fcconfig['message_spam']); ?></textarea>
		</p>

		<p>
			<input type="submit" name="submit" value="aggiorna i dati" />
		</p>
	
	</form>
</div>
<?php
}

?>