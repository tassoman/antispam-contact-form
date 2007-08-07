<?php
/*
Plugin Name: Contact Form Generator
Plugin URI: http://www.fullo.net
Description: This contact form accept any data from your site and send as email to you
Version: 0.4.3
Author: Francesco Fullone
Author URI: http://www.fullo.net/
*/

define(FCC_PATH, ABSPATH.'/wp-content/plugins/fcc/');
define(FCC_FORM_PATH,FCC_PATH.'/forms/');
define(FCC_SUCCESS_PATH,FCC_PATH.'success/');
define(FCC_ERROR_PATH,FCC_PATH.'errors/');
define(FCC_EXEC_PATH,FCC_PATH.'exec/');

add_action('admin_menu', 'fcc_config_page');
add_filter('the_content', 'fcc_replace');

if(function_exists('load_plugin_textdomain'))
	load_plugin_textdomain('fcc','wp-content/plugins/fcc');

/**
 * @TODO add the honeypot again!
 *
 */
class fcc_custom_form
{
	var $error = false;
	var $error_msg = '';
	var $error_input = array();
	var $show = true;
	var $form = array();
	var $page = '';

	var $mailto = '';
	var $title = '';
	
	var $template = '';
	var $templateForm = '';
	
	var $config = array();
	var $execute = '';
	var $parser = '';
	var $executeClass = '';
	
	var $honeypot = true;
	var $honeypotinput = array('email','e-mail','mail','name','surname');
	
	/**
	 * The parser method call the fcc_ParseForm class
	 * that will check if the data is correct
	 *
	 * @param string $type the type of the parser
	 * @param mixed array $data the array with the form input field names
	 * 
	 * @TODO rimuovere i metodi di parsing interni (min, max)
	 * @TODO evitare di ricreare ogni volta l'oggetto parser
	 */
	function execParser($type,$data = array())
	{
		include_once(FCC_PATH.'parse_form.php');
		
		// add the parser function
		if ((is_object($this->execute)) and (method_exists($this->executeClass,"preParser")))
			$this->execute->preParser(); 
		
		if (!is_object($this->parser))
			$this->parser = new fcc_parseForm;
		
		if (method_exists("fcc_parseForm",$type))
		{
			$this->parser->$type($data);			
			
			$this->error =  $this->parser->error;
			$this->error_msg = $this->parser->error_msg;
			$this->error_input = $this->parser->error_input;
		}
		else
		{			
			die("cannot load fcc_parseForm::$type method");
		}
		
		if ((is_object($this->execute)) and (method_exists($this->executeClass,"postParser")))
			$this->execute->postParser(); 

	}
	

	/**
	 * Parse reserved words data, removes \n and \r and validate some input
	 *
	 * @param mixed $form
	 */
	function setForm($form = array())
	{
		if (count($form) > 0)
		foreach ($form as $key => $value)
		{
			$form[$key] = attribute_escape($value);
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
	function compose_mail()
	{
		$from = $title = $message = $email = '';

		$message = __('Message from page: ').$this->page."\n";
		foreach ($this->form as $key => $value)
		{
			$message .= $key.' = '.$value."\n";
		}

		// reserved words
		if (isset($this->form['email']) != '') 	{ $email .= $this->form['email'].' ';	}
		if (isset($this->form['name']) != '') 	{ $from  .= $this->form['name'].' ';	}
		if (isset($this->form['title']) != '')	{ $title .= $this->form['title'].' ';	}

		if ((get_option('wordpress_api_key') != '') AND (file_exists('/wp-content/plugins/fcc/akismet.php'))) return $this->akismet_sendmail($message,$from,$email,$title);
		else return $this->sendmail($message,$from,$email,$title);

	}

	/**
	 * send the email
	 *
	 * @param string $message 	message text
	 * @param string $from  	message from
	 * @param string $email 	email to
	 * @param string $title 	title of the email
	 * 
	 * @return string 
	 */
	function sendmail($message='',$from='',$email='',$title='')
	{
		// get data from parameters
		$header = 'From: '.get_bloginfo('name').' <'.$this->config['mailto'].">\r\nX-Mailer: PHP/BeS";

		if ($this->title != '') $title = $this->title;
		
		// overwrite data if is passed from page
		if ($this->mailto != '') $mailto = $this->mailto;
		else $mailto = $this->config['mailto'];

		$message = stripslashes($message);

		if ((is_object($this->execute)) and (method_exists($this->executeClass,"setupMailer")))
			$this->execute->setupMailer();
		
		if (!wp_mail($mailto,'['.$title.' '.$this->config['subject'].']',$message, $header))
			return $this->error_message();
		else
		{
			return $this->success_message();
		}
			
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
		$akismet->setPermalink($this->page);

		if($akismet->isSpam())
		{
			return $this->error_message('spam');
		}
		else
		{
			return $this->sendmail($message,$from,$email,$title);
		}

	}

	/**
	 * return the success message for a form, if the success
	 * file exist the method parse it and return it with the new
	 * values
	 * 
	 * the values inside the success file must be saved as %%INPUT_ID%%
	 *
	 * @return string $success_message
	 */
	function success_message()
	{

		if (is_file(FCC_SUCCESS_PATH.$this->template.'.php'))
		{
			$success = file_get_contents(FCC_SUCCESS_PATH.$this->template.'.php',false);
			foreach ($this->form as $k => $v)
			{
				$success = str_replace('%%'.strtoupper($k).'%%',$v,$success);
			}
			
			return $success;
		}
		else 
			return stripcslashes($this->config['message']);
	}
	
	/**
	 * generate error output
	 *
	 * @param string $type can be 'mail' or 'spam'
	 */
	function error_message($type = 'mail')
	{		
		if ($type == 'spam')
			return $this->config['message_spam'];
		elseif ($type == 'mail')
			return $this->config['message_error'];
	}

	/**
	 * This method load th fcc_formExec Class and
	 * execute it's own method execute
	 *
	 */	
	function setExecute()
	{
		if (is_file(FCC_EXEC_PATH.$this->template.'.php'))
		{
			include_once(FCC_PATH.'exec_form.php');
			include_once(FCC_EXEC_PATH.$this->template.'.php');
			
			if (class_exists("fcc_formExec_{$this->template}"))
			{
				$className = "fcc_formExec_".$this->template;
				
				$this->executeClass = $className;
				$this->execute = new $className;
				$this->execute->setData(&$this->form);
			}
			else 
				die("error the class fcc_formExec_".$this->template." doesn't exists");
			
		}	
	}
	
	/**
	 * Initialize del object
	 *
	 * @param mixed $param array(0=>filename,1=>title,2=>mailto)
	 */
	public function init($param = array())
	{
		// format filename:title:mailto
		$this->template = $param[0];
		if ((isset($param[1])) and ($param[1] != '')) {$this->title = $param[1]; }
		if ((isset($param[2])) and ($param[2] != '')) {$this->mailto = $param[2]; }
		
		$this->config = get_option('fcc_settings');
		$this->page = get_permalink();
	}
	
	/**
	 * Return the template form html
	 *
	 * @return string template form html
	 */
	public function getTemplateForm()
	{
		$this->templateForm = file_get_contents(FCC_FORM_PATH.$this->template.'.php',false);
		if ($this->honeypot) $this->addHoneypot();
		
		return $this->templateForm;
	}
	
	/**
	 * add some fake form inputs that have to remain blank
	 *
	 */
	public function addHoneypot()
	{
		
		$pots = '';
		foreach ($this->honeypotinput as $pot)
		{
			if (rand(1,2)%2==0)
				$pots .= 	'<input type="hidden" name="'.$pot.'" value=""/>';
			else 
				$pots .= 	'<input type="text" name="'.$pot.'" value="" style="display:none"/>';
		}
		$pots .= 	'</form>';
		
		$this->templateForm = str_ireplace('</form>',$pots,$this->templateForm);
	}
	
	/**
	 * if one of the honeypots is not
	 * null then I kill the script
	 *
	 */
	public function checkHoneypot()
	{
		$die = __('This is SPAM!!!','fcc');
		
		foreach ($this->honeypotinput as $pot)
			if ($_POST[$pot]!='') die($die);
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
		<?php if (get_option('wordpress_api_key') != '') { ?>
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