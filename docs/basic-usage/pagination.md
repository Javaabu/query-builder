---
title: Pagination
sidebar_position: 3
---

By default, the `index` endpoint of API controllers are paginated to `10` results per page.
Users can use the `?per_page=` query parameter to specify how many items to return per page.

## Modifying the default results per page

You can modify the default `per_page` amount of `10` by overriding the `getDefaultPerPage` method.

```php
public function getDefaultPerPage(): int
{
    return 20;
}
```

## Modifying the max results allowed per page

By default the max `per_page` value allowed is `50`.
You can modify this by overriding the `getMaxPerPage` method.

```php
public function getMaxPerPage(): int
{
    return 100;
}
```

## Allowing unlimited results

:::danger

Allowing this option can expose all of your records to the user in a single request.
This can hit your performance if there are a lot of records for the model.

:::

If you want the user to be able to return all results, without any pagination, override `allowUnlimitedResultsPerPage` method. 

```php
public function allowUnlimitedResultsPerPage(): bool
{
    return true;
}
```

Now, if the user sets `?per_page=-1`, then all results will be returned without any pagination.

