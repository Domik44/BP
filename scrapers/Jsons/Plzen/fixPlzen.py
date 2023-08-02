import json
import re

import geopy.distance
from textsearch import TextSearch

from scrapper_header import Matrika
from orator import DatabaseManager, Model

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

class Territory(Model):
    __table__ = 'Territory'
    __timestamps__ = False

db = DatabaseManager(config)
Model.set_connection_resolver(db)

okresy = [31, 80, 45, 68, 83, 32, 54, 66, 34, 48, 81, 69, 77, 67, 75, 25, 76]
okSousedi = {
    'Cheb': [31, 80, 45, 68, 83],               # Cheb, Sokolov, Karlovy Vary, Plzeň-sever, Tachov
    'Sokolov': [31, 80, 45],                    # Sokolov, Cheb, Karlovy Vary
    'Karlovy Vary': [31, 80, 45, 68, 32, 54],   # Sokolov, Cheb, Chomutov, Louny, Plzeň-sever, Karlovy Vary
    'Tachov': [31, 68, 83, 66, 34],             # Tachov, Cheb, Plzeň-sever, Plzeň-jih, Domažlice
    'Domažlice': [83, 66, 34, 48],              # Domažlice, Tachov, Plzeň-jih, Klatovy
    'Klatovy': [66, 34, 48, 81, 69],            # Klatovy, Domažlice, Plzeň-jih, Strakonice, Prachatice
    'Rokycany': [68, 66, 77, 67, 75, 25, 76],           # Rokycany, Plzeň-sever, Plzeň-město, Plzeň-jih, Příbram, Beroun, Rakovník
    'Plzeň-jih': [68, 83, 66, 34, 48, 81, 77, 67, 75],  # Plzeň-jih, Plzeň-město, Rokycany, Příbram, Strakonice, Klatovy, Domažlice, Tachov, Plzeň-sever
    'Plzeň-město': [68, 66, 77, 67],                    # Plzeň-město, Plzeň-jih, Plzeň-sever, Rokycany
    'Plzeň-sever': [31, 45, 68, 83, 54, 66, 77, 67, 76] # Plzeň-sever, Plzeň-jih, Plzeň-město, Rokycany, Tachov, Cheb, Karlovy Vary, Louny, Rakovník
}
okresyDict = {}
obceDict = {}
castiDict = {}
hierarchyDict = {}
territories = {}
obce = []

def get_maps():
    districts = Territory.where_in('id', okresy).get()
    global okresyDict
    okresyDict = {i.id: i for i in districts}
    municipalities = Territory.where_in('partOf', okresy).get()
    global  obceDict
    obceDict = {i.id: i for i in municipalities}
    obce = [i.id for i in municipalities]
    parts = Territory.where_in('partOf', obce).get()

    ter = [i for i in municipalities]
    for i in parts:
        ter.append(i)
    for t in ter:
        if t.name in territories:
            territories[t.name].append(t)
        else:
            territories[t.name] = [t]
        # if t.type == 4:
        #     muniHierarchy[t.id] = [t.name, t.partOf]
        #     if t.name in municipalities:
        #         municipalities[t.name].append(t)
        #     else:
        #         municipalities[t.name] = [t]
        # else:
        #     partsHierarchy[t.id] = [t.name, t.partOf]
        #     if t.name in partsOfMuni:
        #         partsOfMuni[t.name].append(t)
        #     else:
        #         partsOfMuni[t.name] = [t]
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

    # cnt = 0
    # for i in territories.values():
    #     if len(i) == 1:
    #         cnt += 1
    # print(cnt)
    # print(len(territories))


def get_okres(place):
    if place.type == 4:
        okres = okresyDict[place.partOf]
    else:
        mun = obceDict[place.partOf]
        okres = okresyDict[mun.partOf]

    return okres


def set_default(mat):
    m = Matrika()
    m.url = mat['url']
    m.invCislo = mat['invCislo']
    m.signatura = mat['signatura']
    m.jazyk = mat['jazyk']
    m.typ = mat['typ']
    m.puvodce = mat['puvodce']
    m.rozsah = mat['rozsah']
    m.obsah = mat['obsah']
    m.okresy = mat['okresy']

    return m


def assign_with_originator(puvodce, uzemi, m):
    for name, var in uzemi.items():
        territory = None
        if name in territories:
            possibilities = territories[name]
            if len(possibilities) == 1:
                territory = possibilities[0]
            else:
                cor1 = (puvodce.latitude, puvodce.longitude)
                min = [100000000, None]
                for place in possibilities:
                    cor2 = (place.latitude, place.longitude)
                    distance = geopy.distance.geodesic(cor1, cor2).km
                    if min[0] > distance:
                        min = [distance, place]
                if min[1] is not None:
                    territory = min[1]
            if territory is not None and isinstance(territory, Territory):
                m.uzemi[name] = {'typ': territory.type, 'ruian': territory.RUIAN_id}
                if territory.type == 4:
                    okres = okresyDict[territory.partOf]
                    m.uzemi[name]['okres'] = okres.name
                else:
                    obec = obceDict[territory.partOf]
                    okres = okresyDict[obec.partOf]
                    m.uzemi[name]['okres'] = okres.name
                    m.uzemi[name]['obec'] = obec.name
                m.uzemi[name]['varianty'] = var
                if okres.name not in m.okresy:
                    m.okresy.append(okres.name)
            else:
                m.uzemi[name] = {'typ': -1, 'varianty': var, 'ruian': -1}
        else:
            m.uzemi[name] = {'typ': -2, 'varianty': var, 'ruian': -2}


def assing_with_logic(uzemi, m): #TODO
    notAssigned = {}
    processed = []
    d = {}
    for name, var in uzemi.items():
        territory = None
        if name in territories:
            possibilities = territories[name]
            if len(possibilities) == 1:
                territory = possibilities[0]
            if territory is not None and isinstance(territory, Territory):
                m.uzemi[name] = {'typ': territory.type, 'ruian': territory.RUIAN_id}
                if territory.type == 4:
                    okres = okresyDict[territory.partOf]
                    m.uzemi[name]['okres'] = okres.name
                else:
                    obec = obceDict[territory.partOf]
                    okres = okresyDict[obec.partOf]
                    m.uzemi[name]['okres'] = okres.name
                    m.uzemi[name]['obec'] = obec.name
                m.uzemi[name]['varianty'] = var
                processed.append(territory)
                if okres.name not in m.okresy:
                    m.okresy.append(okres.name)
            else:
                # for place in possibilities:
                #     okres = get_okres(place)
                #     if okres.name not in d:
                #         d[okres.name] = [place]
                #     else:
                #         d[okres.name].append(place)
                notAssigned[name] = var
                m.uzemi[name] = {'typ': -1, 'varianty': var, 'ruian': -1}
        else:
            m.uzemi[name] = {'typ': -2, 'varianty': var, 'ruian': -2}

    sum = [0,0]
    cnt = 0
    for place in processed:
        if isinstance(place, Territory):
            sum[0] += place.latitude
            sum[1] += place.longitude
            cnt += 1
    if cnt != 0:
        centroid = (sum[0]/cnt, sum[1]/cnt)
        result = ts.findall(m.puvodce)
        if len(result) == 1:
            possibilities = territories[result[0]]
            min = [100000000, None]
            for pos in possibilities:
                cor2 = (pos.latitude, pos.longitude)
                distance = geopy.distance.geodesic(centroid, cor2).km
                if min[0] > distance:
                    min = [distance, pos]
            if min[1] is not None:
                puvodce = min[1]
                processedPuvodce[m.puvodce] = puvodce
                assign_with_originator(puvodce, notAssigned, m)

    # print(m.url, d)

get_maps()
name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Plzen\Plzen.json'
f = open(name, encoding='utf8')
y = json.load(f)

matriky = dict()
matriky['web'] = "https://www.portafontium.eu/"
matriky['datum'] = "2023-02-08"
matriky['matriky'] = []
ts = TextSearch(case="ignore", returns="norm")
ts.add([*territories])

processedPuvodce = {}

for mat in y['matriky']:
    m = set_default(mat)
    m.uzemi = {}
    m.okresy = []
    d = {}
    notAssigned = []
    puvodce = None

    if len(mat['uzemi']) == 0:
        continue

    if m.puvodce not in processedPuvodce:
        result = ts.findall(m.puvodce)
        if len(result) == 1:
            possibilities = territories[result[0]]
            if len(possibilities) == 1:
                puvodce = possibilities[0]
                processedPuvodce[m.puvodce] = puvodce
    else:
        puvodce = processedPuvodce[m.puvodce]

    if puvodce is not None:
        assign_with_originator(puvodce, mat['uzemi'], m)
    else:
        assing_with_logic(mat['uzemi'], m)

    matriky['matriky'].append(m.__dict__)
    # break

with open("Plzen_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(matriky, outfile, indent=6, ensure_ascii=False)