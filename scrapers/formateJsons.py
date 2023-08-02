import json
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
territories = {}


def get_map():
    global territories
    t = Territory.where('type', '=', 4).or_where('type', '=', 5).get()
    territories = {i.RUIAN_id: (i.latitude, i.longitude) for i in t}
    print(len(territories))


name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Trebon\Trebon_fixed.json'
f = open(name, encoding='utf8')
y = json.load(f)
get_map()

for mat in y['matriky']:
    if mat['invCislo'] == "":
        mat['invCislo'] = None
    if mat['typ'] == "":
        mat['typ'] = None
    if mat['jazyk'] == "":
        mat['jazyk'] = None
    if mat['signatura'] == "":
        mat['signatura'] = None

    if 'uzemi' in mat:
        if mat['uzemi'] is not None:
            for name, val in mat['uzemi'].items():
                d = {}
                d['typ'] = val['typ']
                if val['typ'] < 0:
                    d['typ'] = None
                if val['ruian'] < 0:
                    d['ruian'] = None
                else:
                    d['ruian'] = val['ruian']
                    t = territories[val['ruian']]
                    d['latitude'] = t[0]
                    d['longitude'] = t[1]
                if 'zsj' in val:
                    d['zsj'] = val['zsj']
                if 'cObec' in val:
                    d['cObec'] = val['cObec']
                if 'obec' in val:
                    d['obec'] = val['obec']
                if 'okres' in val:
                    d['okres'] = val['okres']
                if 'varianty' in val:
                    d['varianty'] = val['varianty']
                mat['uzemi'][name] = d


with open("Jsons/Trebon/Trebon_formated.json", "w", encoding='utf8') as outfile:
    json.dump(y, outfile, indent=6, ensure_ascii=False)