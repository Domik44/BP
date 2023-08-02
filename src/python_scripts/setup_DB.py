import sys

fileREAD = open("parser_header.py", "r", encoding='utf-8')
finalSTR = ""
user = "'" + sys.argv[1] + "'"
if len(sys.argv) > 2:
    passw = "'" + sys.argv[2] + "'"
else:
    passw = "'" + "'"

for line in fileREAD:
    if '        \'user\':' in line:
        line = "        'user': "+user+",\n"
    elif '        \'password\':' in line:
        line = "        'password': " + passw + ",\n"
    finalSTR += line
fileREAD.close()

fileWRITE = open("parser_header.py", "w", encoding='utf-8')
fileWRITE.write(finalSTR)
fileWRITE.close()
# f = open(parser_header.py, "w", encoding='utf8')

