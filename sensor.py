#!/usr/bin/python3
import serial,os
se = serial.Serial("/dev/ttyUSB0", 9600)
line = se.readline().decode()
wet = line.split("\r\n")
for i in wet:
    if i != "":
        command = '/shell/solid.php '+i+' >> /tmp/solid_err.log'
        #print(command)
        os.system(command)
