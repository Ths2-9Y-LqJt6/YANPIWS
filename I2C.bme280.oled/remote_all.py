#!/usr/bin/python


# grab args from CLI
import argparse

parser = argparse.ArgumentParser()

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
parser.add_argument('--bus', '-b', default=0, type=int, help='Bus Number, defaults to 0')

# IP address of your YANPIWS device you want to show data from
parser.add_argument('--remote_ip', '-ip', default='192.168.68.105', type=str, help='Temp sensor ID, defaults to 0x76')

# ID from your YANPIWS config.csv of temp 1
parser.add_argument('--temp_id1', '-id1', default='231', type=int, help='remote temp ID #1, defaults to 231')

# ID from your YANPIWS config.csv of temp 2
parser.add_argument('--temp_id2', '-id2', type=int, help='remote temp ID #2, defaults to 63')

args = parser.parse_args()

yanpiws_ip = args.remote_ip
yanpiws_temp_1 = args.temp_id1
yanpiws_temp_2 = args.temp_id2

bus_number = args.bus

temp1url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=humidity&id=' + str(yanpiws_temp_1)
temp2url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=humidity&id=' + str(yanpiws_temp_2)
forecastUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=forecast_full_json'
sunsetUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=sunset'
sunriseUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=sunrise'
datetimeUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=datetime'

def get_string_from_url(url):
    import urllib.request
    raw_html = urllib.request.urlopen(url).read().decode('utf-8').rstrip()
    return raw_html

# fetch the cooked up html -> strings
import json
temp1 = json.loads(get_string_from_url(temp1url))
temp1final = str(int(float(temp1[0]['temp']))) + temp1[0]['label']

if yanpiws_temp_2 != None:
    temp2 = json.loads(get_string_from_url(temp2url))
    temp2final = ' ' + str(int(float(temp2[0]['temp']))) + temp2[0]['label']
else:
    temp2final = ''

forecast = json.loads(get_string_from_url(forecastUrl))

sunset = json.loads(get_string_from_url(sunsetUrl))
sunrise = json.loads(get_string_from_url(sunriseUrl))

date_time = json.loads(get_string_from_url(datetimeUrl))

import smbus
import time
from ctypes import c_short
from ctypes import c_byte
from ctypes import c_ubyte
import json

import time
import os
from random import *
import Adafruit_GPIO.SPI as SPI
import Adafruit_SSD1306
from PIL import Image
from PIL import ImageDraw
from PIL import ImageFont
import random
import subprocess

# set full puth for incling libs below
full_path = os.path.dirname(os.path.abspath(__file__)) + "/"

# Raspberry Pi pin configuration:
RST = None     # on the PiOLED this pin isnt used
# Note the following are only used with SPI:
DC = 23
SPI_PORT = 0
SPI_DEVICE = 0

# Rev 2 Pi, Pi 2 & Pi 3 uses bus 1
# Rev 1 Pi uses bus 0
# Orange Pi Zero uses bus 0 for pins 1-5 (other pins for bus 1 & 2)
disp = Adafruit_SSD1306.SSD1306_128_64(rst=RST, i2c_bus=bus_number)

# Initialize library.
disp.begin()

# Clear display.
disp.clear()
disp.display()

# Create blank image for drawing.
# Make sure to create image with mode '1' for 1-bit color.
width = disp.width
height = disp.height
image = Image.new('1', (width, height))

# Get drawing object to draw on image.
draw = ImageDraw.Draw(image)

# Draw a black filled box to clear the image.
draw.rectangle((-20,-20,width,height), outline=0, fill=0)

# Draw some shapes.
# First define some constants to allow easy resizing of shapes.
padding = -2
top = padding
bottom = height-padding

# Load default font.
font = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 10)
font_small = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 12)
# Alternatively load a TTF font.  Make sure the .ttf font file is in the same directory as the python script!
# Some other nice fonts to try: http://www.dafont.com/bitmap.php
# font = ImageFont.truetype('Minecraftia.ttf', 8)

# Draw a black filled box to clear the image.
draw.rectangle((0,0,width,height), outline=0, fill=0)
sunriseFinal =  sunrise['sunrise'].split(' ')[0]
sunsetFinal =  sunset['sunset'].split(' ')[0]

# render the data
draw.text((0, top ), date_time['date'] + ' ' + date_time['time'] , font=font_small, fill=255)
draw.text((0, top + 17), temp1final + temp2final + ' ' + sunriseFinal + ' ' + sunsetFinal, font=font_small, fill=255)
draw.text((0, top + 35), 'H: ' + str(int(forecast[0]['temperatureHigh'])) + ' L: '
          + str(int(forecast[0]['temperatureLow'])) + ' ' + forecast[0]['icon'], font=font_small, fill=255)
draw.text((0, top + 52), 'H: ' + str(int(forecast[1]['temperatureHigh'])) + ' L: '
          + str(int(forecast[1]['temperatureLow'])) + ' ' + forecast[1]['icon'], font=font_small, fill=255)

# Display image.
disp.image(image)
disp.display()
