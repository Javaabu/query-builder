<?php

namespace Javaabu\QueryBuilder\Tests;

trait InteractsWithDatabase
{
    protected function runMigrations()
    {
        include_once __DIR__ . '/database/create_brands_table.php';
        include_once __DIR__ . '/database/create_products_table.php';

        (new \CreateBrandsTable)->up();
        (new \CreateProductsTable)->up();
    }
}
