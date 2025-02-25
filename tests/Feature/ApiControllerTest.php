<?php

namespace Javaabu\QueryBuilder\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Javaabu\QueryBuilder\Tests\Controllers\ProductsController;
use Javaabu\QueryBuilder\Tests\InteractsWithDatabase;
use Javaabu\QueryBuilder\Tests\Models\Brand;
use Javaabu\QueryBuilder\Tests\Models\Product;
use Javaabu\QueryBuilder\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

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
}
