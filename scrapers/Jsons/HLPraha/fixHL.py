import json

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

name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/HLPraha\HLPraha2.json'
f = open(name, encoding='utf8')
y = json.load(f)


territories = {}
ts = TextSearch(case="ignore", returns="norm")

numberMap = {
    'XVIII': 18,
    'XIX': 19,
    'VIII': 8,
    'IX': 9,
    'XVI': 16,
    'XVII': 17,
    'XII': 12,
    'XIII': 13,
    'XIV': 14,
    'XV': 15,
    'XI': 6,
    'XX': 20,
    'X': 10
}

skip = ['celá farnost', 'židé (?)', 'index (A-K)', 'samoty', 'zápisy společné pro celou farnost',
        'židé (Libeň?)', 'košířský lazaret', 'vinice', 'výpisy z matrik kostela sv. Sávy ve Vídni'
        , 'Židé', 'vojenská matrika pro celou farnost', 'vinice a samoty', 'statky', 'Všeobecná nemocnice',
        'index pro celou farnost']

mapka = {
    'pohřbení na malostranské hřbitově z obcí Malé Strany': 'Malá Strana',
    'Holešovice-Bubny': 'Holešovice',
    'index pro Prahu VII (Holešovice-Bubny)': 'Holešovice'
}

def get_territories():
    ter = Territory.where('partOf', '=', '3903').get()
    for t in ter:
        if t.name not in territories:
            territories[t.name] = t
    ter = Territory.where('type', '=', '4').where('name', '=', 'Praha').first()
    territories[ter.name] = ter


def formate_year(year):
    newYear = []
    for y in year:
        y = y.split('.')[-1]
        newYear.append(y)

    return newYear


def format_obsah(obsah):
    newObsah = []
    for o in obsah:
        spl = o.split('•')
        zn = spl[0]
        years = spl[1].split('-')
        years = formate_year(years)
        if len(years) > 1:
            min = years[0]
            max = years[1]
        else:
            min = years[0]
            max = min
        newO = zn + '•' + min + '-' + max
        newObsah.append(newO)
    return newObsah


get_territories()
ts.add([*territories])
g = []
for mat in y['matriky']:
    obsah = mat['obsah']
    obsah = format_obsah(obsah)
    mat['obsah'] = obsah
    newUzemi = {}
    for name, var in mat['uzemi'].items():
        d = {}
        if var['ruian'] == -1 and isinstance(name, str):
            if name in skip:
                continue
            if name in mapka:
                name = mapka[name]
            elif 'Praha' in name:
                if ' - ' in name:
                    splitted = name.split(' - ')
                    name = splitted[1]
                else:
                    for i, val in numberMap.items():
                        if i in name.split(' '):
                            name = name.replace(i, str(numberMap[i]))
                            break
            elif name.isupper():
                name = name.capitalize()
            elif 'index - ' in name:
                name = name.replace('index - ', '')
            elif '/' in name:
                splitted = name.split('/')
                if len(splitted) == 2:
                    name = splitted[0]
                elif len(splitted) == 3:
                    name = splitted[0]
            else:
                result = ts.findall(name)
                if len(result) == 1:
                    name = result[0]
                else:
                    if name not in g:
                        g.append(name)

            name = name.strip(' ')
            if name in territories:
                ter = territories[name]
                d = {'typ': ter.type, 'ruian': ter.RUIAN_id, 'varianty': [], 'okres': 'území Hlavního města Prahy'}
                if ter.type == 5:
                    d['obec'] = 'Praha'
            else:
                d = var
        else:
            d = var
        newUzemi[name] = d
    mat['uzemi'] = newUzemi


with open("C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/HLPraha\HLPraha_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(y, outfile, indent=6, ensure_ascii=False)

for i in g:
    print(i)

print(len(g))