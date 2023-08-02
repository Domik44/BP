"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import json
from datetime import date
from random import randint

from bs4 import BeautifulSoup
import sys
from time import sleep

from selenium.common import NoSuchElementException
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver import Keys

from scrapper_header import get_text, Matrika, HEADER

prefix = "http://vademecum.soalitomerice.cz"

matriky = dict()
matriky['web'] = "http://vademecum.soalitomerice.cz/vademecum/searchlink?myQuery=f79de4fe747c3b2c098dbb845db7b01e&modeView=LIST"
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []
driver_service = Service(executable_path="chromedriver.exe")
failedCnt = 0

# TODO -> rozprasovat lokality
# TODO -> upravit uzemi podle Brna!


def check_date(string):
    string = string.split('-')
    year = []
    for s in string:
        s = s.strip().split('.')
        for i in s:
            if len(i) == 4:
                year.append(i)

    if len(year) == 2:
        ret = year[0] + "-" + year[1]
    else:
        ret = year[0]

    return ret


def scrape_detail(url):
    # sleep(randint(1,2))
    # url = "http://vademecum.soalitomerice.cz/vademecum/permalink?xid=09ddd7cea03b9b8d:30bdd2c7:1201ea2ef5b:-7c01"
    # webText = get_text(url, HEADER)
    webText = get_text(url)
    labelFloat = webText.findAll(class_="labelFloat")
    contentFloat = webText.findAll(class_="contentFloat")
    m = Matrika()
    # sleep(randint(1))
    sleep(1)
    m.url = webText.find(class_="textareaPermalink").get_text()
    # m.url = url.strip("\n")
    d = {}
    d1 = {}
    districts = []

    for i,j in zip(labelFloat, contentFloat):
        label = i.get_text()
        content = j.get_text()
        if label == "Původce:":
            m.puvodce = content
        elif label == "Signatura:":
            m.signatura = content
        elif label == "Inventární číslo:":
            m.invCislo = content
        elif label == "Typ matriky:":
            m.typ = content
        elif label == "Jazyk:":
            m.jazyk = content.split(", ")
        elif label == "Časový rozsah svazku:":
            m.rozsah = content.strip(" \r\n")
            m.rozsah = check_date(m.rozsah)
            m.rozsah = m.rozsah.replace("-"," - ")
        elif label == "Obsah svazku:":
            m.obsah = content.strip(" \r\n").replace(" ", "").split("\r\n\r\n\r\n")
            obsah = []
            for ob in m.obsah:
                ob = ob.split('•')
                ob[1] = check_date(ob[1])
                ob = ob[0] + "•" + ob[1]
                obsah.append(ob)
            m.obsah = obsah
        elif label == "Územní rozsah:":
            uzemi = content.strip(" \r\n").split(", ")
            for uz in uzemi:
                uz = uz.replace(' - ', '-')
                d1[uz] = {}
                d1[uz]['typ'] = -1
                d1[uz]['okres'] = ""
                d1[uz]['varianty'] = []
        elif label == "Lokality:":
            table = webText.find(id="matrikaLokality")
            table = table.find("tbody")
            rows = table.findAll("tr")
            if len(rows) == 0:
                continue
            propojLok = False
            varLok = False
            rows.pop()
            nameUzemi = ""
            typeUzemi = 0
            okres = ""
            obec = ""

            for row in rows:
                try:
                    attr = row.attrs['class']
                    if attr[0] == 'propojLok':
                        propojLok = True
                        varLok = False
                        continue
                except:
                    if propojLok is True:
                        hierarchy = row.text.strip().replace("; ", ";").replace(' - ', '-').split(";")
                        if hierarchy[2] != "":
                            okres = hierarchy[2]
                            if okres not in districts:
                                districts.append(okres)
                        if hierarchy[3] != "":
                            obec = hierarchy[3]
                            typeUzemi = 4
                        if hierarchy[4] != "":
                            nameUzemi = hierarchy[4]
                            typeUzemi = 5
                        else:
                            nameUzemi = obec

                        if nameUzemi != '':
                            d[nameUzemi] = {}
                            d[nameUzemi]['typ'] = typeUzemi
                            d[nameUzemi]['okres'] = okres
                            if typeUzemi == 5:
                                d[nameUzemi]['obec'] = obec
                            d[nameUzemi]['varianty'] = []
                        else:
                            propojLok = False
                            varLok = False
                            continue
                        propojLok = False
                        varLok = True
                        continue
                    if varLok is True:
                        varHierarchy = row.text.strip().replace("; ", ";").split(";")
                        if typeUzemi == 4:  # Obec
                            d[nameUzemi]['varianty'].append(varHierarchy[0])
                        else:
                            d[nameUzemi]['varianty'].append(varHierarchy[1])

    m.okresy = districts
    if len(d) != 0:
        m.uzemi = d
    else:
        m.uzemi = d1

    global failedCnt
    failedCnt = 0
    return m


def get_links(i):
    url = "http://vademecum.soalitomerice.cz/vademecum/searchlink?myQuery=58dfaa151fd0522ef99aef1d9ac272f5&modeView=LIST"
    # url = "http://vademecum.soalitomerice.cz/vademecum/searchlink?myQuery=9849978fc2ff9b15c4ec098fc205d5f6&modeView=LIST"

    opts = Options()
    user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A"
    opts.add_argument("user-agent="+user_agent)

    driver = Chrome(service=driver_service, options=opts)
    driver.get(url)

    try:
        rowTxt = driver.find_element(By.NAME, "rowTxt")
    except:
        return i

    sleep(5)
    rowTxt.clear()
    rowTxt.send_keys(i+1)
    rowTxt.send_keys(Keys.ENTER)
    sleep(5)
    firstLink = driver.find_element(By.CLASS_NAME, "contentRecordList.contentRecordLineActiv.data-block.no15")
    firstLink.click()

    links = []
    failedLinks = []

    while i != -1:
        sleep(2)
        try:
            link = driver.find_element(By.ID, "permalinkPopupTextarea").get_attribute('value')
        except NoSuchElementException:  # Error happened
            failedLinks.append(i)
            break

        print(i)
        links.append(link)

        try:
            nextButton = driver.find_element(By.CLASS_NAME, "icon-forward3.icon.link")
            driver.execute_script("arguments[0].click();", nextButton)
            i += 1
        except NoSuchElementException:  # End
            i = -1
            break

    driver.quit()

    with open("Jsons/Litomerice/LitomericeLinks.txt", "a", encoding='utf8') as outfile:
        for link in links:
            outfile.write(link + "\n")

    print("Celkove " + str(len(links)))

    # je bud posledni matrika na ktere skoncil a nezpracoval ji
    # nebo -1 pokud uz ma vsechny
    return i


def scrape_links():
    b = False
    global failedCnt
    # with open('Jsons/Litomerice/LitomericeLinks.txt', 'r', encoding='utf8') as linkFile:  # TODO -> vratit
    with open('Jsons/Litomerice/LitomericeFailed3.txt', 'r', encoding='utf8') as linkFile:
        links = linkFile.readlines()
        j = 0
        i = 0
        for link in links:
            link = link.strip("\n")
            if b is True:
                failed.append(link)
                continue
            print(i)
            if j == 400:
                sleep(randint(20,30))
                j = 0
            # sleep(randint(1,5))
            m = scrape_detail(link)
            matriky['matriky'].append(m.__dict__)
            # try:
            #     m = scrape_detail(link)
            #     matriky['matriky'].append(m.__dict__)
            # except:
            #     failed.append(link)
            #     failedCnt += 1
            #     if failedCnt == 3:
            #         failedCnt = 0
            #         sleep(120)
                    # b = True
            j += 1
            i += 1
            # break

        # with open("Jsons/Brno/Brno_failed.json", "w", encoding='utf8') as outfile:
        with open("Jsons/Litomerice/Litomerice4.json", "w", encoding='utf8') as outfile:  # TODO -> vratit
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        i = 0
        while i != -1:
            i = get_links(i)
            sleep(180)
    else:
        scrape_links()
        with open("Jsons/Litomerice/LitomericeFailed4.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(link + "\n")
        # print(failed)


if __name__ == '__main__':
    main()