---
title: Additional method overrides
sidebar_position: 2
---

Apart from the required methods that have to be implemented, API controllers also include other methods than can be overriden. 

## modifyModel()

This method can be used to set additional attributes or hide specific attributes on the model before returning the results.

```php
public function modifyModel(Model $model): Model
{
    $model->setHidden('media');
    
    $model->some_random_fake_attribute = 'some value';

    return $model;
}
```

## authorizeView()

Set authorization on the show endpoint.

```php
protected function authorizeView(Model $model): void
{
    $this->authorize('view', $model);
}
```

## getFieldsToAlwaysInclude

Specifies which fields to always include, regardless of what the user provides in the `?fields=` query parameter.
By default this is set to return the `id` field.

```php
public function getFieldsToAlwaysInclude(): array
{
    return [
        'id'
    ];
}
```

## getIndexAllowedFields

Override this method if you want to allow different fields just for the `index` endpoint.

```php
public function getIndexAllowedFields(): array
{
    return $this->getAllowedFields();
}
```

## getIndexValidation

Override this method to specify any validation rules to run for the `index` endpoint.
The default validation rules are given below.

```php
protected function getIndexValidation(): array
{
    return [
        'per_page' => "integer|" . ($this->allowUnlimitedResultsPerPage() ? 'min:-1' : 'between:1,' . $this->getMaxPerPage()),
        'page' => 'integer|min:1',
    ];
}
```

## getShowValidation

Override this method to specify any validation rules to run for the `show` endpoint.
The default validation rules are given below.

```php
protected function getShowValidation(): array
{
    return [];
}
```

## getShowAllowedAppends

Override this method if you want to specify specific `appends` just for the `show` endpoint.
These will be merged with the `getAllowedAppends`.

```php
public function getShowAllowedAppends(): array
{
    return [];
}
```

## getAllShowAllowedAppends

Override this method if you want to specify specific `appends` just for the `show` endpoint without merging with the `getAllowedAppends`.

```php
public function getAllShowAllowedAppends(): array
{
    return [
        'formatted_name',
    ];
}
```

