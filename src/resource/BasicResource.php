<?php

require_once("../src/lib/DB.php");

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException,
		Tonic\UnauthorizedException,
		Tonic\NotFoundException,
		Tonic\Application,
		Tonic\Request;

/**
 * Resources that inherit from this class will prompt for basic authentication and
 *  will validate some of the standard path params
 * User is 'test' password is 'letmein'
 * ** NOT REAL SECURITY - JUST A DEMONSTRATION **
 */
abstract class BasicResource extends Resource {

	// Override the constructor so that we can jam in some pre-conditions
	public function __construct(Application $app, Request $request, array $urlParams)
	{
		// Don't forget to call the original constructor
		parent::__construct($app, $request, $urlParams);
	}
	
	protected function auth()
	{
		$this->before(array($this, 'runBasicAuth'));
	}

	protected function runBasicAuth($request, $methodName)
	{
		// Make sure that the browser is sending basic auth
		if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) ||
			$_SERVER['PHP_AUTH_USER'] != 'test' || $_SERVER['PHP_AUTH_PW'] != 'letmein')
			throw new UnauthorizedException;
	}

	protected function valid()
	{
		$this->before(array($this, 'runValidation'));
	}

	protected function runValidation($request, $methodName)
	{
		// If the medium parameter is set, make sure that it is a valid one
		if(isset($this->params['medium'])) {
			$medium = $this->params['medium'];
			if(!array_key_exists($medium, $this->db->getMediums()))
				throw new NotFoundException("Unable to find any resources with medium '$medium'");
		}

		// If the brand parameter is set, make sure it is a brand that we know about
		if(isset($this->params['brandkey'])) {
			$brandkey = $this->params['brandkey'];
			if(!in_array($brandkey, $this->db->getBrands()))
				throw new NotFoundException("Unable to find any brand with key '$brandkey'");
		}

		// If the affiliate parameter is set, make sure that it has records in this brand
		if(isset($this->params['affiliateid'])) {
			if(!$brandkey)
				throw new NotFoundException("Unable to find affiliate with no brand specified");
			$affiliateid = $this->params['affiliateid'];
			if($this->db->getAffiliate($brandkey, $affiliateid) == NULL)
				throw new NotFoundException("Unable to find affiliate '$affiliateid' in brand '$brandkey'");
		}

	}

	// Methods that use the @json annotation will get json formatted results
	protected function json() {
		$this->after(array($this, 'formatJSON'));
	}

	protected function formatJSON($response) {
	  $response->contentType = "application/json";
		$response->body = $this->indent(json_encode($response->body));
	}

	// Set up a magical getter for the db connections
	public function __get($name) {
		if($name == 'db') {
			return DB::getInstance();
		}

		return parent::__get($name);
	}

	/* This is _NOT_ something you would do in production =)
		Create a view class with a render method that invokes the given view script
		From a resource class call it like this: $this->getView('index')->render();
		Or, make data from the resource available to the view like this:
		 // In the resource method, send the view a string
		 $view = $this->getView('index');
		 $view->data = "Some data for the view.";
		 $view->render();

		 // In the view script, pull out the data
		 echo "<b>" . $this->data . "</b>";
	*/
	protected function getView($page) {
		$vpath = realpath("../src/view/$page.php");
		$cname = "View_$page" . uniqid();
		$classdef = <<<CDEF
			class $cname {
				public function render() {
					ob_start();
					require_once "$vpath";
					ob_end_flush();
					exit();
				}
			}
CDEF;
		eval($classdef);
		return new $cname;
	}

	/**
	 * I copied this from "http://www.daveperrett.com/articles/2008/03/11/format-json-with-php/"
   * Newer versions of PHP have formatting built into json_encode...
	 * Indents a flat JSON string to make it more human-readable.
	 * @param string $json The original JSON string to process.
	 * @return string Indented version of the original JSON string.
	 */
	function indent($json) {

			$result      = '';
			$pos         = 0;
			$strLen      = strlen($json);
			$indentStr   = '  ';
			$newLine     = "\n";
			$prevChar    = '';
			$outOfQuotes = true;

			for ($i=0; $i<=$strLen; $i++) {

					// Grab the next character in the string.
					$char = substr($json, $i, 1);

					// Are we inside a quoted string?
					if ($char == '"' && $prevChar != '\\') {
							$outOfQuotes = !$outOfQuotes;

					// If this character is the end of an element,
					// output a new line and indent the next line.
					} else if(($char == '}' || $char == ']') && $outOfQuotes) {
							$result .= $newLine;
							$pos --;
							for ($j=0; $j<$pos; $j++) {
									$result .= $indentStr;
							}
					}

					// Add the character to the result string.
					$result .= $char;

					// If the last character was the beginning of an element,
					// output a new line and indent the next line.
					if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
							$result .= $newLine;
							if ($char == '{' || $char == '[') {
									$pos ++;
							}

							for ($j = 0; $j < $pos; $j++) {
									$result .= $indentStr;
							}
					}

					$prevChar = $char;
			}

			return $result;
	}
	
}

