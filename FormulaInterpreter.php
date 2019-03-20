<?php

class FormulaInterpreter {
	private $formula;
	private $parameters;
	private $basicMathOperationPatterns;
	private $basicMathOperationsSinglePattern;
	private $precision;

	public function __construct($formula, $parameters, $precision=0) {
		$this->formula = strtolower($formula);
		$this->parameters = $parameters;
		$this->basicMathOperationPatterns  = [
			"sum" => "/\s*\({0,1}\s*([a-z|0-9\.{0,1}]+_{0,})+\s*(\+\s*([a-z|0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/", #sum
			"subtraction" => "/\s*\({0,1}\s*([a-z|0-9\.{0,1}]+_{0,})+\s*(\-\s*([a-z|0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/", #subtraction
			"division" => "/\s*\({0,1}\s*([a-z|0-9\.{0,1}]+_{0,})+\s*(\/\s*([a-z|0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/", #division
			"multiplication" => "/\s*\({0,1}\s*([a-z|0-9\.{0,1}]+_{0,})+\s*(\*\s*([a-z|0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/", #multiplication
		];
		#sum, subtraction, division & multiplication in a single pattern
		$this->basicMathOperationsSinglePattern = "/\s*\({0,1}\s*([a-z|0-9\.{0,1}]+_{0,})+\s*([\+|\-|\*|\/]\s*([a-z|0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/";
		$this->precision = $precision;
	}

	public function execute($debug=false) {
		if(!preg_match($this->basicMathOperationsSinglePattern, $this->formula)){
			return '<pre style="color:red">Error, your formula (<strong>'.$this->formula.'</strong>) is not valid.</pre>';
		}
		if(!$this->validateParameters($this->parameters)) {
			return '<pre style="color:red"><strong>Non numeric values in parameters are not allowed.</strong></pre>';
		}
		foreach ($this->basicMathOperationPatterns as $operation => $pattern) {
			$matches = null;
			switch ($operation) {
				case 'sum':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '+', $debug);
					break;
				case 'subtraction':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '-', $debug);
					break;
				case 'multiplication':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '*', $debug);
					break;
				case 'division':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '/', $debug);
					break;
			}
		}
		if(preg_match($this->basicMathOperationsSinglePattern, $this->formula)){
			return $this->execute($this->formula, $parameters, $precision);
		}
		if($this->precision>0) {
			return number_format($this->formula, $precision);
		}
		return $this->formula;
	}


	private function basicOperation($op1, $op2, $operator) {
		switch ($operator) {
			case '+':
				return $op1+$op2;
			case '-':
				return $op1-$op2;
			case '*':
				return $op1*$op2;
			case '/':
				if($op1==1) return $op2;
				return $op1/$op2;
		}
	}

	private function operationByOperation($matches, $pattern, $parameters, $formula, $operator='+', $debug=false) {
		$parameters = $this->parametersIndexInLowercase($parameters);

		for ($i=count($matches); $i >= 0; $i--) { 
			preg_match_all($pattern, $formula, $innerMatches);
			if(isset($innerMatches[0]) && isset($innerMatches[0][$i])) {
				$expression = $innerMatches[0][$i];
				$expressionToBeReplaced = $expression;
				$expression = str_replace('(', '', $expression);
				$expression = str_replace(')', '', $expression);
				$expression = explode($operator, $expression);
				
				$result = 0.0;
				if(in_array($operator, ['*', '/'])) {
					$result = 1;
				}
				for ($k=0; $k < count($expression); $k++) { 
					if(is_numeric($expression[$k])) {
						$result = $this->basicOperation($result, (float) $expression[$k], $operator);
					}else if(is_string($expression[$k])){
						$result = $this->basicOperation($result, $parameters[$expression[$k]], $operator);
					}
				}
				$formula = str_replace($expressionToBeReplaced, $result, $formula); # reduced formula
				if($debug){
					echo '<pre>';
					print_r($expression);
					echo '<br>';
					echo $formula;
					echo '</pre>';
				}
			}
		}
		if(preg_match($pattern, $formula)){
			return $this->operationByOperation($matches, $pattern, $parameters, $formula, $operator, $debug=false);
		}
		return $formula;
	}

	private function parametersIndexInLowercase($parameters) {
		$parametersIndexInLowercase = [];
		foreach ($parameters as $key => $value) {
			$parametersIndexInLowercase[strtolower($key)] = $value;
		}
		return $parametersIndexInLowercase;
	}

	private function validateParameters($parameters) {
		$allParametersValuesAreNumeric = true;
		foreach ($parameters as $key => $value) {
			if(!is_numeric($value)) {
				$allParametersValuesAreNumeric = false;
			}
		}
		return $allParametersValuesAreNumeric;
	}
}