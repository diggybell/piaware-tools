<?php

/**
    \file faa-import.php
    \brief This script creates the SQL to create/truncate and import the FAA Registration database
    \ingroup PiAwareTools
*/

/**
    \brief Get the fixed with column length from the data in the file
    \param $line The first line of the file that contains data (headers are in first line)
    \returns Array containing the width of each column in the data file
*/
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

/**
    \brief Read the headers and first line of data to create the name and columns for the file
    \param $basePath Base path to PiAware Tools
    \param $inputFile The data file to be used to create the schema
    \returns Array containing the table name and a list of column names
    \retval table The name of the table
    \retval columns The list of column names
*/
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

/**
    \brief Generate the SQL to create the table
    \param $schema The table name a column list to create SQL for
*/
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

/**
    \brief Generate the SQL to import the data from the FAA data file
    \param $schema The table name and list of columns
    \param $basePath The base path to the PiAware Tools
    \param $inputFile The data file containing the data to be imported
*/
function generateImport($schema, $basePath, $inputFile)
{
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

        return;
    }

    return;
}

/**
    \brief Generate the SQL to truncate the table
    \brief $tableName The name of the table to be truncated
*/
function generateTruncate($tableName)
{
    $tableName = strtolower($tableName);
    $tableName = str_replace('.txt', '', $tableName);
    printf("TRUNCATE TABLE %s;\n", $tableName);
}

/**
   \brief Display usage and help information
*/
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

/**
    \brief Main entry point
    \param $opts Command line parameters
*/
function main($opts)
{
    $schemaFlag = false;
    $importFlag = false;
    $truncateFlag = false;
    $helpFlag = false;
    $basePath = '';
    $inputFile = '';
    $schema = [];

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
}

$shortOpts = '';            ///< Short command line options (Not supoorted)
$longOpts = [
    'schema',
    'import',
    'truncate',
    'directory:',
    'file:',
    'help'];                ///< Long command line options
$opts = getopt($shortOpts, $longOpts);///< Command line options

main($opts);
