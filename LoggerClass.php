<?php

namespace App\Classes;

use App\Models\Logger as Log;
use App\Models\LoggerType;
use App\Models\User;
use App\Models\SearchQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class Logger
{
	private $app;

	private $log_type;

	private $user = null;

	private $site = null;

	private $specific_routes = ['login', 'update-password', 'password'];

	private $specific_parameters = ['draw', 'columns', 'order', 'start', 'length'];

	public function __construct($app, $site = null)
	{
		$this->site = (!is_null($site)) ? $site->id : null;
		$this->app = $app;

		if (!$this->app->runningInConsole()) {
			$this->user = (Auth::user()) ? Auth::user()->getAuthIdentifier() : null;
		}

		$this->log_type = ($this->app->runningInConsole()) ? LoggerType::where('slug', 'console')->pluck('id')->first() : LoggerType::where('slug', 'default')->pluck('id')->first();
	}

	/**
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return void
	 *
	 */
	public function logWebActivity(Request $request)
	{
		$request_data = $this->getRequestData($request);
		$name = $request->route()->getName();
		$log_type = $this->log_type;

		$logged_data = $this->getDataByKey($request->session()->all(), 'logged_data');

		$logger = new Log(['user_id' => $this->user, 'site' => $this->site, 'path' => $request->path(), 'logged_data' => $request_data, 'log_type' => $log_type, 'name' => $name]);

		if (!is_null($logged_data)) {
			$object = $this->getDataByKey($logged_data, 'object');
			$message = $this->getDataByKey($logged_data, 'message');
			$log_type = $this->updateLogType($logged_data);

			if (is_null($this->user) && $object instanceof User) {
				$logger->fill(['user_id' => $object->id]);
			}

			if (isset($logged_data['log_type']) && ($logged_data['log_type'] == 'search')) {
				$this->saveSearch($request->all());
			}

			$logger->fill(['message' => $message, 'log_type' => $log_type]);

			$request->session()->forget('logged_data');
		}

		if (isset($object) && !is_null($object))
			$object->logger()->save($logger);
		else
			$logger->save();
	}


	/**
	 * @return void
	 *
	 */
	public function logConsoleActivity($command_arguments, $command_options, $message)
	{
		$command_name = null;
		if (is_array($command_arguments) && array_key_exists('command', $command_arguments)) {
			$command_name = $command_arguments['command'];
			unset($command_arguments['command']);
		}

		$logger = new Log(['user_id' => $this->user, 'site' => $this->site, 'logged_data' => ['arguments' => $command_arguments, 'options' => $command_options], 'log_type' => $this->log_type, 'name' => $command_name, 'message' => $message]);
		$logger->save();
	}

	/**
	 * @param array $haystack
	 * @param string $searched
	 * @return mixed
	 */
	public function getDataByKey($haystack, $searched)
	{
		$result = ((is_array($haystack) && array_key_exists($searched, $haystack))) ? $haystack[$searched] : null;
		return $result;
	}

	/**
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	private function getRequestData($request)
	{
		if (!in_array($request->segment(2), $this->specific_routes) && !in_array($request->segment(3), $this->specific_routes)) {
			$request_data = $request->all();
			if ($request->isMethod('GET')) {
				foreach ($this->specific_parameters as $key => $value) {
					if (isset($request_data[$value])) {
						unset($request_data[$value]);
					}
				}
			}
			return $request_data;
		}
		return null;
	}

	private function updateLogType($logged_data)
	{
		$type = $this->getDataByKey($logged_data, 'log_type');
		if (!is_null($type) && ($log_type = LoggerType::where('slug', $type)->pluck('id')->first())) {
			return $log_type;
		}

		return $this->log_type;
	}

	private function saveSearch($haystack)
	{
		if (array_key_exists('query', $haystack) && empty(array_intersect_key($haystack, array_flip($this->specific_parameters))) && (trim($haystack['query'] != ''))) {
			$searchQuery = SearchQuery::firstOrNew(array('query' => trim($haystack['query']), 'user_id' => $this->user));
			$searchQuery->count = $searchQuery->count + 1;
			$searchQuery->save();
		}
	}


}