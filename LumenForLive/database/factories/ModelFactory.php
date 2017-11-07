<?php

$factory->define(App\User::class, function(Faker\Generator $faker) {
    static $password;

    return [
        'name'      => $faker->name,
        'email'     => $fake->unique()->safeEmail,
        'password'  => $password ?: $password = bcrypt('secret'),
        'remember'  => str_random(10),
    ];
})