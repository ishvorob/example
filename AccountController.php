<?php

namespace App\Http\Controllers;

use App\Models\FormattingType;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Facades\Client;
use App\Facades\CurrentOrder;
use App\Models\UserSettings;
use App\Models\UserApiSettings;
use App\Http\Requests\UserEmailUpdateRequest;
use App\Http\Requests\UserPasswordUpdateRequest;
use App\Http\Requests\UserSettingsUpdateRequest;
use App\Http\Requests\UserOptionsUpdateRequest;
use App\Http\Requests\UserApiSettingsUpdateRequest;
use App\Mail\ChangePassword;
use Mail;

class AccountController extends Controller
{

	private $client;

	public function __construct()
	{
		$this->client = Client::getUser();
		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$user = $this->client;
		if (is_null($user->settings)) {
			$user->settings = new UserSettings();
		}

		$formatting_types = FormattingType::orderBy('id')->get() ?? [];

		foreach ($formatting_types as $k => $formatting_type) {
			$checked = ($user->settings->formattingType && $user->settings->formattingType->id === $formatting_type->id);
			$formatting_type->checked = $checked;
		}

		$this->logged_data = ['object' => $user, 'message' => 'Перегляд профілю користувача'];

		Session::put('logged_data', $this->logged_data);

		return view('user.account.index', [
			'user' => $user,
			'managers' => $this->client->managers,
			'formatting_types' => $formatting_types,
		]);
	}

	/**
	 * Updates email of logged in user
	 *
	 * @param  \App\Http\Requests\UserEmailUpdateRequest
	 * @return \Illuminate\Http\Response
	 */
	public function updateEmail(UserEmailUpdateRequest $request)
	{
		$this->client->update($request->only('email'));
		$this->logged_data = ['object' => $this->client, 'message' => 'Оновлення пошти користувача'];
		Session::put('logged_data', $this->logged_data);
		return redirect()->back()->with(['status' => __('account.update.successMessageEmail')])->withInput($request->input());
	}

	/**
	 * Updates password of logged in user
	 *
	 * @param  \App\Http\Requests\UserPasswordUpdateRequest $request
	 * @return \Illuminate\Http\Response
	 */
	public function updatePassword(UserPasswordUpdateRequest $request)
	{
		$this->client->password = Hash::make($request->get('new_password'));
		$this->client->save();
		Mail::to([$this->client->email])->send(new ChangePassword($request->get('new_password')));
		$this->logged_data = ['object' => $this->client, 'message' => 'Зміна паролю користувача'];
		Session::put('logged_data', $this->logged_data);
		return redirect()->back()->with(['status' => __('account.update.successMessagePassword')])->withInput($request->input());
	}

	/**
	 * Updates settings of logged in user
	 *
	 * @param  \App\Http\Requests\UserSettingsUpdateRequest $request
	 * @return \Illuminate\Http\Response
	 */
	public function updateSettings(UserSettingsUpdateRequest $request)
	{
		$settings = UserSettings::firstOrNew(array('user_id' => $this->client->id));
		$settings->fill($request->all());
		if (is_null($settings->user_id))
			$settings->user_id = $this->client->id;
		$settings->save();
		$this->logged_data = ['object' => $this->client, 'message' => 'Зміна налаштувань користувача'];
		Session::put('logged_data', $this->logged_data);
		return redirect()->back()->with(['status' => __('account.update.successMessageSettings')])->withInput($request->input());

	}

	/**
	 * Updates setting options of logged in user
	 *
	 *
	 * @param  \App\Http\Requests\UserOptionsUpdateRequest $request
	 * @return \Illuminate\Http\Response
	 */
	public function updateOptions(UserOptionsUpdateRequest $request)
	{
		$settings = UserSettings::firstOrNew(array('user_id' => $this->client->id));
		$settings->fill($request->all());
		$settings->save();

		$this->logged_data = ['object' => $this->client, 'message' => 'Зміна налаштувань користувача'];
		Session::put('logged_data', $this->logged_data);

		return response(['success' => true], 200);
	}

	/**
	 * @param UserApiSettingsUpdateRequest $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function updateApiSettings(UserApiSettingsUpdateRequest $request)
	{
		$api_settings = UserApiSettings::firstOrNew(array('user_id' => $this->client->id));
		$api_settings->fill($request->all());
		if (is_null($api_settings->user_id)) {
			$api_settings->user_id = $this->client->id;
		}
		if (!$request->filled('active')) {
			$api_settings->active = 0;
		}
		if (!$request->filled('only_visible')) {
			$api_settings->only_visible = 0;
		}
		$api_settings->save();
		$this->logged_data = ['object' => $this->client, 'message' => 'Зміна API налаштувань користувача'];
		Session::put('logged_data', $this->logged_data);
		return redirect()->back()->with(['status' => __('account.update.successMessageSettings')])->withInput($request->input());

	}
}
