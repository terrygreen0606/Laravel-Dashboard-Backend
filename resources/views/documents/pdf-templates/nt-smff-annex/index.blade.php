<?php
$items =
    [[
        'title' => 'Introduction',
        'index' => 'SA-1',
        'subtopic' => false
    ], [
        'title' => 'Preamble',
        'index' => 'SA-1',
        'subtopic' => true
    ], [
        'title' => 'General Information',
        'index' => 'SA-2',
        'subtopic' => false
    ], [
        'title' => 'Geographic Specific Information',
        'index' => 'SA-11',
        'subtopic' => false
    ], [
        'title' => 'Vessel Specific Information',
        'index' => 'SA-12',
        'subtopic' => false
    ], [
        'title' => 'Pumps &amp; Tugs Tables',
        'index' => 'SA-13',
        'subtopic' => true
    ], [
        'title' => 'Vessel Information',
        'index' => 'See Vessel',
        'subtopic' => true
    ]];
$addendums = [
    ['title' => 'Letter Certifying Resource Provider Review and Acceptance of Vessel Pre-fire Plan'],
    ['title' => 'Letter Certifying Resource Provider Provision of Services'],
    ['title' => 'Contract with Funding Agreement']
];
?>
        <!DOCTYPE html>
<html>

<head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/common-styles.css') }}"/>
    <link rel="stylesheet" type="text/css" href="{{ public_path('css/smff-anex.css') }}"/>
</head>

<body>
<div id="pageHeader-1"></div>
<div class="pageContent">
    @include('documents.pdf-templates.combined-smff-annex.page-cover')
    @include('documents.pdf-templates.combined-smff-annex.page-toc', compact('items', 'addendums'))
    @include('documents.pdf-templates.combined-smff-annex.page-preamble')
    @include('documents.pdf-templates.combined-smff-annex.page-annex-general')
    @include('documents.pdf-templates.combined-smff-annex.page-annex-geographic')
    @include('documents.pdf-templates.combined-smff-annex.page-annex-vessel')
    <div class="page">
        @include('documents.pdf-templates.combined-smff-annex.page-pumps-tugs-table')
    </div>
</div>
</body>

</html>