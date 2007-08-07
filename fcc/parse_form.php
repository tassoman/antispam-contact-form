<?php
class fcc_parseForm
{
	public $error_msg = '';
	public $error = false;
	public $error_input = array();
	

	/**
	 * check if the input is an integer
	 *
	 * @param mixed $date
	 * @return boolean
	 */	
	public function integer($integer)
	{	
		foreach ($integer as $k => $v)
		{
			$num = intval($_POST['fcc'][$v]);
			
			if ($_POST['fcc'][$v] != "$num")
			{
				$this->error_msg .= __( sprintf("<li>field %s is not numeric</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;							
				
				return false;
			}
		}
		
		return true;
	}
	

	/**
	 * check if the input is a valid date (dd-mm-aaaa)
	 *
	 * @param mixed $date
	 * @return boolean
	 */
	public function date($date)
	{
		foreach ($date as $k => $v)
		{
			if (ereg('^(0?[1-9]|[1-2][0-9]|3[01])[[:blank:]/\.\\-](0?[1-9]|1[0-2])[[:blank:]/\.\\-](19[3-9][0-9]|20[01][0-9])$|^$',$_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf( "<li>field %s is not a valid date (dd-mm-yyyy)</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
				
				return false;
			}
		}
		
		return true;
	}

	/**
	 * check if the input is a valid telephone number
	 *
	 * @param mixed $tel
	 * @return boolean
	 */
	public function telephone($tel)
	{
		foreach ($tel as $k => $v)
		{
			if (ereg("^[00[1-9]{1,4}|\+[1-9]{1,4}]?[[:blank:]\./-]?(3[2-9][0-9]|0[2-9][0-9]{1,2})[[:blank:]\./-]?[0-9]{6,9}$|^$",$_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf("<li>field %s is not a valid telephone number (+xx-xxxx-xxxxxxxx)</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
				
				return false;
			}
		}
		
		return true;
	}	
	
	/**
	 * controllo che il campo non sia vuoto
	 *
	 * @param mixed $required
	 * @return boolean
	 */
	public function required($required)
	{
		foreach ($required as $k => $v)
		{
			if ($_POST['fcc'][$v] == '')
			{
				$this->error_msg .= __(sprintf("<li>field %s is empty</li>", $v) , 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
				
				return false;
			}
		}
		
		return true;		
	}
	
	/**
	 * controllo che il campo sia una email valida
	 *
	 * @param mixed $emails
	 * @return boolean
	 */
	public function email($emails)
	{
		foreach ($emails as $k => $v)
		{
			if (ereg("^a-z0-9@.",$_POST['fcc'][$v]))
			{
				$this->error_msg .= __( sprintf("<li>field %s is not a valid email</li>", $v), 'fcc');
				$this->error_input[] = $v;
				$this->error = true;
				
				return false;
			}
		}
		
		return true;		
	}
	
	/**
	 * check if the value is > of the input check
	 * if the input data  type is a string then 
	 * the max is checked on the string lenght
	 *
	 * @param mixed $max
	 * @return boolean
	 */
	function max($max)
	{
		foreach ($max as $num => $v)
		{
			$campo = explode(',',$v);
			
			foreach ($campo as $id => $valore)
			{
				
				$toNum = intval($_POST['fcc'][$valore]);
							
				if ("$toNum" != $_POST['fcc'][$valore])
					$checker = strlen($_POST['fcc'][$valore]);
				else 
					$checker = $toNum;	
							
				if ( $checker > $num)
				{
					$this->error_msg .= __( sprintf("<li>value %s is greater than max allowed value (%d)</li>", $valore, $num), 'fcc');
					$this->error_input[] = $valore;
					$this->error = true;
					
					return false;
				}
			}
		}
		
		return true;		
	}

	/**
	 * check if the value is < of the input check
	 * if the input data  type is a string then 
	 * the min is checked on the string lenght
	 *
	 * @param mixed $min
	 * @return boolean
	 */
	function min($min)
	{
		foreach ($min as $num => $v)
		{
			$campo = explode(',',$v);
			foreach ($campo as $id => $valore)
			{
				$toNum = intval($_POST['fcc'][$valore]);
				
				if ("$toNum"!=$_POST['fcc'][$valore])
					$checker = strlen($_POST['fcc'][$valore]);	//is a string
				else 
					$checker = $toNum;	//is a integer
							
				if ( $checker < $num)
				{
					$this->error_msg .= __( sprintf("<li>value %s is lesser than min allowed value (%d)</li>", $valore, $num), 'fcc');
					$this->error_input[] = $valore;
					$this->error = true;
					
					return false;
				}
			}
		}
		
		return true;		
	}
	
}

?>