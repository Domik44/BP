"""
Author: Dominik Pop
Date:   21.09.2022
Last:   21.09.2022

Description:

VUT FIT, 3BIT
"""

# Imports
import sys
import gedcom_parser_v3 as GParser
import matcher as GMatcher
from datetime import datetime


# Functions
def main():
    gId = sys.argv[2]
    if sys.argv[1] == 'parser':
        gedcom_path = sys.argv[3]
        GParser.parse_file(gedcom_path, gId)
    else:
        GMatcher.match_records(gId)


if __name__ == '__main__':
    main()
