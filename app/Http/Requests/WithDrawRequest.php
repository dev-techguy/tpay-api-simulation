<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property mixed phone_number
 * @property mixed referenceCode
 * @property mixed amount
 */
class WithDrawRequest extends FormRequest {
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize() {
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules() {
		return [
			'phone_number' => ['required', 'numeric'],
			'amount' => ['required', 'numeric'],
			'referenceCode' => ['required', 'string'],
		];
	}
}
