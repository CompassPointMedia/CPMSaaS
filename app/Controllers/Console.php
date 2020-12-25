<?php namespace App\Controllers;

class Console extends BaseController {

	public function index()
	{
		echo view('console/index');
	}

	public function contacts(){
	    echo view('console/contacts');
    }
}
