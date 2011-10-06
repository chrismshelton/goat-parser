<?php
/*	Copyright (c) 2011 by Chris Shelton
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a
 *	copy of this software and associated documentation files (the "Software"),
 *	to deal in the Software without restriction, including without limitation
 *	the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *	and/or sell copies of the Software, and to permit persons to whom the
 *	Software is furnished to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included
 *	in all copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 *	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *	IN THE SOFTWARE.
 *
 *	Based off Ian Piumarta's peg/leg C parser generators: http://piumarta.com/software/peg/
 */

class PackratSuspension
{
	public $begin, $end, $action;

	function __construct($begin, $end, $action)
	{
		$this->begin = $begin;
		$this->end = $end;
		$this->action = $action;
	}
}

class Packrat
{
	protected $buf, $pos, $begin, $end, $limit, $text, $suspensions, $sPos;
	public $list;

	public function beginRule($text)
	{
		$this->currentRule = $this->list->getRule($text);
		$this->list->push($this->currentRule);
	}

	public function setText($text)
	{
		$this->buf = str_split($text);
		$this->limit = count($this->buf);
		$this->begin = $this->end = $this->pos = $this->sPos = 0;
		$this->text = array();
		$this->suspensions = array();
		$this->list = new NodeList;
	}

	protected function matchDot()
	{
		if($this->pos >= $this->limit) return 0;
		$this->pos = $this->pos + 1;
		return 1;
	}

	protected function matchChar($c)
	{
		if($this->pos >= $this->limit) return 0;
		if($this->buf[$this->pos] === $c) {
			$this->pos += 1;
			return 1;
		}
		return 0;
	}

	protected function markBegin($returnValue = null)
	{
		$this->begin = $this->pos;
		return true;
	}

	protected function markEnd($returnValue = null)
	{
		$this->end = $this->pos;
		return true;
	}

	protected function matchString($str)
	{
		$sav = $this->pos;
		$s = str_split($str);
		$i = 0;
		$t = count($s);
		while($i < $t) {
			if($this->pos >= $this->limit) return 0;

			if($this->buf[$this->pos] != $s[$i]) {
				$this->pos = $sav;
				return 0;
			}
			$i += 1;
			$this->pos += 1;
		}
		return 1;
	}

	protected function matchClass($bits)
	{
		if($this->pos >= $this->limit) return 0;
		$c = ord($this->buf[$this->pos]);
		$bits = str_split($bits);

		if(ord($bits[$c >> 3]) & (1 << ($c & 7))) {
			$this->pos +=1;
			return 1;
		}

		return 0;
	}

	protected function suspend($action)
	{
		$this->suspensions[$this->sPos++] = new PackratSuspension($this->begin, $this->end, $action);
	}

	protected function done()
	{
		for($pos = 0; $pos < $this->sPos; $pos++) {
			$sus = $this->suspensions[$pos];
			$act = $sus->action;
			$text = $this->Text($sus->begin, $sus->end);
			$this->$act($text);
		}
		$this->sPos = 0;
	}

	protected function commit()
	{
		if($this->limit -= $this->pos)
			$this->buf = array_slice($this->buf, $this->pos);
		$this->begin -= $this->pos;
		$this->end -= $this->pos;
		$this->pos = $this->sPos = 0;
	}

	protected function accept($tp0)
	{
		if($tp0 > 0) {
			return 0;
		} else {
			$this->done();
			$this->commit();
		}
		return 1;
	}

	protected function set($text, $count)
	{
		die("not implemented yet");
	}

	protected function Text($begin, $end)
	{
		$this->text = implode(array_slice($this->buf, $begin, $end - $begin));
		return $this->text;
	}

	public function yyParseFrom($start)
	{
	        $ok = $this->$start();
	        if($ok)
	                $this->done();
	        $this->Commit();
	        return $ok;
	}

}
