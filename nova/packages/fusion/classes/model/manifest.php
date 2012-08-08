<?php
/**
 * Manifest Model
 *
 * @package		Nova
 * @subpackage	Fusion
 * @category	Model
 * @author		Anodyne Productions
 * @copyright	2012 Anodyne Productions
 */
 
namespace Fusion;

class Model_Manifest extends \Model {
	
	public static $_table_name = 'manifests';
	
	public static $_properties = array(
		'id' => array(
			'type' => 'int',
			'constraint' => 11,
			'auto_increment' => true),
		'name' => array(
			'type' => 'string',
			'constraint' => 255,
			'default' => ''),
		'order' => array(
			'type' => 'int',
			'constraint' => 5,
			'null' => true),
		'desc' => array(
			'type' => 'text',
			'null' => true),
		'header_content' => array(
			'type' => 'text',
			'null' => true),
		'status' => array(
			'type' => 'tinyint',
			'constraint' => 1,
			'default' => \Status::ACTIVE),
		'default' => array(
			'type' => 'tinyint',
			'constraint' => 1,
			'default' => 0),
	);
	
	public static $_has_many = array(
		'departments' => array(
			'model_to' => '\\Model_Department',
			'key_to' => 'manifest_id',
			'key_from' => 'id',
			'cascade_save' => false,
			'cascade_delete' => false,
		),
	);
}
