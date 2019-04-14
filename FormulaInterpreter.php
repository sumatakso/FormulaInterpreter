<?php

class FormulaInterpreter {
	private $formula;
	private $parameters;
	private $basicMathOperationPatterns;
	private $basicMathOperationsSinglePattern;
	private $precision;
	private $numberBetweenParentesis;
	private $debug;

	public function __construct($formula, $parameters, $precision=0, $debug=false) {
		$this->formula = strtolower($formula);
		$this->parameters = $parameters;
		$this->debug = $debug;
		$this->basicMathOperationPatterns  = [
			"division" => "/\s*\({0,1}\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+\s*(\/\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/",
			"multiplication" => "/\s*\({0,1}\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+\s*(\*\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/",
			"sum" => "/\s*\({0,1}\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+\s*(\+\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/",
			"subtraction" => "/\s*\({0,1}\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+\s*(\-\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/",
			"number_between_parentesis" => "/\s*\(\s*[\-{0,1}0-9]+\s*\)\s*/",
		];
		#sum, subtraction, division & multiplication in a single pattern
		$this->basicMathOperationsSinglePattern = "/\s*\({0,1}\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+\s*([\+|\-|\*|\/]\s*([\-{0,1}a-z|\-{0,1}0-9\.{0,1}]+_{0,})+)+\s*\){0,1}\s*/";
		$this->numberBetweenParentesis = "/\s*\(\s*[\-{0,1}0-9]+\s*\)\s*/";
		$this->precision = $precision;
	}

	public function execute() {
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
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '+', $this->debug);
					break;
				case 'subtraction':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '-', $this->debug);
					break;
				case 'multiplication':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '*', $this->debug);
					break;
				case 'division':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '/', $this->debug);
					break;
				case 'number_between_parentesis':
					preg_match_all($pattern, $this->formula, $matches);
					if(!isset($matches[0])) { break; }
					$this->formula = $this->operationByOperation($matches[0], $pattern, $this->parameters, $this->formula, '()', $this->debug);
					break;
			}
		}
		if(preg_match($this->basicMathOperationsSinglePattern, $this->formula) || preg_match($this->numberBetweenParentesis, $this->formula)){
			return $this->execute($this->formula, $this->parameters, $this->precision);
		}
		if($this->precision>0) {
			return number_format((float)$this->formula, $this->precision);
		}
		return $this->formula;
	}

	private function basicOperation($op1, $op2, $operator) {
		switch ($operator) {
			case '+':
				if($op1==0 && $op2>0){
					return $op2;
				}
				return $op1+$op2;
			case '-':
				if($op1==0 && $op2>0){
					return $op2;
				}
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
			if(isset($innerMatches[0]) && !empty($innerMatches[0]) && isset($innerMatches[0][$i])) {
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
						$paramKey = preg_replace('/\W/', '', $expression[$k]); //to get only the parameter's letters
						$paramKeyValue = $parameters[$paramKey];
						if(preg_match('/[-]/', $expression[$k])){ //if parameter is -
							$paramKeyValue = ($parameters[$paramKey]*-1);
						}
						$result = $this->basicOperation($result, $paramKeyValue, $operator);
					}
				}
				if($operator!=='()'){
					$expressionToBeReplaced = str_replace('(', '', $expressionToBeReplaced);
					$expressionToBeReplaced = str_replace(')', '', $expressionToBeReplaced);
				}else{
					$result = $expression[0];
				}
				$formula = str_replace($expressionToBeReplaced, $result, $formula); # reduced formula
				if($debug){
					self::pr('E: '.$expressionToBeReplaced);
					self::pr('R: '.$result);
					self::pr('F: '.$formula);
				}
			}
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

	public static function pr($data=null, $tag=null) {
		echo '<pre>';
		if($tag!=null){
			echo $tag.'<br/>';
		}
		print_r($data);
		echo '</pre>';
	}
}