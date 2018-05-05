<?php

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $category_ids = rand(1, 9);
      $faker = app(Faker\Generator::class);
      $galleries = factory(Product::class)->times(50)->make()->each(function ($gallery) use ($faker, $category_ids) {
        $gallery->category_id = $faker->randomElement($category_ids);
        $gallery->category_id = $faker->randomElement($category_ids);
        $gallery->category_id = $faker->randomElement($category_ids);
      });
      Product::insert($galleries->toArray());
    }
}
