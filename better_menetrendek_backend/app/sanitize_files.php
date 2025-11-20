<?php

function get_storage_path($addition) {
    if($addition == "")
    {
        return config('custom.storage_upload_dir');
    }
    else
    {
        return config('custom.storage_upload_dir') . "/" . $addition;
    }
}
function sanitize_input($filename)
{
    $in  = fopen(get_storage_path($filename), "r");
    $out = fopen(get_storage_path("output.txt"), "w");

    while (!feof($in)) {
        $line = fgets($in);

        $pattern = '/"([^"]*)"/';
        $line = preg_replace_callback($pattern, function($matches) {
            return '"' . str_replace(',', ';', $matches[1]) . '"';
        }, $line);

        fwrite($out, $line);
    }

    fclose($in);
    fclose($out);
    unlink(get_storage_path($filename));
    rename(get_storage_path("output.txt"), get_storage_path($filename));
} 