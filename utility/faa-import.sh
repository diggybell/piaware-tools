#!/bin/bash

php faa-import.php --directory=faa/ --file=MASTER.txt --schema --import > sql/MASTER.sql
php faa-import.php --directory=faa/ --file=RESERVED.txt --schema --import > sql/RESERVED.sql
php faa-import.php --directory=faa/ --file=ACFTREF.txt --schema --import > sql/ACFTREF.sql
php faa-import.php --directory=faa/ --file=ENGINE.txt --schema --import > sql/ENGINE.sql
