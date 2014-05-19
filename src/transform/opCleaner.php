<?php

namespace Goat;

//error_reporting (E_ALL);

class OpCleaner
{
	public function cleanupOps (State $state, Op $op)
	{
		$this -> state = $state;
		$this -> labels = array();
		$this -> labelEdges = array();
		$this -> labelStack = array();
		$this -> labelSize = array();
		$this -> labelIsCyclic = array();
		$this -> labelIsInlinable = array();
		$this -> labelOps = array();
		$this -> labelsOrdered = array();
		$this -> cleanupStackFrames = array();
		$this -> cleanupStack = array();
		$this -> varsRead = new \SplObjectStorage;
		$this -> varsWritten = new \SplObjectStorage;

		if ($op -> getOpType() !== Op::OP_LABEL)
		{
			$startOp = new OpLabel (new Label ($state -> getUniqueName ("oc")), $op);
			$addedOp = true;
		}
		else
		{
			$startOp = $op;
			$addedOp = false;
		}

		$this -> initLabel = $startOp -> getLabelName();
		$this -> labels[$startOp -> getLabelName()] = $startOp;
		$this -> labelStack[] = $startOp;
		$this -> buildOpInfo();
		$this -> cleanupLabels();
		$resultOp = $this -> cleanupOp ($startOp);

		if ($addedOp)
		{
			return $resultOp -> thenExpr;
		}

		return $resultOp;
	}

	public function buildOpInfo()
	{
		while (sizeof ($this -> labelStack) > 0)
		{
			$currentLabelOp = array_pop ($this -> labelStack);
			$this -> currentLabelName = $currentLabelOp -> getLabelName();
			$this -> labelsOrdered[] = $this -> currentLabelName;
			$this -> labelEdges[$this -> currentLabelName] = array();
			$this -> labelOps[$this -> currentLabelName] = array ($currentLabelOp);
			$this -> labelSize[$this -> currentLabelName] = 0;
			$this -> labelIsInlinable[$this -> currentLabelName] = true;
			$this -> labelIsCyclic[$this -> currentLabelName] = false;

			$this -> opStack = array ($currentLabelOp -> thenExpr);

			while (sizeof ($this -> opStack) > 0)
			{
				$this -> labelSize[$this -> currentLabelName] += 1;
				$currentOp = array_pop ($this -> opStack);

				if ($currentOp -> getOpType() !== Op::OP_LABEL)
				{
					$this -> labelOps[$this -> currentLabelName][] = $currentOp;
				}

				$stackPos = sizeof ($this -> opStack);

				switch ($currentOp -> getOpType())
				{
					/*
					case Op::OP_COND:
						$this -> buildInfoCond ($currentOp);
					break;
					*/
					case Op::OP_COND:
					case Op::OP_MATCH:
					case Op::OP_RULE:
						$this -> labelIsInlinable[$this -> currentLabelName] = false;
						$this -> opStack[] = $currentOp -> successExpr;
						$this -> opStack[] = $currentOp -> failExpr;
					break;

					case Op::OP_COMMENT:
					case Op::OP_DEBUG:
					case Op::OP_ERROR:
					case Op::OP_SET:
					case Op::OP_SET_CONST:
					case Op::OP_SET_SUBSTR:
					case Op::OP_INCR:
					case Op::OP_GET_MATCH_RESULT:
						$this -> opStack[] = $currentOp -> thenExpr;
					break;

					case Op::OP_NOP:
					case Op::OP_RETURN_CONST:
					break;

					//case Op::OP_GET_MATCH_RESULT:
					//	return $this -> buildInfoGetMatchResult ($currentOp);
					//break;
		
					//case Op::OP_INCR:
					//	return $this -> buildInfoIncr ($currentOp);
					//break;

					case Op::OP_LABEL:
						$this -> buildInfoLabel ($currentOp);
					break;

					/*
					case Op::OP_MATCH:
						$this -> buildInfoMatch ($currentOp);
					break;
		
					case Op::OP_RULE:
						$this -> buildInfoRule ($currentOp);
					break;
					*/

					/*
					case Op::OP_SET:
						return $this -> buildInfoSet ($currentOp);
					break;
		
					case Op::OP_SET_SUBSTR:
						return $this -> buildInfoSetSubstr ($currentOp);
					break;
					*/
		
					case Op::OP_THUNK:
						//$this -> buildInfoThunk ($currentOp);
						$this -> opStack[] = $currentOp -> thenExpr;
					break;

					default:
						throw new \InvalidArgumentException ("Unknown type ".get_class ($currentOp));
					break;
				}
			}
		}
	}

	protected function buildInfoCond (OpCond $opCond)
	{
		$this -> labelIsInlinable[$this -> currentLabelName] = false;
		$this -> opStack[] = $opCond -> successExpr;
		$this -> opStack[] = $opCond -> failExpr;
	}

	protected function buildInfoLabel (OpLabel $opLabel)
	{
		if (!array_key_exists ($opLabel -> getLabelName(), $this -> labels))
		{
			$this -> labels[$opLabel -> getLabelName()] = $opLabel;
			$this -> labelStack[] = $opLabel;
		}

		if ($this -> currentLabelName === $opLabel -> getLabelName())
		{
			$this -> labelIsCyclic[$this -> currentLabelName] = true;
		}
		else
		{
			$this -> labelEdges[$this -> currentLabelName][$opLabel -> getLabelName()] = 1;
		}
	}

	protected function buildInfoMatch (OpMatch $opMatch)
	{
		$this -> labelIsInlinable[$this -> currentLabelName] = false;
		$this -> opStack[] = $opMatch -> successExpr;
		$this -> opStack[] = $opMatch -> failExpr;
	}

	protected function buildInfoRule (OpRule $opRule)
	{
		$this -> labelIsInlinable[$this -> currentLabelName] = false;
		$this -> opStack[] = $opRule -> successExpr;
		$this -> opStack[] = $opRule -> failExpr;
	}

	/*
	protected function buildInfoSet (OpSet $opSet)
	{
		$this -> opStack[] = $opSet -> thenExpr;
	}

	protected function buildInfoSetSubstr (OpSetSubstr $opSetSubstr)
	{
		$this -> opStack[] = $opSetSubstr -> thenExpr;
	}
	*/

	protected function buildInfoThunk (OpThunk $opThunk)
	{
		//$this -> labelIsInlinable[$this -> currentLabelName] = false;
		$this -> opStack[] = $opThunk -> thenExpr;
	}

	public function cleanupLabels()
	{
		$this -> cleanOps = new \SplObjectStorage;

		foreach ($this -> labels as $ordered => $label)
		{
			$lazyLabel = new OpLabelLazy ($label -> label);
			$this -> cleanOps[$label] = $lazyLabel;
			$lazy[$ordered] = $lazyLabel;
		}

		while (sizeof ($this -> labelsOrdered) > 0)
		{
			$this -> currentLabelName = array_pop ($this -> labelsOrdered);
			$opStack = $this -> labelOps[$this -> currentLabelName];

			while (sizeof ($opStack) > 0)
			{
				$currentOp = array_pop ($opStack);
				$newOp = $this -> cleanupSingleOp ($currentOp);
				$this -> cleanOps[$currentOp] = $newOp;
				//echo "(".sizeof ($this -> labelsOrdered).", ".sizeof ($opStack).")\n";
			}
		}

		foreach ($this -> labels as $ordered => $label)
		{
			$result = $this -> cleanOps[$label -> thenExpr];

			if ($result === NULL)
			{
				var_dump ($label -> thenExpr);
			}

			$lazy[$ordered] -> setNextOp ($this -> cleanOps[$label -> thenExpr]);
		}
	}

	public function cleanupOp (Op $op)
	{
		if (!$this -> cleanOps -> contains ($op))
		{
			//return $this -> cleanupSingleOp ($op);
			//var_dump ($op);
			echo ("Contains? ".$this -> cleanOps -> contains ($op -> thenExpr))."\n";
			throw new \RuntimeException ("Not found");
		}

		return $this -> cleanOps[$op];
	}

	public function cleanupSingleOp (Op $op)
	{
		switch ($op -> getOpType())
		{
			case Op::OP_COND:
				return $this -> cleanupCond ($op);
			break;

			case Op::OP_COMMENT:
				return $this -> cleanupComment ($op);
			break;

			case Op::OP_DEBUG:
				return $this -> cleanupDebug ($op);
			break;

			case Op::OP_ERROR:
				return $this -> cleanupError ($op);
			break;

			case Op::OP_GET_MATCH_RESULT:
				return $this -> cleanupGetMatchResult ($op);
			break;

			case Op::OP_INCR:
				return $this -> cleanupIncr ($op);
			break;

			case Op::OP_LABEL:
				return $this -> cleanupLabel ($op);
			break;

			case Op::OP_MATCH:
				return $this -> cleanupMatch ($op);
			break;

			case Op::OP_NOP:
				$this -> cleanupNop ($op);
			break;

			case Op::OP_RETURN_CONST:
				return $this -> cleanupReturnConst ($op);
			break;

			case Op::OP_RULE:
				return $this -> cleanupRule ($op);
			break;

			case Op::OP_SET:
				return $this -> cleanupSet ($op);
			break;

			case Op::OP_SET_CONST:
				return $this -> cleanupSetConst ($op);
			break;

			case Op::OP_SET_SUBSTR:
				return $this -> cleanupSetSubstr ($op);
			break;

			case Op::OP_THUNK:
				return $this -> cleanupThunk ($op);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Unknown op type %d (class %s)", $op -> getOpType(), get_class ($op)));
			break;
		}
	}

	public function cleanupCond (OpCond $opCond)
	{
		$successExpr = $this -> cleanupOp ($opCond -> successExpr);
		$failExpr = $this -> cleanupOp ($opCond -> failExpr);
		return new OpCond ($opCond -> expr, $successExpr, $failExpr);
	}

	public function cleanupComment (OpComment $opComment)
	{
		$scanExpr = $opComment -> thenExpr;
		$commentLines = $opComment -> lines;

		while ($scanExpr -> getOpType() === Op::OP_COMMENT)
		{
			$commentLines = array_merge ($commentLines, $scanExpr -> lines);
			$scanExpr = $scanExpr -> thenExpr;
		}

		$thenExpr = $this -> cleanupOp ($scanExpr);

		return new OpComment ($commentLines, $thenExpr);

		/*
		if ($thenExpr -> getOpType() === Op::OP_COMMENT)
		{
			return new OpComment (array_merge ($opComment -> lines, $thenExpr -> lines), $thenExpr -> thenExpr);
		}
		else
		{
			return new OpComment ($opComment -> lines, $thenExpr);
		}
		*/
	}

	public function cleanupDebug (OpDebug $opDebug)
	{
		$thenExpr = $this -> cleanupOp ($opDebug -> thenExpr);
		return new OpDebug ($opDebug -> debugType, $opDebug -> args, $thenExpr);
	}

	public function cleanupError (OpError $opError)
	{
		$thenExpr = $this -> cleanupOp ($opError -> thenExpr);
		return new OpError ($opError -> errorMessage, $opError -> position, $thenExpr);
	}

	public function cleanupGetMatchResult (OpGetMatchResult $opGetMatchResult)
	{
		$thenExpr = $this -> cleanupOp ($opGetMatchResult -> thenExpr);
		return new OpGetMatchResult ($opGetMatchResult -> matchVar, $opGetMatchResult -> resultVar, $thenExpr);
	}

	public function cleanupIncr (OpIncr $opIncr)
	{
		$thenExpr = $this -> cleanupOp ($opIncr -> thenExpr);
		return new OpIncr ($opIncr -> incrVar, $thenExpr);
	}

	public function cleanupLabel (OpLabel $opLabel)
	{
		if ($this -> labelIsInlinable[$opLabel -> getLabelName()] && $opLabel -> getLabelName() !== $this -> initLabel)
		{
			if ($this -> cleanOps -> contains ($opLabel -> thenExpr))
			{
				return $this -> cleanupOp ($opLabel -> thenExpr);
			}
			else
			{
				return $opLabel -> thenExpr;
			}

			/*
			while (true)
			{
				switch ($scan -> getOpType())
				{
					case Op::OP_COMMENT:

					break;
	
				}
			}
			*/
		}
		else
		{
			return $this -> cleanupOp ($opLabel);
		}
	}

	public function cleanupMatch (OpMatch $opMatch)
	{
		$successExpr = $this -> cleanupOp ($opMatch -> successExpr);
		$failExpr = $this -> cleanupOp ($opMatch -> failExpr);
		return new OpMatch ($opMatch -> matchExpr, $opMatch -> position, $successExpr, $failExpr);
	}

	public function cleanupNop (OpNop $opNop)
	{
		return $opNop;
	}

	public function cleanupReturnConst (OpReturnConst $opReturnConst)
	{
		return $opReturnConst;
	}

	public function cleanupRule (OpRule $opRule)
	{
		$successExpr = $this -> cleanupOp ($opRule -> successExpr);
		$failExpr = $this -> cleanupOp ($opRule -> failExpr);
		return new OpRule ($opRule -> ruleName, $opRule -> ruleArgs, $successExpr, $failExpr);	
	}

	public function cleanupSet (OpSet $opSet)
	{
		$thenExpr = $this -> cleanupOp ($opSet -> thenExpr);
		$scan = $thenExpr;
		$continue = true;

		while ($continue)
		{
			switch ($scan -> getOpType())
			{
				case Op::OP_SET:
					if ($scan -> target -> getName() === $opSet -> target -> getName())
					{
						return new OpComment ("dropped set ".$opSet -> target -> getName()." = ".$opSet -> source -> getName(), $thenExpr);
					}
					else
					{
						$continue = false;
					}
				break;

				case Op::OP_COMMENT:
				case Op::OP_DEBUG:
					$scan = $scan -> thenExpr;
				break;

				default:
					$continue = false;
				break;
			}
		}

		return new OpSet ($opSet -> target, $opSet -> source, $thenExpr);
	}

	public function cleanupSetConst (OpSetConst $opSetConst)
	{
		$thenExpr = $this -> cleanupOp ($opSetConst -> thenExpr);
		return new OpSetConst ($opSetConst -> target, $opSetConst -> value, $thenExpr);
	}

	public function cleanupSetSubstr (OpSetSubstr $opSetSubstr)
	{
		$thenExpr = $this -> cleanupOp ($opSetSubstr -> thenExpr);
		return new OpSetSubstr ($opSetSubstr -> target, $opSetSubstr -> first, $opSetSubstr -> last, $thenExpr);
	}

	public function cleanupThunk (OpThunk $opThunk)
	{
		$thenExpr = $this -> cleanupOp ($opThunk -> thenExpr);
		return new OpThunk ($opThunk -> target, $opThunk -> thunk, $opThunk -> vars, $thenExpr);	
	}
}
