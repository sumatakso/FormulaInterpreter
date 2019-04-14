Formula Interpreter
=======================

A simple php formula interpreter

# How does it work ?

First, create an instance of `FormulaInterpreter` with the formula and its parameters

```php
$formulaInterpreter = new FormulaInterpreter("x + y", ["x" => 10, "y" => 20]);
```

Use the `execute()` method  to interpret the formula. It will return the result:

```php
echo $formulaInterpreter->execute();
```
in a single line

```php
echo (new FormulaInterpreter("x + y", ["x" => 10, "y" => 20]))->execute();
```

# Examples

```php
# Formula: speed = distance / time
$speed = (new FormulaInterpreter("distance/time", ["distance" => 338, "time" => 5]))->execute() ;
echo $speed;


#Venezuela night overtime (ordinary_work_day in hours): (normal_salary * days_in_a_work_month)/ordinary_work_day
$parameters = ["normal_salary" => 21000, "days_in_a_work_month" => 30, "ordinary_work_day" => 8];
$venezuelaLOTTTArt118NightOvertime = (new FormulaInterpreter("(normal_salary/days_in_a_work_month)/ordinary_work_day", $parameters))->execute();
echo $venezuelaLOTTTArt118NightOvertime;


#cicle area
$cicleArea = (new FormulaInterpreter("3.1416*(radio*radio)", ["radio" => 10]))->execute();
echo $cicleArea;

```

# Complexity of the expressions. "radio" is a variable (hypotheticals cases)
1. radio*-radio+(10+5*(10-10+(2/2)))
2. -100+(15*(10-10+(1)))
3. (radio*1+(0+0+(-1*0)))*-radio+(10+5*(10-10+(2/2)))

# About the formulas

1.  It must contain at least two operands and an operator.
2.  Operands' name could be in upper or lower case.
3.  By now, math functions as sin, cos, pow… are not included. I'm working to include them.
4.  If your formula is not valid, you will get an error message like: **Error, your formula (single_variable) is not valid.**
5.  Parameters' values must be numeric.