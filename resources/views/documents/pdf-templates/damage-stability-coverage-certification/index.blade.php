<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
    <style type="text/css">
        .doc-title {
            color: #4f81bd;
            font-size: 42px;
            font-weight: bold;
            font-style: italic;
        }

        table th,
        table td {
            text-align: left;
            vertical-align: top;
        }

        thead {
            display: table-header-group
        }

        tfoot {
            display: table-row-group
        }

        tr {
            page-break-inside: avoid
        }
    </style>
</head>

<body>
<br/>
<div class="pageContent">
    <div class="page">
        <div class="doc-title">
            <p style="font-size: 42px !important;">DAMAGED STABILITY ASSESSMENT PROVIDER CERTIFICATE</p>
        </div>

        <div class="clearfix">
            <div class="pull-right" style="text-align:right;">
                <div>Certificate Number: {{$data['certificateNumber']}}</div>
                <div>{{$data['certificateRevision']}}</div>
                <div>Date: {{date('d M Y', strtotime($data['issueDate']))}}</div>
                <div>Location: Houston, TX</div>
            </div>
        </div>

        <div class="clearfix" style="margin-top: 10mm;margin-bottom: 15mm;">
            <div style="width:10%;float:left;">
                Re:
            </div>

            <div style="width:89%;float:right;">
                <div>Annex I to MARPOL 73/78, Regulation 37.4</div>
                <div>Access to Damage Stability and Residual Structural Strength Calculations</div>
                <div>US 33 CFR 155 (Oil Pollution Act of 1990)</div>
            </div>
        </div>

        <p>Donjon-SMIT LLC certifies herein, in accordance with revised Regulation 37.4 of Annex I to MARPOL 73/78 and
            US 33 CFR 155, that the below listed vessel(s) are under contract with Donjon-SMIT LLC for prompt access to
            computerized shore-based damage stability and residual structural strength calculations programs.
            Furthermore, Donjon-SMIT LLC certifies that an electronic model has been made for each of the listed
            vessels.</p>

    </div>
    <div class="page">
        <div class="clearfix" style="padding-top: 5mm;">

            <div style="width: 100%;">
                <table style="width: 100%;">
                    <thead>
                    <tr>
                        <th colspan="4">
                            <div class="doc-title">
                                <p style="font-size: 42px !important;">DAMAGED STABILITY ASSESSMENT PROVIDER CERTIFICATE</p>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th>VESSEL NAME</th>
                        <th style="width:25mm;">IMO #</th>
                        <th>VESSEL NAME</th>
                        <th style="width:25mm;">IMO #</th>
                    </tr>
                    </thead>
                    <tbody>
                    @for ($i = 0,$iMax = count($vessels)-1; $i < $iMax; $i += 2)
                        <tr>
                            <td>{{$vessels[$i]->name}}</td>
                            <td>{{$vessels[$i]->imo}}</td>
                            <td>{{$vessels[$i+1]->name}}</td>
                            <td>{{$vessels[$i+1]->imo}}</td>
                        </tr>
                    @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="page">
        <div class="doc-title">
            <p style="font-size: 42px !important;">DAMAGED STABILITY ASSESSMENT PROVIDER CERTIFICATE</p>
        </div>
        <p><b>Donjon-SMIT LLC</b> verifies, in accordance with revised Regulation 37.4 of Annex I to MARPOL 73/78, that
            Donjon-SMIT LLC is capable of providing access to computerized shore-based damage stability and residual
            structural strength calculations programs.</p>
        <p>This certificate remains valid subject to annual verification and renewal of the terms and conditions of the
            "<b>OPA 90 ACCESS AGREEMENT; DAMAGED STABILITY CALCULATIONS.</b>"</p>

        <p>Access to Donjon-SMIT LLC's damage stability and residual structural strength calculations programs is
            available at any time via telephone at +1 703 299 0081. This number is answered by a duty officer 24
            hours-a-day, 7 days-a-week.</p>


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
            Issue Date:
            {{date('d M Y', strtotime($data['issueDate']))}}
        </div>
    </div>
</div>
</body>
</html>