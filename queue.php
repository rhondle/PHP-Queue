<?php
/**
* Simple and efficient disk-based queue implementation
*
* Provides a simple disk-based queue that can easily support
* millions or even billions of entries.
*
* The queue file must be periodically "vacummed" to remove
* stale entries, otherwise the file will grow indefinitely.
*
* @author	Marty Anstey (https://marty.anstey.ca/)
* @license	AGPL v3 (http://www.gnu.org/licenses/agpl-3.0.txt)
* @version	2.0
* @copyright	Marty Anstey, July 15 2013
*
*/
class queue {
	private $fp, $fn, $enc;

	public function __construct($fn, $bits=32) {
		$this->fn = $fn;
		switch ($bits) {
			case 64:
				$this->enc = array(8, 'P');
				break;
			case 32:					// defaults to 32 bit platforms
			default:					// for compatibility
				$this->enc = array(4, 'V');
		}
		if (!$this->open($fn)) throw new Exception("Can't create or open queue file");
	}
	
	public function __destruct() {
		if (is_resource($this->fp)) fclose($this->fp);
	}

	/**
	* Adds a new item to the queue
	*
	* @param string|array $data	Data to add to the queue
	* @return bool			Always returns TRUE
	*/
	public function add($data) {
		fseek($this->fp, 0, SEEK_END);
		if (is_array($data)) {
			foreach ($data as $str) {
				fwrite($this->fp, rtrim($str)."\n");
			}
		}
		else
			fwrite($this->fp, rtrim($data)."\n");
		return TRUE;
	}

	/**
	* Fetches one or more entries from the head of the queue
	*
	* @param integer $count		Number of queue items to return
	* @return mixed			False on error, string if a single result, or an array if $count>1
	*/
	public function get($count=1) {
		$res = array();
		if ($count<1) return FALSE;
		rewind($this->fp);
		$ofs = unpack($this->enc[1], fread($this->fp, $this->enc[0]))[1];
		//if ($ofs>=filesize($this->fn)) {}			// TODO
		fseek($this->fp, $ofs, SEEK_SET);
		for ($i=0; $i<$count; $i++) {
			$str = fgets($this->fp);			// read a string
			if (feof($this->fp))
				break;
			else
				$res[$i] = rtrim($str);
		}
		$new_ofs = pack($this->enc[1], ftell($this->fp));
		fseek($this->fp, 0, SEEK_SET);
		fwrite($this->fp, $new_ofs, $this->enc[0]);		// update ptr with new offset
		return ($count==1)?rtrim($str):$res;
	}

	/**
	* Removes stale entries from the queue
	*
	* The queue will continually grow in size unless trimmed using this
	* function. While this function is somewhat expensive to run, it
	* only needs to be called infrequently; eg whenever the number of
	* stale entries or the file size savings exceed a given threshold.
	*
	* @return bool			TRUE on success, FALSE on failure
	* @todo				Implement check to abort if stale queue entries < n
	*/
	public function vacuum() {
		fclose($this->fp);
		$fp = fopen($this->fn, 'rb');
		$tmp = fread($fp, $this->enc[0]);
		$ofs = unpack($this->enc[1], $tmp)[1];
		if ($ofs>=filesize($this->fn)) {			// if the queue is empty
			fclose($fp);					// then just
			unlink($this->fn);				// delete the file
			return TRUE;
		}
		$fp2 = fopen($this->fn.'.tmp', 'wb');
		$this->add_header($fp2);
		if (stream_copy_to_stream($fp, $fp2, -1, $ofs)) {
			fclose($fp);
			fclose($fp2);
			unlink($this->fn);				// delete original file
			rename($this->fn.'.tmp', $this->fn);		// move the temporary one in it's place
			return $this->open($this->fn);
		}
		else
		{
			fclose($fp);
			fclose($fp2);
			return FALSE;
		}
	}

	/**
	* Opens and queue file and initializes it if new
	*
	* @param string $fn		Queue filename
	* @return bool			TRUE on success, FALSE on error
	*/
	private function open($fn) {
		$fexists = file_exists($fn);
		if (!$this->fp = @fopen($fn, 'c+b')) {			// mode: create+read+write
			return FALSE;
		}
		else
		{
			if (!$fexists) $this->add_header($this->fp);	// add header if creating a new file
			return TRUE;
		}
	}
	
	/**
	* Adds an header to a new queue file
	*
	* @param resource $fp		Handle of previously opened file
	* @returns bool			Always returns TRUE
	*/
	private function add_header(&$fp) {
		fwrite($fp, pack($this->enc[1], $this->enc[0]), $this->enc[0]);
		return TRUE;
	}

}
