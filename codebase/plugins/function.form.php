<?php
/**
 * Extracts bucket data
 *
 */
class Dwoo_Plugin_form extends PacoBucket
{	
	public function process($src)
	{
		$id = $this->get_bucket_id_by_name($src);
		
		if ($id)
		{
			$form = $this->describe($id);
		}
		
		print($this->form($form));
	}
	
	/**
	 * Createa an HTML form
	 */
	private function form($response, $class=null)
	{
		global $paco_errors;
		$commit = false;
		$errors = array();
		$bucket = $response['bucket'];
		$fields = $bucket['fields'];
		
		if (array_key_exists('incoming_params', $_GET))
		{
			//$postdata = $this->keypair_array($_POST);
			$postdata = unserialize($_GET['incoming_params']);
			
			if(sizeof($postdata) > 0)
			{
			
				foreach($postdata as $key=>$val)
				{
					if (is_array($val))
					{
						$postdata[$key] = implode(',', $val);
					}
				}
				
				$insert = $this->insert($response['bucket']['id'], $postdata);
				// do the insert
				/*$insert = $this->execute(
					$this->call('bucket.insert', $postdata)
				);*/
						
				if (isset($insert['insert']))
				{
					$errors = $insert['insert']['validation_errors'];
	
					if (sizeof($errors) > 0)
					{
						echo '<ul>';
						foreach($errors as $key=>$error)
						{			
							if (isset($paco_errors['errors'][$key][$error[0]]))
							{
								$msg = $paco_errors['errors'][$key][$error[0]];
							} else 
							{
								 $msg = $key . ' failed the ' . $error[0] . ' test';
							}
							echo '<li>' . $msg . '</li>';
						}
						echo '</ul>';
					} 
					else
					{
						if ($insert['insert'] == 1)
						{
							$commit = true;
							
							print("DONE!");
						}
					}
				}
			}
		}
		
		//print('<pre>');
		//print_r($response);
		//print('</pre>');
		
		if (!$commit)
		{
			echo '<form action="" method="post">';
			echo '<input type="hidden" name="id" value="' . $bucket['id'] .  '" />';
			echo '<ul>';
			foreach($fields as $field)
			{
				// extract a unique id
				$id = $bucket['id'] . '-' . $field['name'];
			
				echo '<li>';
				
				// print labels
				echo '<label for="' . $id . '">' . $field['name'] . '</label>';
				
				// decide the type of form item
				switch ($field['type'])
				{
				case 'input':
					
					echo '<input type="input" value="' . $this->globals_extract($field['name']) . '" name="' . $field['name'] . '" id="' . $id . '" />';
					break;
				case 'textarea':
					
					echo '<textarea name="' . $field['name'] . '" id="' . $id . '">' . $this->globals_extract($field['name']) . '</textarea>';
					break;
				case 'select':
					echo '<select name="' . $field['name'] . '" id="' . $id . '">';
					foreach($field['options'] as $option)
					{
						$selected = '';
						if ($option == $this->globals_extract($field['name']))
						{
							$selected = " selected='selected'";
						}
						echo '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
					}
					echo '</select>';
					break;
				case 'checkbox':
					foreach($field['options'] as $option)
					{
						$selected = '';
						$choices = $this->globals_extract($field['name']);
						if (is_array($choices))
						{							
							if (in_array($option, $choices))
							{
								$selected = " checked='checked'";
							}
						}
						echo '<input type="checkbox" name="' . $field['name'] . '[]" value="' . $option . '"' . $selected . ' /> ' . $option . '<br/>';
					}
					break;
				case 'radio':
					foreach($field['options'] as $option)
					{
						$selected = '';
						if ($option == $this->globals_extract($field['name']))
						{
							$selected = " checked='checked'";
						}
						echo '<input type="radio" value="' . $option . '" name="' . $field['name'] . '"' . $selected . '" /> ' . $option . '<br/>';
					}
					break;
				case 'tags':
					echo '<input type="input" value="' . $this->globals_extract($field['name']) . '" name="' . $field['name'] . '" id="' . $id . '" />';
					break;
				}
				
				echo '</li>';
			}
			echo '<input type="submit" />';
			echo '</ul>';
			echo '</form>';
		}
	}
	
	private function globals_extract($key)
	{
		$postdata = unserialize($_GET['incoming_params']);
		if (isset($postdata[$key]))
		{
			return $postdata[$key];
		}
		return '';
	}
}
?>
