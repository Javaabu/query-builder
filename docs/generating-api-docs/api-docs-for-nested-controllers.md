---
title: API Docs for nested controllers
sidebar_position: 4
---

This package automatically determines the allowed sorts, fields, etc. for each controller by statitically creating a new controller instance and calling the relevant methods.
While this works well for basic controllers, this can present an issue for nested controllers which may require parent models to dynamically determine the allowed values.

If a nested controller requires a parent model for determining the values, then you can override the `apiDocNewControllerInstance` static method and call the `setAdditionalParams` to set the parent model(s) to some value.

```php
// in FormFieldsController.php
public static function apiDocNewControllerInstance(): static
{
    /** @var static $controller */
    $controller = app(static::class);
    
    $form = \App\Models\Form::whereSlug('sample-form-for-docs')->first();
    
    $controller->setAdditionalParams(compact('form'));
    
    return $controller;
}
```

Add you can set the relevant URL parameters to use your sample value by giving the value as the example value, by specifying controller metada.

```php
// in FormFieldsController.php

/**
 * @urlParam form_slug string required The slug of the form. Example: sample-form-for-docs
 */
class FormFieldsController extends NestedApiController
{
    // controller methods
}
```

You can do the same thing using Attributes as well.

```php
// in FormFieldsController.php
use Knuckles\Scribe\Attributes\UrlParam;

#[UrlParam('form_slug', 'string', 'The slug of the form.', required: true, example: 'sample-form-for-docs')]
class FormFieldsController extends NestedApiController
{
    // controller methods
}
```









