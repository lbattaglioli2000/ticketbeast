<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Concert;
use Carbon\Carbon;

class ViewConcertListingTest extends TestCase
{

    use DatabaseMigrations;

    /** @test */
    public function a_user_can_view_a_published_concert_listing()
    {
        // Arrange

        // Setting up our code

        $concert = factory(Concert::class)->states('published')->create();

        // Act

        // Run the code we want to test the outcome

        $this->visit('/concerts/' . $concert->id);



        // Assert

        // Make assertions about what happened to ensure
        // we got the outcome we expected

        $this->see('Example Band!');
        $this->see('With the fake openers!');
        $this->see('December 12, 2019');
        $this->see('6:14pm');
        $this->see('$42.00');
        $this->see('The Demonstrative Hall');
        $this->see('1 Example Blvd');
        $this->see('Exampleville');
        $this->see('NY');
        $this->see('12345');
        $this->see('This is not a real concert. For tickets to it, please call us!');
    }

    /** @test */
    public function a_user_cannot_view_an_unpublished_concert_listing()
    {
        $concert = factory(Concert::class)->states('unpublished')->create();

        $this->get('/concerts/' . $concert->id);

        $this->assertResponseStatus(404);
    }
}
