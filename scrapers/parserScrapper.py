"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import os
import sys
import json
from eloquent import DatabaseManager, Model

config = {
    'default': 'mysql',
    'mysql': {
        'driver': 'mysql',
        'host': 'localhost',
        'database': 'testdb',
        'user': 'root',
        'password': 'adminROOT44#'
    }
}

db = DatabaseManager(config)
Model.set_connection_resolver(db)
parishBooksList = []
books = []
territories = {}


class ParishBook(Model):
    __table__ = 'ParishBook'
    __fillable__ = ['fromYear', 'toYear', 'url',
                    'originator', 'originatorType',
                    'birthFromYear', 'birthToYear', 'deathFromYear',
                    'deathToYear', 'marriageFromYear', 'marriageToYear',
                    'birthIndexFromYear', 'birthIndexToYear', 'deathIndexFromYear',
                    'deathIndexToYear', 'marriageIndexFromYear', 'marriageIndexToYear']
    __timestamps__ = False


class Territory(Model):
    __table__ = 'Territory'
    __timestamps__ = False


def process_territories(terr, book):
    if terr is None:
        return
    for name, val in terr.items():
        type = val['typ']
        ruian = val['ruian']
        if ruian is not None and ruian > 0 and type is not None and type > 0:
            if (ruian, type) in territories:
                territory = territories[(ruian, type)]
                record = db.table('Territory_ParishBook').where('bookId', '=', book.id).where('territoryId', '=',
                                                                                             territory.id).first()
                if record is None:
                    db.table('Territory_ParishBook').insert(territoryId=territory.id, bookId=book.id)


def get_map():
    global territories
    terr = Territory.where('type', '=', 4).or_where('type', '=', 5).get()
    territories = {(i.RUIAN_id, i.type): i for i in terr}


def parse_parish_book(book):
    # Basic information
    originator = book['puvodce']
    originatorType = book['typ']
    url = book['url']
    # Year ranges
    yearRange = book['rozsah'].split(' - ')
    if len(yearRange) == 1:
        yearRange.append(yearRange[0])
    fromYear = int(yearRange[0])
    toYear = int(yearRange[1])

    # Event year ranges
    birthFrom = -1
    birthTo = 9999
    deathFrom = -1
    deathTo = 9999
    marriageFrom = -1
    marriageTo = 9999
    marriageIndexFrom = -1
    marriageIndexTo = 9999
    deathIndexFrom = -1
    deathIndexTo = 9999
    birthIndexFrom = -1
    birthIndexTo = 9999

    for event in book['obsah']:
        eventSplitted = event.split('•')
        eventType = eventSplitted[0]
        eventRange = eventSplitted[1].split('-')
        if len(eventRange) == 1:
            eventRange.append(eventRange[0])

        if eventRange[0] == '':
            eventRange[0] = '-1'
        if eventRange[1] == '':
            eventRange[1] = '9999'
        eventRange = [int(i) for i in eventRange]

        if eventType == 'N':
            birthFrom = eventRange[0]
            birthTo = eventRange[1]
        elif eventType == 'Z':
            deathFrom = eventRange[0]
            deathTo = eventRange[1]
        elif eventType == 'O':
            marriageFrom = eventRange[0]
            marriageTo = eventRange[1]
        elif eventType == 'I-N':
            birthIndexFrom = eventRange[0]
            birthIndexTo = eventRange[1]
        elif eventType == 'I-Z':
            deathIndexFrom = eventRange[0]
            deathIndexTo = eventRange[1]
        elif eventType == 'I-O':
            marriageIndexFrom = eventRange[0]
            marriageIndexTo = eventRange[1]

    # d = {
    #     'fromYear': fromYear, 'toYear': toYear, 'url': url,
    #     'originator': originator, 'originatorType': originatorType, 'birthFromYear': birthFrom,
    #     'birthToYear': birthTo, 'deathFromYear': deathFrom, 'deathToYear': deathTo,
    #     'marriageFromYear': marriageFrom, 'marriageToYear': marriageTo, 'birthIndexFromYear': birthIndexFrom,
    #     'deathIndexToYear': deathIndexTo, 'deathIndexFromYear': deathIndexFrom, 'birthIndexToYear': birthIndexTo,
    #     'marriageIndexToYear': marriageIndexTo, 'marriageIndexFromYear': marriageIndexFrom
    # }

    # Creating parish book
    parishBook = ParishBook.first_or_create(fromYear=fromYear, toYear=toYear, url=url,
                                   originator=originator, originatorType=originatorType, birthFromYear=birthFrom,
                                   birthToYear=birthTo, deathFromYear=deathFrom, deathToYear=deathTo,
                                   marriageFromYear=marriageFrom, marriageToYear=marriageTo, birthIndexFromYear=birthIndexFrom,
                                   deathIndexToYear=deathIndexTo, deathIndexFromYear=deathIndexFrom, birthIndexToYear=birthIndexTo,
                                   marriageIndexToYear=marriageIndexTo, marriageIndexFromYear=marriageIndexFrom)

    process_territories(book['uzemi'], parishBook)

#     # TODO -> popremyslet jestli do DB pridat i ty ulice! (viz. problem u Brno2.json Dornych)
def main():
    get_map()
    path = sys.argv[0].rsplit('/', 1)[0]
    files = ['Opava', 'Litomerice', 'Trebon', 'Praha', 'HLPraha', 'Brno', 'Plzen', 'Hradec'] # TODO -> Hradec
    for file in files:
        folder = file
        if file == 'Hradec':
            folder = 'HradecKralove'
        directory = '/Jsons/' + folder
        # directory = '/Jsons/' + sys.argv[1]
        i = 0
        print(file)
        for filename in os.scandir(path + directory):
            name = os.path.join(directory, filename)
            # checkPath = path + directory + '\\' + sys.argv[1] + '_fixed.json'
            checkPath = path + directory + '\\' + file + '_fixed.json'
            if name == checkPath:
                f = open(name, encoding='utf8')
                y = json.load(f)
                for book in y['matriky']:
                    i += 1
                    parse_parish_book(book)
                    # break
                print(i)
                break


if __name__ == '__main__':
    main()
