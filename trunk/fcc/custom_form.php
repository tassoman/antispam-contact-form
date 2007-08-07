<?php

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

		if($akismet->isCommentSpam())
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
?>