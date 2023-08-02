# !/bin/bash

# Installing python modules
pip install python-gedcom
pip install orator
pip install PyMySQL
pip install textsearch
pip install string-grouper
pip install thefuzz
pip install datetime
pip install cryptography

# Fill your database username and password!
DB_USERNAME="root"
DB_PASSWORD=""

# Creating database cheme and filling it
# mysql -u$DB_USERNAME -p$DB_PASSWORD < SQL.sql
if [[ "$OSTYPE" =~ ^msys ]]; then
    if [[ "" == $DB_PASSWORD ]]; then
    	mysql -u$DB_USERNAME < SQL.sql
    	mysql -uroot -e "DROP USER IF EXISTS 'xpopdo00'@'localhost'; CREATE USER 'xpopdo00'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON xpopdo00.* TO 'xpopdo00'@'localhost';  FLUSH PRIVILEGES;"
    else
    	mysql -u$DB_USERNAME -p$DB_PASSWORD < SQL.sql
    	mysql -u$DB_USERNAME -p$DB_PASSWORD -e "DROP USER IF EXISTS 'xpopdo00'@'localhost'; CREATE USER 'xpopdo00'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON xpopdo00.* TO 'xpopdo00'@'localhost';  FLUSH PRIVILEGES;"
    fi
fi

if [[ "$OSTYPE" =~ ^linux ]]; then
    if [[ "" == $DB_PASSWORD ]]; then
    	sudo mysql -u$DB_USERNAME < SQL.sql
    	sudo mysql -uroot -e "DROP USER IF EXISTS 'xpopdo00'@'localhost'; CREATE USER 'xpopdo00'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON xpopdo00.* TO 'xpopdo00'@'localhost';  FLUSH PRIVILEGES;"
    else
    	sudo mysql -u$DB_USERNAME -p$DB_PASSWORD < SQL.sql
    	sudo mysql -u$DB_USERNAME -p$DB_PASSWORD -e "DROP USER IF EXISTS 'xpopdo00'@'localhost'; CREATE USER 'xpopdo00'@'localhost' IDENTIFIED BY 'password'; GRANT ALL PRIVILEGES ON xpopdo00.* TO 'xpopdo00'@'localhost';  FLUSH PRIVILEGES;"
    fi
    
fi

cd src/python_scripts/

# Changing python header file to coresponding information
#if [[ "$OSTYPE" =~ ^msys ]]; then
#    python setup_DB.py $DB_USERNAME $DB_PASSWORD
#fi

#if [[ "$OSTYPE" =~ ^linux ]]; then
    #python3 setup_DB.py $DB_USERNAME $DB_PASSWORD
#fi

cd ..

# Installing dependencies
composer update
composer install

php artisan key:generate

# This is for local use only
# Clearing routes and caching them
php artisan route:clear & php artisan route:cache
# Starting application for local use
php artisan serve