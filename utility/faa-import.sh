#!/bin/bash

/usr/bin/php faa-import.php --directory=../faa/ --file=MASTER.txt --truncate --import > faa-import.sql ; mysql faa < faa-import.sql
/usr/bin/php faa-import.php --directory=../faa/ --file=RESERVED.txt --truncate --import >faa-import.sql ; mysql faa < faa-import.sql
/usr/bin/php faa-import.php --directory=../faa/ --file=ACFTREF.txt --truncate --import >faa-import.sql ; mysql faa < faa-import.sql
/usr/bin/php faa-import.php --directory=../faa/ --file=ENGINE.txt --truncate --import >faa-import.sql ; mysql faa < faa-import.sql
