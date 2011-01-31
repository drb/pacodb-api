<?php
/**
 * Extracts bucket data
 *
 */
class Dwoo_Plugin_bucket extends Dwoo_Block_Plugin
{

	public function init($name=null, $filter=null, $limit=25, $fields=null, $sort=null)
	{
		$data 		= array();
		
		$filter_rules 	= array();
		$sort_rules 	= array();
		$field_rules 	= array();
			
		/*print("bucket name: $name<br/>");
		print("filter: $filter<br/>");
		print("limit: $limit<br/>");
		print("sort: $sort<br/>");
		print("fields: $fields<br/>");*/
				
		if (is_null($name))
		{
			$this->dwoo->assignInScope('Bucket name cannot be empty', 'error');
		}
		
		if (!is_null($filter))
		{
			$filter_rules = PacoUtils::filter_rules($filter); 
			
			// convert to mongoid if this is an id lookup
			if (array_key_exists('_id', $filter_rules))
			{
				$filter_rules['_id'] = new MongoId($filter_rules['_id']);
			}
			/*print('filter:');
			print($filter . "<br/>");
			print('filter_rules:<br/>');
			print_r($filter_rules);
			print('<br/>');*/
		}
		
		if (!is_null($fields))
		{
			$field_rules = PacoUtils::field_rules($fields);
			/*print('fields:<br/>');
			print_r($field_rules);
			print('<br/>');*/
		}
		
		if (!is_null($sort))
		{
			$sort_rules = PacoUtils::sort_rules($sort);
			/*print('Sort:<br/>');
			print_r($sort_rules);
			print('<br/>');*/
		}
		
		// @todo this needs to be incorporated inot the api properly, not just sat here doing direct lookups to mongo!!!
		$m = new Mongo();
		$db = $m->selectDB("paco");
		$coll = $db->selectCollection('buckets');
		// see if the bucket exists
		$bucket = $coll->findOne(
			array(
				'normalised'=>$name
			),
			array('_id', 'name')
		);
			
		if ($bucket)
		{
			$bucket_id = $bucket['_id'];
			//print($bucket_id);
			$filter_rules['bucket_id'] = new MongoId($bucket_id);
			// get the bucket data, filtered
			$bucket_data = $db->selectCollection('bucket_data');
		
			$data = $bucket_data->find(
				$filter_rules,
				$field_rules
			);
			
			if (sizeof($sort_rules) > 0)
			{
				$data->sort($sort_rules);
			}
			
			if (is_numeric($limit))
			{
				$data->limit($limit);
			}
			
			$results = iterator_to_array($data);
		}
		
		$this->dwoo->assignInScope($results, $name);
		
		unset($m);
		unset($data);
	}
}
?>
