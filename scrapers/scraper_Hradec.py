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

from selenium.webdriver import Chrome, ChromeOptions
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By

from scrapper_header import Matrika

prefix = "https://aron.vychodoceskearchivy.cz"

matriky = dict()
matriky['web'] = prefix
matriky['datum'] = str(date.today())
matriky['matriky'] = []
jsonStrings = []
failed = []
failedCnt = 0
lastFailed = -1
failedLinks = []
links = []
driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")
liUrl = None


def get_type(string):
    if 'záznamy narozených' == string:
        string = 'N•'
    elif 'záznamy zemřelých' == string:
        string = 'Z•'
    elif 'záznamy oddaných' == string:
        string = 'O•'
    elif 'rejstřík narozených' == string:
        string = 'I-N•'
    elif 'rejstřík zemřelých' == string:
        string = 'I-Z•'
    elif 'rejstřík oddaných' == string:
        string = 'I-O•'

    return string


def scrape_book(driver, puvodce):
    m = Matrika()
    m.url = driver.current_url
    print(m.url)
    m.puvodce = puvodce
    m.obsah = []
    m.uzemi = {}
    m.okresy = []
    obsah = []

    infoTable1 = driver.find_element(By.XPATH, '//*[@id="app"]/div/div/div[2]/div[2]/div/div[2]/div[2]/div[2]/div[1]/div/div[2]/div[1]/div[1]/div[2]')
    rowsTable1 = infoTable1.find_elements(By.CLASS_NAME, 'makeStyles-flex-31')
    for row in rowsTable1:
        cols = row.find_elements(By.TAG_NAME, 'div')
        label = cols[0].text.lower()
        value = cols[1].text
        if 'signatura přidělená při zpracování archiválie' in label:
            m.signatura = value
        elif 'druh záznamu' in label:
            value = value.split('\n')
            for val in value:
                val = get_type(val)
                obsah.append(val)
        elif 'datace vzniku' in label:
            m.rozsah = value
            value = value.replace(' - ', '-')
            for val in obsah:
                m.obsah.append(val + value)
        elif 'signatura přidělená při zpracování archiválie' in label:
            m.signatura = value
        elif 'ukládací číslo' in label:
            m.invCislo = value

    div = driver.find_element(By.XPATH, '//*[@id="app"]/div/div/div[2]/div[2]/div/div[2]/div[2]/div[2]/div[1]/div/div[2]/div[1]/div[2]/div[2]/div/div[2]')
    while 'Načítání' in div.text:
        sleep(2)

    try:
        button = div.find_element(By.TAG_NAME, 'button')
        if 'Zobrazit všechny' in button.text:
            button.click()
            sleep(1)
    except:
        None

    div = driver.find_element(By.XPATH, '//*[@id="app"]/div/div/div[2]/div[2]/div/div[2]/div[2]/div[2]/div[1]/div/div[2]/div[1]/div[2]/div[2]/div/div[2]')
    hrefs = div.find_elements(By.TAG_NAME, 'a')
    for a in hrefs:
        if a is None:
            raise Exception
        a = a.text.strip(')').split(' (')
        name = a[0].replace(' - ', '-')
        hierarchy = a[1].split(' : ')[0].split(', ')
        if len(hierarchy) == 3:
            type = 5
            obec = hierarchy[0]
            okres = hierarchy[1]
            m.uzemi[name] = {"typ": type, "okres": okres, "obec": obec, "varianty": []}
        elif len(hierarchy) == 2:
            type = 4
            okres = hierarchy[0]
            m.uzemi[name] = {"typ": type, "okres": okres, "varianty": []}
        else:
            type = 6
            cobec = hierarchy[0]
            obec = hierarchy[1]
            okres = hierarchy[2]
            m.uzemi[name] = {"typ": type, "okres": okres, "obec": obec, "cast": cobec, "varianty": []}
        if okres not in m.okresy:
            m.okresy.append(okres)

    matriky['matriky'].append(m.__dict__)


lastJ = 35 #
lastK = 11
def scrape_li(driver, icons, ico, ulNumber):
    global lastJ
    global lastK
    lenIcons = len(icons)

    poc = 0
    for j in range(lastJ, lenIcons):
        lastJ = j
        if poc == 2:
            raise Exception
        icon = icons[j]
        item = ico[j]
    # for icon, item in icons:
        sleep(1)
        item.click()
        sleep(3)
        info = driver.find_element(By.CLASS_NAME, 'makeStyles-flex-31')
        puvodce = info.find_element(By.XPATH, '//*[@id="app"]/div/div/div[2]/div[2]/div/div[2]/div[2]/div/div[1]/div/div[1]/div/div/h3[2]').text
        sleep(1)
        ul = driver.find_elements(By.TAG_NAME, 'ul')[ulNumber]
        lis = ul.find_elements(By.TAG_NAME, 'li')
        items = ul.find_elements(By.CLASS_NAME, 'MuiTypography-root.MuiTreeItem-label.MuiTypography-body1')
        lenItems = len(items)
        for k in range(lastK, lenItems):
            lastK = k
            item = items[k]
            li = lis[k]
        # for item, li in zip(items, lis):
            item.click()
            sleep(3)
            if li.get_attribute('class') != 'MuiTreeItem-root Mui-expanded Mui-selected':
                sleep(10)
                raise Exception
            try:
                scrape_book(driver, puvodce)
            except:
                failed.append(driver.current_url)
            sleep(1)
            # break
        lastK = 0
        try:
            icon.click()
        except:
            lastJ += 1
            raise Exception
        sleep(1)
        poc += 1
        # break
    sleep(2)


lastI = 17 # S
def scrape_hard(driver):
    global lastI
    global lastJ
    ul = driver.find_elements(By.TAG_NAME, 'ul')[2]
    iconsUL = ul.find_elements(By.CLASS_NAME, 'MuiTreeItem-iconContainer')
    lenIcons = len(iconsUL)
    # for icon in iconsUL:
    sleep(2)
    for i in range(lastI, lenIcons):
        lastI = i
        icon = iconsUL[i]
        icon.click()
        sleep(1)
        ulUnder = driver.find_elements(By.TAG_NAME, 'ul')[3]
        iconsUnder = ulUnder.find_elements(By.CLASS_NAME, 'MuiTreeItem-iconContainer')
        itemsUnder = ulUnder.find_elements(By.CLASS_NAME, 'MuiTypography-root.MuiTreeItem-label.MuiTypography-body1')
        scrape_li(driver, iconsUnder, itemsUnder, 4)
        lastJ = 0
        icon.click()
        sleep(1)
        # break  # TODO
    lastI = 0
    sleep(2)


def scrape_simple(driver):
    global lastJ
    ul = driver.find_elements(By.TAG_NAME, 'ul')[2]
    iconsUL = ul.find_elements(By.CLASS_NAME, 'MuiTreeItem-iconContainer')
    items = ul.find_elements(By.CLASS_NAME, 'MuiTypography-root.MuiTreeItem-label.MuiTypography-body1')
    scrape_li(driver, iconsUL, items, 3)
    lastJ = 0


def scrape_links():
    options = ChromeOptions()
    options.add_argument('--allow-insecure-localhost')  # differ on driver version. can ignore.
    prefs = {'profile.default_content_setting_values': {'cookies': 2, 'images': 2, 'javascript': 2,
                                                        'plugins': 2, 'popups': 2, 'geolocation': 2,
                                                        'notifications': 2, 'auto_select_certificate': 2,
                                                        'fullscreen': 2,
                                                        'mouselock': 2, 'mixed_script': 2, 'media_stream': 2,
                                                        'media_stream_mic': 2, 'media_stream_camera': 2,
                                                        'protocol_handlers': 2,
                                                        'ppapi_broker': 2, 'automatic_downloads': 2, 'midi_sysex': 2,
                                                        'push_messaging': 2, 'ssl_cert_decisions': 2,
                                                        'metro_switch_to_desktop': 2,
                                                        'protected_media_identifier': 2, 'app_banner': 2,
                                                        'site_engagement': 2,
                                                        'durable_storage': 2}}
    options.add_experimental_option('prefs', prefs)
    options.add_argument("disable-infobars")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-javascript")
    caps = options.to_capabilities()
    caps["acceptInsecureCerts"] = True
    driver = Chrome(desired_capabilities=caps, service=driver_service)

    driver.get("https://aron.vychodoceskearchivy.cz/apu/1875fe9a-f56e-4735-866e-12f442ac8eb9")
    driver.maximize_window()
    sleep(10)

    icons = driver.find_elements(By.CLASS_NAME, 'MuiTreeItem-iconContainer')
    icons.pop(0)
    i = 0
    while i < len(icons):
        try:
            icons = driver.find_elements(By.CLASS_NAME, 'MuiTreeItem-iconContainer')
            icons.pop(0)
        except:
            driver.get("https://aron.vychodoceskearchivy.cz/apu/1875fe9a-f56e-4735-866e-12f442ac8eb9")
            sleep(10)
            continue

        sleep(1)
        try:
            icons[i].click()
        except:
            driver.get("https://aron.vychodoceskearchivy.cz/apu/1875fe9a-f56e-4735-866e-12f442ac8eb9")
            sleep(5)
            continue

        try:
            if i < 2:
                scrape_hard(driver)
            else:
                scrape_simple(driver)
        except:
            driver.quit()
            driver = Chrome(desired_capabilities=caps, service=driver_service)
            driver.maximize_window()
            driver.get("https://aron.vychodoceskearchivy.cz/apu/1875fe9a-f56e-4735-866e-12f442ac8eb9")
            sleep(5)
            continue

        sleep(1)
        try:
            icons[i].click()
        except:
            driver.get("https://aron.vychodoceskearchivy.cz/apu/1875fe9a-f56e-4735-866e-12f442ac8eb9")
            sleep(6)
            i += 1
            continue
        i += 1
        break  # TODO

    driver.quit()


def main():
    global failedCnt
    global lastFailed
    if len(sys.argv) > 1 and sys.argv[1] == 'links':
        pass
    else:
        scrape_links()
        with open("Jsons/HradecKralove/Hradec4_13.json", "w", encoding='utf8') as outfile:
            json.dump(matriky, outfile, indent=6, ensure_ascii=False)
        print(failed)
        with open("Jsons/HradecKralove/HradecFailedLinks.txt", "a", encoding='utf8') as outfile:
            for link in failed:
                outfile.write(str(link) + "\n")


if __name__ == '__main__':
    main()