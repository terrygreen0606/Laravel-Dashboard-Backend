<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
</head>
<body>
<div id="pageFooter">
    <div class="footer-content clearfix footer-content-page">
        {{-- <div class="pull-left">{!! $data['footerText'] !!}</div> --}}
        <div class="tester"></div>
        <div class="pull-right">
            {{-- {{date('d m Y', strtotime($data['dateCreated']))}}<br/> --}}
{{--            Page {{ $query['page'] }} of {{ $query['topage'] }}--}}
        </div>
    </div>
</div>
</body>
</html>