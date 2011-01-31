<?php

class PacoUtils
{	
	
	
	/**
	 * Takes mongo data and transforms into more normal data structures
	 *
	 */
	public static function transform($mongo_data)
	{
		if (sizeof($mongo_data) == 0 || !$mongo_data) return $mongo_data;
		
		$data = array();
		
		foreach(array_keys($mongo_data) as $m)
		{
			unset($mongo_data[$m]['_id']);
			
			$node = $mongo_data[$m];
			foreach(array_keys($node) as $n)
			{
				if (is_object($node[$n]) && get_class($node[$n]) == 'MongoId')
				{
					$node[$n] = $node[$n]->__toString();
				}
				
			}
			
			$data[] = array_merge(
					array(
						'id'=>$m
					), 
				$node
			);
		}
		
		if (sizeof($data) == 1)
		{
			return $data[0];
		}
		
		return $data;
	}
	
	
	
	/**
	 * Creates keypairs for rules (sorting, filtering), i.e. a ruleset passed in foo:asc,bar:desc will be translated into an array
	 *
	 */	 
	private static function extract_rules($ruleset)
	{
		$rules = array();
		while(strpos($ruleset, ', ') > 0)
		{
			$ruleset = str_replace(', ', ',', $ruleset);
		}
		if (preg_match("/([a-zA-Z0-9\{\}\:\,\_]+)?/", $ruleset))
		{
			$rules = explode(",", $ruleset);
		}
		return $rules;
	}	
	
	
	
	/**
	 * Creates keypairs for rules (sorting, filtering), i.e. a ruleset passed in foo:asc,bar:desc will be translated into an array
	 * [foo]=>1, [bar]=>-1
	 *
	 */	 
	public static function filter_rules($ruleset)
	{
		$set = array();
		$rules = self::extract_rules($ruleset);
		foreach($rules as $rule)
		{
			preg_match("/^([a-z0-9\_ ]+)\{(in|lt|gt|lte|gte|\:)}([a-zA-Z0-9 ]+)$/", $rule, $parts);
			//print_r($parts);
			if ($parts)
			{
				list($null, $key, $operator, $val) = $parts;
				if (is_numeric($val))
				{
					$val = intval($val);
				}
				// id is a reserved name
				if ($key == '_id')
				{
					$val = new MongoId($val);
				}
				//print($key . "\n");
				//print($operator . "\n");
				//print($val . "\n");
				if ($operator == ':')
				{
					$set[$key] = $val;
				} 
				else 
				{
					if ($operator == 'in')
					{
						$val = array($val);
					}						
					$set[$key] = array('$' . $operator=>$val);
				}
			}			
		}
		return $set;
	}
	
	
	
	
	/**
	 * Creates keypairs for rules (sorting, filtering), i.e. a ruleset passed in foo:asc,bar:desc will be translated into an array
	 * [foo]=>1, [bar]=>-1
	 *
	 */	 
	public static function sort_rules($ruleset)
	{
		$set = array();
		$rules = self::extract_rules($ruleset);
		
		foreach($rules as $rule)
		{
			$rule = str_replace(array('{', '}'), '', $rule);
			//
			list($key, $order) = explode(':', $rule);
			// asc = 1, desc = -1
			$set[$key] = (strtolower($order) == 'asc' || $order == 1 ? 1 : -1);
		}
		return $set;
	}
	
	
	/**
	 * Creates keypairs for rules (sorting, filtering), i.e. a ruleset passed in foo:asc,bar:desc will be translated into an array
	 * [foo]=>1, [bar]=>-1
	 *
	 */	 
	public static function field_rules($ruleset)
	{
		$set = array();
		while(strpos($ruleset, ', ') > 0)
		{
			$ruleset = str_replace(', ', ',', $ruleset);
		}
		$rules = explode(',', $ruleset);
		if (sizeof($rules) > 0)	
		{
			$set = $rules;
		}
		return $set;
	}
	
	
	
	/**
	 * Pretty print json
	 *
	 */
	static function json_format($json)
	{
	    $tab = "  ";
	    $new_json = "";
	    $indent_level = 0;
	    $in_string = false;
	    
	    $json_obj = json_decode($json);
	    
	    if(!$json_obj)
		return false;
	    
	    $json = json_encode($json_obj);
	    $len = strlen($json);
	    
	    for($c = 0; $c < $len; $c++)
	    {
		$char = $json[$c];
		switch($char)
		{
		    case '{':
		    case '[':
			if(!$in_string)
			{
			    $new_json .= $char . "\n" . str_repeat($tab, $indent_level+1);
			    $indent_level++;
			}
			else
			{
			    $new_json .= $char;
			}
			break;
		    case '}':
		    case ']':
			if(!$in_string)
			{
			    $indent_level--;
			    $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
			}
			else
			{
			    $new_json .= $char;
			}
			break;
		    case ',':
			if(!$in_string)
			{
			    $new_json .= ",\n" . str_repeat($tab, $indent_level);
			}
			else
			{
			    $new_json .= $char;
			}
			break;
		    case ':':
			if(!$in_string)
			{
			    $new_json .= ": ";
			}
			else
			{
			    $new_json .= $char;
			}
			break;
		    case '"':
			$in_string = !$in_string;
		    default:
			$new_json .= $char;
			break;                    
		}
	    }
	    
	    return $new_json;
	}
}
?>
