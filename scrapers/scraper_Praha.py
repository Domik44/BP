import json
from datetime import date

from bs4 import BeautifulSoup
import requests
from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
import sys
from time import sleep

from scraper_header import get_text, Matrika


prefix = "https://ebadatelna.soapraha.cz"

matriky = dict()
matriky['web'] = prefix
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []

driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")



def scrape_detail(url):
    # url = "https://ebadatelna.soapraha.cz/pages/MatrikaPage/matrikaId/3728?1"
    m = Matrika()
    url = url.strip("\n")
    m.url = url

    d = {}
    okres = []
    webText = get_text(url, {'Accept-Language': 'cs-CS'})
    heading = webText.find('h1').get_text()

    m.signatura = heading

    leftContainer = webText.find(class_="matrikaDetailLeftContainer")
    rows = leftContainer.findAll(class_="tableMatrikaBasicInfoRow")
    obsah = []

    for row in rows:
        # print("-----------------------------------------------------------")
        header = row.find(class_="tableMatrikaBasicInfoColumn tableMatrikaBasicInfoHeader tableMatrikaBasicInfoHeader1").text.strip()
        # print(header)
        val = row.findAll(class_="tableMatrikaBasicInfoColumn tableMatrikaBasicInfoHeader2")
        if len(val) > 1:
            year = val[0].text.strip().replace(" - ", "-")
            index = val[1].text.strip().replace(" - ", "-")
            if "Narození / index" == header:
                if year != "---":
                    obsah.append("N•" + year)
                if index != "---":
                    obsah.append("I-N•" + index)
            if "Oddaní / index" == header:
                if year != "---":
                    obsah.append("O•" + year)
                if index != "---":
                    obsah.append("I-O•" + index)
            if "Zemřelí / index" == header:
                if year != "---":
                    obsah.append("Z•" + year)
                if index != "---":
                    obsah.append("I-Z•" + index)
        else:
            val = val[0].text.strip()
            if "Původce" == header:
                m.puvodce = val
            elif "Jazyk" == header:
                m.jazyk = val
            elif "Okres" == header:
                m.okresy = val.split(", ")

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

    uzemiTable = webText.findAll(class_="obecCastLabel")
    for element in uzemiTable:
        var = []
        element = element.text.split(' / ')
        key = element[0]
        element.pop(0)
        for e in element:
            e = e.split(', ')
            for i in e:
                i = i.split(' [')
                var.append(i[0])
        d[key.replace(' - ', '-')] = var

    m.uzemi = d

    return m


def get_links():
    driver = Chrome(service=driver_service)
    driver.get("https://ebadatelna.soapraha.cz/pages/SearchMatrikaPage?54")
    searchButton = driver.find_element(By.XPATH, "// div[contains(text(),\'Vyhledat')]")
    searchButton.click()
    driver.get(driver.current_url)
    webText = BeautifulSoup(driver.page_source, "html.parser")
    links = webText.findAll(class_="matrikaLink")
    maxPage = int(webText.findAll(class_="pageNumber")[-1].getText())
    for i in range(1, maxPage):#range(1,1):
        nextButton = driver.find_element(By.XPATH, "// a[contains(text(),\'>')]")
        nextButton.click()
        driver.get(driver.current_url)
        webText = BeautifulSoup(driver.page_source, "html.parser")
        links.extend(webText.findAll(class_="matrikaLink"))

    with open("Jsons/Praha/PrahaLinks.txt", "a", encoding='utf8') as outfile:
        for link in links:
            outfile.write(prefix + link.get("href") + "\n")
    print("Celkove " + str(len(links)))

    driver.quit()


def scrape_links():
    with open('Jsons/Praha/PrahaLinks.txt', 'r', encoding='utf8') as linkFile:
    # with open('Jsons/Praha/PrahaFailed.txt', 'r', encoding='utf8') as linkFile: # TODO -> vratit na puvodni
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

        # for link in failed:
        #     try:
        #         m = scrape_detail(link)
        #         matriky['matriky'].append(m.__dict__)
        #         failed.remove(link)
        #     except:
        #         failed.append(link)

        # with open("Jsons/Brno/Brno_failed.json", "w", encoding='utf8') as outfile:
        with open("Jsons/Praha/Praha2.json", "w", encoding='utf8') as outfile: # TODO -> vratit na puvodni
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)


def main():
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        get_links()
    else:
        scrape_links()
        with open("Jsons/Praha/PrahaFailed2.txt", "a", encoding='utf8') as outfile: # TODO -> vratit na puvodni
            for link in failed:
                outfile.write(link.strip("\n") + "\n")
        print(failed)


if __name__ == '__main__':
    main()