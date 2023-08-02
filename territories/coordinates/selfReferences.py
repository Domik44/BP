d = {}
c = {}
writeList = []
other = ['Stát', 'Region', 'Kraj', 'Okres', 'Obec', 'Casti', 'Celky']
id = 1

with open('coordinates.csv', 'r', encoding='utf8') as cFile:
    skip = True
    for line in cFile:
        if skip is True:
            skip = False
            continue
        line = line.split(';')
        name = line[3]
        kod = int(line[2])
        long = line[-1]
        lat = line[-2]
        c[kod] = (long, lat)

with open('territories.txt', 'r', encoding='utf8') as file:
    with open('territories2.sql', 'a', encoding='utf8') as wFile:
        for line in file:
            line = line.split(', ')
            if len(line) == 3:
                d[(0,1)] = 1
                id += 1
                continue
            if line[0].strip('\n') in other:
                if 'Stát' in line[0].strip('\n'):
                    wFile.write('insert into Territory (type, RUIAN_id, name) values (0, 1, "Česká republika");\n')
                else:
                    wFile.write('insert into Territory (type, RUIAN_id, name, partOf, longitude, latitude) values\n')
                    for i in range(len(writeList)):
                        val = writeList[i]
                        if i == len(writeList)-1:
                            writeStr = '(' + str(val[0]) + ', ' + str(val[1]) + ', "' + str(val[2]) + '", ' + str(val[3]) + ', ' + str(val[4]) + ', ' + str(val[5]) + ');\n'
                        else:
                            writeStr = '(' + str(val[0]) + ', ' + str(val[1]) + ', "' + str(val[2]) + '", ' + str(val[3]) + ', ' + str(val[4]) + ', ' + str(val[5]) + '),\n'
                        wFile.write(writeStr)
                writeList.clear()
                continue
            type = int(line[0])
            RUIAN = int(line[1])
            name = line[2]
            partType = int(line[3])
            partR = int(line[4])
            d[(type, RUIAN)] = id
            partOf = d[(partType, partR)]
            long = 'NULL'
            lat = 'NULL'
            if (type == 4 or type == 5) and RUIAN in c.keys():
                long = float(c[RUIAN][0])
                lat = float(c[RUIAN][1])
            writeList.append((type, RUIAN, name, partOf, long, lat))
            id += 1