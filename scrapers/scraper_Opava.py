"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

import json
from datetime import date

from bs4 import BeautifulSoup
import sys
from time import sleep

from selenium.common import NoSuchElementException
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver import Keys

from scrapper_header import get_text, Matrika

prefix = "https://digi.archives.cz"

matriky = dict()
matriky['web'] = prefix
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []
failedLinks = []
driver_service = Service(executable_path="chromedriver.exe")


def check_date(string):
    string = string.replace(",", "-")
    string = string.split('-')
    if len(string) < 2:
        string = string[0].split('–')

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

failedCnt = 0
def scrape_detail(url):
    global failedCnt
    # url = 'https://digi.archives.cz/da/permalink?xid=be8b6e6c-f13c-102f-8255-0050568c0263'
    webText = get_text(url)
    m = Matrika()
    m.url = url.strip('\n')
    d = {}
    d1 = {}
    districts = []

    labelFloat = webText.findAll(class_="labelFloat")
    contentFloat = webText.findAll(class_="contentFloat")
    for i,j in zip(labelFloat, contentFloat):
        label = i.get_text()
        content = j.get_text()
        if label == "Původce:":
            m.puvodce = content
        elif label == "Signatura archivu:":
            m.signatura = content
        elif label == "Inventární číslo:": # TODO -> mozna predelat na int?
            m.invCislo = content
        elif label == "Typ matriky:":
            m.typ = content
        elif label == "Jazyk:":
            m.jazyk = content.split(", ")
        elif label == "Časový rozsah:":
            m.rozsah = content.strip(" \r\n")
            m.rozsah = check_date(m.rozsah)
            m.rozsah = m.rozsah.replace("-"," - ")
        elif label == "Obsah svazku:":
            m.obsah = content.strip(" \r\n").replace(" ", "").split("\r\n\r\n")
            obsah = []
            for ob in m.obsah:
                ob = ob.split('•')
                if len(ob) < 2:
                    ob = ob[0] + "•" + m.rozsah.replace(" - ", "-")
                    obsah.append(ob)
                    continue
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

    if m.rozsah is None:
        failedCnt += 1
        if failedCnt == 3:
            failedCnt = 0
            sleep(120)
    else:
        failedCnt = 0

    return m


def get_links(i, url, cnt):
    opts = Options()
    user_agent = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A"
    opts.add_argument("user-agent=" + user_agent)

    driver = Chrome(service=driver_service)
    driver.get(url)

    try:
        rowTxt = driver.find_element(By.NAME, "rowTxt")
    except:
        return i

    sleep(5)
    rowTxt.clear()
    rowTxt.send_keys(i + 1)
    rowTxt.send_keys(Keys.ENTER)
    sleep(5)

    firstLink = driver.find_element(By.CLASS_NAME, "textBlockLink.linOn.linkRight")
    firstLink.click()
    sleep(3)

    links = []

    while i != -1:
        sleep(1)
        try:
            link = driver.find_element(By.ID, "permalinkPopupTextarea").get_attribute('value')
        except:
            failedLinks.append((i, cnt))
            break

        print(i)
        links.append(link)

        try:
            nextButton = driver.find_element(By.CLASS_NAME, "icon-forward3.icon.link")
            driver.execute_script("arguments[0].click();", nextButton)
            i += 1
        except NoSuchElementException:
            i = -1
            break

    driver.quit()

    with open("Jsons/Opava/OpavaLinks.txt", "a", encoding='utf8') as outfile:
        for link in links:
            outfile.write(link + "\n")

    print("Celkove " + str(len(links)))

    return i


def scrape_links():
    # with open('Jsons/Opava/OpavaLinks.txt', 'r', encoding='utf8') as linkFile:
    with open('Jsons/Opava/OpavaMissing.txt', 'r', encoding='utf8') as linkFile:
        links = linkFile.readlines()
        j = 0
        i = 0
        for link in links:
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

        with open("Jsons/Opava/Opava2.json", "w", encoding='utf8') as outfile:
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        cnt = 0
        i = 0
        while i != -1:
            i = get_links(i,"https://digi.archives.cz/da/searchlink?myQuery=544361307c2fe803ee0748390c1aa18d&modeView=LIST", cnt)
            sleep(180)
        print(failedLinks)
        cnt += 1
        i = 0
        while i != -1:
            i = get_links(i, "https://digi.archives.cz/da/searchlink?myQuery=cc8376f2bbd677e2a773137481adbe15&modeView=LIST", cnt)
            sleep(180)
        print(failedLinks)
    else:
        scrape_links()
        with open("Jsons/Opava/OpavaFailed2.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(link + "\n")
        print(failed)


if __name__ == '__main__':
    main()