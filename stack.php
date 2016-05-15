<?php
/**
* Simple and efficient disk-based stack implementation
*
* Implements simple a disk-based stack that scales elegantly
* to millons or billions of entries.
*
* @author	Marty Anstey (https://marty.anstey.ca/)
* @license	AGPL v3 (http://www.gnu.org/licenses/agpl-3.0.txt)
* @version	2.0
* @copyright	Marty Anstey, July 12 2013
*
*/

class stack {
	private $fp, $enc;
	
	public function __construct($fn, $bits=32) {
		switch ($bits) {
			case 64:
				$this->enc = array(8, 'P');
				break;
			case 32:						// defaults to 32 bit platforms
			default:						// for compatibility
				$this->enc = array(4, 'V');
		}
		$fexists = file_exists($fn);
		if (!$this->fp = @fopen($fn, 'c+b'))
			throw new Exception("Can't create or open file");
		if (!$fexists) {						// initialize new stack file
			fwrite($this->fp, pack($this->enc[1], $this->enc[0]), $this->enc[0]);
		}
	}

	public function __destruct() {
		if (is_resource($this->fp)) fclose($this->fp);
	}

	/**
	* Pushes a new entry onto the stack
	*
	* @param string $data		Data to add to the queue
	* @return bool			Always returns TRUE
	*/
	public function push($str) {
		rewind($this->fp);
		$opos = unpack($this->enc[1], fread($this->fp, $this->enc[0]))[1];
		fseek($this->fp, 0, SEEK_END);
		$pos = ftell($this->fp);
		fwrite($this->fp, pack($this->enc[1], $opos).($s=rtrim($str, "\n"))."\n", ($this->enc[0]+strlen($s)+1));
		rewind($this->fp);
		fwrite($this->fp, pack($this->enc[1], $pos), $this->enc[0]);
		return TRUE;
	}

	/**
	* Pops an entry off the top of the stack
	*
	* @return string|bool		Returns a string, or FALSE if stack is empty
	*/
	public function pop() {
		rewind($this->fp);
		$ofs = unpack($this->enc[1], fread($this->fp, $this->enc[0]))[1];
		if ($ofs<=$this->enc[0]) return FALSE;
		fseek($this->fp, $ofs, SEEK_SET);
		$opos = unpack($this->enc[1], fread($this->fp, $this->enc[0]))[1];
		$str = fgets($this->fp);
		ftruncate($this->fp, $ofs);
		rewind($this->fp);
		fwrite($this->fp, pack($this->enc[1], $opos), $this->enc[0]);
		return rtrim($str,"\n");
	}

}
