"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import json
from datetime import date
import sys
from time import sleep
from scrapper_header import get_text, Matrika, HEADER
from random import randint

prefix = "https://www.mza.cz"

matriky = dict()
matriky['web'] = "https://www.mza.cz/actapublica/matrika/hledani_obec"
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
TYPE = ""


def convert_commas(str):
    priznak = False
    newStr = ""

    for i in str:
        if i == '(' or i == ')':
            priznak = not priznak
        if i == ',' and priznak:
            i = ';'
        newStr += i

    return newStr


def contains_number(string):
    return any(char.isdigit() for char in string)


def scrape_detail(url):
    # url = 'https://www.mza.cz/actapublica/matrika/detail/11856'
    webText = get_text(url, headers=HEADER)
    minY = 9999
    maxY = -1
    m = Matrika()
    m.url = url.strip('\n')
    m.typ = TYPE

    navItem = webText.findAll(class_="nav-item px-3")
    for item in navItem:
        span = item.findAll("span")
        label = span[0].getText()
        content = span[1].getText()
        if label == "Číslo knihy":
            m.invCislo = content
        if label == "Původce":
            m.puvodce = content

    # territories = webText.find(class_="nav-item px-3 mt-1")
    # if territories is not None:
    #     territories = territories.find(class_="btn btn-outline-light").get("data-content").split("<br>")
    #     territories = [item.replace("</strong>", ";").replace("<strong>", "").replace("; ", ";") for item in territories]
    # else:
    #     territories = []

    # d = {}
    # for item in territories:
    #     item = item.split(";")
    #     if len(item) > 1:
    #         var = item[1]
    #         if var != '':
    #             var = var.strip("()").split(",")
    #         else:
    #             var = []
    #         d[item[0].replace(" - ", "-")] = var
    # m.uzemi = d

    content = webText.findAll(class_="nav-item mr-5")
    finalContent = []
    for item in content:
        item = item.getText().strip().replace(" ", "").replace("\r\n", "")
        item = item.replace("Narození:", "N•").replace("Oddaní:", "O•").replace("Zemřelí:", "Z•")
        item = item.replace("Indexnarození:", "I-N•").replace("Indexoddaní:", "I-O•").replace("Indexzemřelí:", "I-Z•")
        value = item.split(("•"))[1]
        if value != "-":
            finalContent.append(item)
            year = value.split("-")

            if contains_number(year[0]):
                fromYear = int(year[0])
            else:
                fromYear = 9999 # TODO -> nevim jestli je to ok nebo tam dat 9999, ale proste bych asi rekl ze -1 je invalid hodnota

            if contains_number(year[1]):
                toYear = int(year[1])
            else:
                toYear = -1

            if fromYear == 9999 and toYear != -1:
                fromYear = toYear
            if toYear == -1 and fromYear != 9999:
                toYear = fromYear

            if minY > fromYear:
                minY = fromYear
            if maxY < toYear:
                maxY = toYear

    m.obsah = finalContent
    m.rozsah = str(minY) + " - " + str(maxY)

    localities = webText.findAll(title="Vyhledat matriky s touto obcí")
    d = {}
    districts = []

    for loc in localities:
        loc = convert_commas(loc.text).split(', ')
        var = []
        if len(loc) > 3:
            a = loc[1].split('(')
            loc.pop(1)
            if len(a) == 2:
                loc[0] = loc[0] + ' (' + a[1]
            var.append(a[0])
            loc.pop(1)

        uzemi = loc[0].split('(')
        if len(uzemi) == 2:
            var.extend(uzemi[1].strip(')').split('; '))

        uzemi = uzemi[0].strip().replace(' - ', '-')
        d[uzemi] = {}

        if len(loc) == 2:
            d[uzemi]['typ'] = 4
        else:
            d[uzemi]['typ'] = 5
            d[uzemi]['obec'] = loc[1].replace('obec: ', '')

        district = loc[-1].replace('okres: ', '')
        if district not in districts:
            districts.append(district)
        d[uzemi]['okres'] = district
        d[uzemi]['varianty'] = var

    # districts = []
    # districtsElements = webText.findAll(title="Vyhledat matriky s touto obcí")
    # for districtElement in districtsElements:
    #     districtText = districtElement.getText()
    #     if 'okres: ' in districtText:
    #         districtText = districtText.split('okres: ')[1]
    #         if districtText not in districts:
    #             districts.append(districtText)

    m.uzemi = d
    m.okresy = districts

    return m


def get_links():
    url = "https://www.mza.cz/actapublica/matrika/hledani_puvodce"

    links = []
    webText = get_text(url, headers=HEADER)
    selectClass = webText.find(class_="custom-select")
    options = [item.getText() for item in selectClass.findAll("option")]
    numberOfOptions = len(options) - 1
    for option in range(1, numberOfOptions+1):
        i = 1
        url = "https://www.mza.cz/actapublica/matrika/hledani_puvodce?typ=puvodce&typ_puvodce_id=" + str(option) + "&page=" + str(i)
        global TYPE
        TYPE = options[option]
        while i != -1:
            webText = get_text(url, headers=HEADER)
            links.extend(webText.findAll(class_="btn btn-outline-secondary"))
            nextPage = webText.findAll(class_="page-link")
            if nextPage:
                nextPage = nextPage[-1]
                textPage = nextPage.find("span").getText()
                if textPage == "»":
                    url = nextPage.get("href")
                    i += 1
                else:
                    i = -1
            else:
                i = -1

    with open("Jsons/Brno/BrnoLinks.txt", "a", encoding='utf8') as outfile:
        for link in links:
            outfile.write(link.get("href") + "\n")


failed = []


def scrape_links():
    # with open('Jsons/Brno/BrnoFailed.txt', 'r', encoding='utf8') as linkFile:
    with open('Jsons/Brno/BrnoLinks.txt', 'r', encoding='utf8') as linkFile:
        links = linkFile.readlines()
        j = 0
        i = 0
        for link in links:
            print(i)
            if j == 1000:
                sleep(30)
                j = 0
            # sleep(randint(1,5))
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

        # with open("Jsons/Brno/Brno_failed.json", "w", encoding='utf8') as outfile:
        with open("Jsons/Brno/Brno.json", "w", encoding='utf8') as outfile:
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        get_links()
    else:
        scrape_links()
        with open("Jsons/Brno/BrnoFailed.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(link + "\n")
        print(failed)


if __name__ == '__main__':
    main()
