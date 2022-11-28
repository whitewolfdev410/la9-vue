<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Mail\Contact_send;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Exception;
use Mail;

class RemoteContoller extends Controller
{
    /**
     * Contact email
     * 
     * @param Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     */
    public function getRouteKey()
    {
        return app(IdHasher::class)->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        $id = $this->decodeId($value);

        return parent::resolveRouteBinding($id, $field);
    }

    protected function decodeId($value)
    {
        return app(IdHasher::class)->decodeId($value);
    }

    /**
     * Returns the available HTTP verbs and the resource URLs
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
     */
    public function optionsAction(ServerRequestInterface $request)
    {
        if (
            config('shop.authorize', true)
        ) {
            $this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
        }

        return $this->createAdmin()->options($request, (new Psr17Factory)->createResponse());
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

    public function hashID()
    {
        return $this->getRouteKey();
    }

    public function viewsPerDay(): Collection
    {
        $period = Period::make(now()->subDays(30), now());

        /** @var \Domain\Post\Collections\ViewCollection $views */
        $views = View::query()
            ->where('created_at', '>=', $period->getStart())
            ->get();

        return $views->spreadForPeriod($period);
    }

    public function votesPerDay(): Collection
    {
        $period = Period::make(now()->subDays(30), now());

        /** @var \Domain\Post\Collections\VoteCollection $votes */
        $votes = Vote::query()
            ->where('created_at', '>=', $period->getStart())
            ->get();

        try {
            $place = app(UpdatePlace::class)->execute(
                $request->except(['account_id', 'place_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'place_id' => $placeId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new PlaceResource($place);
    }

    public function deleteAction(ServerRequestInterface $request)
    {
        if (
            config('shop.authorize', true)
        ) {
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
        if (
            config('shop.authorize', true)
        ) {
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
        if (
            config('shop.authorize', true)
        ) {
            $this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
        }

        return $this->createAdmin()->patch($request, (new Psr17Factory)->createResponse());
    }


    /**
     * Creates a new resource object or a list of resource objects
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
     */
    public function postAction(ServerRequestInterface $request)
    {
        if (
            config('shop.authorize', true)
        ) {
            $this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
        }

        return $this->createAdmin()->post($request, (new Psr17Factory)->createResponse());
    }


    /**
     * Creates or updates a single resource object
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request Request object
     * @return \Psr\Http\Message\ResponseInterface Response object containing the generated output
     */
    public function putAction(ServerRequestInterface $request)
    {
        if (
            config('shop.authorize', true)
        ) {
            $this->authorize('admin', [JsonadmController::class, ['admin', 'editor', 'api']]);
        }

        return $this->createAdmin()->put($request, (new Psr17Factory)->createResponse());
    }




    public function treeAction()
    {
        try {
            $params = ['page' => 'page-catalog-tree'];

            foreach (app('config')->get('shop.page.catalog-tree') as $name) {
                $params['aiheader'][$name] = Shop::get($name)->header();
                $params['aibody'][$name] = Shop::get($name)->body();
            }

            return Response::view(Shop::template('catalog.tree'), $params)
                ->header('Cache-Control', 'private, max-age=' . config('shop.cache_maxage', 30));
        } catch (\Exception $e) {
            if ($e->getCode() >= 400 && $e->getCode() < 600) {
                abort($e->getCode());
            }
            throw $e;
        }
    }
}
