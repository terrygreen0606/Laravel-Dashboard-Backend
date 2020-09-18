<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
</head>

<body>
<div class="pageContent">
    <div class="page">
        <div class="doc-title">
            <p>SALVAGE, FIREFIGHTING &amp; LIGHTERING<br/>CONTRACT CERTIFICATION</p>
        </div>

        <div class="letter-company clearfix">
            <div class="letter-address">
                <div class="line">{{$company->name}}</div>
                <div class="line">
                    {{trim($data['address']->street) ?: ''}}
                    {{$data['address']->city ? ', '. $data['address']->city : ''}}
                    {{$data['address']->zip ? ', ' . $data['address']->zip : ''}}
                    {{$data['address']->state ? ', ' . $data['address']->state : ''}}
                    {{$data['address']->country ? ', ' . $data['address']->country : ''}}
                </div>
            </div>

            <div class="letter-date">{{date('d M Y', strtotime($data['issueDate']))}}</div>
        </div>

        <p>Donjon-SMIT LLC certifies that {{$company->name}} (Client), as owner, operator, manager and/or agent of owner
            has ensured, by contract or other approved means, the availability of private salvage, firefighting and
            lightering personnel and equipment capable of responding to a casualty to the list of Covered Vessel(s) in
            Attachment A in accordance with the Oil Pollution Act of 1990 (OPA-90) and Donjon-SMIT LLC separately
            certifies that Client has ensured, by contract or other approved means, the availability of required Vessel
            Emergency Services capable of responding within the required response times and in the specified Geographic
            Regions for the list of Covered Vessel(s) in Attachment A in accordance with the California Code of
            Regulations Title 14.</p>
        <p>Donjon-SMIT LLC agrees that Client has the right to name Donjon-SMIT LLC and its resources for OPA-90 and
            California Code of Regulations Title 14 coverage for the below listed Covered Vessel(s). Donjon-SMIT, LLC
            reserves the right to rescind this authorization in the event of termination of its contractual arrangements
            with the list of Covered Vessel(s) in Attachment A.</p>

        <div class="clearfix" style="margin-top: 30mm;">
            <div style="width:49%;float:left;">
                <div>By:</div>
                <img src="{{ public_path('images/Williamson-signature.png') }}" class="williamson-signature"/>
                <div>DONJON-SMIT, LLC</div>
                <div>Timothy P. Williamson</div>
                <div>Vice President</div>
            </div>

            <div style="width:49%;float:right;">
                Issue Date:
                {{date('d M Y', strtotime($data['issueDate']))}}
            </div>

            <br style="clear:both;"/>
        </div>
    </div>
    <div class="page">
        <div class="doc-title">
            <p>ATTACHMENT A - COVERED VESSELS</p>
        </div>
        <p>The following vessels are covered by this certification:</p>
        @include('documents.pdf-templates.partials.covered-vessels-list')
    </div>
</div>
</body>
</html>