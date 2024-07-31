<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>LIVE STREAM</title>
        <!-- Styles -->
        <style>
            #body {
                margin: 0px;
                padding: 0px;
            }
            #flex {
                position: fixed;
                height: 100%;
                width: 100%;
            }
            #frame {
                border: none;
                display: block;
                height: 100%;
                width: 100%;
            }
        </style>
    </head>
    <body id="body">
        <div id="flex">
           <iframe id="frame" src="https://youtube.com//embed/{{$id}}" frameborder="0"></iframe>
        </div>
    </body>
</html>