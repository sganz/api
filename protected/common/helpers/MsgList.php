<?php
namespace common\helpers;

/**
 * Simple Message List helper class. It keeps a list of messages and allows
 * it to be output oldest first or newest first. This can be a bit tricky
 * as all items are added to the end of the list, so keep this in mind when
 * generating the output
 */

class MsgList
{
    const NEW_FIRST = 0;
    const OLD_FIRST = 1;
	const LINE_SEP = "\n";

    private $msg_list;
    private $line_numbers;
    private $output_order;
    private $line_sep;

    public function __construct()
    {
        $this->msg_list = [];
        $this->line_numbers = true;
        $this->line_sep = self::LINE_SEP;
        $this->output_order = self::NEW_FIRST;
    }

    /**
     * Returns a COPY of the list
     */

    public function getList()
    {
        return $this->msg_list;
    }

    /**
     * Sets line number mode for the stringify output
     *
     * @param bool $state If true then will build line numbers into string, false, no line numbers
     */

    public function setLineNumbers($state)
    {
        $this->line_numbers =  $state;
    }

	/**
	 * Gets state of line numbering
	 *
	 * @return bool True if line numbers enable, false if not.
	 */

    public function getLineNumbers()
    {
        return $this->line_numbers;
    }

	/**
	 * Sets the Line Seperator for stringinze
	 *
	 * @param string $str The line seperator ('\n', '</br>' or what ever)
	 */

    public function setLineSep($str)
    {
        $this->line_sep = $str;
    }

	/**
	 * Gets the Line Seperator used for stringinze
	 *
	 * @return string $str The current line seperator
	 */

    public function getLineSep()
    {
        return $this->line_sep;
    }

	/**
	 * Sets the Output Order for stringinze
	 *
	 * @param int $order (const NEW_FIRST or OLD_FIRST)
	 *
	 * Will only update if valid
	 */

    public function setOutputOrder($order)
    {
		if($order === self:: NEW_FIRST || $order === self::OLD_FIRST)
			$this->output_order = $order;
    }

	/**
	 * Gets the Output Order for stringinze
	 *
	 * @return int NEW_FIRST or OLD_FIRST
	 */

    public function getOutputOrder()
    {
        return $this->output_order;
    }

	/**
	 * Returns number of elements in list
	 *
	 * @return int The count of elements
	 */

	public function getCount()
	{
		return count($this->msg_list);
	}


    /**
     * Appends a String onto the back of the List
     *
     * @param string $str to append
     */

    public function append($str)
    {
        $this->msg_list[] = $str;
    }

    /**
    * Remove the last item appended to the list, if empty does nothing
    */

    public function remove()
    {
        array_pop($this->msg_list);
    }

	// if needed add push (to front) pop (off front) stuff

    /**
    * clear the list, does not affect other settings
    */

    public function clear()
    {
        $this->msg_list = [];
    }

    /**
    * Converts a stack into a flat string
    *
    * @return  string The list created as a string
    */

    public function stringify()
    {
        $str = '';

        // flip the list if displaying oldest last

        if($this->output_order == self::NEW_FIRST)
            $this->msg_list = array_reverse($this->msg_list);

        $i = 1;
        foreach($this->msg_list as $line)
            $str .= (($this->line_numbers)? $i++ .  '. ' : '') . $line . $this->line_sep;

        return $str;
    }

}
