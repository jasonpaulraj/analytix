<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{config('app.name')}}</title>
    <link rel="icon" type="image/x-icon" href="{{asset('images/Analytix.ico')}}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>

<body class="text-white font-['Space_Grotesk'] bg-system-primary">
    <div class="3xs:w-[100%] xs:w-[95%] lg:w-[90%] llg:w-[75%] mx-auto">
        @yield('content')
    </div>
</body>

</html>