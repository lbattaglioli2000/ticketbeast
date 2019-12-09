<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Concert::class, function (Faker\Generator $faker){
    return [
        'title' => 'Example Band!',
        'subtitle' => 'With the fake openers!',
        // Should be in the future
        'date' => Carbon\Carbon::parse('December 12, 2019 6:14pm'),
        'ticket_price' => 4200,
        'venue' => 'The Demonstrative Hall',
        'venue_address' => '1 Example Blvd',
        'city' => 'Exampleville',
        'state' => 'NY',
        'zipcode' => '12345',
        'additional_information' => 'This is not a real concert. For tickets to it, please call us!'
    ];
});

$factory->state(\App\Concert::class, 'published', function(Faker\Generator $faker){
    return [
        'title' => 'Example Band!',
        'subtitle' => 'With the fake openers!',
        // Should be in the future
        'date' => Carbon\Carbon::parse('December 12, 2019 6:14pm'),
        'ticket_price' => 4200,
        'venue' => 'The Demonstrative Hall',
        'venue_address' => '1 Example Blvd',
        'city' => 'Exampleville',
        'state' => 'NY',
        'zipcode' => '12345',
        'additional_information' => 'This is not a real concert. For tickets to it, please call us!',
        'published_at' => \Carbon\Carbon::parse('+1 week')
    ];
});

$factory->state(\App\Concert::class, 'unpublished', function(Faker\Generator $faker){
    return [
        'title' => 'Example Band!',
        'subtitle' => 'With the fake openers!',
        // Should be in the future
        'date' => Carbon\Carbon::parse('December 12, 2019 6:14pm'),
        'ticket_price' => 4200,
        'venue' => 'The Demonstrative Hall',
        'venue_address' => '1 Example Blvd',
        'city' => 'Exampleville',
        'state' => 'NY',
        'zipcode' => '12345',
        'additional_information' => 'This is not a real concert. For tickets to it, please call us!',
        'published_at' => null
    ];
});