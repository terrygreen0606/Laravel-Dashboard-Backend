<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/smff-anex.css') }}"/>
</head>
<body>
<div id="pageHeader">
    <div class="header-content header-content-2">
        <div class="change-and-date">
            Change 0<br/>
            {{date('d M Y', strtotime($data['issueDate']))}}
        </div>
        <div class="header-title-outer-wrap">
            <div class="header-title">SALVAGE &amp; MARINE FIREFIGHTING ANNEX</div>
        </div>
    </div>
</div>
</body>
</html>