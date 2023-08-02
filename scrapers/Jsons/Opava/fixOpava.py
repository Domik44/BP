import json
import re

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

districtsDict = {}
municipalitiesDict = {}
muniDict = {}
partsDict = {}
parDict = {}

def get_maps():
    global districtsDict
    global municipalitiesDict, muniDict
    global partsDict, parDict
    districts = Territory.where('type', '=', 3).get()
    districtsDict = {i.id: i for i in districts}
    municipalities = Territory.where('type', '=', 4).get()
    municipalitiesDict = {i.id: i for i in municipalities}
    for muni in municipalities:
        if muni.name not in muniDict:
            muniDict[muni.name] = [muni]
        else:
            muniDict[muni.name].append(muni)
    parts = Territory.where('type', '=', 5).get()
    partsDict = {i.id: i for i in parts}
    for part in parts:
        if part.name not in parDict:
            parDict[part.name] = [part]
        else:
            parDict[part.name].append(part)


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


get_maps()
name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Opava\Opava.json'
f = open(name, encoding='utf8')
y = json.load(f)

mapa = {
    'Nová Dědina': 44423,
    'Velký Újezd': 179795,
    'Mistřovice': 96121,
    'Střítež': 157937,
    'Albrechtice': 124,
    'Hradiště': 47481,
    'Těšín': 413437,
    'Pstruží': 136468,
    'Zlatníky': 193232,
    'Kyjovice': 78565,
    'Horní Újezd': 44547,
    'Býškovice': 17205,
    'Chválkovice': 110914,
    'Kravaře': 74233,
    'Malenovice': 90786,
    'Smilovice': 151041,
    'Skalice': 147974,
    'Vojkovice': 184578,
    'Lubno': 88064,
    'Lískovec': 84891,
    'Dobrá': 26981,
    'Krásná': 73393,
    'Dolní Suchá': 37770,
    'Lazy': 413461,
    'Životice': 37681,
    'Janovice': 57100,
    'Petřkovice': 120472,
    'Závada': 120375,
    'Petrovice': 599077,
    'Bohuslavice': 6521,
    'Bělá': 1619,
    'Sudice': 158755,
    'Bernartice': 2852,
    'Babice': 680,
    'Petřvald': 120481,
    'Volárna': 141852,
    'Kouty': 74225,
    'Vrbka': 186023,
    'Oldřichovice': 110035,
    'Václavovice': 176036
}

matriky = dict()
matriky['web'] = "https://digi.archives.cz"
matriky['datum'] = "2023-02-08"
matriky['matriky'] = []

for mat in y['matriky']:
    m = set_default(mat)
    m.uzemi = {}
    m.okresy = []
    notAssigned = []
    for name, var in mat['uzemi'].items():
        noOkres = False
        if var['typ'] == 4:
            m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'varianty': var['varianty']}
            if name in muniDict:
                possibilities = muniDict[name]
                filtered = []
                if var['okres'] != "":
                    for pos in possibilities:
                        if districtsDict[pos.partOf].name == var['okres']:
                            filtered.append(pos)
                else:
                    noOkres = True
                    filtered = possibilities
                if len(filtered) == 0: # Neco jsme v DB nasli s takovym nazvem, ale neproslo to filtrem
                    m.uzemi[name]['ruian'] = -1
                elif len(filtered) == 1:
                    m.uzemi[name]['ruian'] = filtered[0].RUIAN_id
                    okres = districtsDict[filtered[0].partOf]
                    if noOkres:
                        m.uzemi[name]['okres'] = okres.name
                    if okres.name not in m.okresy:
                        m.okresy.append(okres.name)
                else:
                    m.uzemi[name]['ruian'] = [i.RUIAN_id for i in filtered]
            else:
                m.uzemi[name]['ruian'] = -2 # Nic s danym nazvem jsme nenasli v DB
            if not noOkres and var['okres'] != "":
                if var['okres'] not in m.okresy:
                    m.okresy.append(var['okres'])
        elif var['typ'] == 5:
            m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'obec': var['obec'], 'varianty': var['varianty']}
            if name in parDict:
                possibilities = parDict[name]
                filtered = []
                tmp = []
                if var['okres'] != "":
                    for pos in possibilities:
                        if municipalitiesDict[pos.partOf].name == var['obec']:
                            tmp.append(pos)
                    for pos in tmp:
                        muni = municipalitiesDict[pos.partOf]
                        if districtsDict[muni.partOf].name == var['okres']:
                            filtered.append(pos)
                else:
                    noOkres = True
                    filtered = possibilities
                if len(filtered) == 0: # Neco jsme v DB nasli s takovym nazvem, ale neproslo to filtrem
                    m.uzemi[name]['ruian'] = -1
                elif len(filtered) == 1:
                    m.uzemi[name]['ruian'] = filtered[0].RUIAN_id
                    obec = municipalitiesDict[filtered[0].partOf]
                    okres = districtsDict[obec.partOf]
                    if okres.name not in m.okresy:
                        m.okresy.append(okres.name)
                    if noOkres:
                        m.uzemi[name]['okres'] = okres.name
                        m.uzemi[name]['obec'] = obec.name
                else:
                    m.uzemi[name]['ruian'] = [i.RUIAN_id for i in filtered]
            else:
                m.uzemi[name]['ruian'] = -2
            if not noOkres and var['okres'] != "":
                if var['okres'] not in m.okresy:
                    m.okresy.append(var['okres'])
        else:
            if name in mapa:
                ruian = mapa[name]
                t = Territory.where('RUIAN_id', '=', ruian).where('type', '!=', 2).first()
                m.uzemi[name] = {'typ': t.type, 'varianty': var['varianty'], 'ruian': ruian}
                if t.type == 4:
                    okres = districtsDict[t.partOf]
                else:
                    obec = municipalitiesDict[t.partOf]
                    okres = districtsDict[obec.partOf]
                    m.uzemi[name]['obec'] = obec.name
                m.uzemi[name]['okres'] = okres.name
                if okres.name not in m.okresy:
                    m.okresy.append(okres.name)
            else:
                query = Territory.where('name', '=', name).or_where('type', '=', 4)
                t = [i for i in query.where('name', '=', name).get()]
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
                if len(newList) == 1:
                    terr = newList[0]
                    m.uzemi[name] = {'typ': terr.type, 'varianty': var['varianty'], 'ruian': terr.RUIAN_id}
                    if terr.type == 4:
                        okres = districtsDict[terr.partOf]
                    else:
                        obec = municipalitiesDict[terr.partOf]
                        okres = districtsDict[obec.partOf]
                        m.uzemi[name]['obec'] = obec.name
                    m.uzemi[name]['okres'] = okres.name
                    if okres.name not in m.okresy:
                        m.okresy.append(okres.name)
                # elif len(newList) > 1:
                #     m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'varianty': var['varianty'], 'ruian': [i.RUIAN_id for i in newList]}
                else:
                    m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'varianty': var['varianty'], 'ruian': -3}

    matriky['matriky'].append(m.__dict__)

with open("Opava_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(matriky, outfile, indent=6, ensure_ascii=False)