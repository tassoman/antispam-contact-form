<?php

abstract class fcc_formExec
{
	public $form = array();
	
	public function setData($form=array())
	{
		if ((is_array($form)) and (count($form) > 0))
			$this->form = $form;
	}
	
	abstract function execute();
		
	abstract function setupMailer();
	
	abstract function postParser();
	abstract function preParser();
}

?>