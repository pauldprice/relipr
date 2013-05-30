<?php

require_once('BasicResource.php');
require_once('ListDTO.php');

use Tonic\Response,
	Tonic\NotFoundException;

/**
 * This class defines the list estimate resource - used to get estimated list counts
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/estimate
 */
class Estimate extends BasicResource{

	/**
	 * Get an estimate for a given criteria and filter
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function get(){
		// Get the criteria filter
		if(!isset($_GET['filter']) || trim($_GET['filter']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'filter'");

		$filter = ListDTO::decodeObject($_GET['filter']);

		// Try to find the list and send it back as the response
		$count = $this->db->getFilterCount($filter, $this->medium, $this->brandkey, $this->criteriaid);
		return new Response(Response::OK, $count);
	}

}

