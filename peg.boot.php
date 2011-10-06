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

class PackratParserGenerator extends Packrat {

	function yy_Definition_1($text)
	{
		 $this->beginRule($text); 
	}
	function yy_Definition_2($text)
	{
		 $this->currentRule->setExpression($this->list->pop()); 
	}
	function yy_Expression_1($text)
	{
		 $this->list->addToAlternates(); 
	}
	function yy_Sequence_1($text)
	{
		 $this->list->addToSequence(); 
	}
	function yy_Sequence_2($text)
	{
		 $this->list->push(new PredicateNode("1")); 
	}
	function yy_Prefix_1($text)
	{
		 $this->list->push(new PredicateNode($text)); 
	}
	function yy_Prefix_2($text)
	{
		 $this->list->push(new PeekNode($this->list->pop())); 
	}
	function yy_Prefix_3($text)
	{
		 $this->list->push(new PeekNotNode($this->list->pop())); 
	}
	function yy_Suffix_1($text)
	{
		 $this->list->push(new QuestionNode($this->list->pop())); 
	}
	function yy_Suffix_2($text)
	{
		 $this->list->push(new StarNode($this->list->pop())); 
	}
	function yy_Suffix_3($text)
	{
		 $this->list->push(new PlusNode($this->list->pop())); 
	}
	function yy_Primary_1($text)
	{
		 $this->list->push(new NameNode($this->list->getRule($text))); 
	}
	function yy_Primary_2($text)
	{
		 $this->list->push(new StringNode($text)); 
	}
	function yy_Primary_3($text)
	{
		 $this->list->push(new CharClassNode($text)); 
	}
	function yy_Primary_4($text)
	{
		 $this->list->push(new DotNode()); 
	}
	function yy_Primary_5($text)
	{
		 $this->list->addAction($text, $this->currentRule); 
	}
	function yy_Primary_6($text)
	{
		 $this->list->push(new PredicateNode('$this->markBegin()')); 
	}
	function yy_Primary_7($text)
	{
		 $this->list->push(new PredicateNode('$this->markEnd()')); 
	}
	function yyGrammar()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->yySpacing())
			goto l0;

		l32:
		if(!$this->yyDefinition())
			goto l0;
		$pos33 = $this->pos;
		$sPos33 = $this->sPos;
		if(!$this->yyDefinition())
			goto l33;
		goto l32;

		l33:
		$this->pos = $pos33;
		$this->sPos = $sPos33;
		if(!$this->yyEndOfFile())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyDefinition()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->yyIdentifier())
			goto l0;
		$this->suspend('yy_Definition_1');
		if(!$this->yyLEFTARROW())
			goto l0;
		if(!$this->yyExpression())
			goto l0;
		$this->suspend('yy_Definition_2');
		$this->Text($this->begin, $this->end);
		if(!( $this->accept($sPos0) ))
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyExpression()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->yySequence())
			goto l0;

		l34:
		$pos35 = $this->pos;
		$sPos35 = $this->sPos;
		if(!$this->yySLASH())
			goto l35;
		if(!$this->yySequence())
			goto l35;
		$this->suspend('yy_Expression_1');
		goto l34;

		l35:
		$this->pos = $pos35;
		$this->sPos = $sPos35;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySequence()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos36 = $this->pos;
		$sPos36 = $this->sPos;
		if(!$this->yyPrefix())
			goto l37;

		l38:
		$pos39 = $this->pos;
		$sPos39 = $this->sPos;
		if(!$this->yyPrefix())
			goto l39;
		$this->suspend('yy_Sequence_1');
		goto l38;

		l39:
		$this->pos = $pos39;
		$this->sPos = $sPos39;
		goto l36;

		l37:
		$this->pos = $pos36;
		$this->sPos = $sPos36;
		$this->suspend('yy_Sequence_2');

		l36:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyPrefix()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos40 = $this->pos;
		$sPos40 = $this->sPos;
		if(!$this->yyAND())
			goto l41;
		if(!$this->yyAction())
			goto l41;
		$this->suspend('yy_Prefix_1');
		goto l40;

		l41:
		$this->pos = $pos40;
		$this->sPos = $sPos40;
		if(!$this->yyAND())
			goto l42;
		if(!$this->yySuffix())
			goto l42;
		$this->suspend('yy_Prefix_2');
		goto l40;

		l42:
		$this->pos = $pos40;
		$this->sPos = $sPos40;
		if(!$this->yyNOT())
			goto l43;
		if(!$this->yySuffix())
			goto l43;
		$this->suspend('yy_Prefix_3');
		goto l40;

		l43:
		$this->pos = $pos40;
		$this->sPos = $sPos40;
		if(!$this->yySuffix())
			goto l0;

		l40:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySuffix()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->yyPrimary())
			goto l0;
		$pos44 = $this->pos;
		$sPos44 = $this->sPos;
		$pos46 = $this->pos;
		$sPos46 = $this->sPos;
		if(!$this->yyQUESTION())
			goto l47;
		$this->suspend('yy_Suffix_1');
		goto l46;

		l47:
		$this->pos = $pos46;
		$this->sPos = $sPos46;
		if(!$this->yySTAR())
			goto l48;
		$this->suspend('yy_Suffix_2');
		goto l46;

		l48:
		$this->pos = $pos46;
		$this->sPos = $sPos46;
		if(!$this->yyPLUS())
			goto l44;
		$this->suspend('yy_Suffix_3');

		l46:
		goto l45;

		l44:
		$this->pos = $pos44;
		$this->sPos = $sPos44;

		l45:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyPrimary()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos49 = $this->pos;
		$sPos49 = $this->sPos;
		if(!$this->yyIdentifier())
			goto l50;
		$pos51 = $this->pos;
		$sPos51 = $this->sPos;
		if(!$this->yyLEFTARROW())
			goto l51;
		goto l50;

		l51:
		$this->pos = $pos51;
		$this->sPos = $sPos51;
		$this->suspend('yy_Primary_1');
		goto l49;

		l50:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyOPEN())
			goto l52;
		if(!$this->yyExpression())
			goto l52;
		if(!$this->yyCLOSE())
			goto l52;
		goto l49;

		l52:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyLiteral())
			goto l53;
		$this->suspend('yy_Primary_2');
		goto l49;

		l53:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyClass())
			goto l54;
		$this->suspend('yy_Primary_3');
		goto l49;

		l54:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyDOT())
			goto l55;
		$this->suspend('yy_Primary_4');
		goto l49;

		l55:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyAction())
			goto l56;
		$this->suspend('yy_Primary_5');
		goto l49;

		l56:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyBEGIN())
			goto l57;
		$this->suspend('yy_Primary_6');
		goto l49;

		l57:
		$this->pos = $pos49;
		$this->sPos = $sPos49;
		if(!$this->yyEND())
			goto l0;
		$this->suspend('yy_Primary_7');

		l49:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyIdentifier()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$this->Text($this->begin, $this->end);
		if(!($this->markBegin()))
			goto l0;
		if(!$this->yyIdentStart())
			goto l0;

		l58:
		$pos59 = $this->pos;
		$sPos59 = $this->sPos;
		if(!$this->yyIdentCont())
			goto l59;
		goto l58;

		l59:
		$this->pos = $pos59;
		$this->sPos = $sPos59;
		$this->Text($this->begin, $this->end);
		if(!($this->markEnd()))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyIdentStart()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchClass("\000\000\000\000\000\000\000\000\376\377\377\207\376\377\377\007\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyIdentCont()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos60 = $this->pos;
		$sPos60 = $this->sPos;
		if(!$this->yyIdentStart())
			goto l61;
		goto l60;

		l61:
		$this->pos = $pos60;
		$this->sPos = $sPos60;
		if(!$this->matchClass("\000\000\000\000\000\000\377\003\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l0;

		l60:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyLiteral()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos62 = $this->pos;
		$sPos62 = $this->sPos;
		if(!$this->matchClass("\000\000\000\000\200\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l63;
		$this->Text($this->begin, $this->end);
		if(!($this->markBegin()))
			goto l63;

		l64:
		$pos65 = $this->pos;
		$sPos65 = $this->sPos;
		$pos66 = $this->pos;
		$sPos66 = $this->sPos;
		if(!$this->matchClass("\000\000\000\000\200\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l66;
		goto l65;

		l66:
		$this->pos = $pos66;
		$this->sPos = $sPos66;
		if(!$this->yyChar())
			goto l65;
		goto l64;

		l65:
		$this->pos = $pos65;
		$this->sPos = $sPos65;
		$this->Text($this->begin, $this->end);
		if(!($this->markEnd()))
			goto l63;
		if(!$this->matchClass("\000\000\000\000\200\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l63;
		if(!$this->yySpacing())
			goto l63;
		goto l62;

		l63:
		$this->pos = $pos62;
		$this->sPos = $sPos62;
		if(!$this->matchClass("\000\000\000\000\004\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l0;
		$this->Text($this->begin, $this->end);
		if(!($this->markBegin()))
			goto l0;

		l67:
		$pos68 = $this->pos;
		$sPos68 = $this->sPos;
		$pos69 = $this->pos;
		$sPos69 = $this->sPos;
		if(!$this->matchClass("\000\000\000\000\004\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l69;
		goto l68;

		l69:
		$this->pos = $pos69;
		$this->sPos = $sPos69;
		if(!$this->yyChar())
			goto l68;
		goto l67;

		l68:
		$this->pos = $pos68;
		$this->sPos = $sPos68;
		$this->Text($this->begin, $this->end);
		if(!($this->markEnd()))
			goto l0;
		if(!$this->matchClass("\000\000\000\000\004\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;

		l62:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyClass()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("["))
			goto l0;
		$this->Text($this->begin, $this->end);
		if(!($this->markBegin()))
			goto l0;

		l70:
		$pos71 = $this->pos;
		$sPos71 = $this->sPos;
		$pos72 = $this->pos;
		$sPos72 = $this->sPos;
		if(!$this->matchChar("]"))
			goto l72;
		goto l71;

		l72:
		$this->pos = $pos72;
		$this->sPos = $sPos72;
		if(!$this->yyRange())
			goto l71;
		goto l70;

		l71:
		$this->pos = $pos71;
		$this->sPos = $sPos71;
		$this->Text($this->begin, $this->end);
		if(!($this->markEnd()))
			goto l0;
		if(!$this->matchChar("]"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyRange()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos73 = $this->pos;
		$sPos73 = $this->sPos;
		if(!$this->yyChar())
			goto l74;
		if(!$this->matchChar("-"))
			goto l74;
		if(!$this->yyChar())
			goto l74;
		goto l73;

		l74:
		$this->pos = $pos73;
		$this->sPos = $sPos73;
		if(!$this->yyChar())
			goto l0;

		l73:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyChar()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos75 = $this->pos;
		$sPos75 = $this->sPos;
		if(!$this->matchChar("\\"))
			goto l76;
		if(!$this->matchClass("\000\000\000\000\204\000\000\000\000\000\000\070\146\100\124\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l76;
		goto l75;

		l76:
		$this->pos = $pos75;
		$this->sPos = $sPos75;
		if(!$this->matchChar("\\"))
			goto l77;
		if(!$this->matchClass("\000\000\000\000\000\000\017\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l77;
		if(!$this->matchClass("\000\000\000\000\000\000\377\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l77;
		if(!$this->matchClass("\000\000\000\000\000\000\377\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l77;
		goto l75;

		l77:
		$this->pos = $pos75;
		$this->sPos = $sPos75;
		if(!$this->matchChar("\\"))
			goto l78;
		if(!$this->matchClass("\000\000\000\000\000\000\377\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l78;
		$pos79 = $this->pos;
		$sPos79 = $this->sPos;
		if(!$this->matchClass("\000\000\000\000\000\000\377\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000\000"))
			goto l79;
		goto l80;

		l79:
		$this->pos = $pos79;
		$this->sPos = $sPos79;

		l80:
		goto l75;

		l78:
		$this->pos = $pos75;
		$this->sPos = $sPos75;
		if(!$this->matchChar("\\"))
			goto l81;
		if(!$this->matchChar("-"))
			goto l81;
		goto l75;

		l81:
		$this->pos = $pos75;
		$this->sPos = $sPos75;
		$pos82 = $this->pos;
		$sPos82 = $this->sPos;
		if(!$this->matchChar("\\"))
			goto l82;
		goto l0;

		l82:
		$this->pos = $pos82;
		$this->sPos = $sPos82;
		if(!$this->matchDot())
			goto l0;

		l75:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyLEFTARROW()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchString("<-"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySLASH()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("/"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyAND()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("&"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyNOT()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("!"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyQUESTION()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("?"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySTAR()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("*"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyPLUS()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("+"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyOPEN()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("("))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyCLOSE()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar(")"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyDOT()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("."))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySpacing()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;

		l83:
		$pos84 = $this->pos;
		$sPos84 = $this->sPos;
		$pos85 = $this->pos;
		$sPos85 = $this->sPos;
		if(!$this->yySpace())
			goto l86;
		goto l85;

		l86:
		$this->pos = $pos85;
		$this->sPos = $sPos85;
		if(!$this->yyComment())
			goto l84;

		l85:
		goto l83;

		l84:
		$this->pos = $pos84;
		$this->sPos = $sPos84;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyComment()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("#"))
			goto l0;

		l87:
		$pos88 = $this->pos;
		$sPos88 = $this->sPos;
		$pos89 = $this->pos;
		$sPos89 = $this->sPos;
		if(!$this->yyEndOfLine())
			goto l89;
		goto l88;

		l89:
		$this->pos = $pos89;
		$this->sPos = $sPos89;
		if(!$this->matchDot())
			goto l88;
		goto l87;

		l88:
		$this->pos = $pos88;
		$this->sPos = $sPos88;
		if(!$this->yyEndOfLine())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yySpace()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos90 = $this->pos;
		$sPos90 = $this->sPos;
		if(!$this->matchChar(" "))
			goto l91;
		goto l90;

		l91:
		$this->pos = $pos90;
		$this->sPos = $sPos90;
		if(!$this->matchChar("\t"))
			goto l92;
		goto l90;

		l92:
		$this->pos = $pos90;
		$this->sPos = $sPos90;
		if(!$this->yyEndOfLine())
			goto l0;

		l90:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyEndOfLine()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos93 = $this->pos;
		$sPos93 = $this->sPos;
		if(!$this->matchString("\r\n"))
			goto l94;
		goto l93;

		l94:
		$this->pos = $pos93;
		$this->sPos = $sPos93;
		if(!$this->matchChar("\n"))
			goto l95;
		goto l93;

		l95:
		$this->pos = $pos93;
		$this->sPos = $sPos93;
		if(!$this->matchChar("\r"))
			goto l0;

		l93:
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyEndOfFile()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		$pos96 = $this->pos;
		$sPos96 = $this->sPos;
		if(!$this->matchDot())
			goto l96;
		goto l0;

		l96:
		$this->pos = $pos96;
		$this->sPos = $sPos96;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyAction()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("{"))
			goto l0;
		$this->Text($this->begin, $this->end);
		if(!($this->markBegin()))
			goto l0;

		l97:
		$pos98 = $this->pos;
		$sPos98 = $this->sPos;
		if(!$this->matchClass("\377\377\377\377\377\377\377\377\377\377\377\377\377\377\377\337\377\377\377\377\377\377\377\377\377\377\377\377\377\377\377\377"))
			goto l98;
		goto l97;

		l98:
		$this->pos = $pos98;
		$this->sPos = $sPos98;
		$this->Text($this->begin, $this->end);
		if(!($this->markEnd()))
			goto l0;
		if(!$this->matchChar("}"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyBEGIN()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar("<"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function yyEND()
	{
		$pos0 = $this->pos;
		$sPos0 = $this->sPos;
		if(!$this->matchChar(">"))
			goto l0;
		if(!$this->yySpacing())
			goto l0;
		return 1;

		l0:
		$this->pos = $pos0;
		$this->sPos = $sPos0;
		return 0;
	}

	function Parse()
	{
		return $this->yyParseFrom('yyGrammar');
	}

}
