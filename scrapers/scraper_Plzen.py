"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import json
import sys
from datetime import date
from time import sleep

from selenium.common import NoSuchElementException
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver import Keys

from scrapper_header import get_text, Matrika, HEADER

prefix = "https://www.portafontium.eu"

matriky = dict()
matriky['web'] = ""
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []
driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")


def return_type(string):
    val = None
    if string == "*":
        val = "N•"
    elif string == "+":
        val = "Z•"
    elif string == "oo":
        val = "O•"
    elif string == "*i" or string == "i*":
        val = "I-N•"
    elif string == "+i" or string == "i+":
        val = "I-Z•"
    elif string == "ooi" or string == "ioo":
        val = "I-O•"
    return val


# def scrape_detail(url):
#     driver = Chrome(service=driver_service)
#     driver.get(url)
#     driver.refresh()
#     sleep(1)
#
#     m = Matrika()
#     puvodceDiv = driver.find_element(By.CLASS_NAME, "field.field-name-field-originator.field-type-text.field-label-inline.clearfix").text
#     m.puvodce = puvodceDiv.split('\n')[1]
#     # Datace
#     # Uzemi
#     placesTable = driver.find_element(By.CLASS_NAME, "field.field-name-field-doc-place.field-type-field-collection.field-label-inline.clearfix")
#
#     driver.quit()

def scrape_firstTab(m, tab):
    fieldSets = tab.findAll("fieldset")
    secondSet = fieldSets[1]
    secondLabels = secondSet.find(class_="field-label")
    secondItems = secondSet.find(class_="field-items")
    if "Číslo fondu:" in secondLabels.text:
        m.invCislo = secondItems.text

    thirdSet = fieldSets[2]
    thirdLabels = thirdSet.find(class_="field-label")
    thirdItems = thirdSet.find(class_="field-items")
    if "Signatura:" in thirdLabels.text:
        m.signatura = thirdItems.text


def prepare_string(string):
    # string = string.replace("i ", "i")
    return string.replace("i ", "i")
    # newString = ""
    #
    # for i in range()
    #
    # return newString


def scrape_secondTab(m, tab):
    fieldSets = tab.findAll("fieldset")
    obsah = []
    string = ""
    if len(fieldSets) > 1:
        set = fieldSets[1]
        setLabels = set.findAll(class_="field-label")
        setItems = set.findAll(class_="field-items")
        b = True

        for label, items in zip(setLabels, setItems):
            if "od" in label.text:
                if "Index" in label.text:
                    if b is False:
                        obsah.append(string)
                    label = label.text.split(' ')[1][0].upper()
                    string = 'I-' + label + "•" + items.text
                    b = False
                else:
                    string = label.text[0] + "•" + items.text
            elif "do" in label.text:
                string += '-' + items.text
                obsah.append(string)
                b = True
            else:
                raise Exception("Chyba v datacich advanced!", setLabels, setItems)
    else:
        set = fieldSets[0]
        setItems = set.find(class_="field-items").text.strip()
        setItems = prepare_string(setItems).replace(", ", ";").split(" ")
        if len(setItems) > 1:
            year = setItems[0]
            events = setItems[1].split(";")
            if len(events) == 1 and len(setItems) > 2:
                setItems.pop(0)
                events = [i for i in setItems]
            for event in events:
                eventType = return_type(event.strip())
                string = eventType + year
                obsah.append(string)
        else:
            raise Exception("Chyba v datacich basic!", setItems)

    m.obsah = obsah
    m.rozsah = fieldSets[0].find(class_="field-items").text.split(" ")[0].replace("-", " - ")


def convert_commas(str):
    priznak = False
    newStr = ""

    for i in str:
        if i == '(' or i == '[':
            i = '('
            priznak = not priznak
            # continue
        elif i == ')' or i == ']':
            i = ')'
            priznak = not priznak
            # continue
        if i == ',' and priznak:
            i = ';'
        newStr += i

    return newStr


def scrape_thirdTab(m, tab):
    fieldSets = tab.findAll("fieldset")
    firstSet = fieldSets[0]
    d = {}
    m.okresy = []

    firstSetLabels = firstSet.findAll(class_="field-label")
    firstSetItems = firstSet.findAll(class_="field-items")

    for label, items in zip(firstSetLabels, firstSetItems):
        label = label.text
        if "Původce:" in label:
            m.puvodce = items.text
        elif "Místo:" in label:
            places = convert_commas(items.text)
            places = places.split(", ")
            for place in places:
                place = place.split(" (")
                placeName = place[0].replace(' - ', '-')
                d[placeName] = []
                place.pop(0)
                for var in place:
                    var = var.strip(')')
                    var = var.split('; ')
                    for i in var:
                        d[placeName].append(i)
                    break
                # if len(place) > 1:
                #     ok = place[1].strip(')').split('; ')
                #     for o in ok:
                #         m.okresy.append(o)
    m.uzemi = d


def scrape_detail(url):  # TODO
    # url = "https://www.portafontium.eu/register/soap-pn/dobrany-13"
    # url = "https://www.portafontium.eu/register/soap-pn/as-mikulov-39"
    m = Matrika()
    m.url = url
    webText = get_text(m.url, HEADER)

    hTabs = webText.findAll(class_="field-group-htabs-wrapper")
    firstTab = hTabs[0]
    scrape_firstTab(m, firstTab)

    secondTab = hTabs[1]
    scrape_secondTab(m, secondTab)

    thirdTab = webText.find(class_="field-group-tabs-wrapper")
    scrape_thirdTab(m, thirdTab)

    return m


def get_links():
    # TODO -> Mel by si ten max page najit sam ne byt na tvrdo!
    pages = 346

    links = []
    for i in range(0, pages):
        url = "https://www.portafontium.eu/searching/register?page=" + str(i)
        webText = get_text(url)
        td = webText.findAll(class_="views-field views-field-title-field")
        hrefs = [prefix + i.find('a').get('href') for i in td if i.find('a') is not None]
        print(i, len(hrefs))
        links.extend(hrefs)

    with open("Jsons/Plzen/PlzenLinks.txt", "a", encoding='utf8') as outfile:
        for link in links:
            outfile.write(link + "\n")
    print("Celkove " + str(len(links)))


def scrape_links():
    with open('Jsons/Plzen/PlzenLinks.txt', 'r', encoding='utf8') as linkFile:
    # with open('Jsons/Plzen/PlzenFailed.txt', 'r', encoding='utf8') as linkFile:
        links = linkFile.readlines()
        j = 0
        i = 0
        for link in links:
            link = link.strip("\n")
            print(i)
            if j == 1000:
                sleep(30)
                j = 0
            # m = scrape_detail(link)
            # matriky['matriky'].append(m.__dict__)
            try:
                m = scrape_detail(link)
                matriky['matriky'].append(m.__dict__)
            except:
                failed.append(link)
            j += 1
            i += 1
            # break
        with open("Jsons/Plzen/Plzen3.json", "w", encoding='utf8') as outfile:
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        get_links()
    else:
        scrape_links()
        with open("Jsons/Plzen/PlzenFailed3.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(link + "\n")
        print(failed)


if __name__ == '__main__':
    main()