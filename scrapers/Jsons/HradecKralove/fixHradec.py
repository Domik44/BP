import json
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
name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/HradecKralove\Hradec.json'
f = open(name, encoding='utf8')
y = json.load(f)

matriky = dict()
matriky['web'] = "https://aron.vychodoceskearchivy.cz"
matriky['datum'] = "2023-03-29"
matriky['matriky'] = []

for mat in y['matriky']:
    m = set_default(mat)
    m.uzemi = {}
    m.okresy = []
    notAssigned = []
    for name, var in mat['uzemi'].items():
        if var['typ'] == 4:
            m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'varianty': var['varianty']}
            if name in muniDict:
                possibilities = muniDict[name]
                filtered = []
                for pos in possibilities:
                    if districtsDict[pos.partOf].name == var['okres']:
                        filtered.append(pos)
                if len(filtered) == 0: # Neco jsme v DB nasli s takovym nazvem, ale neproslo to filtrem
                    m.uzemi[name]['ruian'] = -1
                elif len(filtered) == 1:
                    m.uzemi[name]['ruian'] = filtered[0].RUIAN_id
                else:
                    m.uzemi[name]['ruian'] = [i.RUIAN_id for i in filtered]
            else:
                m.uzemi[name]['ruian'] = -2 # Nic s danym nazvem jsme nenasli v DB
        elif var['typ'] == 5:
            m.uzemi[name] = {'typ': var['typ'], 'okres': var['okres'], 'obec': var['obec'], 'varianty': var['varianty']}
            if name in parDict:
                possibilities = parDict[name]
                filtered = []
                tmp = []
                for pos in possibilities:
                    if municipalitiesDict[pos.partOf].name == var['obec']:
                        tmp.append(pos)
                for pos in tmp:
                    muni = municipalitiesDict[pos.partOf]
                    if districtsDict[muni.partOf].name == var['okres']:
                        filtered.append(pos)
                if len(filtered) == 0: # Neco jsme v DB nasli s takovym nazvem, ale neproslo to filtrem
                    m.uzemi[name]['ruian'] = -1
                elif len(filtered) == 1:
                    m.uzemi[name]['ruian'] = filtered[0].RUIAN_id
                else:
                    m.uzemi[name]['ruian'] = [i.RUIAN_id for i in filtered]
            else:
                m.uzemi[name]['ruian'] = -2
        else:
            m.uzemi[name] = {'typ': var['typ'], 'ruian': -3, 'okres': var['okres'], 'obec': var['obec'], 'cObec': var['cast'], 'varianty': var['varianty']}
        if var['okres'] not in m.okresy:
            m.okresy.append(var['okres'])

    matriky['matriky'].append(m.__dict__)


with open("Hradec_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(matriky, outfile, indent=6, ensure_ascii=False)


# def formate_date(years):
#     newYears = []
#     for year in years:
#         year = year.split('/')[-1]
#         newYears.append(year)
# 
#     return newYears
# 
# for mat in y['matriky']:
#     rYears = mat['rozsah'].split(' - ')
#     rYears = formate_date(rYears)
#     if len(rYears) == 1:
#         rYears.append(rYears[0])
#     mat['rozsah'] = rYears[0] + ' - ' + rYears[1]
# 
#     newObsah = []
#     for obsah in mat['obsah']:
#         splitted = obsah.split('•')
#         years = splitted[1].split('-')
#         years = formate_date(years)
#         if len(years) == 1:
#             years.append(years[0])
#         newObsah.append(splitted[0] + '•' + years[0] + '-' + years[1])
#     print(newObsah)
#     mat['obsah'] = newObsah
# 
# with open("C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/HradecKralove\Hradec_fixed.json", "w", encoding='utf8') as outfile:
#     json.dump(y, outfile, indent=6, ensure_ascii=False)