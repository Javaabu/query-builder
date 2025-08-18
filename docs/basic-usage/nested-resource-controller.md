---
title: Nested resource controller
sidebar_position: 2
---

To create a nested API resource controller using this package, create a controller that extends the `Javaabu\QueryBuilder\Http\Controllers\NestedApiController` class.

```php
<?php

namespace App\Http\Controllers\Api;

use Javaabu\QueryBuilder\Http\Controllers\NestedApiController;
use App\Models\Form;
use App\Models\FormFields;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;

class FormFieldsController extends NestedApiController
{
    public function index(Form $form, Request $request)
    {
        $this->setAdditionalParams(compact('form'));      

        return $this->indexEndpoint($request);
    }
    
    public function show(Form $form, $model_id, Request $request)
    {
        $this->setAdditionalParams(compact('form'));      

        return $this->showEndpoint($model_id, $request);
    }
    
    /**
     * Get the base query
     *
     * @return Builder
     */
    public function getBaseQuery(): Builder
    {
        $form = $this->getAdditionalParams('form');
        
        return FormFields::query()
                    ->where('form_id', $form->id);
    }

    /**
     * Get the allowed fields
     *
     * @return array
     */
    public function getAllowedFields(): array
    {
        return array_diff(\Schema::getColumnListing('form_fields'), (new FormField)->getHidden());
    }

    /**
     * Get the allowed includes
     *
     * @return array
     */
    public function getAllowedIncludes(): array
    {
        return [           
        ];
    }

    /**
     * Get the allowed appends
     *
     * @return array
     */
    public function getAllowedAppends(): array
    {
        return [
            
        ];
    }

    /**
     * Get the allowed sorts
     *
     * @return array
     */
    public function getAllowedSorts(): array
    {
        return [
            'id',
            'created_at',
            'updated_at',
            'slug',
            'name',
        ];
    }

    /**
     * Get the default sort
     *
     * @return string
     */
    public function getDefaultSort(): string
    {
        return '-created_at';
    }

    /**
     * Get the allowed filters
     *
     * @return array
     */
    public function getAllowedFilters(): array
    {
        return [
            'id',
            'slug',
            'name',            
            AllowedFilter::scope('search'),
        ];
    }
}
```

For nested controllers, you will have to manually define the `index` and `show` methods, which will enable you to add dependency injection for the parent model.
Use the `setAdditionalParams()` method to set the parent models.

```php
public function index(Form $form, Request $request)
{
    $this->setAdditionalParams([
        'form' => $form 
    ]);      

    return $this->indexEndpoint($request);
}

public function show(Form $form, $model_id, Request $request)
{
    $this->setAdditionalParams(compact('form'));      

    return $this->showEndpoint($model_id, $request);
}
```

You can use the `getAdditionalParams()` method in other methods to retrieve a previously set named parent model.

```php
public function getBaseQuery(): Builder
{
    $form = $this->getAdditionalParams('form');
    
    return FormFields::query()
                ->where('form_id', $form->id);
}
```

If you don't specify a parameter name for the `getAdditionalParams()` method, then it will return all the additional parameters.

For frequently access additional parameters, you can create helper methods in your controller with defined return types. This will also help with intellisense.

```php
protected function getForm(): Form
{
    return $this->getAdditionalParams('form');
}

public function getBaseQuery(): Builder
{
    $form = $this->getForm();
    
    return FormFields::query()
                ->where('form_id', $form->id);
}
```

Once the `index` and `show` methods are defined, you can use them to define the routes in your `api.php` route file.

```php
// in api.php route file

/**
 * Form Fields
 */
Route::get('forms/{form}/fields', [\App\Controllers\Api\FormFieldsController::class, 'index'])->name('forms.fields.index');
Route::get('forms/{form}/fields/{id}', [\App\Controllers\Api\FormFieldsController::class, 'show'])->name('forms.fields.show');
```

## Multi level deep nested controllers.

This package does not limit how deep you can nest your API resources. But we recommend not nest more than 1 level deep.
To nest more than 1 level deep, just include your parent models, in the `index` and `show` methods and add set those as additional parameters.

```php
// FormFieldOptionsController.php

public function index(Form $form, FormField $field, Request $request)
{
    $this->setAdditionalParams(compact('form', 'field'));      

    return $this->indexEndpoint($request);
}

public function show(Form $form, FormField $field, $model_id, Request $request)
{
    $this->setAdditionalParams(compact('form', 'field'));      

    return $this->showEndpoint($model_id, $request);
}

public function getBaseQuery(): Builder
{
    $form = $this->getAdditionalParams('form');
    
    $field = $this->getAdditionalParams('field');
    
    return FormFieldOption::query()
                ->whereHas('formField', function (Builder $query) use ($form, $field) {
                    $query->where('form_fields.id', $field->id)
                          ->where('form_fields.form_id', $form->id)
                });
}
```

Then in your `api.php` route file, you can define the routes like so:

```php
// in api.php route file

/**
 * Form Field Options
 */
Route::get('forms/{form}/fields/{field}/options', [\App\Controllers\Api\FormFieldOptionsController::class, 'index'])->name('forms.fields.options.index');
Route::get('forms/{form}/fields/{field}/options/{id}', [\App\Controllers\Api\FormFieldOptionsController::class, 'show'])->name('forms.fields.options.show');
```

