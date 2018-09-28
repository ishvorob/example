<?php

namespace App\Http\Requests;

use App\Models\Currency;
use App\Rules\InArrayValue;
use Illuminate\Foundation\Http\FormRequest;
use App\Facades\Client;

class UserSettingsUpdateRequest extends FormRequest
{

	private $currencies;

	private $client;

	private $warehouses;

	private $categories;

	public function __construct()
	{
		$this->client = Client::getUser();
		$this->currencies = Currency::all()->pluck('id')->toArray();
		$this->warehouses = $this->client->warehouses->pluck('id')->toArray();
		$this->categories = $this->client->categories->pluck('id')->toArray();
	}

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules()
	{
		return [
			'formatting_type_id' => 'required_if:is_send_price,1|exists:formatting_types,id',
			'price_separator' => [new InArrayValue([',', '.']), 'required_if:is_send_price,1'],
			'price_currencies.*' => [new InArrayValue($this->currencies)],
			'price_warehouses.*' => [new InArrayValue($this->warehouses)],
			'price_categories.*' => [new InArrayValue($this->categories)],
		];
	}

	/**
	 * Get the error messages for the defined validation rules.
	 *
	 * @return array
	 */
	public function messages()
	{
		return [
			'price_separator.required_if' => __('account.validation.settings.price_separator_required'),
		];
	}

	public function all($keys = null)
	{
		$data = parent::all($keys);

		if (array_key_exists('is_send_price', $data)) {
			foreach ($data as $key => $value) {
				if (is_array($value)) {
					$data[$key] = json_encode($value);
				}
			}
			$data['use_min_retail_price'] = (array_key_exists('use_min_retail_price', $data)) ? 1 : 0;
			$data['is_send_price'] = 1;

			return $data;
		}

		return ['is_send_price' => 0];
	}

}
