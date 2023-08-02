"""
Author: Dominik Pop
Date:   21.09.2022
Last:   21.09.2022

Description: Matching system.

VUT FIT, 3BIT
"""

# Imports
from parser_header import Person, Family, AppUser, GFile, db, Territory, Record, ParishBook
from sys import getsizeof
import json
from math import radians, sin, cos, asin, sqrt

# Functions

# Global variables
# All age number are theoretical
BIRTH_MIN = 15 # Depends on year.
BIRTH_MAX = 70 # Max age for man to concieve a child (Theoreticly max age is unlimited).
WOMAN_BIRTH_MAX = 50 # Max age for woman to convieve a child (As well can bi bigger number).
DEATH_MAX = 100 # Max age person can live up to (Also can be bigger).
MARRIAGE_MIN = 15 # Depends on year. Till 1811 it was 15 for woman, after that it was 24, 21, 18....
MARRIAGE_MAX = 100

# Record types
FAMILY = 2
BIRTH = 0
DEATH = 1

# Important ID
GEDCOMID = None
MAP_PATH = None
IGNORE = None

# Priorities
HIGH_H = 1
HIGH_M = 2
HIGH_L = 3
MEDIUM_H = 4
MEDIUM_M = 5
MEDIUM_L = 6
LOW_H = 7
LOW_M = 8
LOW_L = 9

# Dictionaries
territories = {}
territoryMap = {}
people = {}
parentChildren = {}
families = {}
familyChildren = {}
familyMarriage = {}
familyParents = {}

books = {}
terr_book = {}

# Lists
suggestionsList = []

'''
@brief Function checking if events year are in spread of the books years
@param bookFrom Books from year
@param bookTo Book to year
@param eventFrom Event from year
@param eventTo Event to year
'''
def is_in_spread(bookFrom, bookTo, eventFrom, eventTo):
    if bookTo >= eventFrom and bookFrom <= eventTo:
        return True

    return False

'''
@brief Function for checking if spread is valid or not
@param fromY Year from
@param toY Year to
'''
def check_spread(fromY, toY):
    if fromY == -1 and toY == 9999:
        return False

    return True

'''
@brief Function for checking if given event type is covered by the book
@param eventType Type of event
@param book Parish book
'''
def check_book_type(eventType, book):
    if eventType == FAMILY:
        if not check_spread(book.marriageFromYear, book.marriageToYear) and not check_spread(book.marriageIndexFromYear, book.marriageIndexToYear):
            return False
    elif eventType == BIRTH:
        if not check_spread(book.birthFromYear, book.birthToYear) and not check_spread(book.birthIndexFromYear, book.birthIndexToYear):
            return False
    elif eventType == DEATH:
        if not check_spread(book.deathFromYear, book.deathToYear) and not check_spread(book.deathIndexFromYear, book.deathIndexToYear):
            return False

    return True

'''
@brief Function for computing Harvesine distance between to places in kilometres.
@params Coordination of places.

This function was taken from: https://www.geeksforgeeks.org/program-distance-two-points-earth/
'''
def distance(lat1, lat2, lon1, lon2):
    lon1 = radians(lon1)
    lon2 = radians(lon2)
    lat1 = radians(lat1)
    lat2 = radians(lat2)

    # Haversine formula
    dlon = lon2 - lon1
    dlat = lat2 - lat1
    a = sin(dlat / 2) ** 2 + cos(lat1) * cos(lat2) * sin(dlon / 2) ** 2
    c = 2 * asin(sqrt(a))

    # Radius of earth in kilometers
    r = 6371

    return c * r


'''
@brief Function for getting places within 5km radius from JSON file.
@param place Referencial place
'''
def get_more_places(place, priority):
    key = place.id
    if key in territoryMap:
        places = [territories[i] for i in territoryMap[key]]
        return places
    else:
        return []


'''
@brief Function for finding suitable parish books to given event, places and spread in years
@param years Spread in years in which event could take place
@param places Place where event could possibly take place
@param eventType Type of event
'''
def find_books(years, places, eventType):
    places = list(places)
    places.sort(key=lambda x: x[1])
    allPlaces = {}
    placeBooks = {}
    for place, priority in places:
        if place.id not in allPlaces and place.id != IGNORE:
            allPlaces[place.id] = [priority, False]
            if place.type == 5 and place.partOf not in allPlaces:
                allPlaces[place.partOf] = [priority, False]
            around = get_more_places(place, priority)
            for ar in around:
                if ar.id not in allPlaces:
                    allPlaces[ar.id] = [priority, True]
                    if ar.type == 5:
                        if ar.partOf not in allPlaces:
                            allPlaces[ar.partOf] = [priority, True]
                        else:
                            if allPlaces[ar.partOf][0] > priority:
                                allPlaces[ar.partOf][0] = priority
                else:
                    if allPlaces[ar.id][0] > priority:
                        allPlaces[ar.id][0] = priority

    placesIDs = [*allPlaces]
    for id in placesIDs:
        if id in terr_book:
            for rel in terr_book[id]:
                if rel not in placeBooks:
                    placeBooks[rel] = allPlaces[id]
                else:
                    if placeBooks[rel] > allPlaces[id]:
                        placeBooks[rel] = allPlaces[id]

    finalBooks = []
    for bookID, items in placeBooks.items():
        priority, isAround = items
        book = books[bookID]
        if not check_book_type(eventType, book):
            continue
        if eventType == FAMILY:
            if check_spread(book.marriageFromYear, book.marriageToYear) and is_in_spread(book.marriageFromYear, book.marriageToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))
                continue
            if check_spread(book.marriageIndexFromYear, book.marriageIndexToYear) and is_in_spread(book.marriageIndexFromYear, book.marriageIndexToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))
        elif eventType == BIRTH:
            if check_spread(book.birthFromYear, book.birthToYear) and is_in_spread(book.birthFromYear, book.birthToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))
                continue
            if check_spread(book.birthIndexFromYear, book.birthIndexToYear) and is_in_spread(book.birthIndexFromYear, book.birthIndexToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))
        elif eventType == DEATH:
            if check_spread(book.deathFromYear, book.deathToYear) and is_in_spread(book.deathFromYear, book.deathToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))
                continue
            if check_spread(book.deathIndexFromYear, book.deathIndexToYear) and is_in_spread(book.deathIndexFromYear, book.deathIndexToYear, years[0], years[1]):
                finalBooks.append((book, priority, isAround))

    return finalBooks

'''
@brief Function for creating dictionary which is mapping id of territory to its object
'''
def setup_function():
    global territories, territoryMap, books, terr_book, IGNORE

    mapRows = db.table('Territory_Neighbours').get()
    for item in mapRows:
        if item['searchedID'] in territoryMap:
            territoryMap[item['searchedID']].append(item['neighbourID'])
        else:
            territoryMap[item['searchedID']] = [item['neighbourID']]

    ignore = Territory.where('type', '=', 6).first()
    IGNORE = ignore.id
    terr = []
    people = Person.where('gedcomID', '=', GEDCOMID).get()
    for person in people:
        if person.birthPlaceID is not None:
            terr.append(person.birthPlaceID)
        if person.deathPlaceID is not None:
            terr.append(person.deathPlaceID)
    families = Family.where('gedcomID', '=', GEDCOMID).get()
    for family in families:
        if family.marriagePlaceID is not None:
            terr.append(family.marriagePlaceID)
        if family.marriagePlaceID2 is not None:
            terr.append(family.marriagePlaceID2)

    terr = set(terr)
    allTerr = []
    for t in terr:
        allTerr.append(t)
        if t != ignore.id:
            if t in territoryMap:
                around = territoryMap[t]
                allTerr.extend(around)
    allTerr = list(set(allTerr))
    territories = {i.id: i for i in Territory.where_in('id', allTerr).get()}

    muniID = []
    for t in territories.values():
        if t.type == 5:
            muniID.append(t.partOf)
    muniID = list(set(muniID))
    muni = Territory.where_in('id', muniID).get()
    for t in muni:
        territories[t.id] = t

    territories[ignore.id] = ignore

    relations = db.table('Territory_ParishBook').where_in('territoryId', [*territories]).get()
    terr_book = {}
    bookIds = []
    for rel in relations:
        bookIds.append(rel['bookId'])
        if rel['territoryId'] not in terr_book:
            terr_book[rel['territoryId']] = [rel['bookId']]
        else:
            terr_book[rel['territoryId']].append(rel['bookId'])
    bookIds = list(set(bookIds))
    books = {i.id: i for i in ParishBook.where_in('id', bookIds).get()}


'''
@brief Function for extracting year out of date format
@param date String representing date
'''
def formate_year(date):
    return date.split(' ')[-1]


'''
@brief Function for gathering information about marriage record.
Also calls function for finding suitable books.
@param record Record that needs to be solved.
'''
def match_marriage_record(record):
    family = families[record.familyID]
    husband = people[family.husbandID] if family.husbandID is not None else -1
    wife = people[family.wifeID] if family.wifeID is not None else -1
    if husband != -1 or wife != -1:
        husbandID = husband.personID if husband != -1 else -1
        wifeID = wife.personID if wife != -1 else -1
        children = familyChildren[(husbandID, wifeID)] if (husbandID, wifeID) in familyChildren else []
    else:
        children = []

    yearSpread = [-1, 9999]
    yearFrom, yearTo, placePossibility = [], [], []
# Husband/Wife
    for person in [husband, wife]:
        if person != -1:
            personFather = people[person.fatherID] if person.fatherID is not None else -1
            personFatherID =  personFather.personID if personFather != -1 else -1
            personMother = people[person.motherID] if person.motherID is not None else -1
            personMotherID = personMother.personID if personMother != -1 else -1
            if record.missing != 2:
                if person.birthYear is not None:
                    # Year person was born + minimal age needed for marriage
                    yearFrom.append(person.birthYear + MARRIAGE_MIN)
                    # Year person was born + maximal age he could marry
                    yearTo.append(person.birthYear + MARRIAGE_MAX)
                if person.deathYear is not None:
                    # Year when husband died - maximal age he could live up to + minimal age needed for marriage
                    yearFrom.append(person.deathYear - DEATH_MAX + MARRIAGE_MIN)
                    # Year when husband died
                    yearTo.append(person.deathYear)
            if record.missing != 1:
                if person.birthPlaceID is not None:
                    placePossibility.append((territories[person.birthPlaceID], HIGH_H))
                if person.deathPlaceID is not None:
                    placePossibility.append((territories[person.deathPlaceID], MEDIUM_M))
            # Persons parents
            for parent in [personFather, personMother]:
                if parent != -1:
                    if record.missing != 2:
                        if parent.birthYear is not None:
                            # Year when persons father was born + minimal age he could have a kid + minimal age the kid could marry
                            yearFrom.append(parent.birthYear + BIRTH_MIN + MARRIAGE_MIN)
                            if parent.gender == 'F':
                                # Year when persons mother was born + maximal age she could have a kid + maximal age the kid could marry
                                yearTo.append(parent.birthYear + WOMAN_BIRTH_MAX + MARRIAGE_MAX)
                            else:
                                # Year when persons father was born + maximal age he could have a kid + maximal age the kid could marry
                                yearTo.append(parent.birthYear + BIRTH_MAX + MARRIAGE_MAX)
                    if record.missing != 1:
                        if parent.birthPlaceID is not None:
                            placePossibility.append((territories[parent.birthPlaceID], LOW_L))
                        if parent.deathPlaceID is not None:
                            placePossibility.append((territories[parent.deathPlaceID], HIGH_M))

            parentFamily = familyParents[(personFatherID, personMotherID)] if (personFatherID, personMotherID) in familyParents else None
            if parentFamily is not None:
                if record.missing != 2:
                    if parentFamily.marriageYear is not None:
                        # Year of parents marriage + minimal age for marriage of person
                        yearFrom.append(parentFamily.marriageYear + MARRIAGE_MIN)
                        # yearTo.append(parentFamily.marriageYear + MARRIAGE_MAX)
                if record.missing != 1:
                    if parentFamily.marriagePlaceID is not None:
                        placePossibility.append((territories[parentFamily.marriagePlaceID], LOW_M))
                    if parentFamily.marriagePlaceID2 is not None:
                        placePossibility.append((territories[parentFamily.marriagePlaceID2], LOW_M))
            if personFatherID != -1 or personMotherID != -1:
                siblings = familyChildren[(personFatherID, personMotherID)] if (personFatherID, personMotherID) in familyChildren else []
            else:
                siblings = []

            for sibling in siblings:
                if sibling.personID == person.personID:
                    continue
                if record.missing != 1:
                    if sibling.birthPlaceID is not None:
                        placePossibility.append((territories[sibling.birthPlaceID], HIGH_L))
    # Children
    for child in children:
        chFamily = familyMarriage[child.personID] if child.personID in familyMarriage else []
        if record.missing != 2:
            if child.birthYear is not None:
                # Year of childs birth - maximal age parent could have the child + minimal age for marriage
                yearFrom.append(child.birthYear - WOMAN_BIRTH_MAX + MARRIAGE_MIN)
                # Parents should be married by the time their child is born
                yearTo.append(child.birthYear)
            else:
                if child.deathYear is not None:
                    # Year of childs death - maximal age child could have - maximal age parent could have the child + minimal age for marriage
                    yearFrom.append(child.deathYear - DEATH_MAX - WOMAN_BIRTH_MAX + MARRIAGE_MIN)
                    # Parents should be married by the time their child is dead
                    yearTo.append(child.deathYear)
        if record.missing != 1:
            if child.birthPlaceID is not None:
                placePossibility.append((territories[child.birthPlaceID], MEDIUM_H))
        for fam in chFamily:
            if record.missing != 2:
                if fam.marriageYear is not None:
                    # Year when persons kid got married - maximal age kid could marry - maximal age kids parent could have him + minimal age parent should have when getting married
                    yearFrom.append(fam.marriageYear - DEATH_MAX - WOMAN_BIRTH_MAX + MARRIAGE_MIN)
                    # Year when persons kid got married - minimal age person should have when getting married
                    yearTo.append(fam.marriageYear - MARRIAGE_MIN)
            if record.missing != 1:
                if fam.marriagePlaceID is not None:
                    placePossibility.append((territories[fam.marriagePlaceID], LOW_H))
                if fam.marriagePlaceID2 is not None:
                    placePossibility.append((territories[fam.marriagePlaceID2], LOW_H))

    yearSpread[0] = max(yearFrom, default=-1)
    yearSpread[1] = min(yearTo, default=9999)
    if yearSpread[0] == -1 and yearSpread[1] != 9999:
        yearSpread[0] = yearSpread[1] - DEATH_MAX
    elif yearSpread[0] != -1 and yearSpread[1] == 9999:
        yearSpread[1] = yearSpread[0] + DEATH_MAX

    if record.missing == 2:
        yearSpread = (family.marriageYear, family.marriageYear)
    elif record.missing == 1:
        if family.marriagePlaceID is not None:
            placePossibility = [(territories[family.marriagePlaceID], HIGH_H)]
        if family.marriagePlaceID2 is not None:
            placePossibility.append((territories[family.marriagePlaceID2], HIGH_H))

    if yearSpread[0] != -1 and yearSpread[1] != 9999 and len(placePossibility) != 0:
        placePossibility = set(placePossibility)
        books = find_books(yearSpread, placePossibility, FAMILY)
        b = [{'recordId': record.id, 'bookId':book.id, 'priority': priority, 'isAround': isAround} for book, priority, isAround in books]
        suggestionsList.extend(b)

'''
@brief Function for gathering information about birth record.
Also calls function for finding suitable books.
@param record Record that needs to be solved.
'''
def match_birth_record(record):
    person = people[record.personID]
    father = people[person.fatherID] if person.fatherID is not None else -1
    mother = people[person.motherID] if person.motherID is not None else -1
    if father != -1 or mother != -1:
        motherID = mother.personID if mother != -1 else -1
        fatherID = father.personID if father != -1 else -1
        siblings = familyChildren[(fatherID, motherID)] if (fatherID, motherID) in familyChildren else []
        parentFamily = familyParents[(fatherID, motherID)] if (fatherID, motherID) in familyParents else None
    else:
        siblings = []
        parentFamily = None
    children = parentChildren[person.personID] if person.personID in parentChildren else []
    marriageFamilies = familyMarriage[person.personID] if person.personID in familyMarriage else []
    yearSpread = [-1, 9999]
    yearFrom, yearTo, placePossibility = [], [], []

    # Person
    if person is not None:
        if record.missing != 2:
            if person.deathYear is not None:
                # Year of death - maximal age person could live up to
                yearFrom.append(person.deathYear - DEATH_MAX)
                # Year of death
                yearTo.append(person.deathYear)
        if record.missing != 1:
            if person.deathPlaceID is not None:
                placePossibility.append((territories[person.deathPlaceID], MEDIUM_L))
    # Marriages
    for family in marriageFamilies:
        if record.missing != 2:
            if family.marriageYear is not None:
                # Year of marriage - maximal age person could marry at
                yearFrom.append(family.marriageYear - MARRIAGE_MAX)
                # Year of marriage - minimal age person could marry at
                yearTo.append(family.marriageYear - MARRIAGE_MIN)
        if record.missing != 1:
            if family.marriagePlaceID is not None:
                placePossibility.append((territories[family.marriagePlaceID], HIGH_L))
            if family.marriagePlaceID2 is not None:
                placePossibility.append((territories[family.marriagePlaceID2], HIGH_L))
    # Parents
    for parent in [father, mother]:
        if parent != -1:
            if record.missing != 2:
                if parent.birthYear is not None:
                    # Year of parents birth + minimal age parent could have children
                    yearFrom.append(parent.birthYear + BIRTH_MIN)
                    # Year of parents birth + maximal age parent could have children
                    if parent.gender == 'F':
                        yearTo.append(parent.birthYear + WOMAN_BIRTH_MAX)
                    else:
                        yearTo.append(parent.birthYear + BIRTH_MAX)
                if parent.deathYear is not None:
                    if parent.birthYear is None:
                        # Year of paretns death - maximal age parent could live up to + minimal age needed for children
                        yearFrom.append(parent.deathYear - DEATH_MAX + BIRTH_MIN)
                    if parent.gender == 'F':
                        # Year of paretns death
                        yearTo.append(parent.deathYear)
                    else:
                        # Year of paretns death + 1 in case of father
                        yearTo.append(parent.deathYear+1)
            if record.missing != 1:
                # TODO -> pridat i misto narozeni? Take muze byt relevantni
                if parent.deathPlaceID is not None:
                    placePossibility.append((territories[parent.deathPlaceID], HIGH_M))
                if parent.birthPlaceID is not None:
                    placePossibility.append((territories[parent.birthPlaceID], MEDIUM_M))
    if parentFamily is not None:
        if record.missing != 2:
            if parentFamily.marriageYear is not None:
                # -> Can change outcome in situation where child was born before marriage
                # -> But in past it didnt happen very often (1940 -> 3,8%; 2015 -> 40,3%)
                yearFrom.append(parentFamily.marriageYear)
        if record.missing != 1:
            if parentFamily.marriagePlaceID is not None:
                placePossibility.append((territories[parentFamily.marriagePlaceID], MEDIUM_H))
            if parentFamily.marriagePlaceID2 is not None:
                placePossibility.append((territories[parentFamily.marriagePlaceID2], MEDIUM_H))
    # Children
    for child in children:
        if record.missing != 2:
            if child.birthYear is not None:
                if person.gender == 'F':
                    # Childs birth year - maximal age mother could have when child was born
                    yearFrom.append(child.birthYear - WOMAN_BIRTH_MAX)
                else:
                    # Childs birth year - maximal age father could have when child was born
                    yearFrom.append(child.birthYear - BIRTH_MAX)
                # Childs birth year - minmal age person could have when child was born
                yearTo.append(child.birthYear - BIRTH_MIN)
        if record.missing != 1:
            if child.birthPlaceID is not None:
                placePossibility.append((territories[child.birthPlaceID], LOW_H))
    # Siblings
    for sibling in siblings:
        if sibling.personID == person.personID:
            continue
        if record.missing != 2:
            if sibling.birthYear is not None:
                yearFrom.append(sibling.birthYear - WOMAN_BIRTH_MAX)
                yearTo.append(sibling.birthYear + WOMAN_BIRTH_MAX)
        if record.missing != 1:
            if sibling.birthPlaceID is not None:
                placePossibility.append((territories[sibling.birthPlaceID], HIGH_H))

    yearSpread[0] = max(yearFrom, default=-1)
    yearSpread[1] = min(yearTo, default=9999)
    if yearSpread[0] == -1 and yearSpread[1] != 9999:
        yearSpread[0] = yearSpread[1] - DEATH_MAX
    elif yearSpread[0] != -1 and yearSpread[1] == 9999:
        yearSpread[1] = yearSpread[0] + DEATH_MAX

    if record.missing == 2:
        yearSpread = (person.birthYear, person.birthYear)
    elif record.missing == 1:
        if person.birthPlaceID is not None:
            placePossibility = [(territories[person.birthPlaceID], HIGH_H)]

    if yearSpread[0] != -1 and yearSpread[1] != 9999 and len(placePossibility) != 0:
        placePossibility = set(placePossibility)
        books = find_books(yearSpread, placePossibility, BIRTH)
        b = [{'recordId': record.id, 'bookId':book.id, 'priority': priority, 'isAround': isAround} for book, priority, isAround in books]
        suggestionsList.extend(b)


'''
@brief Function for gathering information about death record.
Also calls function for finding suitable books.
@param record Record that needs to be solved.
'''
def match_death_record(record):
    person = people[record.personID]
    father = people[person.fatherID] if person.fatherID is not None else None
    mother = people[person.motherID] if person.motherID is not None else None
    children = parentChildren[person.personID] if person.personID in parentChildren else []
    marriageFamilies = familyMarriage[person.personID] if person.personID in familyMarriage else []
    yearSpread = [-1, 9999]
    yearFrom, yearTo, placePossibility = [], [], []

    # Person
    if person is not None:
        if record.missing != 2:
            if person.birthYear is not None:
                yearFrom.append(person.birthYear)
                yearTo.append(person.birthYear + DEATH_MAX)
        if record.missing != 1:
            if person.birthPlaceID is not None:
                placePossibility.append((territories[person.birthPlaceID], LOW_H))
    # Marriages
    for family in marriageFamilies:
        if family.wifeID == person.personID:
            spouseID = family.husbandID if family.husbandID is not None else -1
        else:
            spouseID = family.wifeID if family.wifeID is not None else -1
        if spouseID != -1:
            spouse = people[spouseID]
        else:
            spouse = None
        if record.missing != 2:
            if family.marriageYear is not None:
                yearFrom.append(family.marriageYear)
                yearTo.append(family.marriageYear + DEATH_MAX - MARRIAGE_MIN)
        if record.missing != 1:
            if family.marriagePlaceID is not None:
                placePossibility.append((territories[family.marriagePlaceID], MEDIUM_H))
            if family.marriagePlaceID2 is not None:
                placePossibility.append((territories[family.marriagePlaceID2], MEDIUM_H))
            if spouse is not None and spouse.deathPlaceID is not None:
                placePossibility.append((territories[spouse.deathPlaceID], HIGH_L))

    # Children
    for child in children:
        if record.missing != 2:
            if child.birthYear is not None:
                if person.gender == 'F':
                    yearFrom.append(child.birthYear)
                else:
                    yearFrom.append(child.birthYear + 1)
                yearTo.append(child.birthYear + DEATH_MAX - BIRTH_MIN)
        if record.missing != 1:
            if child.birthPlaceID is not None:
                placePossibility.append((territories[child.birthPlaceID], HIGH_H))
        chFamilies = familyMarriage[child.personID] if child.personID in familyMarriage else []
        for family in chFamilies:
            if record.missing != 1:
                if family.marriagePlaceID is not None:
                    placePossibility.append((territories[family.marriagePlaceID], HIGH_M))
                if family.marriagePlaceID2 is not None:
                    placePossibility.append((territories[family.marriagePlaceID2], HIGH_M))
    # Parents
    for parent in [father, mother]:
        if parent is not None:
            if record.missing != 2:
                if parent.birthYear is not None:
                    yearFrom.append(parent.birthYear + BIRTH_MIN + DEATH_MAX)
                    if parent.gender == 'F':
                        yearTo.append(parent.birthYear + WOMAN_BIRTH_MAX + DEATH_MAX)
                    else:
                        yearTo.append(parent.birthYear + BIRTH_MAX + DEATH_MAX)

    yearSpread[0] = max(yearFrom, default=-1)
    yearSpread[1] = min(yearTo, default=9999)
    if yearSpread[0] == -1 and yearSpread[1] != 9999:
        yearSpread[0] = yearSpread[1] - DEATH_MAX
    elif yearSpread[0] != -1 and yearSpread[1] == 9999:
        yearSpread[1] = yearSpread[0] + DEATH_MAX

    if record.missing == 2:
        yearSpread = (person.deathYear, person.deathYear)
    elif record.missing == 1:
        if person.deathPlaceID is not None:
            placePossibility = [(territories[person.deathPlaceID], HIGH_H)]

    if yearSpread[0] != -1 and yearSpread[1] != 9999 and len(placePossibility) != 0:
        placePossibility = set(placePossibility)
        books = find_books(yearSpread, placePossibility, DEATH)
        b = [{'recordId': record.id, 'bookId':book.id, 'priority': priority, 'isAround': isAround} for book, priority, isAround in books]
        suggestionsList.extend(b)


'''
@brief Function for setting computing values for this file.
@param file File to set values for.
'''
def set_values(file):
    global BIRTH_MIN, BIRTH_MAX, WOMAN_BIRTH_MAX
    global DEATH_MAX
    global MARRIAGE_MIN, MARRIAGE_MAX

    BIRTH_MIN = file.birthMin
    BIRTH_MAX = file.birthMax
    WOMAN_BIRTH_MAX = file.birthMaxW
    DEATH_MAX = file.deathMax
    MARRIAGE_MIN = file.marriageMin
    MARRIAGE_MAX = file.marriageMax


'''
@brief Base function of matcher
@param gId ID of gedcom file
'''
def match_records(gId):
    global GEDCOMID
    GEDCOMID = gId
    file = GFile.find(GEDCOMID)
    set_values(file)

    records = Record.where('gedcomID', '=', GEDCOMID).get()
    setup_function()

    global people, families, parentChildren, familyChildren
    for person in Person.where('gedcomID', '=', GEDCOMID).get():
        people[person.personID] = person
        fatherID = person.fatherID if person.fatherID is not None else -1
        motherID = person.motherID if person.motherID is not None else -1
        if fatherID != -1 or motherID != -1:
            if (fatherID, motherID) not in familyChildren:
                familyChildren[(fatherID, motherID)] = [person]
            else:
                familyChildren[(fatherID, motherID)].append(person)
        if fatherID != -1:
            if fatherID not in parentChildren:
                parentChildren[fatherID] = [person]
            else:
                parentChildren[fatherID].append(person)
        if motherID != -1:
            if motherID not in parentChildren:
                parentChildren[motherID] = [person]
            else:
                parentChildren[motherID].append(person)

    for family in Family.where('gedcomID', '=', GEDCOMID).get():
        families[family.familyID] = family
        husbandID = family.husbandID if family.husbandID is not None else -1
        wifeID = family.wifeID if family.wifeID is not None else -1
        if husbandID != -1:
            if husbandID not in familyMarriage:
                familyMarriage[husbandID] = [family]
            else:
                familyMarriage[husbandID].append(family)
        if wifeID != -1:
            if wifeID not in familyMarriage:
                familyMarriage[wifeID] = [family]
            else:
                familyMarriage[wifeID].append(family)
        if husbandID != -1 or wifeID != -1:
            if (husbandID, wifeID) not in familyParents:
                familyParents[(husbandID, wifeID)] = family

    for record in records:
        if record.type == FAMILY:
            match_marriage_record(record)
        else:
            if record.type == BIRTH:
                match_birth_record(record)
            else:
                match_death_record(record)

    db.table('Record_ParishBook').insert(suggestionsList)
