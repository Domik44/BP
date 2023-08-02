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

from selenium.common import NoSuchElementException
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By

from scrapper_header import Matrika

prefix = "https://digi.ceskearchivy.cz"

matriky = dict()
matriky['web'] = "https://digi.ceskearchivy.cz/Uvod"
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []  # 10795 záznamů celkem
driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")
processedPlaces = {}

typesList = [
    "https://digi.ceskearchivy.cz/Matriky-Civilni-matriky",
    "https://digi.ceskearchivy.cz/Matriky-Ceskobratrska-evangelicka-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Ceskoslovenska-husitska-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Evangelicka-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Nemecka-evangelicka-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Nemecke-civilni-matriky",
    "https://digi.ceskearchivy.cz/Matriky-Nemecke-vojenske-matriky",
    "https://digi.ceskearchivy.cz/Matriky-Pravoslavna-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Rimskokatolicka-cirkev",
    "https://digi.ceskearchivy.cz/Matriky-Stavovske-matriky"
]
regionList = []


def get_region_links(driver):
    menu = driver.find_element(By.ID, "abc_menu4")
    links = menu.find_elements(By.TAG_NAME, "a")
    for link in links:
        regionList.append(link.get_attribute("href"))


def get_regions():
    driver = Chrome(service=driver_service)
    for type in typesList:
        driver.get(type)
        driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
        sleep(2)
        if ("Rimskokatolicka-cirkev" in type) or ("Stavovske-matriky" in type):
            letters = len(driver.find_elements(By.CLASS_NAME, "abc_pis"))
            for i in range(letters):
                driver.get(type)
                driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
                driver.find_elements(By.CLASS_NAME, "abc_pis")[i].click()
                driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
                sleep(2)
                get_region_links(driver)
        else:
            get_region_links(driver)

    print(regionList)
    driver.quit()

    with open("Jsons/Trebon/TrebonRegions.txt", "a", encoding='utf8') as outfile:
        for link in regionList:
            outfile.write(link + "\n")


def convert_commas(str, delims):
    priznak = False
    newStr = ""
    delim1 = delims[0]
    if len(delims) == 1:
        delim2 = delim1
    else:
        delim2 = delims[1]

    for i in str:
        if i == delim1 or i == delim2:
            priznak = not priznak
        if i == ',' and priznak:
            i = ';'
        newStr += i

    newStr = newStr.replace('; ', ';')
    newStr = newStr.replace(' : ', ':')
    return newStr


def get_back_to_scrape(driver, url):
    if url is not None:
        sleep(1)
        driver.get(url)
    sleep(1)
    driver.find_element(By.ID, "c4").click()
    sleep(1)
    driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))


def scrape_modal(driver, currentUrl):
    # print(currentUrl)
    try:
        iframeUrl = driver.find_elements(By.TAG_NAME, 'iframe')[1].get_attribute('src')
    except:
        sleep(3)
        iframeUrl = driver.find_elements(By.TAG_NAME, 'iframe')[1].get_attribute('src')
    driver.get(iframeUrl)
    driver.switch_to.frame(driver.find_element(By.TAG_NAME, 'iframe'))
    try:
        ruian = driver.find_element(By.CLASS_NAME, 'sresp7p').text
    except:
        ruian = -1

    d = {}
    span = driver.find_element(By.CLASS_NAME, 'sresaut').text
    span = convert_commas(span, ['(', ')'])
    span = span.split(' (')
    keyName = span[0]
    res = span[1].strip(')')
    res = res.split(':')[0]
    res = res.split(';')
    d['ruian'] = int(ruian)
    if ruian != -1:
        if len(res) == 2:
            d['okres'] = res[0]
            d['typ'] = 4
        elif len(res) == 3:
            d['obec'] = res[0]
            d['okres'] = res[1]
            d['typ'] = 5
    else:
        if len(res) == 3:
            d['obec'] = res[0]
            d['okres'] = res[1]
            d['typ'] = 6
        elif len(res) == 4:
            d['cObec'] = res[0]
            d['obec'] = res[1]
            d['okres'] = res[2]
            d['typ'] = 7
    # print(keyName, d)
    get_back_to_scrape(driver, currentUrl)

    return keyName, d


def process_place(url):
    driver2 = Chrome(service=driver_service)
    driver2.maximize_window()
    driver2.get(url)
    sleep(1)
    driver2.switch_to.frame(driver2.find_element(By.TAG_NAME, 'iframe'))
    sleep(1)
    try:
        ruian = driver2.find_element(By.CLASS_NAME, 'sresp7p').text
    except:
        ruian = -1

    d = {}
    span = driver2.find_element(By.CLASS_NAME, 'sresaut').text
    span = convert_commas(span, ['(', ')'])
    span = span.split(' (')
    res = span[1].strip(')')
    res = res.split(':')[0]
    res = res.split(';')
    d['ruian'] = int(ruian)
    if ruian != -1:
        if len(res) == 2:
            d['okres'] = res[0]
            d['typ'] = 4
        elif len(res) == 3:
            d['obec'] = res[0]
            d['okres'] = res[1]
            d['typ'] = 5
    else:
        if len(res) == 3:
            d['obec'] = res[0]
            d['okres'] = res[1]
            d['typ'] = 6
        elif len(res) == 4:
            d['cObec'] = res[0]
            d['obec'] = res[1]
            d['okres'] = res[2]
            d['typ'] = 7
    processedPlaces[url] = d
    driver2.close()


def scrape_digi_detail(driver, bUrl, sig, typ):
    # currentUrl = driver.current_url
    m = Matrika()
    get_back_to_scrape(driver, None)
    puvodce = ""

    table = driver.find_element(By.CLASS_NAME, "tab_popis")
    # lenRows = len(table.find_elements(By.TAG_NAME, "tr"))
    rows = table.find_elements(By.TAG_NAME, "tr")
    # for j in range(lenRows):
    for row in rows:
        # table = driver.find_element(By.CLASS_NAME, "tab_popis")
        # rows = table.find_elements(By.TAG_NAME, "tr")
        # row = rows[j]
        cols = row.find_elements(By.TAG_NAME, "td")
        label = cols[0].text
        val = cols[1].text
        if label == "Sídlo správního úřadu" or label == "Sídlo farního úřadu":
            m.puvodce = val
            m.signatura = val
        elif label == "Inventární číslo":
            m.invCislo = val
        elif label == "Původce":
            puvodce = val
        elif label == "Datace":  # TODO -> indexy!!
            m.obsah = [i.replace(" ", "•") for i in val.split("\n")]
        elif label == "Okres":
            m.okresy = [i.replace(" - ", "-") for i in val.split(", ")]
        elif label == "Kniha vedena pro":
            d = {}
            okresy = []
            hrefs = cols[1].find_elements(By.TAG_NAME, 'a')
            placesDict = {}
            for href in hrefs:
                url = href.get_attribute('href')
                placesDict[href.text] = url
                if url not in processedPlaces:
                    process_place(href.get_attribute('href'))

            for name, url in placesDict.items():
                name = name.replace(" - ", "-")
                dictionary = processedPlaces[url]
                d[name] = dictionary
                if dictionary['okres'] not in okresy:
                    okresy.append(dictionary['okres'])
            # processedPlaces = {}
            # lenHrefs = len(cols[1].find_elements(By.TAG_NAME, 'a'))
            # text = '//*[@id="invframe"]/table/tbody/tr['+str(j+1)+']/td[2]'
            # print(cols[1].text)
            # d = {}
            # for i in range(lenHrefs):
            #     col = driver.find_element(By.XPATH, text)
            #     try:
            #         a = col.find_elements(By.TAG_NAME, 'a')[i]
            #     except:
            #         sleep(3)
            #         a = col.find_elements(By.TAG_NAME, 'a')[i]
            #     if a.text not in processedPlaces:
            #         a.click()
            #         sleep(1)
            #         driver.switch_to.default_content()
            #         sleep(4)
            #         key, dictionary = scrape_modal(driver, currentUrl)
            #         processedPlaces[key] = dictionary
            #     else:
            #         key = a.text
            #         dictionary = processedPlaces[key]
            #     d[key.replace(" - ", "-")] = dictionary
            #
            val = convert_commas(val, '/')
            uzemi = val.split(", ")
            for z in uzemi:
                var = []
                z = z.split(' /')
                if len(z) > 1:
                    var = z[1].strip("/").split(";")
                d[z[0].replace(" - ", "-")]['varianty'] = var
            m.uzemi = d
        elif label == "Jazyk":
            m.jazyk = val
        elif label == "Odkaz na snímek":
            m.url = val

    if puvodce != "":
        m.puvodce = puvodce

    if m.puvodce == "":
        m.puvodce = sig

    if m.signatura == "":
        m.signatura = sig

    min = 9999
    max = -1
    for i in m.obsah:
        i = i.split("•")[1].split("-")
        if len(i) == 1:
            if int(i[0]) < min:
                min = int(i[0])
            if int(i[0]) > max:
                max = int(i[0])
            continue
        if int(i[0]) < min:
            min = int(i[0])
        if int(i[1]) > max:
            max = int(i[1])
    m.rozsah = str(min) + " - " + str(max)
    m.typ = typ

    driver.get(bUrl)
    driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
    sleep(1)
    return m


def scrape_nondigi_detail(row, url, sig, okres):
    m = Matrika()
    m.url = url
    m.puvodce = sig
    m.okresy = [i.replace(" - ", "-") for i in okres.split(", ")] # TODO -> osetrit pripady pro vice napsanych okresu
    cols = row.find_elements(By.CLASS_NAME, "m_k_t0")
    obsah = cols[2].text.split("\n")
    obsah = [i.replace(" ", "•") for i in obsah]
    min = 9999
    max = -1
    for i in obsah:
        i = i.split("•")[1].split("-")
        if len(i) == 1:
            if int(i[0]) < min:
                min = int(i[0])
            if int(i[0]) > max:
                max = int(i[0])
            continue
        if int(i[0]) < min:
            min = int(i[0])
        if int(i[1]) > max:
            max = int(i[1])
    m.rozsah = str(min) + " - " + str(max)
    m.obsah = obsah
    # m.invCislo = cols[0].text
    uzemi = cols[5].text.split("|")[0].split(", ")
    d = {}
    for z in uzemi:
        d[z.replace(" - ", "-")] = []
    m.uzemi = d

    return m


def process_infoTable(table):
    rows = table.find_elements(By.TAG_NAME, "tr")
    signatura = ""
    okres = ""
    html = ""
    puvodce = ""
    typ = ""
    for row in rows:
        cols = row.find_elements(By.TAG_NAME, "td")
        if len(cols) != 2:
            continue
        head = cols[0].text
        val = cols[1].text
        if "Současný matriční úřad" in head:
            signatura = val
        elif "Okres" in head:
            okres = val
        elif "HTML odkaz" in head:
            html = val
        elif "Původce" in head:
            puvodce = val
        elif "Správní úřad" in head:
            typ = val

    if puvodce != "":
        signatura = puvodce

    return signatura, okres, html, typ


def scrape_links(url, driver):
    # url = "https://digi.ceskearchivy.cz/Matriky-Rimskokatolicka-cirkev-B-Bavorov"  # TODO -> smazat
    # url = "https://digi.ceskearchivy.cz/9030"
    driver.get(url)
    driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
    sleep(1)
    # thisPageMatriky = []

    infoTable = driver.find_element(By.CLASS_NAME, "tab_popis")
    sig, okres, html, typ = process_infoTable(infoTable)

    try:
        digi_table = driver.find_element(By.CLASS_NAME, "box_nadpis")
    except NoSuchElementException:
        None
    else:
        rows = digi_table.find_elements(By.CLASS_NAME, "p_m_p")
        for i in range(len(rows)):
            sleep(2)
            digi_table = driver.find_element(By.CLASS_NAME, "box_nadpis")
            rows = digi_table.find_elements(By.CLASS_NAME, "p_m_p")
            column = rows[i].find_element(By.TAG_NAME, "td")
            column.click()
            # m = scrape_digi_detail(driver, html, sig, typ)
            # thisPageMatriky.append(m.__dict__)
            try:
                m = scrape_digi_detail(driver, html, sig, typ)
                # thisPageMatriky.append(m.__dict__)
                matriky['matriky'].append(m.__dict__)
            except:
                failed.append(driver.current_url)
                driver.get(url)
                driver.switch_to.frame(driver.find_element(By.TAG_NAME, "iframe"))
                # digi_table = driver.find_element(By.CLASS_NAME, "box_nadpis")
                # rows = digi_table.find_elements(By.CLASS_NAME, "p_m_p")
                # failed.append((html, rows[i].find_elements(By.CLASS_NAME, "m_k_t")[0].text, "Digi"))
                # return

    # try:
    #     nondigi_table = driver.find_elements(By.CLASS_NAME, "matriky_table")
    # except NoSuchElementException:
    #     pass
    # else:
    #     for table in nondigi_table:
    #         rows = table.find_elements(By.CLASS_NAME, "p_m_p0")
    #         for row in rows:
    #             try:
    #                 m = scrape_nondigi_detail(row, html, sig, okres)
    #                 thisPageMatriky.append(m.__dict__)
    #                 # matriky['matriky'].append(m.__dict__)
    #             except:
    #                 failed.append((html, row.find_elements(By.CLASS_NAME, "m_k_t0")[0].text, "NonDigi"))
    #                 return

    # matriky['matriky'].extend(thisPageMatriky)



def main():
    if len(sys.argv) > 1:
        if sys.argv[1] == 'regions':
            get_regions()
    else:
        driver = Chrome(service=driver_service)
        driver.maximize_window()
        with open('Jsons/Trebon/TrebonRegions3.txt', 'r', encoding='utf8') as regionsFile:
            links = regionsFile.readlines()
            for link in links:
                print(link)
                scrape_links(link, driver)
                # break # TODO -> delete
        driver.quit()
        print("Celkem zpracovano: " + str(len(matriky['matriky'])))
        with open("Jsons/Trebon/Trebon2_2.json", "w", encoding='utf8') as outfile:
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)
        # print(failed)
        with open("Jsons/Trebon/TrebonFailed2.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(str(link) + "\n")


if __name__ == '__main__':
    main()