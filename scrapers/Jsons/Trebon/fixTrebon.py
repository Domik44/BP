import json

name = 'C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Trebon\Trebon2.json'
f = open(name, encoding='utf8')
y = json.load(f)

for mat in y['matriky']:
    if mat['uzemi'] is not None:
        for name, var in mat['uzemi'].items():
            if var['okres'] not in mat['okresy']:
                mat['okresy'].append(var['okres'])
    else:
        print(mat['url'])

with open("C:/Users/popdo/Desktop/BCP/Scrappery/Jsons/Trebon\Trebon_fixed.json", "w", encoding='utf8') as outfile:
    json.dump(y, outfile, indent=6, ensure_ascii=False)