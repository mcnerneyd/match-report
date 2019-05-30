<?php
class Controller_Help extends Controller_Rest
{
	public function before() {
		if (!\Auth::has_access('help.*')) throw new HttpNoAccessException;

		parent::before();
	}

	public function get_index() {
		$id = \Input::param("id");

		$filename = APPPATH."/help/$id.md";

		$text = file_get_contents($filename);

		echo Markdown::parse($text);
	}
}
