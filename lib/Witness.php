<?php
/**
 * Interface to print to stream or keep track of information
 * ...not exactly an Observer pattern
 */
class Witness
{
	private $messages = '';

	/**
	 * Echo $msg in $color
	 * @param $eol Print end of line
	 */
	public function report($msg = '', $color = null, $eol = true)
	{
		$msg = $color ? Escape_Colors::fg_color($color, $msg) : $msg;
		$msg = $eol ? $msg . PHP_EOL : $msg;

		echo $msg;
	}

	/**
	 * Track $msg
	 */
	public function track($msg)
	{
		$this->messages .= $msg;
	}

	/**
	 * Get all messages tracked thus far
	 */
	public function get_messages()
	{
		return $this->messages;
	}
}
