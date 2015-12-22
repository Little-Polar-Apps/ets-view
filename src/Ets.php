<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart
 * @author      Andrew Smith
 * @link        http://www.slimframework.com
 * @copyright   2013 Josh Lockhart
 * @version     0.1.2
 * @package     SlimViews
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/**
 * ETSView
 *
 * The ETSView is a custom View class that renders templates using the ETS
 * easy template system (http://littlepolarapps.com).
 *
 * Two fields that you, the developer, will need to change are:
 * - parserDirectory
 * - parserCompileDirectory
 * - parserCacheDirectory
 *
 * @package Ets
 * @author  Ian Tearle (http://iantearle.com)
 */

namespace Slim\Views;

use Psr\Http\Message\ResponseInterface;

class Ets implements \ArrayAccess {

	/**
     * Default view variables
     *
     * @var array
     */
    protected $defaultVariables = [];

    /**
     * @var string The path to the Smarty code directory WITHOUT the trailing slash
     */
    public $parserDirectory = null;

    /**
     * @var string The path to the Smarty compiled templates folder WITHOUT the trailing slash
     */
    public $parserCompileDirectory = null;

    /**
     * @var string The path to the Smarty cache folder WITHOUT the trailing slash
     */
    public $parserCacheDirectory = null;

    /**
     * @var parserInstance persistent instance of the Parser object.
     */
    private $parserInstance = null;


	/**
	 * user_vars
	 *
	 * (default value: array())
	 * @var array
	 * @access public
	 */
	public $user_vars = array('menu' => array(), 'header' => array(), 'main' => array(), 'footer' => array(), 'loops' => array());

	private $header = null;

	private $footer = null;

	private $menu = null;

	private $content = null;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @param mixed $user_vars
	 * @return void
	 */
	public function __construct() {
		parent::__construct();


//		$this->user_vars = $user_vars;
	}


    /**
     * Render Template
     *
     * This method will output the rendered template content
     *
     * @param string $template The path to the template, relative to the  templates directory.
     * @param null $data
     * @return string
     */
	public function render(ResponseInterface $response, $template, $data = null) {

		$themetemplates = ETS_TEMPLATESPATH;//\_ets::getTemplatesDirectory();
		if(!class_exists('\_ets')) {
			if (!is_dir($this->parserDirectory)) {
                throw new \RuntimeException('Cannot set the Ets lib directory : ' . $this->parserDirectory . '. Directory does not exist.');
            }
            require_once $this->parserDirectory . '/src/ets.php';
		}

		$this->header = $this->inject_variables($this->header, $this->user_vars['header']);
		$this->header = $this->inject_variables($this->header, $this->user_vars['loops']);
		$this->footer = $this->inject_variables($this->footer, $this->user_vars['footer']);
		$this->footer = $this->inject_variables($this->footer, $this->user_vars['loops']);
		$this->menu = $this->inject_variables($this->menu, $this->user_vars['menu']);
		$this->menu = $this->inject_variables($this->menu, $this->user_vars['loops']);
		$this->content->content = $this->content;
		$this->content = $this->inject_variables($this->content, $this->user_vars['main']);
		$this->content = $this->inject_variables($this->content, $this->user_vars['loops']);

		if(isset($this->nav) && $this->nav && $this->nav->total > 0) {
			$nav = $this->nav;
			$array['pages'] = $nav;
			$array['pages']->next = $nav->next('<li><a href="{path}/page/{nr}" class="next">&raquo;</a></li>','<li class="disabled"><a href="{path}/page/{nr}">&raquo;</a></li>');
			$array['pages']->prev = $nav->previous('<li><a href="{path}/page/{nr}" class="prev">&laquo;</a></li>','<li class="disabled"><a href="{path}/page/{nr}">&laquo;</a></li>');
			$array['pages']->numbers = $nav->numbers('<li><a href="{path}/page/{nr}" class="number">{nr}</a></li>', '<li class="disabled"><a href="{path}/page/{nr}">{nr}</a></li>');
			$array['pages']->first = $nav->first('<a href="{path}/page/{nr}" class="first">First</a>');
			$array['pages']->last = $nav->last('<a href="{path}/page/{nr}" class="last">Last</a>');
			$array['pages']->info = $nav->info(' Page {page} of {pages} ');
			$this->content = $this->inject_variables($this->content, $array);
		}

		if(!is_array($template)) {

			$response->getBody()->write(\_ets::sprintt($this->header, "$themetemplates/@header.tpl.html") . \_ets::sprintt($this->menu, "$themetemplates/@menu.tpl.html") . \_ets::sprintt($this->content, "$themetemplates/@$template") . \_ets::sprintt($this->footer, "$themetemplates/@footer.tpl.html"));

		} else {

			$template = $template[0];

			$response->getBody()->write(\_ets::sprintt($this->content, "$themetemplates/@$template"));

		}

		return $response;
	}

	public function add_loop($arg, $name) {
		$nav = $this->data->{$name};
		if(is_array($arg)) {
			$loop[$name] = $nav;
			foreach($arg as $k => $v) {
				$loop[$name][$k] = $v;
			}
		}
		$this->content->{$name} = (isset($this->user_vars['main'][$name])) ? array_merge($this->user_vars['main'][$name],$loop) : $loop;

	}



	/**
	 * make_header function.
	 *
	 * @access public
	 * @param mixed $header
	 * @return void
	 */
	public function make_header($header) {

		$themetemplates = $this->getTemplatesDirectory();

		$this->header = $header;

	}


	/**
	 * make_footer function.
	 *
	 * @access public
	 * @param mixed $footer
	 * @return void
	 */
	public function make_footer($footer) {

		$themetemplates = $this->getTemplatesDirectory();

		$footer = $this->inject_variables($footer, $this->user_vars['footer']);
		$footer = $this->inject_variables($footer, $this->user_vars['loops']);

		$this->footer = $footer;

	}

	public function make_content($content) {

		$this->content = $content;

	}


	/**
	 * make_menu function.
	 *
	 * @access public
	 * @param mixed $menu
	 * @return void
	 */
	public function make_menu($menu) {

		$themetemplates = $this->getTemplatesDirectory();

		$menu = $this->inject_variables($menu, $this->user_vars['menu']);
		$menu = $this->inject_variables($menu, $this->user_vars['loops']);

		$this->menu = $menu;

	}


	/**
	* Inserts the variables from $var into the template object $tpl_obj
	* @param object $tpl_obj
	* @param array $vars
	* @return object $tpl_obj
	*/
	public function inject_variables($tpl_obj, $vars) {
		if(!empty($vars) && is_array($vars)) {
			foreach ($vars as $ind => $val) {
				if(is_array($val)) {
					if(!isset($tpl_obj->{$ind})) {
						global $user_vars;
						$tpl_obj->{$ind} = (!isset($user_vars['loops'][$ind])) ? array() : $user_vars['loops'][$ind];
					}
					foreach($tpl_obj->{$ind} as $ev_i => $ev_v) {
						foreach ($val as $ni => $nv) {
							if(is_int($ni)){continue;}
							$tpl_obj->{$ind}[$ev_i]->{$ni} = $nv;
						}
					}
					continue;
				}
				$tpl_obj->{$ind} = $val;
			}
		}
		return $tpl_obj;
	}

	/********************************************************************************
     * ArrayAccess interface
     *******************************************************************************/
    /**
     * Does this collection have a given key?
     *
     * @param  string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->defaultVariables);
    }
    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->defaultVariables[$key];
    }
    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     */
    public function offsetSet($key, $value)
    {
        $this->defaultVariables[$key] = $value;
    }
    /**
     * Remove item from collection
     *
     * @param string $key The data key
     */
    public function offsetUnset($key)
    {
        unset($this->defaultVariables[$key]);
    }
    /********************************************************************************
     * Countable interface
     *******************************************************************************/
    /**
     * Get number of items in collection
     *
     * @return int
     */
    public function count()
    {
        return count($this->defaultVariables);
    }
    /********************************************************************************
     * IteratorAggregate interface
     *******************************************************************************/
    /**
     * Get collection iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->defaultVariables);
    }

}
