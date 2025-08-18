<?php

namespace Javaabu\QueryBuilder\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Javaabu\QueryBuilder\Tests\Controllers\ProductsController;
use Javaabu\QueryBuilder\Tests\InteractsWithDatabase;
use Javaabu\QueryBuilder\Tests\Models\Brand;
use Javaabu\QueryBuilder\Tests\Models\Product;
use Javaabu\QueryBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\QueryBuilder\Exceptions\InvalidFieldQuery;

class ApiControllerTest extends TestCase
{
    use InteractsWithDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->runMigrations();
        $this->registerApiRoutes();

    }

    protected function registerApiRoutes()
    {
        Route::get('/products', [ProductsController::class, 'index'])->name('products.index');
        Route::get('/products/{id}', [ProductsController::class, 'show'])->name('products.show');
    }

    #[Test]
    public function it_can_get_index_method_api_doc_metadata(): void
    {
        $params = ProductsController::apiDocControllerMethodMetadata('index');

        $this->assertIsArray($params);

        $this->assertEquals('Products', $params['groupName']);
        $this->assertEquals('Endpoints for listing and viewing products', $params['groupDescription']);
        $this->assertEquals('List all products', $params['title']);
        $this->assertEquals('Fetch all products. Supports filtering, sorting, pagination and field selection.', $params['description']);
    }

    #[Test]
    public function it_can_get_show_method_api_doc_metadata(): void
    {
        $params = ProductsController::apiDocControllerMethodMetadata('show');

        $this->assertIsArray($params);

        $this->assertEquals('Products', $params['groupName']);
        $this->assertEquals('Endpoints for listing and viewing products', $params['groupDescription']);
        $this->assertEquals('View a single product', $params['title']);
        $this->assertEquals('Fetch a single product. Supports field selection.', $params['description']);
    }

    #[Test]
    public function it_can_get_other_methods_api_doc_metadata(): void
    {
        $params = ProductsController::apiDocControllerMethodMetadata('fake');

        $this->assertIsArray($params);

        $this->assertEquals('Products', $params['groupName']);
        $this->assertEquals('Endpoints for listing and viewing products', $params['groupDescription']);

        $this->assertArrayNotHasKey('title', $params);
        $this->assertArrayNotHasKey('description', $params);
    }

    #[Test]
    public function it_can_get_index_method_api_doc_query_params(): void
    {
        $params = ProductsController::apiDocControllerMethodQueryParameters('index');

        $this->assertIsArray($params);

        $this->assertArrayHasKey('fields', $params);
        $this->assertArrayHasKey('include', $params);
        $this->assertArrayHasKey('append', $params);
        $this->assertArrayHasKey('sort', $params);
        $this->assertArrayHasKey('per_page', $params);
        $this->assertArrayHasKey('page', $params);
        $this->assertArrayHasKey('filter[name]', $params);
        $this->assertArrayHasKey('filter[search]', $params);
    }

    #[Test]
    public function it_can_get_show_method_api_doc_query_params(): void
    {
        $params = ProductsController::apiDocControllerMethodQueryParameters('show');

        $this->assertIsArray($params);

        $this->assertArrayHasKey('fields', $params);
        $this->assertArrayHasKey('include', $params);
        $this->assertArrayHasKey('append', $params);

        $this->assertArrayNotHasKey('sort', $params);
        $this->assertArrayNotHasKey('per_page', $params);
        $this->assertArrayNotHasKey('page', $params);
        $this->assertArrayNotHasKey('filter[name]', $params);
        $this->assertArrayNotHasKey('filter[search]', $params);
    }

    #[Test]
    public function it_can_list_api_models(): void
    {
        $products = Product::factory()->count(10)->create();

        $this->getJson('/products')
             ->assertSuccessful();
    }

    #[Test]
    public function it_can_show_api_models(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/products/' . $product->id)
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $product->name,
            ]);
    }

    #[Test]
    public function it_can_filter_api_models(): void
    {
        $product_1 = Product::factory()->create([
            'name' => 'Apple'
        ]);

        $product_2 = Product::factory()->create([
            'name' => 'Orange'
        ]);

        $this->getJson('/products?filter[search]=pple')
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $product_1->id,
            ])
            ->assertJsonMissing([
                'id' => $product_2->id,
            ]);
    }

    #[Test]
    public function it_can_load_api_model_relations(): void
    {
        $this->withoutExceptionHandling();

        $brand = Brand::factory()->create([
            'name' => 'Vanhouten'
        ]);

        $product = Product::factory()->create([
            'name' => 'Apple',
            'brand_id' => $brand->id
        ]);

        $this->getJson('/products?include[]=brand')
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $brand->name,
            ]);
    }

    #[Test]
    public function it_can_load_api_model_appends(): void
    {
        $this->withoutExceptionHandling();

        $product = Product::factory()->create([
            'name' => 'Apple',
            'slug' => 'orange',
        ]);

        $this->getJson('/products?fields=id,formatted_name')
            ->assertSuccessful()
            ->assertJsonFragment([
                'formatted_name' => 'Formatted Apple',
            ])
            ->assertJsonMissing([
                'slug' => 'orange',
            ]);
    }

    #[Test]
    public function it_can_list_only_specific_api_model_fields(): void
    {
        $this->withoutExceptionHandling();

        $product = Product::factory()->create([
            'name' => 'Apple',
            'slug' => 'orange',
        ]);

        $this->getJson('/products?fields=id,name&append=')
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'Apple',
            ])
            ->assertJsonMissing([
                'slug' => 'orange',
            ]);
    }

    #[Test]
    public function it_can_load_api_model_appends_from_fields_even_if_appends_is_blank(): void
    {
        $this->withoutExceptionHandling();

        $product = Product::factory()->create([
            'name' => 'Apple',
            'slug' => 'orange',
        ]);

        $this->getJson('/products?fields=id,formatted_name&append=')
            ->assertSuccessful()
            ->assertJsonFragment([
                'formatted_name' => 'Formatted Apple',
            ])
            ->assertJsonMissing([
                'slug' => 'orange',
            ]);
    }

    #[Test]
    public function it_can_include_dynamic_fields(): void
    {
        $this->withoutExceptionHandling();

        $product_1 = Product::factory()->create([
            'name' => 'Apple'
        ]);

        $product_2 = Product::factory()->create([
            'name' => 'Orange'
        ]);

        $this->getJson('/products?fields=id,rating&rating=10')
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $product_1->id,
                'rating' => $product_1->id + 10,
            ])
            ->assertJsonFragment([
                'id' => $product_2->id,
                'rating' => $product_2->id + 10,
            ]);
    }

    #[Test]
    public function it_can_sort_by_dynamic_fields(): void
    {
        $this->withoutExceptionHandling();

        $product_1 = Product::factory()->create([
            'name' => 'Apple'
        ]);

        $product_2 = Product::factory()->create([
            'name' => 'Orange'
        ]);

        $this->getJson('/products?fields=id,name&rating=10&sort=rating')
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $product_1->id,
            ])
            ->assertJsonFragment([
                'id' => $product_2->id,
            ]);
    }

    #[Test]
    public function it_cannot_include_dynamic_fields_if_required_params_are_missing(): void
    {
        $this->withoutExceptionHandling();

        $product_1 = Product::factory()->create([
            'name' => 'Apple'
        ]);

        $product_2 = Product::factory()->create([
            'name' => 'Orange'
        ]);

        $this->expectException(InvalidFieldQuery::class);

        $this->getJson('/products?fields=id,rating')
            ->assertJsonMissing([
                'id' => $product_1->id,
                'rating' => $product_1->id + 10,
            ])
            ->assertJsonMissing([
                'id' => $product_2->id,
                'rating' => $product_2->id + 10,
            ]);
    }
}
