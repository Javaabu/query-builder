---
title: Customizing API Docs Metadata
sidebar_position: 2
---

This package will try to automatically generate documentation for the sorts, fields, appends, includes, and filters you have defined for your API controllers.
The package uses the class name to determine the resource name and the resource group. You can see the relevant methods in `Javaabu\QueryBuilder\Concerns\ApiDocHelpers` trait.

While the automatically generated documentation might be sufficient for most use cases, we recommend you to override some of the methods to provide more contextual documentation to developers.

Here are some of the methods we recommend to override.

## apiDocFilterMetadata()

Use this method to describe what each filter does. Will support all properties allowed by `Knuckles\Scribe\Attributes\QueryParam` Attribute.

```php
// in ProductsController.php
public static function apiDocFilterMetadata(): array
{
    return [
        'id' => [
            'type' => 'integer',
        ],

        'search' => [
            'type' => 'string',
            'description' => 'Search products by name.',
        ],
    ];
}
```


## apiDocGroupDescription()

Use this method to describe what your resource is.

```php
// in ProductsController.php
public static function apiDocGroupDescription(): string
{
    return 'Endpoints for listing and viewing products.';
}
```

Apart from these methods, you can still use Sribe docblocks, Attributes and `inheritedDocsOverrides` to fully customize the documentation.
