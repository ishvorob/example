<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Logger extends Model
{
	protected $fillable = [
		'message',
		'path',
		'logged_data',
		'site',
		'log_type',
		'name'
	];

	protected $logged_data = [
		'logged_data' => 'array'
	];

	/**
	 * Get all of the owning models.
	 */
	public function loggerable()
	{
		return $this->morphTo();
	}

	public function setLoggedDataAttribute($logged_data)
	{
		$this->attributes['logged_data'] = json_encode($logged_data);
	}
}
