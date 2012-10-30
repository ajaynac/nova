<?php
/**
 * The Location class provides methods for searching through Nova's file structure
 * to find the correct view or image to pull. This is the heart and soul of seamless
 * substitution.
 *
 * @package		Nova
 * @subpackage	Fusion
 * @category	Class
 * @author		Anodyne Productions
 * @copyright	2012 Anodyne Productions
 */

namespace Fusion;

class Location
{
	/**
	 * Finds and returns the path to an asset image. Asset images are not part of 
	 * seamless substitution since they're stored entirely outside of the Nova core.
	 *
	 * @param	string	the name and extension of the image
	 * @param	string	what to return (image, path, abspath, urlpath)
	 * @param	array 	an array of attributes to be used on the image
	 * @return	string
	 */
	public static function asset($image, $return = 'image', $attr = array(), $module = 'nova')
	{
		// Create a new Location object
		$path = new static($image, false, $module);

		return $path->setType('asset')->findImage($return, $attr);
	}

	/**
	 * Searches to find where to pull the specified file from. If the file exists
	 * in the skin, it'll use that that one and stop searching. If the file exists
	 * in the override module (app/modules/override), it'll use that and stop searching.
	 * Otherwise, it'll use whatever's found in the nova module (core/modules/nova).
	 *
	 * @param	string	The file
	 * @param	string	The current skin
	 * @param	string	The type of file (structure, template, partial, pages, js)
	 * @param	string	The module to fall back to
	 * @return	string
	 */
	public static function file($file, $skin, $type, $module = 'nova')
	{
		// Create a new Location object
		$path = new static($file, $skin, $module);

		return $path->setType($type)->findFile();
	}
	
	/**
	 * Finds and returns the path to an image. Nova will first look inside the
	 * current skin to find the image. If the image is found, it'll use that
	 * and stop looking, otherwise it'll move on to the override module and
	 * check there. Again, if the image is found, it'll use that and stop looking,
	 * otherwise it'll finally look in the nova module and use that image.
	 *
	 * @param	string	The name of the image
	 * @param	string	The name of the skin
	 * @param	string	The fallback module
	 * @param	string	What to return (image, path, abspath, urlpath)
	 * @param	array 	An array of attributes to be used on the image
	 * @return	string
	 */
	public static function image($image, $skin, $return = 'image', $attr = array(), $module = 'nova')
	{
		// Create a new Location object
		$path = new static($image, $skin, $module);

		return $path->setType('image')->findImage($return, $attr);
	}

	/**
	 * Finds and returns the path to a rank image (app/assets/common/{GENRE}/ranks).
	 * Rank images are not part of seamless substitution since they're stored
	 * entirely outside of the Nova core.
	 *
	 * @param	string	The name of the base image
	 * @param	string	The name of the pip image
	 * @param	string	The location of the rank set
	 * @return	string
	 */
	public static function rank($base, $pip, $location = false, $module = 'nova')
	{
		// Create a new Location object
		$path = new static(false, false, $module);

		return $path->setType('rank')->findRank($base, $pip, $location);
	}

	/**
	 * @var	string	The file name.
	 */
	protected $file;

	/**
	 * @var	string	The current skin.
	 */
	protected $skin;

	/**
	 * @var	string	The type of asset.
	 */
	protected $type;

	/**
	 * @var	string	The fallback module.
	 */
	protected $module;

	/**
	 * Create a new Location object.
	 *
	 * @param	string	The file name
	 * @param	string	The skin name
	 * @param	string	The fallback module
	 * @return	void
	 */
	public function __construct($file, $skin, $module)
	{
		$this->file = $file;
		$this->skin = $skin;
		$this->module = $module;
	}

	/**
	 * Find the right path to a view.
	 *
	 * @return	string
	 */
	public function findFile()
	{
		if (is_file(APPPATH."modules/override/views/components/{$this->type}/{$this->file}.php"))
		{
			return "override::components/{$this->type}/{$this->file}";
		}
		elseif (is_file(APPPATH."views/{$this->skin}/components/{$this->type}/{$this->file}.php"))
		{
			return "{$this->skin}/components/{$this->type}/{$this->file}";
		}
		
		return $this->module."::components/{$this->type}/{$this->file}";
	}

	/**
	 * Find the right path to an image.
	 *
	 * @param	string	What to return (image, urlpath, abspath, path)
	 * @param	array	Attributes for the image return type
	 * @return	string
	 */
	public function findImage($return = 'path', $attributes = array())
	{
		// Find the path to the image
		$path = $this->findAssetPath();

		switch ($return)
		{
			case 'image':
				return \Html::img(\Uri::base(false).$path, $attributes);
			break;

			case 'urlpath':
				return \Uri::base(false).$path;
			break;

			case 'abspath':
				return APPPATH.$path;
			break;

			default:
				return $path;
			break;
		}
	}

	/**
	 * Find the right path to a rank.
	 *
	 * @param	string	The base image
	 * @param	string	The pip image
	 * @param	string	An optional rank location
	 * @return	string
	 */
	public function findRank($base, $pip, $location = false)
	{
		// Get the genre
		$genre = \Config::get('nova.genre');

		// Get the rank catalog object
		$catalog = ( ! $location)
			? \Model_Catalog_Rank::getItem(\Utility::getRank(), 'location')
			: \Model_Catalog_Rank::getItem($location, 'location');

		if (is_dir(APPPATH."assets/common/{$genre}/ranks/{$catalog->location}/base") 
				and is_dir(APPPATH."assets/common/{$genre}/ranks/{$catalog->location}/pips"))
		{
			// Set up the properties
			$properties = array(
				'base' => "background:transparent url(".\Uri::base(false).'app/assets/common/'.$genre.'/ranks/'.$catalog->location.'/base/'.$base.$catalog->extension.") no-repeat top left;",
				'pip' => "background:transparent url(".\Uri::base(false).'app/assets/common/'.$genre.'/ranks/'.$catalog->location.'/pips/'.$pip.$catalog->extension.") no-repeat top left;",
			);

			return \View::forge(\Location::file('common/rank', $this->skin, 'partial'))
				->set('props', $properties)
				->render();
		}

		// Clean up the base
		$base = str_replace('cadet/', '', $base);

		// Clean up the pip
		$pip = (strpos($pip, '/') !== false) ? substr_replace($pip, '', 0, (strpos($pip, '/') + 1)) : $pip;

		// Set the image name now
		$imageName = (empty($pip)) ? $base.$catalog->extension : $base."-".$pip.$catalog->extension;

		// Return the old rank style
		return \Html::img("app/assets/common/{$genre}/ranks/{$catalog->location}/{$imageName}");
	}

	/**
	 * Executes the search and return of images from the file system. Alias
	 * methods exist to interact with this since it uses dynamic arguments to
	 * make the API a little cleaner.
	 *
	 * @internal
	 * @return	string	The path to the asset
	 * @throws	NovaImageNotFoundException
	 */
	protected function findAssetPath()
	{
		switch ($this->type)
		{
			case 'asset':
				return "app/assets/images/{$this->file}";
			break;
			
			case 'image':
				if (is_file(APPPATH."modules/override/views/design/images/{$this->file}"))
				{
					return "app/modules/override/views/design/images/{$this->file}";
				}
				elseif (is_file(APPPATH."views/{$this->skin}/design/images/{$this->file}"))
				{
					return "app/views/{$this->skin}/design/images/{$this->file}";
				}
				
				return "nova/modules/{$this->module}/views/design/images/{$this->file}";
			break;
			
			case 'rank':
				return "app/assets/common/".\Config::get('nova.genre')."/ranks/{$args[1]}/{$args[2]}";
			break;
			
			default:
				throw new \NovaInvalidImageTypeException(lang('error.exception.invalid_image'));
			break;
		}
	}

	/**
	 * Get the file.
	 *
	 * @return	string
	 */
	public function getFile()
	{
		return $this->file;
	}

	/**
	 * Get the skin.
	 *
	 * @return	string
	 */
	public function getSkin()
	{
		return $this->skin;
	}

	/**
	 * Get the type.
	 *
	 * @return	string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Get the fallback module.
	 *
	 * @return	string
	 */
	public function getModule()
	{
		return $this->module;
	}

	/**
	 * Set the file name.
	 *
	 * @param	string	The name of the file (including extension if anything except .php)
	 * @return	Location
	 */
	public function setFile($value)
	{
		$this->file = $value;

		return $this;
	}

	/**
	 * Set the skin name.
	 *
	 * @param	string	The name of the skin
	 * @return	Location
	 */
	public function setSkin($value)
	{
		$this->skin = $value;

		return $this;
	}

	/**
	 * Set the type of file.
	 *
	 * @param	string	The type of file (only necessary when searching for files, not images)
	 * @return	Location
	 */
	public function setType($value)
	{
		$this->type = $value;

		return $this;
	}

	/**
	 * Set the fallback module.
	 *
	 * @param	string	The name of the module to fall back to
	 * @return	Location
	 */
	public function setModule($value)
	{
		$this->module = $value;

		return $this;
	}
}
