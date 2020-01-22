<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Lato:100" rel="stylesheet" type="text/css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

    <title>SMS Management System</title>
</head>

{{--<style>--}}
{{--    html, body {--}}
{{--        height: 100%;--}}
{{--    }--}}

{{--    body {--}}
{{--        margin: 0;--}}
{{--        padding: 0;--}}
{{--        width: 100%;--}}
{{--        display: table;--}}
{{--        font-weight: 100;--}}
{{--        font-family: 'Lato';--}}
{{--    }--}}

{{--    .container {--}}
{{--        text-align: center;--}}
{{--        display: table-cell;--}}
{{--        vertical-align: middle;--}}
{{--    }--}}

{{--    .content {--}}
{{--        text-align: center;--}}
{{--        display: inline-block;--}}
{{--    }--}}

{{--    .title {--}}
{{--        font-size: 96px;--}}
{{--    }--}}
{{--</style>--}}

    <body>
        <div class="container">
        <div class="content">
            <div class="title">Client List</div>
            <table class="table table-bordered">
                <thead>
                    <th>Sl.</th>
                    <th>Client</th>
                    <th>Type</th>
                    <th>Mask</th>
                    <th>Masking Rate</th>
                    <th>Non Masking Rate</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th>Created Date</th>
                    <th>Updated Date</th>
                    <th>Action</th>
                </thead>
                <tbody>
                @foreach($clients as $index => $client)
                    <tr>
                        <td>{{++$index}}</td>
                        <td>{{$client->client_id}}</td>
                        <td>{{$client->type}}</td>
                        <td>{{$client->mask}}</td>
                        <td>{{$client->masking_rate}}</td>
                        <td>{{$client->no_masking_rate}}</td>
                        <td>{{$client->balance}}</td>
                        <td>{{$client->status}}</td>
                        <td>{{$client->created_at}}</td>
                        <td>{{$client->updated_at}}</td>
                        <td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

        <!-- Optional JavaScript -->
        <!-- jQuery first, then Popper.js, then Bootstrap JS -->
        <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    </body>
</html>
