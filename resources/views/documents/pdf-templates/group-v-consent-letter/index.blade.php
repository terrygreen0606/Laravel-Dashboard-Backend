<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style type="text/css">
        .signature-and-date {
            width: 100%;
            margin-top: 25mm;
            vertical-align: top;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
</head>

<body>
<div class="pageContent" style="">
    <div class="page">
        <br>
        <div class="doc-title">
            <p>Consent Agreement<br/>For Vessel Response Plan</p>
        </div>
        <p>DONJON-SMIT LLC hereby agrees to provide Salvage and Marine Fire Fighting services as referenced within 33
            CFR 155.1052(f) and consents to be listed within Vessel Response Plans issued by the below referenced
            company.</p>

        <p>This agreement remains valid only for vessels carrying <u>Group V</u> petroleum as cargo.</p>

        <p>This agreement remains valid for one year from the date of issue and will renew automatically for each
            subsequent one year period.</p>

        <p>This agreement may be terminated by either party by giving the other party 30 days written notice of such
            termination.</p>

        <div class="clearfix">
            <div style="width:19%;float:left;">Company:</div>
            <div style="width:79%;float:right;">
                {{$company->name}}<br/>
                <div>
                    <div class="line">{{trim($data['address']->street)}}</div>
                    <div class="line">
                        {{$data['address']->city ?: ''}}
                        {{$data['address']->zip ? ', ' . $data['address']->zip : ''}}
                        {{$data['address']->state ? ', ' . $data['address']->state : ''}}
                        {{$data['address']->country ? ', ' . $data['address']->country : ''}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix" style="margin-top: 30mm;">
        <div style="width:49%; float:left; margin-left:20px">
            <div>By:</div>
            <img src="{{ public_path('images/Williamson-signature.png') }}" class="williamson-signature"/>
            <div>DONJON-SMIT, LLC</div>
            <div>Timothy P. Williamson</div>
            <div>General Manager</div>
        </div>

        <div style="width:40%;float:right;">
            Issue Date: {{date('d M Y', strtotime($data['issueDate']))}}
        </div>
    </div>
</div>
</body>
</html>