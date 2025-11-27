<?php

function get_storage_path($addition) 
{
    if($addition == "")
    {
        return config('custom.storage_upload_dir');
    }
    else
    {
        return config('custom.storage_upload_dir') . "/" . $addition;
    }
}

function switch_commas(string $input, bool $stripQuotes = false): string
{
    $out = '';
    $len = strlen($input);

    for ($i = 0; $i < $len; $i++) 
    {
        $ch = $input[$i];

        if ($stripQuotes) 
        {
            if ($ch === ';') 
            {
                $out .= ',';
            } 
            elseif ($ch !== '"') 
            {
                $out .= $ch;
            }
        } 
        else 
        {
            static $inQuotes = false;

            if ($ch === '"') 
            {
                $inQuotes = !$inQuotes;
                $out .= $ch;
            } 
            elseif ($inQuotes && $ch === ',') 
            {
                $out .= ';';
            } 
            else 
            {
                $out .= $ch;
            }
    }
}

return $out;

}

function sanitize_input(string $filename): void
{
    $inputPath  = get_storage_path($filename);
    $outputPath = get_storage_path('output.tmp');

    $in  = fopen($inputPath, 'r');
    $out = fopen($outputPath, 'w');

    if (!$in || !$out) 
    {
        throw new Exception("Failed to open files");
    }

    $buffer          = '';
    $bufferLineCount = 0;
    $maxBufferLines  = 5000;
    $bufferSize      = 65536;

    while (!feof($in)) {
        $line = fgets($in, $bufferSize);
        if ($line === false) break;

        $line = switch_commas($line);

        $buffer .= $line;
        $bufferLineCount++;

        if ($bufferLineCount >= $maxBufferLines) {
            fwrite($out, $buffer);
            $buffer = '';
            $bufferLineCount = 0;
        }
    }

    if ($buffer !== '') 
    {
        fwrite($out, $buffer);
    }

    fclose($in);
    fclose($out);

    unlink($inputPath);
    rename($outputPath, $inputPath);
}