<?php

namespace Payomatix\Service;

use Payomatix\Helper\Fields as FieldOptions;

class ValidationService extends FieldOptions
{
	public static function paymentAPIValidation(array $fields)
	{
		$fields = self::setFields($fields);

		$validations = self::paymentAPIFields();

		return self::validateFields($validations, $fields);
	}

	public static function paymentAPIFields()
	{
		return [
			'email' => 'required|email',
			'amount' => 'required',
			'currency' => 'required',
			'return_url' => 'required',
			'notify_url' => 'required',

			'other_data' => 'nullable|array',

			'other_data.first_name' => 'nullable',
			'other_data.last_name' => 'nullable',
			'other_data.address' => 'nullable',
			'other_data.state' => 'nullable',
			'other_data.city' => 'nullable',
			'other_data.zip' => 'nullable|min:6',
			'other_data.country' => 'nullable|min:2|max:2',
			'other_data.phone_no' => 'nullable',
			'other_data.card_no' => 'nullable',
			'other_data.merchant_ref' => 'nullable',
			'other_data.search_key' => 'nullable',
			'other_data.select_type_id' => 'nullable|in:1,2,3,4,5',
			'other_data.customer_vpa' => 'nullable',

			'other_data.products' => 'nullable|array',

			'other_data.products.*.product_id' => 'nullable',
			'other_data.products.*.name' => 'nullable',
			'other_data.products.*.quantity' => 'nullable',
			'other_data.products.*.price' => 'nullable',
			'other_data.products.*.description' => 'nullable',
			'other_data.products.*.product_code' => 'nullable',
			'other_data.products.*.image_url' => 'nullable',
			'other_data.products.*.category' => 'nullable',
			'other_data.products.*.tax_rate' => 'nullable',
			'other_data.products.*.discount' => 'nullable',
			'other_data.products.*.weight' => 'nullable',
		];
	}

	public static function validateFields($validations, $fields)
	{
		if (empty($validations)) {
			return null;
		}

		$validation_errors = [];

		foreach ($validations as $key => $validation) {
			$validate_types = explode('|', $validation);

			foreach ($validate_types as $validate_method) {
			    // field is required
				if ($validate_method === 'required') {
			        if (null !== self::validateRequired($fields, $key)) {
			        	$validation_errors[$key]['required'] = $key. ' field is required.';
			        }
			    // field should be email
				} elseif ($validate_method === 'email') {
					if (null !== self::validateEmail($fields, $key)) {
			        	$validation_errors[$key]['required'] = $key. ' field is not valid email.';
			        }
			    // field is array
				} elseif ($validate_method === 'array') {
					if (null !== self::validateArray($fields, $key)) {
			        	$validation_errors[$key]['required'] = $key. ' field is required.';
			        }
			    // nothing to do in case nullable
				} elseif ($validate_method === 'nullable') {
					continue;
			    // field minimum
				} elseif (substr($validate_method, 0, 4) == 'min:') {
					if (null !== self::validateMin($fields, $key, $validate_method)) {
			        	$validation_errors[$key]['min'] = $key. ' field should not be less than '.str_replace('min:', '', $validate_method);
			        }
				} elseif (substr($validate_method, 0, 4) == 'max:') {
					if (null !== self::validateMax($fields, $key, $validate_method)) {
			        	$validation_errors[$key]['max'] = $key. ' field should not be greater than '.str_replace('max:', '', $validate_method);
			        }
			    // field from in
				} elseif (substr($validate_method, 0, 3) == 'in:') {
					if (null !== self::validateIn($fields, $key, $validate_method)) {
			        	$validation_errors[$key]['in'] = $key. ' field should be from '.str_replace('in:', '', $validate_method);
			        }
				} else {
					// 
				}
			}
		}

		return $validation_errors;
	}

	public static function validateRequired($fields, $key)
	{
		if (isset($fields[$key]) && $fields[$key] !== null) {
        	return null;
        } else {
        	return 1;
        }
	}

	public static function validateArray($fields, $key)
	{
		if (!(null !== self::getValueFromArray($fields, $key) && is_array(self::getValueFromArray($fields, $key)))) {
			return 1;
		}
	}

	public static function validateEmail($fields, $key)
	{
		if (isset($fields[$key]) && $fields[$key] !== null) {
			if (!filter_var($fields[$key], FILTER_VALIDATE_EMAIL)) {
        		return 1;
			}
        }
	}

	public static function validateIn($fields, $key, $validate_method)
	{
		$validate_value = self::getValueFromArray($fields, $key);
		if (null !== $validate_value) {
			if (!in_array($validate_value, explode(',', str_replace('in:', '', $validate_method)))) {
        		return 1;
			}
		}
	}

	public static function validateMin($fields, $key, $validate_method)
	{
		if (self::getValueFromArray($fields, $key) !== null && strlen(self::getValueFromArray($fields, $key)) < str_replace('min:', '', $validate_method)) {
    		return 1;
        }
	}

	public static function validateMax($fields, $key, $validate_method)
	{
		if (self::getValueFromArray($fields, $key) !== null && strlen(self::getValueFromArray($fields, $key)) > str_replace('max:', '', $validate_method)) {
    		return 1;
        }
	}

	public static function getValueFromArray($fields, $key)
	{
		$key_explode = explode('.', $key);

		// only for other_data validation
		if (count($key_explode) == 1) {
			if (isset($fields[$key]) && $fields[$key] !== null) {
				return $fields[$key_explode[0]];
			}
		// other_data.product validation
		} elseif (count($key_explode) == 2) {
			if (isset($fields[$key_explode[0]][$key_explode[1]]) && $fields[$key_explode[0]][$key_explode[1]] !== null) {
				return $fields[$key_explode[0]][$key_explode[1]];
			}
		} elseif (count($key_explode) == 3) {
			if (isset($fields[$key_explode[0]][$key_explode[1]][$key_explode[2]]) && $fields[$key_explode[0]][$key_explode[1]][$key_explode[2]] !== null) {
				return $fields[$key_explode[0]][$key_explode[1]][$key_explode[2]];
			}
		} else {
			return null;
		}
	}
}
