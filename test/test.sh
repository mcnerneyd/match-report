#!/bin/bash

if [ ! -d tmp ]
	then
		mkdir tmp
	fi

robot -d tmp Admin Card Registration Test.robot
