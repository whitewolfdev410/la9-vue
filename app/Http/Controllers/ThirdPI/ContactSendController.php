<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Helpers\SearchHelper;

class ApiContactController extends ApiController
{
    /**
     * Instantiate a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('limitations')->only('setMe');
        parent::__construct();
    }

    /**
     * Get the list of the contacts.
     * We will only retrieve the contacts that are "real", not the partials
     * ones.
     *
     * @param  Request  $request
     * @return JsonResource|JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $entries = auth()->user()->account->entries()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return JournalResource::collection($entries);
    }

    /**
     * Get the detail of a given contact.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return ContactResource|JsonResponse
     */
    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof Model ? $id->{$id->getKeyName()} : $id;

        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? app($dataType->model_name)->findOrFail($id)
            : DB::table($dataType->name)->where('id', $id)->first(); // If Model doest exist, get data from table name

        foreach ($dataType->editRows as $key => $row) {
            $details = json_decode($row->details);
            $dataType->editRows[$key]['col_width'] = isset($details->width) ? $details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        $allCategories = Category::all();

        $product = Product::find($id);
        $categoriesForProduct = $product->categories()->get();

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'allCategories', 'categoriesForProduct'));
    }

    /**
     * Set how you met the contact.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return ContactResource|JsonResponse
     */
    public function updateIntroduction(Request $request, $contactId)
    {
        try {
            $contact = app(UpdateContactIntroduction::class)->execute(
                $request->except(['account_id', 'contact_id'])
                + [
                    'contact_id' => $contactId,
                    'account_id' => auth()->user()->account_id,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new ContactResource($contact);
    }
}