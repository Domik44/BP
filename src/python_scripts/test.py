# import re
#
# import db as DB
# from datetime import datetime
# import sys


# import ged4py.model
# from gedcom.parser import Parser
# from ged4py.parser import GedcomReader

# from gedcom.parser import Parser
# from gedcom.element.individual import IndividualElement

# from python_gedcom_2.parser import Parser
# from python_gedcom_2.element.individual import IndividualElement

# start_time = datetime.now() # TODO
# db, cursor = DB.connect_to_db()
#
# sql_str = "select exists (select * from territory where name = \"Kateřina\")"  # TODO -> dodelat pro usera
# cursor.execute(sql_str)
# ret = cursor.fetchone()
#
# print(ret)

# path = "../testing_files/MyGEDCOM.ged"
# path1 = "../testing_files/eva1.ged"

# parser = GedcomReader(path)
#
# for i, person in enumerate(parser.records0("INDI")):
#     if isinstance(person, ged4py.model.Individual):
#         print(person.name, person.sub_tag_value("BIRT/DATE"))

# path.replace(".ged", "_decoded.ged")
# try:
#     f = open(path, "r")
#     content = f.read()
#     f.close()
# except:
#     path = path
# else:
#     path = path.replace(".ged", "_decoded.ged")
#     f = open(path, "w", encoding="utf-8")
#     f.write(content)
#     f.close()

# gedcom_parser = Parser()
#
# gedcom_parser.parse_file(path, False)
#
# root_child_elements = gedcom_parser.get_root_child_elements()
#
# for element in root_child_elements:
#     if isinstance(element, IndividualElement):
#         None

# d = {}
# d.update({"a" : [1]})
# d.update({"a" : d["a"] + [2]})
# print(d)

# string = "Bukovinka"
#
# compare = "Bukovinka 89"
#
# if string in compare:
#     print("Ano")
#
#
# print(datetime.now()-start_time) # TODO
#
# if re.match("^(([0-9])|([0-2][0-9])|([3][0-1]))\ (Jan|JAN|Feb|FEB|MAR|Mar|Apr|APR|May|MAY|Jun|JUN|Jul|JUL|Aug|AUG|Sep|SEP|Oct|OCT|Nov|NOV|Dec|DEC)\ \d{4}$", "18 Sep 1972"):
#     print("Matching")
#
# l = [1,2,3,4]
# l = [i for i in l if i%2 != 0]
# print(l)

# string = "(['War - now'])"
# print(re.sub("(\()|(\)|(\[)|(\])|(\')|(\"))", "", string.replace(' - ', '-')))

# print("Hello")

import json

def haha():
    # some JSON:
    x = '{ "name":"John", "age":30, "city":"New York"}'

    # parse x:
    y = json.loads(x)

    print(y)
    # f = open('Ahoj.txt', "w")

haha()
