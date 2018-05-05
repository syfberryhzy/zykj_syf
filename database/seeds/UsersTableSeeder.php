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
      $users = factory(User::class)->times(20)->make();
       User::insert($users->makeVisible(['password', 'remember_token'])->toArray());
       $user = User::find(1);
       $user->name = str_random(10);
       $user->avatar = 'http://www.gravatar.com/avatar';
       $user->email = 'aufree@yousails.com';
       $user->password = bcrypt('password');
       $user->openid = str_random(10);
       $user->phone = '18256084531';
       $user->save();
    }
}
