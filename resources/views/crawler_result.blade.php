<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Crawler Results</title>
</head>

<body>
<section class="wrapper" style="text-align: center;">
    <div class="container">
        @if($status === TRUE)
        <h2 style="background: #0e7045c7; color: #fff; border-radius: 5px; margin: 10px  auto 0; font-size: 1.2rem; padding: 10px; border: 4px double #ffffff;" class="my" id="message">{{ $message }}</h2>
        <br><br>
        <div>
            <table style="font-family: arial, sans-serif; border-collapse: collapse; margin: 0 auto;">
                <tr style="background-color: #dddddd;">
                    <td colspan="2" style="border: 1px solid; text-align: center; padding: 8px; font-weight: bold;">Crawl Result</td>
                </tr>
                @foreach($result['details'] as $key => $value)
                    <tr style="background-color: #dddddd;" >
                        <td style="border: 1px solid; text-align: left; padding: 10px;" ><span> {{ $key }} </span></td>
                        <td style="border: 1px solid; text-align: center; padding: 10px;" ><span style="font-weight: bold;"> {{ $value }} </span></td>
                    </tr>
                @endforeach
            </table>

            <br>
            <br>

            <table style="font-family: arial, sans-serif; border-collapse: collapse; margin: 0 auto;">
                <tr style="background-color: #a0aec0" >
                    <th style="border: 1px solid; padding: 10px;">Webpage URL</th>
                    <th style="border: 1px solid; padding: 10px;">HTTP Status Code</th>
                @foreach($result['http_code'] as $value)
                    <tr style="background-color: #cbd5e0" >
                        <td style="border: 1px solid; text-align: left; padding: 10px;" ><span> {{ $value['url'] }} </span></td>
                        <td style="border: 1px solid; text-align: center; padding: 10px;" ><span style="font-weight: bold; color: @if($value['http_code'] === 200) darkgreen @else darkred @endif"> {{ $value['http_code'] }} </span></td>
                    </tr>
                @endforeach
            </table>
        </div>
        @else
        <h2 style="background: #b00020; color: #fff; border-radius: 5px; margin: 10px  auto 0; font-size: 1.2rem; padding: 10px; border: 4px double #ffffff;" class="my" id="message">{{ $message }}</h2>
        @endif
    </div>
</section>
</body>
