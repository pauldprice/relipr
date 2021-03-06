<?php
/*
	This class defines the actions available on a list.
	A list may be submitted, canceled or downloaded.
*/

require_once('BasicResource.php');
require_once('ResultDTO.php');

use Tonic\Response,
		Tonic\NotFoundException;

/**
 * @uri /medium/:medium/brand/:brandkey/criteria/:criteriaid/list/:listid/:command
 */
class ListAction extends BasicResource{

	/**
	 * Dispatch a list POST action
	 * @method POST
	 * @auth
	 * @valid
	 * @json
	 */
	public function dispatchPost(){
		// Determine which action to take based on the :command parameter in the URL
		switch($this->command) {
			case 'submit': return $this->submitList();
			case 'cancel': return $this->cancelList();
			case 'result': return $this->postResults();
			default:
				throw new NotFoundException("List action '{$this->command}' not found");
		}
	}

	/**
	 * Dispatch a list GET action
	 * @method GET
	 * @auth
	 * @valid
	 * @json
	 */
	public function dispatchGet() {
		// Determine which action to take based on the :command parameter in the URL
		switch($this->command) {
			case 'download': return $this->downloadList();
			case 'count': return $this->countList();
			default:
				throw new NotFoundException("List action '{$this->command}' not found");
		}
	}

	// Try to submit the list
	private function submitList() {
		$list = $this->db->submitList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $list);
	}

	// Try to cancel the list
	private function cancelList() {
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		$this->db->cancelList($list);
		return new Response(Response::OK, $list);
	}

	// Try to download the list
	private function downloadList() {
		if($this->listid != 'test') {
			$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);

			// Make sure that the requested list is ready to be downloaded
			if($list->status != ListDTO::STATUS_LISTREADY)
				return new Response(Response::BADREQUEST, 'This list is not ready for download');
		} else {
			$list = new ListDTO();
			$list->listid = 0;
			$list->brandkey = $this->brandkey;
			$list->medium = $this->medium;
			$list->criteriaid = $this->criteriaid;
			$list->requestedcount = 100;
			$list->setFilter('{}');
		}

		// We'll cache the generated csv in a temporary file
		$filePath = "/tmp/list_{$this->listid}.csv";
		// If the file doesn't already exist, then create it
		$this->db->pullList($list, $filePath);


		// Send the file download response and exit
		header('Content-Type: application/csv');
		header("Content-Disposition: attachment; filename=list{$list->listid}.csv");
		readfile($filePath);
		unlink($filePath);
		exit;
	}

	// Return a list count
	private function countList() {
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $this->db->getListCount($list));
	}

	// Post results
	private function postResults() {
		// Get the results array
		if(!isset($_POST['results']) || trim($_POST['results']) == '')
			return new Response(Response::BADREQUEST, "Missing or empty 'results'");

		// Parse the results array (decodeObject should be moved to a utility function)
		$results = ListDTO::decodeObject($_POST['results']);

		// Validate each of the results and pretend to do something with them
		$processed = 0;
		foreach($results as $resultArr) {
			$result = ResultDTO::fromArray($resultArr);
			$this->db->saveResult($result);
			$processed++;
		}

		return new Response(Response::OK, "Processed $processed results");
	}

}

