<?php

if (!function_exists('add_cors_headers_group_cdt')) {
    function add_cors_headers_group_cdt()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods:  *');
        header('Access-Control-Allow-Headers:  *');
    }
}

if (!function_exists('add_cors_headers_group_cdt_individual')) {
    function add_cors_headers_group_cdt_individual($request)
    {
        $request->headers->set('Access-Control-Allow-Origin', '*');
        $request->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE');
        $request->headers->set('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Origin, Authorization');
    }
}

if (!function_exists('sanitize_data_for_doc')) {
    function sanitize_data_for_doc($text)
    {
        return str_replace( array( '\'', '"', ',' , ';', '<', '>' , '&' ), '-', $text);
    }
}
