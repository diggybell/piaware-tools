<?php

/**
    \file icao.php
    \ingroup Lib
    \brief A collections of functions for working in ICAO Hex Codes
 */

define('ALLCHARS', 35);                                     ///< Total number of available characters
define('ALPHAONLY', 25);                                    ///< Number of alpha characters
define('BASE9', "123456789");                               ///< Numbers 1-9 (first digit of N-number)
define('BASE10', "0123456789");                             ///< Numbers 0-9 (2-3) digits of N-number)
define('BASE34', "ABCDEFGHJKLMNPQRSTUVWXYZ0123456789");     ///< Letters A-Z (except 'I' and 'O') and numbers 0-9
define('ICAO_OFFSET', 0xA00001);                            ///< Start of ICAO allocations for US aircraft
define('GROUP1', 101711);                                   ///< Group 1 to control conversion between ICAO/N-number
define('GROUP2', 10111);                                    ///< Group 2 to control conversion between ICAO/N-number

$icaoCountryMap = null; ///< global ICAO hex code range for country

/**
    \brief Get the country from the ICAO Hex code
    \param $hexCode The ICAO Hex code to translate
    \returns Name of country where aircraft is registered
*/
function getICAOCountry($hexCode)
{
    global $icaoCountryMap;

    if($icaoCountryMap === null)
    {
        $icaoCountryMap = json_decode(file_get_contents(ICAOHEX_FILE));      
    }

    $hexCode = '0x' . strtoupper($hexCode);
    
    foreach($icaoCountryMap as $index => $country)
    {
        if($hexCode >= $country->start && $hexCode <= $country->end)
        {
            return $country->country;
        }
    }

    return null;
}

/**
    \brief Internal method to calculate the suffix (Internal use)
    \param $rem The remainder during conversion between ICAO <-> N-number
    \returns The suffix to be appended to the N-nmber
*/
function icaoSuffix($rem)
{
    if($rem == 0)
    {
        $suf = "";
    }
    elseif($rem <= 600)    // class A suffix -- only letters.
    {
        $rem--;
        $suf = BASE34[$rem / ALPHAONLY];

        if($rem % ALPHAONLY > 0)
        {
            $suf .= BASE34[($rem % ALPHAONLY) - 1]; // second class A letter, if present.
        }
    }
    else // $rem > 600 : first digit of suffix is a number.  second digit may be blank, letter, or number.
    {
        $rem -= 601;
        $suf = BASE10[$rem / ALLCHARS];
        
        if($rem % ALLCHARS > 0)
        {
            $suf .= BASE34[($rem % ALLCHARS) - 1];
        }
    }

    return $suf;
}

/**
    \brief Encode the suffix (Internal use)
    \param $suf The suffix from the N-number to be encoded to hex
*/
function encodeSuffix($suf)
{
    // produces a remainder from a 0 - 2 digit suffix.
    if(strlen($suf) == 0)
    {
        return 0;
    }
    
    $r0 = stripos(BASE34, $suf[0]);
    
    if(strlen($suf) == 1)
    {
        $r1 = 0;
    }
    else
    {
        $r1 = stripos(BASE34, $suf[1]) + 1;
    }

    if($r0 < 24)
    {
        return $r0 * ALPHAONLY + $r1 + 1; // first char is a letter, use base 25
    }
    else
    {  
        return $r0 * ALLCHARS + $r1 - 239; // first is a number -- base 35.
    }
}

/**
    \brief Convert ICAO hex code to N-number
    \param $hexCode The ICAO Hex code to convert to N-number
    \returns The US N-number for the ICAO Hex code if it is a US registered aircraft
*/
function icaoTailNumber($hexCode)
{
    $tailNumber = 0;
    $d1 = 0;
    $d2 = 0;
    $d3 = 0;
    $r1 = 0;
    $r2 = 0;
    $r3 = 0;
    
    $icao = intval($hexCode, 16);

    // N numbers fit in this range. other ICAO not decoded.
    if(($icao < 0xA00001) || ($icao > 0xADF7C7))
    {
        return "";
    }

    $icao -= ICAO_OFFSET;     // A00001

    $d1 = $icao / GROUP1;
    $r1 = $icao % GROUP1;
    $tailNumber = "N" . BASE9[$d1];

    if($r1 < 601)
    {
        $tailNumber .= icaoSuffix($r1); // of the form N1ZZ
    }
    else
    {
        $d2 = ($r1 - 601) / GROUP2; // find second digit.
        $r2 = ($r1 - 601) % GROUP2;  // and residue after that
        $tailNumber .= BASE10[$d2];

        if($r2 < 601)
        {
            $tailNumber .= icaoSuffix($r2);   // No third digit.(form N12ZZ
        }
        else
        {
            $d3 = ($r2 - 601) / 951; // Three-digits have extended suffix.
            $r3 = ($r2 - 601) % 951;
            $tailNumber .= BASE10[$d3] . icaoSuffix($r3);
        }
    }

    return $tailNumber;
}

//
// convert an N-number to ICAO hex code
//
/**
    \brief Convert an N-number to ICAO Hex code
    \param $tail The N-number to be converted to ICAO Hex code
    \returns The ICAO Hex code for the N-number that was passed
*/
function icaoHexCode($tail)
{
    $d2 = 0;
    $d3 = 0;
    $icao = 0;
    
    $tail = strtoupper($tail);
    
    if(!$tail[0] == "N")
    {
        return -1;
    }

    $icao = ICAO_OFFSET;
    $icao += stripos(BASE9, $tail[1]) * GROUP1;

    if(strlen($tail) == 2) // simple 'N3' etc.
    {
        return $icao;
    }

    $d2 = stripos(BASE10, $tail[2]);

    if($d2 == -1)
    {
        $icao += encodeSuffix(substr($tail, 2, 4)); // form N1A
    }
    else
    {
        $icao += $d2 * GROUP2 + 601; // form N11... or N111..

        if (strlen($tail) != 3) // simple 'N34' etc.
        {
            $d3 = stripos(BASE10, $tail[3]);

            if ($d3 > -1) // form N111 Suffix is base 35.
            {
                $icao += $d3 * 951 + 601;
                $icao += encodeSuffix(substr($tail, 4, 6));
            }
            else // form N11A
            {
                $icao += encodeSuffix(substr($tail, 3, 5));
            }
        }
    }

    return $icao;
}
