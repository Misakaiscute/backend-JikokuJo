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

function replace_commas_in_quotes(string $line, string $replacement): string
{
    $len = strlen($line);
    $out = [];
    $outIndex = 0;
    $inQuotes = false;
    $searchChar = ($replacement === ';') ? ',' : ';';
    $replaceChar = $replacement;

    for ($i = 0; $i < $len; $i++) 
    {
        $ch = $line[$i];

        if ($ch === '"') {
            $inQuotes = !$inQuotes;
            $out[$outIndex++] = '"';
        } elseif ($ch === $searchChar && $inQuotes) {
            $out[$outIndex++] = $replaceChar;
        } else {
            $out[$outIndex++] = $ch;
        }
    }

    return implode('', $out);
}

function sanitize_input(string $filename)
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

        $line = replace_commas_in_quotes($line, ";");

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