<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Facades\CurrentOrder;

class UserSettings extends BaseModel
{
	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'user_id',
		'is_send_price',
		'formatting_type_id',
		'price_separator',
		'price_currencies',
		'price_warehouses',
		'price_categories',
		'warehouses',
		'price_types',
		'currencies',
		'last_order'
	];

	/**
	 * List of JSON attribtues
	 *
	 * @var  array
	 */
	protected $jsonAttributes = [
		'warehouses',
		'price_types',
		'currencies'
	];

	public function logger()
	{
		return $this->morphMany('App\Models\Logger', 'loggerable');
	}

	public function last_order()
	{
		return $this->hasOne('App\Models\Order', 'id', 'last_order');
	}

	public function formattingType()
	{
		return $this->belongsTo('App\Models\FormattingType');
	}

	/**
	 * JSON wrapper
	 *
	 * {@inheritdoc}
	 */
	public function getAttributeValue($key)
	{
		$value = parent::getAttributeValue($key);

		if (in_array($key, $this->jsonAttributes)) {
			return $this->getJsonAttribute($value);
		}

		return $value;
	}

	/**
	 * JSON wrapper
	 *
	 * {@inheritdoc}
	 */
	public function setAttribute($key, $value)
	{
		if (in_array($key, $this->jsonAttributes)) {
			return $this->setJsonAttribute($key, $value);
		}

		return parent::setAttribute($key, $value);
	}


	/**
	 * Decode
	 *
	 * @param  mixed $value
	 * @return  mixed  Decoded value
	 */
	public function getJsonAttribute($value)
	{
		return ($value === null) ? null : json_decode($value);
	}

	/**
	 * Encode
	 *
	 * @param   string $key
	 * @param   boolean|integer|string|array|stdClass|JsonSerializable $value
	 * @return  Model instance
	 */
	public function setJsonAttribute($key, $value)
	{
		$this->attributes[$key] = ($value === null) ? null : json_encode($value);

		return $this;
	}

	/**
	 * Define model event callbacks.
	 *
	 * @return void
	 */
	protected static function boot()
	{
		parent::boot();

		//Check if changed last_order and updating current order id
		static::updating(function ($user_settings) {
			if ($user_settings->isDirty('last_order')) {
				CurrentOrder::setOrderId($user_settings->last_order);
			}
		});
	}
}
