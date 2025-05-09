<?php

function getColumnWidths($line)
{
    $widths = [];

    $startPos = 0;
    $currentIndex = 0;

    while($pos = strpos($line, ',', $startPos))
    {
        $widths[$currentIndex++] = $pos - $startPos;
        $startPos = $pos + 1;
    }

    return $widths;
}

function getCSVSchema($basePath, $inputFile)
{
    $ret = [];

    $tableName = str_replace('.txt', '', $inputFile);
    $tableName = strtolower($tableName);

    printf("/*\nGenerating Schema for %s\nDate: %s\n*/\n", $tableName, date('Y-m-d H:i:s'));

    $file = fopen($basePath . $inputFile, 'r');
    if($file)
    {
        $firstLine = fgets($file);
        if($firstLine)
        {
            while(ord($firstLine[0]) > 127)
            {
                $firstLine = substr($firstLine, 1);
            }
            $secondLine = fgets($file);
            $columns = str_getcsv($firstLine);
            $widths = getColumnWidths($secondLine);
            if(is_array($columns))
            {
                // normalize column names associate column widths
                foreach($columns as $index => $col)
                {
                    $col = trim($col);
                    $col = strtolower($col);
                    $col = str_replace([' ','-','('], '_', $col);
                    $col = str_replace(')', '', $col);
                    if(strlen($col))
                    {
                        $ret[$index] = [ 'column' => $col, 'width' => $widths[$index] ];
                    }
                }
            }
        }
        fclose($file);

        return [ 'table' => $tableName, 'columns' => $ret];
    }

    return null;
}

function generateSchema($schema)
{
    printf("\nDROP TABLE IF EXISTS %s;\n", $schema['table']);
    printf("CREATE TABLE %s \n(\n", $schema['table']);

    foreach($schema['columns'] as $column)
    {
        printf("   %-25s VARCHAR(%d),\n", $column['column'], $column['width']);
    }

    printf("\n   PRIMARY KEY(%s)", $schema['columns'][0]['column']);
    printf("\n) ENGINE=InnoDB;\n");
}

function generateImport($schema, $basePath, $inputFile)
{
    $ret = '';

    // create preamble
    $preamble = sprintf("INSERT INTO %s (", $schema['table']);

    $count = 0;
    foreach($schema['columns'] as $index => $column)
    {
        if($count++ > 0)
        {
            $preamble .= sprintf(', ');
        }
        $preamble .= sprintf("%s", $column['column']);
    }

    $preamble .= sprintf(") VALUES \n", $schema['table']);

    $postamble = sprintf(";\n");

    $tableName = str_replace('.txt', '', $inputFile);
    $tableName = strtolower($tableName);

    $file = fopen($basePath . $inputFile, 'r');
    if($file)
    {
        $firstLine = fgets($file);
        if($firstLine)
        {
            $lineCount = 0;
            while($line = fgets($file))
            {
                if(($lineCount % 100) == 0)
                {
                    printf($preamble);
                }

                if(strlen($line))
                {
                    if($lineCount % 100)
                    {
                        printf(",\n");
                    }
                    printf("(");
                    $columns = str_getcsv($line);
                    foreach($columns as $index => $column)
                    {
                        if(isset($schema['columns'][$index]))
                        {
                            if($index > 0)
                            {
                                printf(",");
                            }
                            $column = str_replace("'", "''", $column);
                            printf("'%s'", trim($column));
                        }
                    }
                    printf(")");

                }
                if((++$lineCount % 100) == 0)
                {
                    printf($postamble);
                }
                //$count++;
            }
        }
        if($lineCount % 100)
        {
            printf($postamble);
        }
        fclose($file);

        return $ret;
    }

    return null;
}

function generateTruncate($fileName)
{
    $fileName = strtolower($fileName);
    $fileName = str_replace('.txt', '', $fileName);
    printf("TRUNCATE TABLE %s;\n", $fileName);
}

function usage()
{
?>
PiAware Tools - FAA Database Management Utility
Copyright 2025 (c) - Diggy Bell

    --schema            - Generaet SQL to define tables based on FAA source data
    --import            - Generate SQL to import from FAA source data
    --truncate          - Generate SQL to truncate the table (ignored with --schema)
    --directory<dir>    - Directory containing FAA source data
    --file=<filename>   - The name of the file to import (case sensitive)
    --help              - Display this help

<?php
}

//
// main application code
//

$inputFiles = 
[
    'MASTER.txt',
    'RESERVED.txt',
//    'DEREG.txt',
    'ACFTREF.txt',
    'ENGINE.txt'
];

$schemaFlag = false;
$importFlag = false;
$truncateFlag = false;
$helpFlag = false;

$basePath = '';
$inputFile = '';

$shortOpts = '';
$longOpts = [ 'schema', 'import', 'truncate', 'directory:', 'file:', 'help'];
$opts = getopt($shortOpts, $longOpts);

if(isset($opts['schema']))
{
    $schemaFlag = true;
}
if(isset($opts['import']))
{
    $importFlag = true;
}
if(isset($opts['truncate']) && !$schemaFlag)
{
    $truncateFlag = true;
}
if(isset($opts['directory']))
{
    $basePath = $opts['directory'];
}
if(isset($opts['file']))
{
    $inputFile = $opts['file'];
}
if(isset($opts['help']))
{
    $helpFlag = true;
}

if($helpFlag || strlen($inputFile) == 0 || (!$schemaFlag && !$importFlag && !$truncateFlag))
{
    if(strlen($inputFile) == 0)
    {
        printf("Error: You must provide a valid input file name (--file)\n");
    }
    if(!$schemaFlag && !$importFlag && !$truncateFlag)
    {
        printf("Error: You must include one of these flags (--schema, --import, --truncate)\n");
    }
    usage();
    exit;
}

$schema = getCSVSchema($basePath, $inputFile);
if($schemaFlag)
{
    generateSchema($schema);
}
if($truncateFlag)
{
    generateTruncate($inputFile);
}
if($importFlag)
{
    generateImport($schema, $basePath, $inputFile);
}
