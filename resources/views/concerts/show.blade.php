<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $concert->title }}</title>
</head>
<body>

<h1>{{ $concert->title }}</h1>
<p>{{ $concert->subtitle }}</p>

<p>Date: {{ $concert->formatted_date }}</p>
<p>Doors at: {{ $concert->start_time }}</p>
<p>Price: ${{ $concert->formatted_ticket_price }}</p>

<h2>Venue Info</h2>
<p>Venue: {{ $concert->venue }}</p>
<p>Address: {{ $concert->venue_address }}</p>
<p>{{ $concert->city }}, {{ $concert->state }}</p>
<p>{{ $concert->zipcode }}</p>

<p>Additional Info: {{ $concert->additional_information }}</p>
<p></p>

</body>
</html>