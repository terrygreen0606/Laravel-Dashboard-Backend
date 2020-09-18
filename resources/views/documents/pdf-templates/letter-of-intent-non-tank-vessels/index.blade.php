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
<div class="pageContent">
    <div class="page">
        <div class="doc-title">
            <p>Consent Agreement<br/>For Vessel Response Plan</p>
        </div>

        <p>This Agreement is entered into in accordance with 33 Code of Federal Regulations (&ldquo;CFR&rdquo;) Part
            155, Salvage and Marine Firefighting Requirements; Nontank Vessel Response Plans; Final Rule dated September
            30, 2013 </p>

        <p>DONJON-SMIT LLC hereby agrees to provide Salvage and Marine Fire Fighting services as further referenced
            within 33 CFR 155.4030 (a through h) and these services are capable of arriving within the response times
            listed in Table 155.4030(b) and consents to be listed within Vessel Response Plans issued to the below
            referenced company.</p>

        <p>This agreement remains valid only for non-tank vessels carrying groups I through IV petroleum as fuel or
            cargo with a total capacity of less than {{$data['capacity']}} barrels.</p>

        <p>This agreement remains valid for one year from the date of issue and will renew automatically for each
            subsequent one year period.</p>

        <p>This agreement may be terminated by either party by giving the other party 30 days written notice of such
            termination.</p>


        <div style="margin: 0;clear:both;">
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

        <div style="margin-top: 30mm; clear:both;">
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

            <br style="clear:both;"/>
        </div>
    </div>
</div>
</body>
</html>