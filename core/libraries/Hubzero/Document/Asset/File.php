<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Hubzero\Document\Asset;

use Hubzero\Base\Object;

/**
 * Base asset class
 */
class File extends Object
{
	/**
	 * Asset type
	 *
	 * @var  string
	 */
	protected $type = null;

	/**
	 * Asset name (without file extension)
	 *
	 * @var  string
	 */
	protected $name = null;

	/**
	 * Asset directory
	 *
	 * @var  string
	 */
	protected $directory = null;

	/**
	 * CMS Extension name (e.g., com_blog)
	 *
	 * @var  string
	 */
	protected $extension = null;

	/**
	 * CMS Extension type
	 *
	 * @var  string
	 */
	protected $kind = null;

	/**
	 * Is a declaration?
	 *
	 * Declarations do not have files or paths.
	 * These will be raw scripts or styles.
	 *
	 * @var  string
	 */
	protected $declaration = false;

	/**
	 * Asset paths
	 *
	 * @var  array
	 */
	protected $paths = array(
		'source'   => null,
		'override' => null,
		'target'   => null
	);

	/**
	 * Constructor
	 *
	 * @param   string  $extension  CMS Extension to load asset from
	 * @param   string  $name       Asset name (optional)
	 * @return  void
	 */
	public function __construct($extension, $name=null)
	{
		$this->extension = strtolower(trim((string) $extension));

		switch (substr($this->extension, 0, 4))
		{
			case 'com_':
				$this->kind = 'components';
				$name = $name ?: substr($this->extension, 4);
			break;
			case 'mod_':
				$this->kind = 'modules';
				$name = $name ?: $this->extension;
			break;
			case 'plg_':
				$this->kind = 'plugins';
				if (!$name)
				{
					$parts = explode('_', $this->extension);
					$name = $parts[2];
				}
			break;
			case 'tpl_':
				$this->kind = 'templates';
			break;
			default:
				if ($this->extension == 'system')
				{
					$this->kind = $this->extension;
				}
			break;
		}

		$this->name = $name;

		if (substr($this->name, 0, strlen('http')) == 'http'
		 || substr($this->name, 0, strlen('://')) == '://'
		 || substr($this->name, 0, strlen('//')) == '//')
		{
			$this->kind = 'external';
			$this->paths['source'] = ltrim($this->name, ':');
			$this->paths['target'] = ltrim($this->name, ':');
		}

		if (strstr($this->name, '.'))
		{
			if (strtolower(substr($name, strrpos($name, '.') + 1)) == $this->type)
			{
				$this->name = preg_replace('/\.[^.]*$/', '', $this->name);
			}
		}

		$this->directory = $this->dir($this->name, $this->type());
	}

	/**
	 * Determine the asset directory
	 *
	 * @param   string  $name     Asset path
	 * @param   string  $default  Default directory
	 * @return  string
	 */
	protected function dir(&$name, $default='')
	{
		if (substr($name, 0, 2) == './')
		{
			$name = substr($name, 2);

			return '';
		}

		if (substr($name, 0, 1) == '/')
		{
			$name = substr($name, 1);

			return '/';
		}

		return $default;
	}

	/**
	 * Get the asset type
	 *
	 * @return  string
	 */
	public function type()
	{
		return $this->type;
	}

	/**
	 * Get the CMS extension name
	 * [mod_*, com_*, plg_folder_type, tpl_*, system]
	 *
	 * @return  string
	 */
	public function extensionName()
	{
		return $this->extension;
	}

	/**
	 * Get the CMS extension type
	 * [modules, plugins, components, templates, system]
	 *
	 * @return  string
	 */
	public function extensionType()
	{
		return $this->kind;
	}

	/**
	 * Get the file name
	 *
	 * @return  string
	 */
	public function file()
	{
		return $this->name . '.' . $this->type;
	}

	/**
	 * Is the asset a declaration?
	 *
	 * @return  boolean
	 */
	public function isDeclaration()
	{
		return $this->declaration;
	}

	/**
	 * Is the asset external to the site?
	 *
	 * @return  boolean
	 */
	public function isExternal()
	{
		return $this->kind == 'external';
	}

	/**
	 * Adds to the list of paths
	 *
	 * @param   string  $type  The type of path to add.
	 * @param   mixed   $path  The directory or stream, or an array of either, to search.
	 * @return  object
	 */
	public function setPath($type, $path)
	{
		if (!array_key_exists($type, $this->paths))
		{
			throw new \Exception(\App::get('language')->txt('Unknown asset path type of %s given.', $type));
		}

		$path = trim((string) $path);

		// Add separators as needed
		$path = DS . trim($path, DS);

		// Add to list of paths
		$this->paths[$type] = $path;

		return $this;
	}

	/**
	 * Get the source path
	 *
	 * @return  string
	 */
	public function sourcePath()
	{
		if (!isset($this->paths['source']))
		{
			// If loading from an absolute path
			if ($this->directory == '/')
			{
				$this->paths['source'] = PATH_ROOT . DS . $this->directory . $this->file();
			}
			else
			{
				$paths  = array();

				$basea  = PATH_APP . DS;
				$basec  = PATH_CORE . DS;

				$client = (isset(\App::get('client')->alias) ? \App::get('client')->alias : \App::get('client')->name);

				switch ($this->extensionType())
				{
					case 'plugins':
						$parts = explode('_', $this->extensionName());
						$path = $this->extensionType() . DS . $parts[1] . DS . $parts[2] . DS;
					break;

					case 'components':
						$path = $this->extensionType() . DS . $this->extensionName() . DS . $client . DS;
					break;

					case 'modules':
						$path = $this->extensionType() . DS . $this->extensionName() . DS;
					break;

					case 'system':
					case 'core':
					default:
						$path = '';
					break;
				}

				// App
				$paths[] = $basea . $path . 'assets' . ($this->directory ? DS . $this->directory : '') . DS . $this->file();
				$paths[] = $basea . $path . ($this->directory ? $this->directory . DS : '') . $this->file();

				// Core
				$paths[] = $basec . $path . 'assets' . ($this->directory ? DS . $this->directory : '') . DS . $this->file();
				$paths[] = $basec . $path . ($this->directory ? $this->directory . DS : '') . $this->file();

				// Run through each path until we find one that works
				foreach ($paths as $path)
				{
					if (file_exists($path))
					{
						$this->paths['source'] = $path;
						break;
					}
				}
			}
		}
		return $this->paths['source'];
	}

	/**
	 * Get the override path
	 *
	 * @return  string
	 */
	public function overridePath()
	{
		if (!isset($this->paths['override']))
		{
			$this->paths['override']  = (\App::get('template')->protected ? PATH_CORE : PATH_APP) . DS . 'templates' . DS . \App::get('template')->template . DS . 'html';
			$this->paths['override'] .= DS . $this->extensionName() . DS . ($this->extensionType() == 'system' ? $this->type() . DS : '') . $this->file();
		}
		return $this->paths['override'];
	}

	/**
	 * Get the target path
	 *
	 * @return  string
	 */
	public function targetPath()
	{
		if (!isset($this->paths['target']))
		{
			if ($this->declaration)
			{
				$this->paths['target'] = '';
			}
			else
			{
				$this->paths['target'] = $this->sourcePath();

				if ($this->overridePath() && file_exists($this->overridePath()))
				{
					$this->paths['target'] = $this->overridePath();
				}
			}
		}

		return $this->paths['target'];
	}

	/**
	 * Get the last modified time for a file
	 *
	 * @return  integer
	 */
	public function lastModified()
	{
		$source = $this->targetPath();

		if ($this->declaration || !is_file($source))
		{
			return 0;
		}

		return filemtime($source);
	}

	/**
	 * Does asset exist?
	 *
	 * @return  boolean
	 */
	public function exists()
	{
		if ($this->isExternal())
		{
			return true;
		}

		if ($this->declaration && $this->name)
		{
			return true;
		}

		if ($this->targetPath() && file_exists($this->targetPath()))
		{
			return true;
		}

		return false;
	}

	/**
	 * Get public asset path
	 *
	 * @param   boolean  $timestamp  Append timestamp?
	 * @return  string
	 */
	public function link($timestamp=true)
	{
		$output = $this->targetPath();

		if (!$output)
		{
			return $output;
		}

		if ($this->isExternal())
		{
			return $output;
		}

		if ($this->extensionType() == 'system')
		{
			$relative = rtrim(str_replace('/administrator', '', \Request::base(true)), '/') . substr($output, strlen(PATH_ROOT));
		}
		else
		{
			$relative = rtrim(str_replace('/administrator', '', \Request::base(true)), '/') . substr($output, strlen(PATH_ROOT));
			$relative = rtrim(\Request::root(true), '/') . rtrim(substr($output, strlen(PATH_ROOT)), '/');
		}

		return $relative . ($timestamp ? '?v=' . $this->lastModified() : '');
	}

	/**
	 * Get target asset's content
	 *
	 * @return  string
	 */
	public function contents()
	{
		if ($this->declaration)
		{
			return $this->name;
		}

		return file_exists($this->targetPath()) ? file_get_contents($this->targetPath()) : '';
	}

	/**
	 * Convert to string
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) ($this->declaration ? $this->name : $this->link());
	}
}
