"""
Author: Dominik Pop
Date:   20.12.2022
Last:   20.12.2022

Description:

VUT FIT, 3BIT
"""

from bs4 import BeautifulSoup
import requests
import random

user_agents_list = [
    'Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.83 Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.51 Safari/537.36'
]
HEADER = {'User-Agent': random.choice(user_agents_list), 'Connection': 'keep-alive'}


class Matrika:
    def __init__(self):
        self.url = None
        self.puvodce = None
        self.signatura = None
        self.invCislo = None
        self.typ = None
        self.jazyk = None
        self.rozsah = None
        self.obsah = None
        self.uzemi = None
        self.okresy = None

    def __str__(self):
        return self.signatura


def get_text(url, headers=None):
    if headers is None:
        response = requests.get(url)
    else:
        response = requests.get(url, headers=headers)
    webText = BeautifulSoup(response.text, "html.parser")

    return webText