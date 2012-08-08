<?php
/**
 * The model class is the foundation for all of Nova's models and provides core
 * functionality that's shared across a lot of the models used in Nova,
 * including creating a new items, updating an existing item, finding items
 * based on criteria, and deleting items.
 *
 * Because these methods are the basis for the majority of CRUD operations in 
 * Nova models, any changes to this class should be done carefully and 
 * deliberately since they can cause wide ranging issues if not done properly.
 *
 * @package		Nova
 * @subpackage	Fusion
 * @category	Class
 * @author		Anodyne Productions
 * @copyright	2012 Anodyne Productions
 */

namespace Fusion;

class Model extends \Orm\Model
{	
	/**
	 * Create an item with the data passed.
	 *
	 * @api
	 * @param	mixed	an array or object of data
	 * @param	bool	should the object be returned?
	 * @param	bool	should the data be filtered?
	 * @return	mixed
	 */
	public static function create_item($data, $return_object = false, $filter = true)
	{
		// create a forge
		$item = static::forge();
		
		// loop through the data and add it to the item
		foreach ($data as $key => $value)
		{
			if ($key != 'id' and array_key_exists($key, static::$_properties))
			{
				if (is_array($data))
				{
					if ($filter)
					{
						$item->{$key} = trim(\Security::xss_clean($data[$key]));
					}
					else
					{
						$item->{$key} = $data[$key];
					}
				}
				else
				{
					if ($filter)
					{
						$item->{$key} = trim(\Security::xss_clean($data->{$key}));
					}
					else
					{
						$item->{$key} = $data->{$key};
					}
				}
			}
		}
		
		if ($item->save())
		{
			if ($return_object)
			{
				return $item;
			}

			return true;
		}
		
		return false;
	}
	
	/**
	 * Find an item in the table based on the arguments passed.
	 *
	 * @api
	 * @param	mixed	an array of arguments or the item ID
	 * @return	object
	 */
	public static function find_item($args)
	{
		if (is_array($args))
		{
			// start the find
			$record = static::find();
			
			// loop through the arguments and build the where clause
			foreach ($args as $column => $value)
			{
				if (array_key_exists($column, static::$_properties))
				{
					$record->where($column, $value);
				}
			}
			
			// get the record
			$result = $record->get_one();
		}
		else
		{
			// if we have an ID, just go straight to that item
			$result = static::find($args);
		}
		
		return $result;
	}

	/**
	 * Find items in the table based on the arguments passed.
	 *
	 * @api
	 * @param	array	an array of arguments
	 * @param	string	the order to return the results in
	 * @return	object
	 */
	public static function find_items($args = array())
	{
		// start the find
		$record = static::find();

		if (is_array($args) and count($args) > 0)
		{
			// loop through the arguments and build the where clause
			foreach ($args as $type => $values)
			{
				if (method_exists($record, $type))
				{
					switch ($type)
					{
						case 'order_by':
							if (array_key_exists($values[0], static::$_properties))
							{
								$record->order_by($values[0], $values[1]);
							}
						break;

						case 'limit':
							$record->limit($values[0]);
						break;
						
						default:
							if (array_key_exists($values[0], static::$_properties))
							{
								$record->{$type}($values[0], $values[1], $values[2]);
							}
						break;
					}
				}
			}
		}
		
		return $record->get();
	}

	/**
	 * Find a form item based on the form key.
	 *
	 * @api
	 * @param	string	the form key to use
	 * @param	bool	pull back displayed items (true) or all items (false)
	 * @return	object
	 */
	public static function find_form_items($key, $only_active = false)
	{
		// get the object
		$items = static::find();

		// make sure we're pulling back the right form
		$items->where('form_key', $key);

		// should we be getting all items or just enabled ones?
		if ($only_active)
		{
			$items->where('status', \Status::ACTIVE);
		}

		// order the items
		$items->order_by('order', 'asc');

		// return the object
		return $items->get();
	}
	
	/**
	 * Update an item in the table based on the ID and data.
	 *
	 * @api
	 * @param	mixed	a specific ID to update or NULL to update everything
	 * @param	array 	the array of data to update with
	 * @param	bool	should the data be run through the XSS filter
	 * @return	mixed
	 */
	public static function update_item($id, array $data, $return_object = false, $filter = true)
	{
		if ($id !== null)
		{
			// get the item
			$item = static::find($id);
			
			// loop through the data array and make the changes
			foreach ($data as $key => $value)
			{
				if ($key != 'id' and array_key_exists($key, static::$_properties))
				{
					if ($filter)
					{
						$value = trim(\Security::xss_clean($value));
					}

					$item->{$key} = $value;
				}
			}
			
			// save the item
			if ($item->save())
			{
				if ($return_object)
				{
					return $item;
				}

				return true;
			}

			return false;
		}
		else
		{
			// pull everything from the table
			$items = static::find('all');
			
			// loop through all the items
			foreach ($items as $item)
			{
				// loop through the data and make the changes
				foreach ($data as $key => $value)
				{
					if ($filter)
					{
						$value = trim(\Security::xss_clean($value));
					}

					$item->{$key} = $value;
				}
				
				// save the item
				$item->save();
			}

			return true;
		}
	}
	
	/**
	 * Delete an item in the table based on the arguments passed.
	 *
	 * @api
	 * @param	mixed	an array of arguments or the item ID
	 * @return	bool
	 */
	public static function delete_item($args)
	{
		// if we have a list of arguments, loop through them
		if (is_array($args))
		{
			// start the find
			$item = static::find();

			// loop through the arguments to build the where
			foreach ($args as $column => $value)
			{
				if (array_key_exists($column, static::$_properties))
				{
					$item->where($column, $value);
				}
			}

			// get the item
			$entry = $item->get_one();
		}
		else
		{
			// go directly to the item
			$entry = static::find($args);
		}

		// now that we have the item, delete it
		if ($entry->delete(null, true))
		{
			return true;
		}
		
		return false;
	}
}
