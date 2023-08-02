import re

from orator import DatabaseManager, Model

from selenium.webdriver import Chrome
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from time import sleep

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
    __fillable__ = ['type', 'RUIAN_id', 'name',
                    'partOf', 'longitude', 'latitude']


db = DatabaseManager(config)
Model.set_connection_resolver(db)
driver_service = Service(executable_path="C:\\Users\\popdo\\Desktop\\BCP\\Scrappery\\Drivers\\chromedriver.exe")
d = {}
municipalities = Territory.where('type', '=', '4').get()
failedParts = []
failedMuni = []


def write_line(file, mun):
    file.write(
        str(mun.id) + ';' + str(mun.type) + ';' + str(mun.RUIAN_id) + ';' + mun.name + ';' + str(
            mun.partOf) + ';' + str(mun.latitude) + ';' + str(mun.longitude) + '\n')


def get_url(string, driver):
    prefix = 'https://cs.wikipedia.org/w/index.php?search='
    sufix = '&title=Speciální%3AHledání&ns0=1&ns100=1&ns102=1'

    url = prefix + string + sufix
    driver.get(url)
    sleep(1)

    resultDiv = driver.find_elements(By.CLASS_NAME, 'mw-search-result-heading')[0]
    resultDiv.find_element(By.TAG_NAME, 'a').click()
    sleep(1)


def format_coordinates(string):
    string = string.split('params=')[1]
    string = re.split(r"_N_|_E_", string)

    return round(float(string[0]), 6), round(float(string[1]), 6)


def scrape_municipality(driver, mun):
    coor = driver.find_element(By.XPATH,
                               '/html/body/div[1]/div/div[3]/main/div[2]/div[1]/div[1]/div/div/span/a').get_attribute(
        'href')
    lat, long = format_coordinates(coor)

    mun.latitude = lat
    mun.longitude = long
    mun.save()

    # print(lat, long)


def scrape_municipality_part(driver, mun):
    xpath = "//a[@title='" + mun.name + "']"
    link = driver.find_element(By.XPATH, xpath)
    link.click()
    sleep(1)
    scrape_municipality(driver, mun)
    driver.back()
    sleep(1)


def main():
    driver = Chrome(service=driver_service)
    f = open("coordinates.csv", "w")
    f.close()

    with open('coordinates.csv', 'a', encoding='utf8') as wFile:
        wFile.write('ID;type;RUIAN;name;partOf;latitude;longitude\n')
        for mun in municipalities:
            # mun = Territory.where('id', '=', 402).first()
            CO = Territory.where('type', '=', '5').where('partOf', '=', mun.id).get()
            print(mun.name, mun.RUIAN_id)
            try:
                get_url(mun.name + '+' + str(mun.RUIAN_id), driver)
                scrape_municipality(driver, mun)
            except:
                failedMuni.append((mun.name, mun.id))
            write_line(wFile, mun)
            for part in CO:
                if part.name == mun.name:
                    part.latitude = mun.latitude
                    part.longitude = mun.longitude
                    part.save()
                else:
                    # try:
                    #     scrape_municipality_part(driver, part)
                    # except:
                    try:
                        get_url(part.name + '+' + str(part.RUIAN_id), driver)
                        scrape_municipality(driver,part)
                        # driver.back()
                        # sleep(1)
                    except:
                        failedParts.append((part.name, part.id))
                write_line(wFile, part)
            # break

    wFile.close()
    driver.quit()


if __name__ == '__main__':
    main()
