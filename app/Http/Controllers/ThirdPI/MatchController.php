<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Mail\Contact_send;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Exception;
use Mail;

class MatchController extends Controller
{
	/**
	 * Contact email
	 * 
	 * @param Illuminate\Http\Request $request
	 * @return Illuminate\Http\Response
	 */
	public function activityType(Request $request)
	{

		$activityTypeCategoriesData = collect([]);
		$activityTypeCategories = auth()->user()->account->activityTypeCategories;

		foreach ($activityTypeCategories as $activityTypeCategory) {
			$activityTypesData = collect([]);
			$activityTypes = $activityTypeCategory->activityTypes;

			foreach ($activityTypes as $activityType) {
				$dataActivityType = [
					'id' => $activityType->id,
					'name' => $activityType->name,
				];
				$activityTypesData->push($dataActivityType);
			}

			$data = [
				'id' => $activityTypeCategory->id,
				'name' => $activityTypeCategory->name,
				'activityTypes' => $activityTypesData,
			];
			$activityTypeCategoriesData->push($data);
		}

		return $activityTypeCategoriesData;
	}



	public function getCurrentSyncToken($collectionId): ?SyncToken
	{
		$tokens = SyncToken::where([
			'account_id' => $this->user->account_id,
			'user_id' => $this->user->id,
			'name' => $collectionId ?? $this->backendUri(),
		])
			->orderBy('created_at')
			->get();

		return $tokens->count() > 0 ? $tokens->last() : null;
	}

	public static function processEntitySelect(&$element, FormStateInterface $form_state, &$complete_form)
	{
		// Nothing to do if there is no target entity type.
		if (empty($element['#target_type'])) {
			throw new \InvalidArgumentException('Missing required #target_type parameter.');
		}

		// These keys only make sense on the actual input element.
		foreach (['#title', '#title_display', '#description', '#ajax'] as $key) {
			if (isset($element[$key])) {
				$element['value'][$key] = $element[$key];
				unset($element[$key]);
			}
		}

		return $element;
	}

	public function indexAction()
	{
		$params = ['page' => 'page-basket-index'];

		foreach (app('config')->get('shop.page.basket-index') as $name) {
			$params['aiheader'][$name] = Shop::get($name)->header();
			$params['aibody'][$name] = Shop::get($name)->body();
		}

		return Response::view(Shop::template('basket.index'), $params)
			->header('Cache-Control', 'no-store, , max-age=0');
	}

	public function deleteAction(ServerRequestInterface $request)
	{
		if (config('shop.authorize', true)) {
			$this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
		}

		return $this->createAdmin()->delete($request, (new Psr17Factory)->createResponse());
	}


	/**
	 * Returns the requested resource object or list of resource objects
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
	 */
	public function getAction(ServerRequestInterface $request)
	{
		if (config('shop.authorize', true)) {
			$this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
		}

		return $this->createAdmin()->get($request, (new Psr17Factory)->createResponse());
	}


	/**
	 * Updates a resource object or a list of resource objects
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
	 */
	public function patchAction(ServerRequestInterface $request)
	{
		return $this->createClient()->patch($request, (new Psr17Factory)->createResponse());
	}


	/**
	 * Creates a new resource object or a list of resource objects
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
	 */
	public function postAction(ServerRequestInterface $request)
	{
		return $this->createClient()->post($request, (new Psr17Factory)->createResponse());
	}


	/**
	 * Creates or updates a single resource object
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request Request object
	 * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
	 */
	public function putAction(ServerRequestInterface $request)
	{
		return $this->createClient()->put($request, (new Psr17Factory)->createResponse());
	}


	/**
	 * Returns the JsonAdm client
	 *
	 * @return \Aimeos\Admin\JsonAdm\Iface JsonAdm client
	 */
	protected function createAdmin(): \Aimeos\Admin\JsonAdm\Iface
	{
		$site = Route::input('site', Request::get('site', config('shop.mshop.locale.site', 'default')));
		$lang = Request::get('locale', config('app.locale', 'en'));
		$resource = Route::input('resource', '');

		$aimeos = app('aimeos')->get();
		$templatePaths = $aimeos->getTemplatePaths('admin/jsonadm/templates');

		$context = app('aimeos.context')->get(false, 'backend');
		$context->setI18n(app('aimeos.i18n')->get(array($lang, 'en')));
		$context->setLocale(app('aimeos.locale')->getBackend($context, $site));
		$context->setView(app('aimeos.view')->create($context, $templatePaths, $lang));

		return \Aimeos\Admin\JsonAdm::create($context, $aimeos, $resource);
	}

	public function indexAction()
	{
		$params = ['page' => 'page-index'];

		foreach (app('config')->get('shop.page.cms', ['cms/page', 'catalog/tree', 'basket/mini']) as $name) {
			$params['aiheader'][$name] = Shop::get($name)->header();
			$params['aibody'][$name] = Shop::get($name)->body();
		}

		if (empty($params['aibody']['cms/page'])) {
			abort(404);
		}

		return Response::view(Shop::template('page.index'), $params)
			->header('Cache-Control', 'private, max-age=10');
	}
}
