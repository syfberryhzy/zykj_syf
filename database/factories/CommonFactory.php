<?php

use Faker\Generator as Faker;
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/
$factory->define(App\Models\UserCoupon::class, function (Faker $faker) {
    $date_time = $faker->date . ' ' . $faker->time;
    return [
        'user_id' => rand(1, 20),
        'coupon_id' => rand(1, 2),
        'status' => rand(1, 3),
        'created_at' => $date_time,
        'updated_at' => $date_time,
    ];
});
