<!DOCTYPE html>
<html lang="en">
<head>
    <title>CDT - API</title>
    <style>
        .img {
            position: absolute;
            margin: auto;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
    </style>
</head>
<body style="background-color: #588CC8">
<img src="img.jpg" alt="TED Laboratory" class="img" />
<pre>
    Version: {{ isset($version) ? $version : '' }}
</pre>
</body>
</html>
