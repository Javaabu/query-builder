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
    
    $form = \App\Models\Form::whereSlug('sample_form_for_docs')->first();
    
    $controller->setAdditionalParams(compact('form'));
    
    return $controller;
}
```









