##Goat parser generator

Sorta like bison, but smaller, dumber, and less powerful

Goat is a badly-named program for creating parsers in PHP.

It takes a grammar, and gives you a parser. That's it.

Goat uses a parsing-expression-grammar style format, although unlike some PEG parser implementations, goat does allow *some* left recursion (see the example below).

##Grammar

A grammar is a set of rules. A rule is defined like this:

`RuleName = [expression...]`

##Expressions

Types of expressions are:
###String
`"im a string!"`

(match the characters ['i', 'm', ' ', 'a', ...] exactly)

Single and double quoted strings are allowed, and follow the standard PHP string escaping conventions.

###Class
`[abw-z]`

(match a single character - either 'a', 'b', 'w', 'x', 'y', or 'z')

`[^abw-z]`

(match any character NOT 'a', 'b', 'w', 'x', 'y', 'z')

###Dot
`.`

(match any single character)

###Rule
`RuleName`

(the rule named "RuleName")


There are also expressions that modify other expressions:
###Quantifiers
(*, +, and ? - you know these from regular expressions)

`[a-c]*`

(match 0 or more 'a's, 'b's, and 'c's)

If the quantified expression returns a value, then the quantifier will return an array of those values.

###Assertions (!, &)
`&[a-c]`

(succeed only if the next character IS an 'a', 'b', or 'c' - but don't change the parser's position either way)

`![a-c]`

(succeed only if the next character ISN'T an 'a', 'b', or 'c' - but don't change the parser's position either way)


There are also ways to combine multiple expressions:
###Sequence
`Expression1 Expression2`

(do Expression1, then do Expression2)

###Choice
`Expression1 | Expression2`

(do Expression1, and if that fails then do Expression2...)


Finally, there are two types of expressions for making your parser actually do something
###Actions
`{ /* PHP expression (not statement - no semicolons allowed here) */ }`

(evaluate the expression inside the brackets, and return the result)

###Variables
`$v:RuleName`

(evaluate the rule named RuleName, and store the returned value in $v)
(you can use this value in any actions that follow $v)

example:

`$name:Name Whitespace	{ $name }`

(take the result of Name, then match the rule "Whitespace", then return the value returned by "Name")

If "RuleName" does not explicitly return a value, then the variable will be set to the complete text matched by "RuleName".


##Options
There are a few different options you can set for your parser

The syntax for setting an option in the grammar is:
`%optionName (optionValue...)`

Note, the option has to all be on one line. You can't do
```
%optionName
	optionValue....
```

The options are as follows:

- `%global VarName [, VarType]`

	Creates an additional parser argument that will be available
	from each of your actions. If you include the variable type,
	it will be type-hinted each place it is passed.

- `%class ClassName`

	Set the class name of the generated parser.

- `%namespace NamespaceName`

	Set the namespace of the generated parser.

- `%top RuleName`

	Create a public parser method, named "parse" + RuleName, for parsing a string beginning from
	the named rule.

These options can also be set from the command line.


##Example grammar
```
# An example grammar for parsing and evaluating basic arithmetic

%class ArithmeticParser
%top Arithmetic

Arithmetic
	= Space		# leading whitespace
	  $e:Expr
	  !.    	# make sure we parsed the whole string; otherwise, there was an error somewhere
	  { $e }	# return $e

Expr    = $s:Sum                                { $s }
Sum     = $a:Sum Plus $b:Product                { $a + $b }
        | $a:Sum Minus $b:Product               { $a - $b }
        | $a:Product                            { $a }
Product = $a:Product Times $b:Value             { $a * $b }
        | $a:Product Div $b:Value               { $a / $b }
        | $a:Value                              { $a }
Value   = $n:Number Space                       { intval ($n) }
        | Open $e:Expr Close                    { $e }

Number  = [1-9] [0-9]*
        | '0'

Plus    = '+' Space
Minus   = '-' Space
Times   = '*' Space
Div     = '/' Space

Open    = '(' Space
Close   = ')' Space

Space   = Space1*
Space1  = "\n" | "\r" | "\t" | " "

# (this would generate a class named ArithmeticParser, which could be used like this)
# $parser = new ArithmeticParser;
# $result = $parser -> parseArithmetic ("33 + (49 * 25 / 5) + 44 - 20");

```

##Using

```
php src/app/main.php --help
```

There is also a script for building an executable phar archive, if you want.

```
php src/gen/makePhar.php -o build/goatp
```

For another example, see src/gen/goat.peg for goat's own grammar.
