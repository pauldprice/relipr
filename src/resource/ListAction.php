<?php

require_once('BasicResource.php');

use Tonic\Response;

/**
 * This class defines the actions available on a list
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
		switch($this->command) {
			case 'submit': return $this->submitList();
			case 'cancel': return $this->cancelList();
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
		switch($this->command) {
			case 'download': return $this->downloadList();
			default:
				throw new NotFoundException("List action '{$this->command}' not found");
		}
	}

	private function submitList() {
		$list = $this->db->submitList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		return new Response(Response::OK, $list);
	}

	private function cancelList() {
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		$this->db->cancelList($list);
		return new Response(Response::OK, $list);
	}

	private function downloadList() {
		$list = $this->db->getList($this->medium, $this->brandkey, $this->criteriaid, $this->listid);
		$filePath = "/tmp/list_{$this->listid}.csv";
		if(!file_exists($filePath))
			$this->db->pullList($list, $filePath);

		header('Content-Type: application/csv');
		header("Content-Disposition: attachment; filename=list{$list->listid}.csv");

		readfile($filePath);
		exit;
	}

}
