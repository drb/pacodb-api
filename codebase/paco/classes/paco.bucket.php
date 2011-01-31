<?php
/**
 * Buckets act as data repositories for a users' datasets. Each bucket is exposed by the query method, and can be used to extract data intelligently.
 *
 * [WebClass]
 */
class PacoBucket extends PacoAPI
{

	/**
	 * Queries a bucket
	 *
	 * @param string $id Bucket ID
	 * @param array $fields Number of records to extract, after sorting rules have processed
	 * @param array $sort Fields to sort by. Unknown fields are ignored
	 *
	 * [WebMethod]
	 */
	public function query($id, $fields=null, $filter=null, $sort=null, $limit=self::API_DEFAULT_LIMIT)
	{

		$filter_rules 	= array(
					'bucket_id'=>new MongoId($id)
				);
		$sort_rules 	= array();
		$fields_rules 	= array();

		if (!$id)
		{
			return new PacoError('No bucket ID supplied', 301);
		}

		if (!is_null($filter))
		{
			// parse filter rules
			$filter_rules += PacoUtils::filter_rules($filter);
		}

		if (!is_null($sort))
		{
			// parse sorting rules
			$sort_rules += PacoUtils::sort_rules($sort);
		}

		if (!is_null($fields))
		{
			// parse fields to extract
			$fields_rules = explode(',', $fields);
		}

		$data = $this->find(
			'bucket_data',
			$filter_rules,
			$fields_rules,
			$sort_rules,
			$limit
		);

		$data = PacoUtils::transform($data);

		return array('query'=>$data);
	}


	/**
	 * Inserts data into the specified bucket. Validation occurs beforehand to ensure data integrity.
	 *
	 * @param string $sort Sorting rules for data. Fields will be ignored that don't exist in the bucket
	 * @param int $limit How many buckets to return. Default is 25
	 *
	 * [WebMethod]
	 */
	public function insert($id=null, array $incoming=null)
	{
		// @todo, make this neater
		if (!is_null($incoming))
		{
			$data = $incoming;
		}
		else
		{
			$data = $this->request;
		}

		// contains all errors found in incoming data
		$errors = array();

		// these are the fields we're going to insert
		$valid_fields	= array();

		if (is_null($id))
		{
			return new PacoError('Bucket insertions require a valid bucket id', 102);
		}

		$bucket = $this->find(
			'buckets',
			array('_id'=>new MongoId($id)),
			array('fields')
		);

		if (isset($bucket[$id]))
		{
			$fields = $bucket[$id]['fields'];

			// loop over the fields in the bucket. we're going to test these against the incoming data
			foreach($fields as $field)
			{
				$post_val	= null;
				$name 		= trim($field['name']);
				$type 		= $field['type'];
				$validation 	= $field['validation'];
				$options	= null;

				//
				if (isset($field['options']))
				{
					$options = $field['options'];
				}

				if (isset($data[$name]))
				{
					$post_val = $data[$name];

					// here we do the final processing on the incoming fields for datatype integrity
					// i.e. if a field is designated numeirc, we cast the value as an int etc

					if (in_array('number', $validation) && is_numeric($post_val))
					{
						$post_val = intval($post_val);
					}

					// if this is a checkbox type, we split into the parts
					if (in_array($type, array('tags', 'checkbox')))
					{
						$post_val = str_replace(', ', ',', $post_val);
						$post_val = explode(',', $post_val);
					}

					// add the data to the array we'll ultimately send to the database
					$valid_fields[$name] = $post_val;
				}

				//print('validating ' . $name . "\n");

				if (is_array($validation))
				{
					foreach($validation as $rule)
					{
						$validate = $this->validate($rule, $post_val, $name, $options);

						if (!$validate)
						{
							// add error to errors object
							if (!isset($errors[$name]))
							{
								$errors[$name] = array();
							}
							array_push($errors[$name], $rule);
						}
					}

				}
				else
				{
					$validate = $this->validate($validation, $post_val, $name, $options);

					if (!$validate)
					{
						// add error to errors object
						if (!isset($errors[$name]))
						{
							$errors[$name] = array();
						}
						array_push($errors[$name], $rule);
					}
				}
			}
		}
		else
		{
			return new PacoError('No such bucket id', 309);
		}

		if (sizeof($errors) > 0)
		{
			$errors = array('validation_errors'=>$errors);
			return array('insert'=>$errors);
		}
		else
		{


			$insert = $this->db->insert(
				'bucket_data',
				array_merge(
					array(
						'bucket_id'=>new MongoId($id),
						'date_create'=>new MongoDate()
					),
					$valid_fields
				)
			);

			// do the insertion
			return array('insert'=>$insert);
		}
	}

	private function validate($rule, $value, $name=null, $options=null)
	{
		$validated = true;
		switch($rule)
		{
		case 'required':
			if (is_null($value) || empty($value))
			{
				$validated = false;
			}
			break;
		case 'number':
			if (!is_numeric($value))
			{
				$validated = false;
			}
			break;
		case 'alphanumeric':
			if (empty($value) || preg_match("/([^a-zA-Z0-9 ]+)/", $value))
			{
				$validated = false;
			}
			break;
		case 'text':
			if (preg_match("/([^a-zA-Z0-9\!\?\.\[\]\-\_\+\(\) ]+)/", $value))
			{
				$validated = false;
			}
			break;
		case 'limit_options':
			if (!is_array($value))
			{
				if (!in_array($value, $options))
				{
					$validated = false;
				}
			}
			else
			{
				foreach($value as $v)
				{
					if (!in_array($v, $options))
					{
						$validated = false;
					}
				}
			}
			break;
		}

		// return results
		return $validated;
	}


	public function get_bucket_id_by_name($name)
	{
		$normalised = $name;

		$b = $this->find(
			'buckets',
			array(
				'normalised'=>$normalised
			),
			array('_id'),
			array(),
			1
		);

		if ($b)
		{
			$b = array_keys($b);
			return $b[0];
		}

		return false;
	}


	/**
	 * Displays a list of all data buckets under this account, their fields, and how many records per bucket
	 *
	 * @param string $sort Sorting rules for data. Fields will be ignored that don't exist in the bucket
	 * @param int $limit How many buckets to return. Default is 25
	 *
	 * [WebMethod]
	 */
	public function describe($id=null, $sort=null, $fields=null, $limit=self::API_DEFAULT_LIMIT)
	{
		$sort_rules = array();
		$filter_rules = array();
		$field_rules = array();

		if (!is_null($id))
		{
			$filter_rules = array('_id'=>new MongoId($id));
		}

		if(!is_numeric($limit))
		{
			return new PacoError('Limit is not an integer', 201);
		}

		if (!is_null($sort))
		{
			// if parse sorting rules
			$sort_rules = PacoUtils::sort_rules($sort);
		}

		if (!is_null($fields))
		{
			// if parse sorting rules
			$field_rules = PacoUtils::field_rules($fields);
		}

		$bu = $this->find(
			'buckets',			// collection
			$filter_rules,			// filter
			$field_rules,			// fields
			$sort_rules,			// sort
			$limit				// limit
		);

		$bu = PacoUtils::transform($bu);

		return array('bucket'=>$bu);
	}

}
?>
