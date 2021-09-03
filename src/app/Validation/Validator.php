<?php

namespace App\Validation;

class Validator
{

    private $data;
    private $errors;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules): ?array
    {
        foreach ($rules as $name => $rulesArray) {
            foreach ($rulesArray as $rule) {
                switch ($rule) {
                    case 'required':
                        $this->required($name);
                        break;
                    case substr($rule, 0, 3) === 'min':
                        $this->min($name, $rule);
                    default:
                        # code...
                        break;
                }
            }
        }

        return $this->getErrors();
    }

    private function required(string $name)
    {
        if (!isset($this->data[$name])) {
            $this->errors[$name][] = "is_required";
        } else {
            $value = trim($this->data[$name]);

            if (!isset($value) || is_null($value) || empty($value)) {
                $this->errors[$name][] = "is_required";
            }
        }
    }

    private function min(string $name, string $rule)
    {
        preg_match_all('/(\d+)/', $rule, $matches);
        $limit = (int) $matches[0][0];

        if (!isset($this->data[$name]) || strlen($this->data[$name]) < $limit) {
            $this->errors[$name][] = "must_have_minimum_length";
        }
    }

    private function getErrors(): ?array
    {
        return $this->errors;
    }
}
