#!/bin/bash

/usr/bin/php faa-import.php --directory=../faa/ --file=MASTER.txt --truncate --import > mysql faa
/usr/bin/php faa-import.php --directory=../faa/ --file=RESERVED.txt --truncate --import > mysql faa
/usr/bin/php faa-import.php --directory=../faa/ --file=ACFTREF.txt --truncate --import > mysql faa
/usr/bin/php faa-import.php --directory=../faa/ --file=ENGINE.txt --truncate --import > mysql faa
