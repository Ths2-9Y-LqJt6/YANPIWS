#!/usr/bin/python3


# grab args from CLI
import argparse
from luma.core.interface.serial import i2c
from luma.core.render import canvas
from luma.oled.device import ssd1306
from PIL import ImageFont
import os
import argparse
import json
import time
import logging.handlers

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

my_logger = logging.getLogger('MyLogger')
my_logger.setLevel(logging.DEBUG)
handler = logging.handlers.SysLogHandler(address='/dev/log')
my_logger.addHandler(handler)


def get_string_from_url(url):
    import urllib.request
    raw_html = urllib.request.urlopen(url).read().decode('utf-8').rstrip()
    return raw_html


def get_humid_and_temp(id):
    forecastUrl = 'http://' + str(yanpiws_ip) + '/ajax.php?content=humidity&id=' + str(id)
    return json.loads(get_string_from_url(forecastUrl))


def get_date_time():
    url = 'http://' + str(yanpiws_ip) + '/ajax.php?content=datetime'
    date_time = json.loads(get_string_from_url(url))
    string = date_time['date'] + ' ' + date_time['time']
    return string


def show_info(device):
    my_logger.debug("Weathercaster: remote_all start")

    # fetch the cooked up json -> strings
    humid_and_temp1 = get_humid_and_temp(yanpiws_temp_1)
    first_line = get_date_time()
    second_line = ''
    third_line = ''

    if humid_and_temp1[0]['temp'] != 'NA':
        second_line = str(int(float(humid_and_temp1[0]['temp']))) + '°' + humid_and_temp1[0]['label']
        if 'humidity' in humid_and_temp1[0]:
            second_line = str(int(float(humid_and_temp1[0]['humidity']))) + '% ' + second_line
    if yanpiws_temp_2 is not None and humid_and_temp1[0]['humidity'] != '' and humid_and_temp1[0]['temp'] != 'NA':
        humid_and_temp2 = get_humid_and_temp(yanpiws_temp_2)
        third_line = str(int(float(humid_and_temp2[0]['temp']))) + '°' + str(humid_and_temp2[0]['label'])
        if 'humidity' in humid_and_temp2[0] and humid_and_temp2[0]['humidity'] != '':
            third_line = str(int(float(humid_and_temp2[0]['humidity']))) + '% ' + third_line

    full_path = os.path.dirname(os.path.abspath(__file__)) + "/"
    font2 = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 12)
    font1 = ImageFont.truetype(full_path + "Lato-Heavy.ttf", 22)

    my_logger.debug("Weathercaster: remote_all draw")

    with canvas(device) as draw:
        # draw.rectangle(device.bounding_box, outline="white", fill="black")
        draw.text((0, 0), first_line, font=font2, fill="white")
        draw.text((0, 17), second_line, font=font1, fill="white")
        draw.text((0, 41), third_line, font=font1, fill="white")


def full_stack():
    import traceback, sys
    exc = sys.exc_info()[0]
    stack = traceback.extract_stack()[:-1]  # last one would be full_stack()
    if exc is not None:  # i.e. an exception is present
        del stack[-1]  # remove call of full_stack, the printed exception
        # will contain the caught exception caller instead
    trc = 'Traceback (most recent call last):\n'
    stackstr = trc + ''.join(traceback.format_list(stack))
    if exc is not None:
        stackstr += '  ' + traceback.format_exc().lstrip(trc)
    return stackstr


def main(device):
    while True:
        show_info(device)
        time.sleep(5)


if __name__ == "__main__":
    try:
        my_logger.debug('Weathercaster: remote_all Starting ')
        serial = i2c(port=bus_number, address=0x3C)
        device = ssd1306(serial)

        main(device)
    except KeyboardInterrupt:
        my_logger.debug("Weathercaster: remote_all Stopping(Ctrl + C) ")
        pass
    finally:
        my_logger.debug("Weathercaster remote_all exit trace: " + full_stack())
