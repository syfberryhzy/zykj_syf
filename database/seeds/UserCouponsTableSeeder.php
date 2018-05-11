<?php

use Illuminate\Database\Seeder;

class UserCouponsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = factory(App\Models\UserCoupon::class)->times(20)->create();
        $user = App\Models\UserCoupon::find(1);
        $user->save();
    }
}
