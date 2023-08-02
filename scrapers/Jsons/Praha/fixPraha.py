import json

from scrapper_header import Matrika
from orator import DatabaseManager, Model
from textsearch import TextSearch
import geopy.distance

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

districtsDict = {}
municipalitiesDict = {}
muniDict = {}
partsDict = {}
parDict = {}

okresy = [24, 25, 47, 49, 51, 55, 57, 59, 70, 71, 75, 76]
okSousedi = {
    'Kladno': [47, 76, 57, 71, 25, 54, 53],                 # Kladno, Rakovník, Mělník, Praha-Západ, Beroun, Louny, Litoměřice
    'Rakovník': [47, 76, 25, 77, 54, 68],                   # Kladno, Rakovník, Beroun, Rokycany, Louny, Plzeň-sever
    'Mělník': [47, 57, 71, 70, 55, 53, 96],                 # Kladno, Mělník, Praha-Západ, Praha-Východ, Mladá Boleslav, Litoměřice, Česká Lípa
    'Mladá Boleslav': [57, 70, 55, 59, 96, 52, 79, 44],     # Mělník, Praha-Východ, Mladá Boleslav, Nymburk, Česká Lípa, Liberec, Semily, Jičín
    'Nymburk': [70, 55, 59, 49, 44, 39],                    # Praha-Východ, Mladá Boleslav, Nymburk, Kolín, Jičín, Hradec Králové
    'Kolín': [70, 59, 49, 51, 39, 64],                      # Praha-Východ, Nymburk, Kolín, Kutná Hora, Hradec Králové, Pardubice
    'Kutná Hora': [70, 49, 51, 24, 64, 33, 37],             # Praha-Východ, Kolín, Kutná Hora, Benešov, Pardubice, Chrudim, Havlíčkův Brod
    'Praha-východ': [70, 71, 49, 51, 24, 55, 59, 57, 95],       # Praha-Východ, Praha-Západ, Kolín, Kutná Hora, Benešov, Mladá Boleslav, Nymburk, Mělník, Praha
    'Praha-západ': [47, 70, 71, 24, 57, 75, 25, 95],            # Praha-Východ, Praha-Západ, Benešov, Mělník, Příbram, Beroun, Kladno, Praha
    'Benešov': [70, 71, 24, 75, 51, 37, 65, 86],            # Praha-Východ, Praha-Západ, Benešov, Příbram, Kutná Hora, Havlíčkův Brod, Pelhřimov, Tábor
    'Příbram': [71, 24, 75, 25, 86, 73, 81, 66, 77],        # Praha-Západ, Benešov, Příbram, Beroun, Tábor, Písek, Strakonice, Plzeň-jih, Rokycany
    'Beroun': [25, 71, 75, 47, 76, 77]                      # Praha-Západ, Příbram, Beroun, Kladno, Rakovník, Rokycany
}

mapa = {
    'Libčice': 81809,
    'Obora': 108782,
    'Chlumín': 108766,
    'Předbořice': 193119
}

regionMap = {}
disTer = {}

def get_maps():
    for name, val in okSousedi.items():
        b = None
        districts = Territory.where_in('id', val).get()
        disTer[name] = {}
        mID = []
        for i in districts:
            if i.id not in districtsDict:
                districtsDict[i.id] = i
            if i.name == name:
                b = i.id
        municipalities = Territory.where_in('partOf', val).get()
        for i in municipalities:
            if i.id not in municipalitiesDict:
                municipalitiesDict[i.id] = i
            if i.partOf == b:
                if i.name not in disTer[name]:
                    disTer[name][i.name] = [i]
                else:
                    disTer[name][i.name].append(i)
                mID.append(i.id)
        muniID = [i.id for i in municipalities]
        parts = Territory.where_in('partOf', muniID).get()
        d = {}
        for p in parts:
            if p.name in d:
                d[p.name].append(p)
            else:
                d[p.name] = [p]
            if p.partOf in mID:
                if p.name not in disTer[name]:
                    disTer[name][p.name] = [p]
                else:
                    disTer[name][p.name].append(p)
        regionMap[name] = d

        for t in disTer[name].values():
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
            disTer[name][key] = newList


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

def assign_with_originator(puvodce, uzemi, m, territories):
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
                    okres = districtsDict[territory.partOf]
                    m.uzemi[name]['okres'] = okres.name
                else:
                    obec = municipalitiesDict[territory.partOf]
                    okres = districtsDict[obec.partOf]
                    m.uzemi[name]['okres'] = okres.name
                    m.uzemi[name]['obec'] = obec.name
                m.uzemi[name]['varianty'] = var
                if okres.name not in m.okresy:
                    m.okresy.append(okres.name)
            else:
                m.uzemi[name] = {'typ': -1, 'varianty': var, 'ruian': -1}
        else:
            m.uzemi[name] = {'typ': -2, 'varianty': var, 'ruian': -2}

def assing_with_logic(uzemi, m, territories):
    notAssigned = {}
    processed = []
    d = {}
    for name, var in uzemi.items():
        territory = None
        if name in territories:
            possibilities = territories[name]
            if len(possibilities) == 1:
                territory = possibilities[0]
            elif len(possibilities) > 1:
                territory = possibilities
            if territory is not None and isinstance(territory, Territory):
                m.uzemi[name] = {'typ': territory.type, 'ruian': territory.RUIAN_id}
                if territory.type == 4:
                    okres = districtsDict[territory.partOf]
                    m.uzemi[name]['okres'] = okres.name
                else:
                    obec = municipalitiesDict[territory.partOf]
                    okres = districtsDict[obec.partOf]
                    m.uzemi[name]['okres'] = okres.name
                    m.uzemi[name]['obec'] = obec.name
                m.uzemi[name]['varianty'] = var
                processed.append(territory)
                if okres.name not in m.okresy:
                    m.okresy.append(okres.name)
            else:
                notAssigned[name] = var
                if territory is not None:
                    if name in mapa:
                        ruian = mapa[name]
                        for t in territory:
                            if t.RUIAN_id == ruian:
                                obec = municipalitiesDict[t.partOf]
                                okres = districtsDict[obec.partOf]
                                m.uzemi[name] = {'typ': 5, 'varianty': var, 'ruian': t.RUIAN_id}
                                m.uzemi[name]['okres'] = okres.name
                                m.uzemi[name]['obec'] = obec.name
                    else:
                        m.uzemi[name] = {'typ': 5, 'varianty': var, 'ruian': [t.RUIAN_id for t in territory]}
                else:
                    m.uzemi[name] = {'typ': -1, 'varianty': var, 'ruian': -1}
        else:
            m.uzemi[name] = {'typ': -2, 'varianty': var, 'ruian': -2}

    sum = [0, 0]
    cnt = 0
    for place in processed:
        if isinstance(place, Territory):
            sum[0] += place.latitude
            sum[1] += place.longitude
            cnt += 1
    if cnt != 0:
        centroid = (sum[0] / cnt, sum[1] / cnt)
        for name, var in notAssigned.items():
            if name in territories:
                possibilities = territories[name]
                min = [100000000, None]
                for pos in possibilities:
                    cor2 = (pos.latitude, pos.longitude)
                    distance = geopy.distance.geodesic(centroid, cor2).km
                    if min[0] > distance:
                        min = [distance, pos]
                if min[1] is not None and isinstance(min[1], Territory):
                    territory = min[1]
                    if isinstance(territory, Territory):
                        m.uzemi[name] = {'typ': territory.type, 'ruian': territory.RUIAN_id}
                        if territory.type == 4:
                            okres = districtsDict[territory.partOf]
                            m.uzemi[name]['okres'] = okres.name
                        else:
                            obec = municipalitiesDict[territory.partOf]
                            okres = districtsDict[obec.partOf]
                            m.uzemi[name]['okres'] = okres.name
                            m.uzemi[name]['obec'] = obec.name
                        m.uzemi[name]['varianty'] = var
                        if okres.name not in m.okresy:
                            m.okresy.append(okres.name)


        # result = ts.findall(m.puvodce)
        # if len(result) == 1:
        #     possibilities = territories[result[0]]
        #     min = [100000000, None]
        #     for pos in possibilities:
        #         cor2 = (pos.latitude, pos.longitude)
        #         distance = geopy.distance.geodesic(centroid, cor2).km
        #         if min[0] > distance:
        #             min = [distance, pos]
        #     if min[1] is not None:
        #         puvodce = min[1]
        #         processedPuvodce[m.puvodce] = puvodce
        #         assign_with_originator(puvodce, notAssigned, m, territories)

get_maps()
name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Praha\Praha.json'
f = open(name, encoding='utf8')
y = json.load(f)

matriky = dict()
matriky['web'] = "https://ebadatelna.soapraha.cz"
matriky['datum'] = "2023-02-08"
matriky['matriky'] = []
processedPuvodce = {}

puvodci = 0
for mat in y['matriky']:
    m = set_default(mat)
    m.uzemi = {}
    m.okresy = []
    d = {}
    notAssigned = []
    okres = mat['okresy'][0]
    puvodce = None

    if len(mat['uzemi']) == 0:
        continue

    if okres in regionMap:
        territories = regionMap[okres]
    else:
        print(mat['url'])
        continue

    if m.puvodce not in processedPuvodce:
        ts = TextSearch(case="ignore", returns="norm")
        if okres in disTer:
            ts.add([*disTer[okres]])
            result = ts.findall(m.puvodce)
            if len(result) == 1:
                possibilities = disTer[okres][result[0]]
                if len(possibilities) == 1:
                    puvodce = possibilities[0]
                    processedPuvodce[m.puvodce] = puvodce
                    puvodci += 1
    else:
        puvodce = processedPuvodce[m.puvodce]
        puvodci += 1

    if puvodce is not None:
        assign_with_originator(puvodce, mat['uzemi'], m, territories)
    else:
        assing_with_logic(mat['uzemi'], m, territories)

    matriky['matriky'].append(m.__dict__)

print(puvodci)
with open("Praha_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(matriky, outfile, indent=6, ensure_ascii=False)