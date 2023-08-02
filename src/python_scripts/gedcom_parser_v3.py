"""
Author: Dominik Pop
Date:   21.09.2022
Last:   05.10.2022

Description: Module for main.py. Functions are used to parse GEDCOM file.

VUT FIT, 3BIT
"""

# Imports
import re

from gedcom.parser import Parser, FAMILY_MEMBERS_TYPE_WIFE, FAMILY_MEMBERS_TYPE_HUSBAND, FAMILY_MEMBERS_TYPE_CHILDREN
from gedcom.element.individual import IndividualElement
from gedcom.element.family import FamilyElement
import gedcom.tags

from datetime import date as DATE
import sys
from parser_header import Person, Family, AppUser, GFile, db, Territory
from textsearch import TextSearch
from thefuzz import process, fuzz

import json
import base64

BIRTH = 0
DEATH = 1
MARRIAGE = 2

# Values retaken from bachelors work of Jakub Konecný
BIRTH_MIN = 15
BIRTH_MAX = 45
DEATH_MAX = 100
MARRIAGE_MIN = 18
MARRIAGE_MAX = 50

# Global variables
personList = []
recordList = []
familyList = []
suggestionsDict = {}
GEDCOMID = -1
PREFIX = None
FPREFIX = None
parser = None

# Territory dictionaries
territories = {}
districts = {}
municipalities = {}
partsOfMuni = {}
personRecordDict = {}
familyRecordDict = {}
processedPlaces = {}
allocatedPlaces = []

# Text searchers
ts = TextSearch(case="ignore", returns="norm")
tsMuni = TextSearch(case="ignore", returns="norm")
tsParts = TextSearch(case="ignore", returns="norm")
tsDistricts = TextSearch(case="ignore", returns="norm")
tsAliases = TextSearch(case="ignore", returns="norm")

# Territory dictionaries
partsHierarchy = {}
muniHierarchy = {}
districtsHierarchy = {}
districtsTerritories = {}

# Territory aliases for city Přerov
aliases = {
    'Čekyně': 'Přerov VII-Čekyně',
    'Žeravice': 'Přerov XII-Žeravice',
    'Popovice': 'Přerov X-Popovice',
    'Vinary': 'Přerov XI-Vinary',
    'Penčice': 'Přerov XIII-Penčice',
    'Henčlov': 'Přerov VIII-Henčlov',
    'Újezdec': 'Přerov VI-Újezdec',
    'Dluhonice': 'Přerov V-Dluhonice',
    'Lýsky': 'Přerov IX-Lýsky',
    'Kozlovice': 'Přerov IV-Kozlovice',
    'Lověšice': 'Přerov III-Lověšice',
    'Předmostí': 'Přerov II-Předmostí'
}


'''
@brief Function for preparing territory dictionaries
'''
def get_territories():
    global municipalities, districts, territories
    ter = Territory.where('type', '=', 3).get()
    for t in ter:
        districtsHierarchy[t.id] = t.name
        districtsTerritories[t.id] = []
        if t.name in districts:
            districts[t.name].append(t)
        else:
            districts[t.name] = [t]

    ter = Territory.where('type', '=', 4).or_where('type', '=', 5).get()
    for t in ter:
        if t.name in territories:
            territories[t.name].append(t)
        else:
            territories[t.name] = [t]
        if t.type == 4:
            muniHierarchy[t.id] = [t.name, t.partOf, t]
            districtsTerritories[t.partOf].append(t.id)
            if t.name in municipalities:
                municipalities[t.name].append(t)
            else:
                municipalities[t.name] = [t]
        else:
            partsHierarchy[t.id] = [t.name, t.partOf, t]
            muniID = t.partOf
            distID = muniHierarchy[muniID][1]
            districtsTerritories[distID].append(t.id)
            if t.name in partsOfMuni:
                partsOfMuni[t.name].append(t)
            else:
                partsOfMuni[t.name] = [t]

    for t in territories.values():
        if len(t) == 1:
            continue
        key = t[0].name
        t.sort(key=lambda x: x.id, reverse=True)
        tmp = []
        newList = []
        for i in t:
            if i.type == 5:
                tmp.append(i.partOf)
                newList.append(i)
                continue
            if i.type == 4 and i.id not in tmp:
                newList.append(i)
        territories[key] = newList


'''
@brief Function checking if given string is empty or an nonsense
'''
def empty_string(string):
    if string is not None:
        newString = "".join(filter(lambda x: not x.isdigit() and x != '?', string))
    else:
        newString = ""
    if newString is None or newString == '' or newString == ' ':
        return True
    return False


'''
@brief Function for checking if date is in right format
'''
def check_date_format(date):
    # Only exact date is true information, everything other is just additional
    if re.match("^((([0-9])|([0-2][0-9])|([3][0-1]))\s)?((JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)\s)?\d{4}$", date):
        return False
    return True


'''
@brief Function rewriting month shortcuts to numeric ones
'''
def alias_month(string):
    dictionary = {
        'JAN': '01',
        'FEB': '02',
        'MAR': '03',
        'APR': '04',
        'MAY': '05',
        'JUN': '06',
        'JUL': '07',
        'AUG': '08',
        'SEP': '09',
        'OCT': '10',
        'NOV': '11',
        'DEC': '12',
    }
    for key, val in dictionary.items():
        if key in string:
            string = string.replace(key, val)

    splitted = string.split(' ')
    newStr = ''
    for s in splitted:
        if len(s) == 1 and s.isnumeric():
            s = '0' + s
        newStr += s + '. '

    return newStr.strip().strip('.')


'''
@brief Function for checking if given territories have same name but are different types
'''
def is_lesser_with_same_name(ter1, ter2):
    if ter1.id == ter2.partOf or ter2.id == ter1.partOf:
        return True

    return False


'''
@brief Function for picking smaller of given territories
'''
def pick_lesser_one(ter1, ter2):
    if ter1.id == ter2.partOf:
        return ter2
    else:
        return ter1


'''
@brief UNUSED function for comparing if given name is used fully or as an substring
'''
def compare_name_to_string(name, string):
    nameSplitted = name.split(' ')
    stringSplitted = string.split(' ')
    nameCnt = len(nameSplitted)
    cnt = 0
    for n in nameSplitted:
        for l in stringSplitted:
            if n == l:
                cnt += 1
                break

    if cnt == nameCnt:
        return True

    return False


'''
@brief Function for replacing commas inside brackets of given string
'''
def replace_commas_inside_bracket(string):
    boolean = False
    new = ''
    for i in string:
        if i == '(' or i == '[' or i == '{' and boolean is False:
            boolean = True
        elif i == ')' or i == ']' or i == '}' and boolean is True:
            boolean = False
        else:
            if i == ',' and boolean is True:
                i = ';'
        new += i

    # return new.replace(' - ', '-')
    return new


'''
@brief UNUSED function for deleting wrongly suggested places
'''
def delete_wrong_substrings(possibilities, lowest):
    tmp = []
    for pos in possibilities:
        for p in pos:
            if compare_name_to_string(p.name, lowest):
                tmp.append(p)

    return tmp

'''
@brief Function formatting place string for processing
'''
def format_place(place):
    place = replace_commas_inside_bracket(place)
    # place = re.sub("(\()|(\)|(\[)|(\])|(\')|(\")|(\¨))", "", place)
    # place = place.split(', ')  # Splitting in case of hierarchy

    return place


'''
@brief Function for checking if this place string was already processed and returning result taken before if it was
@param string Place string
'''
def check_processed(string):
    for place in processedPlaces:
        if compare_name_to_string(place, string):
            return place
    return None


'''
@brief Function for getting hierarchy string for output diplsay
@param ter Territory to get hierarchy for
'''
def get_hierarchy(ter):
    if ter.type == 4:
        resStr = ter.name + ', okr.: ' + districtsHierarchy[ter.partOf] + ' [' + str(ter.RUIAN_id) + ']'
    else:
        resStr = ter.name + ', ' + muniHierarchy[ter.partOf][0] + ', okr.: ' + districtsHierarchy[muniHierarchy[ter.partOf][1]] + ' [' + str(ter.RUIAN_id) + ']'

    return resStr


'''
@brief Function for creating new suggestion
@param origPlace Original place string
@param possibilities List of possible territories that could be assigned to given place string
@param indi ID of person/family inside GEDCOM file
@param type Type of event
@param idType Type of ID -> used for marriage event, when two places are given
@param completed Boolean telling if place was assigned or not
@param allSame Boolean telling if all places from possibilities have the same key or not
@param outName Name of person/family parents used for display by application
'''
def create_suggestion(origPlace, possibilities, indi, type, idType, completed, allSame, outName):
    suggestionsDict[origPlace] = {}
    suggestionsDict[origPlace]['suggestions'] = [[i.id, get_hierarchy(i)] for i in possibilities]
    suggestionsDict[origPlace]['suggestions'].sort(key=lambda x: x[1])
    if completed:
        suggestionsDict[origPlace]['picked'] = [possibilities[0].id, get_hierarchy(possibilities[0]), True]
    else:
        suggestionsDict[origPlace]['picked'] = [-1, '', False]
    if type == MARRIAGE:
        suggestionsDict[origPlace]['people'] = []
        suggestionsDict[origPlace]['families'] = [{'type': type, 'indi': indi, 'idType': idType, 'name': outName}]
    else:
        suggestionsDict[origPlace]['people'] = [{'type': type, 'indi': indi, 'name': outName}]
        suggestionsDict[origPlace]['families'] = []
    suggestionsDict[origPlace]['allSame'] = allSame

'''
@brief Function finding possible place names inside given string
@param place Given string containing place
@param ts Text searcher
'''
def find_place(place, ts):
    result = list(set(ts.findall(place)))
    res = [territories[i] for i in result]
    return res

'''
@brief UNUSED function for finding place by string similarity
'''
def find_by_similarity(place):
    resultName = process.extractOne(place, [*territories], scorer=fuzz.token_set_ratio, score_cutoff=80)
    print(place, resultName)

    # return territories[resultName]


'''
@brief Function for formatting list of possible places
'''
def format_possibilities_list(tmp):
    possibilities = []
    for pos in tmp:
        for p in pos:
            possibilities.append(p)

    return possibilities


'''
@brief Function for checking if place was(n't) assigned
@param List of possible territories that could be assigned to given place string
'''
def check_if_place_found(possibilities):
    if len(possibilities) == 1:
        allocatedPlaces.append(possibilities[0])
        return possibilities[0].id, possibilities
    elif len(possibilities) == 2 and is_lesser_with_same_name(possibilities[0], possibilities[1]):
        ter = pick_lesser_one(possibilities[0], possibilities[1])
        possibilities = [ter]
        allocatedPlaces.append(ter)
        return ter.id, possibilities

    return -1, possibilities


'''
@brief Function function for checking if suggestion already exists or if it needs to be created
@param place String containing place where event happened
@param possibilities List of possible territories that could be assigned to given place string
@param type Type of event
@param indi ID of person/family inside GEDCOM file
@param idType Type of ID -> used for marriage event, when two places are given
@param outName Name of person/family parents used for display by application
'''
def check_suggestion(place, possibilities, type, indi, idType, outName):
    keyPlace = place
    completed = False
    allSame = False
    if len(possibilities) != 0:
        if len(possibilities) == 1:
            completed = True
        allSame = all(pos.name == possibilities[0].name for pos in possibilities)
        if allSame:
            keyPlace = possibilities[0].name
    else:
        keyPlace = "".join(filter(lambda x: not x.isdigit(), place))

    keyPlace = keyPlace.strip()
    if keyPlace not in suggestionsDict:
        create_suggestion(keyPlace, possibilities, indi, type, idType, completed, allSame, outName)
    else:
        if type == MARRIAGE:
            suggestionsDict[keyPlace]['families'].append({'type': type, 'indi': indi, 'idType': idType, 'name': outName})
        else:
            suggestionsDict[keyPlace]['people'].append({'type': type, 'indi': indi, 'name': outName})


def process_hierarchy(placeSplitted):
    pass


'''
@brief Function for checking if given place string is valid or not and trying to assign right territory to it
@param place String containing place where event happened
@param type Type of event
@param indi ID of person/family inside GEDCOM file
@param outName Name of person/family parents used for display by application
'''
def check_place_format(place, type, indi, outName):
    if empty_string(place):
        return -10, -10

    place = format_place(place)
    placeSplitted = place.split(', ')

    secondID = -1
    possibilities = find_place(place, ts)
    aliasPossibility = tsAliases.findall(place)
    if len(aliasPossibility) != 0:
        aliasNames = [aliases[i] for i in aliasPossibility]
        aliasRes = [territories[i] for i in aliasNames]
        if len(aliasRes) != 0:
            possibilities.extend(aliasRes)

    if type == MARRIAGE and len(possibilities) > 1:
        if len(possibilities) >= 3:
            tmpD = {place.find(possibilities[0][0].name): possibilities[0], place.find(possibilities[1][0].name): possibilities[1], place.find(possibilities[2][0].name): possibilities[2]}
            possibilities = [tmpD[min(tmpD)]]
        elif len(possibilities) == 2:
            tmpPossibilities = format_possibilities_list([possibilities[1]])
            possibilities.pop(1)
            secondID, tmpPossibilities = check_if_place_found(tmpPossibilities)
            check_suggestion(place, tmpPossibilities, type, indi, 2, outName)

    possibilities = format_possibilities_list(possibilities)
    firstID, possibilities = check_if_place_found(possibilities)

    check_suggestion(place, possibilities, type, indi, 1, outName)

    return firstID, secondID


'''
@brief Function for determining if any info is missing for event or not
@param date String holding date of event
@param place String holding place where event happened
'''
def define_missing(date, place):
    return_value = 0
    dateMissing = empty_string(date)
    placeMissing = False

    if not dateMissing:
        dateMissing = check_date_format(date)

    if place == -10:
        placeMissing = True

    if dateMissing is True and placeMissing is True:  # Both missing
        return_value = 3
    elif dateMissing is True and placeMissing is False:  # Date missing
        return_value = 1
    elif dateMissing is False and placeMissing is True:  # Place missing
        return_value = 2

    return return_value


'''
@brief Function for trying to get age of person
@param person Person object
'''
def get_age(person):
    birthYear = person.get_birth_year()
    currentYear = int(DATE.today().year)

    if birthYear != -1:  # Return age of individual
        return currentYear - birthYear
    else:   # We cannot determine age of individual
        return -1


def format_year(date):
    date = date.split(' ')[-1]

    return int(date)


'''
@brief Function for getting numeric ID (called INDI by me) from INDI/FAM TAGs
@param string String holding the ID
@param type Type determining if its person/family ID TAG
'''
def format_indi(string, type):
    result = ""
    prefix = ""
    global PREFIX, FPREFIX
    for c in string:
        if c.isdigit():
            result = result + c
        else:
            if c != '@':
                prefix = prefix + c

    if PREFIX is None and type == 0:
        PREFIX = prefix
    elif FPREFIX is None and type == 1:
        FPREFIX = prefix
    return int(result)

'''
@brief Function getting place and date from marriage event
@param fam Family object
'''
def get_marriage_info(fam):
    marriageDate = None
    marriagePlace = None
    for el in fam.get_child_elements():
        if el.get_tag() == "MARR":
            for i in el.get_child_elements():
                tag = i.get_tag()
                val = i.get_value()
                if tag == "DATE":
                    marriageDate = val.upper()
                elif tag == "PLAC":
                    marriagePlace = val

    return marriageDate, marriagePlace

'''
@brief Function for parsing person TAG
@param person Person object recieved from python-gedcom parser
'''
def parse_person(person):
    # Proccesing name
    first, last = person.get_name()
    outName = first + ' ' + last
    indi = format_indi(person.get_pointer(), 0)

    # BIRTH date and place
    birthDate, birthPlace, birthSources = person.get_birth_data()
    birthPlaceID, garbage = check_place_format(birthPlace, BIRTH, indi, outName)

    # DEATH date and place
    deathDate, deathPlace, deathSources = person.get_death_data()
    deathPlaceID, garbage = check_place_format(deathPlace, DEATH, indi, outName)

    d = {'gedcomID': GEDCOMID,
         'personINDI': indi,
         'firstName': first,
         'lastName': last,
         'gender': person.get_gender(),
         'birthDate': birthDate.upper() if len(birthDate) != 0 else None,
         'birthYear': None,
         'birthPlaceStr': birthPlace if len(birthPlace) != 0 else None,
         'birthPlaceId': birthPlaceID if birthPlaceID != -1 and birthPlaceID != -10 else None,
         'deathDate': deathDate.upper() if len(deathDate) != 0 else None,
         'deathYear': None,
         'deathPlaceStr': deathPlace if len(deathPlace) != 0 else None,
         'deathPlaceId': deathPlaceID if deathPlaceID != -1 and deathPlaceID != -10 else None
         }

    missing = define_missing(d['birthDate'], birthPlaceID)
    d['birthDate'] = alias_month(d['birthDate']) if d['birthDate'] is not None else d['birthDate']
    if missing != 0:
        personRecordDict[d['personINDI']] = [{'type': BIRTH, 'missing': missing, 'gedcomID': GEDCOMID, 'familyID': None}]
    if missing != 1 and missing != 3:
        d['birthYear'] = format_year(d['birthDate'])

    missing = define_missing(d['deathDate'], deathPlaceID)
    d['deathDate'] = alias_month(d['deathDate']) if d['deathDate'] is not None else d['deathDate']
    if missing != 0:
        age = get_age(person)
        if age > DEATH_MAX or age == -1: # Persons where we cannot decide their age are added as well
            if d['personINDI'] in personRecordDict.keys():
                personRecordDict[d['personINDI']].append({'type': DEATH, 'missing': missing, 'gedcomID': GEDCOMID, 'familyID': None})
            else:
                personRecordDict[d['personINDI']] = [{'type': DEATH, 'missing': missing, 'gedcomID': GEDCOMID, 'familyID': None}]
    if missing != 1 and missing != 3:
        d['deathYear'] = format_year(d['deathDate'])

    # Adding person to persons list
    personList.append(d)

'''
@brief Function for parsing family TAG
@param fam Family object recieved from python-gedcom parser
'''
def parse_family(fam):
    if isinstance(parser, Parser):
        wifeO = parser.get_family_members(fam, FAMILY_MEMBERS_TYPE_WIFE)
        if len(wifeO) != 0:
            wifeFirst, wifeLast = wifeO[0].get_name()
            wifeName = wifeFirst + ' ' + wifeLast
            wifeINDI = format_indi(wifeO[0].get_pointer(), 0)
        else:
            wifeName = None
            wifeINDI = None

        husbandO = parser.get_family_members(fam, FAMILY_MEMBERS_TYPE_HUSBAND)
        if len(husbandO) != 0:
            husbandFirst, husbandLast = husbandO[0].get_name()
            husbandName = husbandFirst + ' ' + husbandLast
            husbandINDI = format_indi(husbandO[0].get_pointer(), 0)
        else:
            husbandName = None
            husbandINDI = None

        childINDIs = [format_indi(i.get_pointer(), 0) for i in
                      parser.get_family_members(fam, FAMILY_MEMBERS_TYPE_CHILDREN)]
    else:
        return

    if wifeINDI is not None:
        wife = db.table('Person').where('gedcomID', '=', GEDCOMID).where('personINDI', '=', wifeINDI).pluck('personID')
    else:
        wife = wifeINDI

    if husbandINDI is not None:
        husband = db.table('Person').where('gedcomID', '=', GEDCOMID).where('personINDI', '=', husbandINDI).pluck('personID')
    else:
        husband = husbandINDI

    if len(childINDIs) != 0:
        db.table('Person').where('gedcomID', '=', GEDCOMID).where_in('personINDI', childINDIs).update(motherID=wife,
                                                                                                      fatherID=husband)

    indi = format_indi(fam.get_pointer(), 1)
    marriageDate, marriagePlace = get_marriage_info(fam)
    if husbandName is not None and wifeName is not None:
        outName = husbandName + ' a ' + wifeName
    else:
        if husbandName is None:
            outName = wifeName
        elif wifeName is None:
            outName = husbandName
        else:
            outName = ""
    marriagePlaceId, marriagePlaceId2 = check_place_format(marriagePlace, MARRIAGE, indi, outName)

    d = {
        'gedcomID': GEDCOMID,
        'familyINDI': indi,
        'wifeID': wife,
        'husbandID': husband,
        'marriageDate': marriageDate,
        'marriageYear': None,
        'marriagePlaceStr': marriagePlace,
        'marriagePlaceID': marriagePlaceId if marriagePlaceId != -1 and marriagePlaceId != -10 else None,
        'marriagePlaceID2': marriagePlaceId2 if marriagePlaceId2 != -1 and marriagePlaceId2 != -10 else None
    }

    familyList.append(d)

    # Determining if some information are missing
    missing = define_missing(d['marriageDate'], marriagePlaceId)
    d['marriageDate'] = alias_month(d['marriageDate']) if d['marriageDate'] is not None else d['marriageDate']
    if missing != 0:
        familyRecordDict[d['familyINDI']] = [{'type': MARRIAGE, 'missing': missing, 'gedcomID': GEDCOMID, 'personID': None}]
    if missing != 1 and missing != 3:
        d['marriageYear'] = format_year(d['marriageDate'])


'''
@brief Function that sets text searchers with given territories names
'''
def set_searchers():
    ts.add([*territories])
    tsMuni.add([*municipalities])
    tsParts.add([*partsOfMuni])
    tsDistricts.add([*districts])
    tsAliases.add([*aliases])

'''
@brief Function that tries to handle files with different encoding than UTF-8
@param path Path to input file
'''
def change_encoding(path):
    f = open(path, "r")
    try:
        content = f.read()
    except:
        f.close()
        f = open(path, "r", encoding="utf-8", errors='ignore')
        content = f.read()
    f.close()
    gedcom_path = path
    f = open(gedcom_path, "w", encoding="utf-8")
    f.write(content)
    f.close()

    return gedcom_path


'''
@brief Function for autocompleting unresolved territory string based on distance of suggestions.
'''
def autocomplete_suggestions():
    # List of IDs of all identified places used in given file
    allocated = list(set(allocatedPlaces))
    # Creating map of all territories from neighborhood
    maps = list(set([i['neighbourID'] for i in
                     db.table('Territory_Neighbours').where_in('searchedID', [i.id for i in allocated]).select(
                         'neighbourID').get()]))
    for key, item in suggestionsDict.items():
        # All suggested territories bare the same name
        if item['picked'][0] == -1 and item['allSame'] is True:
            possibilities = []
            for place in item['suggestions']:
                # Place is from the neighborhood
                if place[0] in maps:
                    possibilities.append(place[0])
            # If we found only one place it is probably the right one
            if len(possibilities) == 1:
                item['picked'][0] = possibilities[0]
                if possibilities[0] in muniHierarchy:
                    t = muniHierarchy[possibilities[0]][2]
                else:
                    t = partsHierarchy[possibilities[0]][2]
                item['picked'][1] = get_hierarchy(t)
                item['picked'][2] = True
                item['check'] = True
            # If we found more or none user needs to specify the right one
            else:
                item['check'] = False
        else:
            item['check'] = False

'''
@brief Function for grouping different unresolved territory string by their suggestions.
@return Returns new shortened dictionary
'''
def group_suggestions():
    similar_suggestions = {}
    new_suggestions = {}
    for key, item in suggestionsDict.items():
        if item['picked'][2] is True:
            new_suggestions[key] = item
            continue
        if item['allSame'] or len(item['suggestions']) == 0:
            new_suggestions[key] = item
            continue

        sug = []
        for sugID, name in item['suggestions']:
            sug.append(sugID)
        sug = tuple(set(sug))
        if sug not in similar_suggestions:
            similar_suggestions[sug] = {'keys': [key], 'sug': item}
        else:
            similar_suggestions[sug]['keys'].append(key)
            similar_suggestions[sug]['sug']['people'].extend(item['people'])
            similar_suggestions[sug]['sug']['families'].extend(item['families'])

    for lKeys, d in similar_suggestions.items():
        key = min(d['keys'])
        new_suggestions[key] = d['sug']

    return new_suggestions


'''
@brief Main function of parser
@param path GEDCOM file path
@param gId GEDCOM file ID
'''
def parse_file(path, gId):
    # Getting id of gedcom file
    global GEDCOMID
    GEDCOMID = gId

    # Parsing gedcom
    global parser
    parser = Parser()
    try:
        parser.parse_file(path, False)
    except:
        path = change_encoding(path)
        try:
            parser.parse_file(path, False)
        except:
            print("Error")

    # Getting territory map
    get_territories()
    set_searchers()

    # Getting INDI/FAM elements from file
    families = []
    for i in parser.get_root_child_elements():
        if isinstance(i, IndividualElement):
            # pass
            parse_person(i)
            # break
        elif isinstance(i, FamilyElement):
            families.append(i)

    # Inserting people into Person table
    db.table('Person').insert(personList)

    # Inserting families into Family table
    f = [parse_family(fam) for fam in families]
    db.table('Family').insert(familyList)

    # Referencing person/family with record
    INDIS = [i for i in personRecordDict.keys()]
    personIDs = db.table('Person').where('gedcomID', '=', GEDCOMID).where_in('personINDI', INDIS).select('personID', 'personINDI').get()
    for personID in personIDs:
        records = personRecordDict[personID['personINDI']]
        for d in records:
            d['personID'] = personID['personID']
            recordList.append(d)

    INDIS = [i for i in familyRecordDict.keys()]
    familyIDs = db.table('Family').where('gedcomID', '=', GEDCOMID).where_in('familyINDI', INDIS).select('familyID', 'familyINDI').get()
    for familyID in familyIDs:
        records = familyRecordDict[familyID['familyINDI']]
        for d in records:
            d['familyID'] = familyID['familyID']
            recordList.append(d)

    # Inserting records for each person to database
    db.table('Record').insert(recordList)

    # Trying to autosolve territories
    autocomplete_suggestions()

    # Grouping unresolved strings with same suggestions
    new_suggestions = group_suggestions()

    # Saving file prefixes
    gedcomFile = GFile.find(GEDCOMID)
    gedcomFile.prefix = PREFIX
    gedcomFile.famPrefix = FPREFIX
    gedcomFile.save()

    # Returning encoded dictionary as JSON to controller
    outDict = dict(sorted(new_suggestions.items(), key=lambda x: (x[1]['picked'][2], x[0].lower())))
    jso_en = json.dumps(outDict, indent=4)
    res_arr_bytes = jso_en.encode("ascii")
    base64_bytes = base64.b64encode(res_arr_bytes)
    base64_enc = base64_bytes.decode("ascii")
    print(base64_enc)
