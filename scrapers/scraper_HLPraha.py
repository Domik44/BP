"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import json
import re
from datetime import date
import sys
from time import sleep

from selenium.common import NoSuchElementException
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver import Keys

from scrapper_header import get_text, Matrika
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

prefix = "http://katalog.ahmp.cz"

matriky = dict()
matriky['web'] = "http://katalog.ahmp.cz"
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []
driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")
territories = {}
# TODO -> v Jirchářích -> ulice podobne jako Dornych v Brně
nazvyMapa = {'Praha I - Staré Město': 'Staré Město',
             'Praha II - Nové Město': 'Nové Město',
             'Praha III - Malá Strana': 'Malá Strana',
             'Praha IV - Hradčany': 'Hradčany',
             'Praha VI - Vyšehrad': 'Vyšehrad',
             'Německá evangelická Církev v Jirchářích': 'Nové Město', # TODO
             'Českobratrská evangelická u sv. Salvátora': 'Staré Město',
             'Českobratrská evangelická u sv. Klimenta': 'Nové Město',
             'Pravoslavná církev v Praze': 'Praha',
             'Magistrát hlavního města Prahy - centrální úřad': 'Praha',
             'Okresní úřad Praha - venkov': 'Praha',
             'Okresní úřad Praha - venkov jih': 'Praha',
             'Okresní úřad Praha - venkov sever': 'Praha'
             }

# TODO -> upravit uzemi podle Brna!
# Uzemi bych udelal ze
# Kdyz jsou katolicke a vnitrni tak je beres z te hlavicky (+)
# Kdyz katolicke a vnejsi beres je z obsahu
# Kdyz nejsou katolicke tak je beres taky z hlavicky (+ asi)


def get_territories():
    ter = Territory.where('partOf', '=', '3903').get()
    for t in ter:
        if t.name not in territories:
            territories[t.name] = t
    ter = Territory.where('type', '=', '4').where('name', '=', 'Praha').first()
    territories[ter.name] = ter

def get_content_prefix(string):
    if string == 'NOZ':
        return ['N•', 'Z•', 'O•']
    elif string == 'N' or string == 'O' or string == 'Z':
        return [string+'•']
    elif string == 'N, i' or string == 'O, i' or string == 'Z, i':
        prefix = string.split(', ')[0]
        return [prefix+'•', 'I-'+prefix+'•']
    elif string == 'N,i' or string == 'O,i' or string == 'Z,i':
        prefix = string.split(',')[0]
        return [prefix+'•', 'I-'+prefix+'•']
    elif string == 'i':
        return ['I-N•', 'I-Z•', 'I-O•']
    elif string == 'iN':
        return ['I-N•']
    elif string == 'iZ':
        return ['I-Z•']
    elif string == 'iO':
        return ['I-O•']
    elif string == 'iOZ' or string == 'iZO':
        return ['I-O•', 'I-Z•']
    elif string == 'iON' or string == 'iNO':
        return ['I-O•', 'I-N•']
    elif string == 'iNZ' or string == 'iZN':
        return ['I-N•', 'I-Z•']
    elif string == 'OZ' or 'ZO':
        return ['Z•', 'O•']
    elif string == 'ON' or 'NO':
        return ['N•', 'O•']
    elif string == 'NZ' or 'ZN':
        return ['Z•', 'N•']
    else:
        return None


def format_place(string):
    string = re.sub(r"(ČS )|(ČCE )", "", string)
    # string = string.strip('ČS ').strip('ČCE')
    string = string.split(' (')[0]
    return string


def process_place(driver):
    ul = driver.find_element(By.CLASS_NAME, 'category')
    lis = ul.find_elements(By.TAG_NAME, 'li')
    lenLis = len(lis)
    typ = format_place(lis[3].text)
    if lenLis == 7:
        placeStr = format_place(lis[-2].text)
        if placeStr in nazvyMapa:
            return nazvyMapa[placeStr], typ
        return placeStr, typ
    elif lenLis == 5 or lenLis == 6:
        placeStr = format_place(lis[-1].text)
        if placeStr == 'Starokatolická církev (1)':
            return 'Praha', typ
        elif placeStr in nazvyMapa:
            return nazvyMapa[placeStr], typ
        else:
            return placeStr, typ
    return -1, -1


def formate_year(year):
    newYear = []
    for y in year:
        y = y.split('.')[-1]
        newYear.append(y)

    return newYear


def scrape_detail(driver):
    m = Matrika()
    m.url = driver.find_element(By.ID, "permalinkPopupTextarea").get_attribute('value')

    content = driver.find_element(By.CLASS_NAME, "contentArticle.row")
    signatura = content.find_element(By.TAG_NAME, "h1").text.split(" • ")
    m.signatura = signatura[0]
    m.rozsah = signatura[1].replace("-", " - ")

    placeStr, typ = process_place(driver)
    place = [placeStr]
    d = {}

    rows = driver.find_elements(By.CLASS_NAME, "itemRow")
    for row in rows:
        label = row.find_element(By.CLASS_NAME, "tabularLabel").text
        val = row.find_element(By.CLASS_NAME, "tabularValue").text
        if "Obsahy" in label:
            obsah = []
            obsahy = val.split('\n')
            processedType = {}
            for o in obsahy:
                oSplitted = o.split('; ')
                prefix = get_content_prefix(oSplitted[0])
                if prefix is None:
                    continue
                for pre in prefix:
                    if oSplitted[1] == ' ' or oSplitted[1] == '':
                        if m.rozsah != '':
                            oSplitted[1] = m.rozsah.replace(' - ', '-')
                        else:
                            raise Exception("")
                    finalStr = pre+oSplitted[1]
                    years = oSplitted[1].split('-')
                    years = formate_year(years)
                    if len(years) > 1:
                        min = int(years[0])
                        max = int(years[1])
                    else:
                        min = int(years[0])
                        max = min
                    if pre not in processedType:
                        processedType[pre] = {'min': min, 'max': max, 'str': finalStr}
                    else:
                        processedType[pre]['min'] = min if min < processedType[pre]['min'] else processedType[pre]['min']
                        processedType[pre]['max'] = max if max > processedType[pre]['max'] else processedType[pre]['max']
                        finalStr = pre+str(processedType[pre]['min'])+'-'+str(processedType[pre]['max'])
                        processedType[pre]['str'] = finalStr
                for j in oSplitted[2::]:
                    if j != ' ' and j != '' and j != ';':
                        j = j.strip(';')
                        if j not in place:
                            place.append(j)
                    else:
                        continue
            for t in processedType.values():
                obsah.append(t['str'])
            m.obsah = obsah

            for p in place:
                p = re.sub(r"(farnost )", "", p)
                if p in territories:
                    pl = territories[p]
                    d[pl.name] = {'typ': pl.type, 'ruian': pl.RUIAN_id, 'okres': 'území Hlavního města Prahy'}
                    if pl.type == 5:
                        d[pl.name]['obec'] = 'Praha'
                    d[pl.name]['varianty'] = []
                else:
                    if p != 'index':
                        d[p] = {'typ': -1, 'ruian': -1}

            m.uzemi = d
            m.okresy = ['území Hlavního města Prahy']
            m.typ = typ
        if "Fara/úřad" in label:
            m.puvodce = val
    return m


def scrape_links(driver):
    maxPage = 3055
    i = 1
    while i != maxPage:
        print(i+1)
        try:
            m = scrape_detail(driver)
            matriky['matriky'].append(m.__dict__)
        except:
            failed.append(i+1)  # i+1 aby to sedelo se strankovanim
        try:
            driver.find_element(By.CLASS_NAME, "icon-forward3.icon.link").click()
        except:
            None
        i += 1
        # if i == 4:
        #     break

    with open("Jsons/HLPraha/HLPraha2.json", "w", encoding='utf8') as outfile:
        json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    get_territories()
    driver = Chrome(service=driver_service)
    driver.get(prefix)
    sleep(1)
    homePage = driver.find_element(By.ID, "homepage")
    homePage.find_element(By.CLASS_NAME, "permaLabelLink").click()
    sleep(1)
    driver.find_element(By.CLASS_NAME, "ui-state-active.ui-button.ui-widget.ui-state-default.ui-button-text-only.ui-corner-left.ui-corner-right").click()
    driver.find_element(By.NAME, "searchFulltext").click()
    sleep(1)
    activeDiv = driver.find_element(By.CLASS_NAME, "navigatorLine.listArticle.navigatorLineActiv")
    activeDiv.find_elements(By.TAG_NAME, "a")[2].click()
    driver.find_element(By.CLASS_NAME, "icon-forward3.icon.link").click()
    scrape_links(driver)
    with open("Jsons/HLPraha/HLPrahaFailed2.txt", "a", encoding='utf8') as outfile:
        for link in failed:
            outfile.write(str(link) + "\n")
    print(failed)
    driver.quit()


if __name__ == '__main__':
    main()
