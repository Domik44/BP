# Author: Dominik Pop [xpopdo00]

# Brief:
The goal of this thesis is to design and implement application which is capable of advising
user, genealogist, suitable parish books for searching information about missing events
of practicular person. The base of this application is processing input GEDCOOM file,
choosing of suitable parish books and offering of these books to user through a graphical
user interface.

# URL:
http://perun.fit.vutbr.cz/xpopdo00/
[Application is currently down, due to server reset]

# Project structure:
- doc -> documentation, notes
- scrapers -> python scripts for web scraping digital archives
- src -> main folder holding web application
    - app
        - Models -> models for application
        - Http
            - Controllers -> controllers for application
    - python_scripts -> parser, matcher scripts
    - resources
        - views -> application views
    - routes -> routing folder

# Prerequisites
- PHP 8.1
- Python 3.10
- Composer
- MySQL
- Bash

# Running application on local system
- Edit .env file DB_USERNAME and DB_PASSWORD with your information
- Edit setup.sh script DB_USERNAME and DB_PASSWORD variables
- Run setup.sh with ./setup.sh
- Application should be running on: 127.0.0.1:8000

# Operating with application
- Log in, on main page upload file
- Wait for upload, set unresolved territories and wait for matching to complete
- On file page you can see records sorted by type
- You can see suggested books by clicking on 'Zobrazit matriky'
