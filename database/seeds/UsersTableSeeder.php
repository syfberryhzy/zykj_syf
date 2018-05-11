<?php

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
      $users = factory(User::class)->times(20)->create();
       $user = User::find(1);
       $user->username = str_random(10);
       $user->nickname = str_random(10);
       $user->avatar = 'http://www.gravatar.com/avatar';
       $user->openid = str_random(10);
       $user->phone = '18256084531';
       $user->status = rand(0,2);
       $user->save();
    }
}
