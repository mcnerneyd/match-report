#!/bin/bash

source ./venv/bin/activate

if [ ! -d tmp ]
	then
		mkdir tmp
	fi

robot -d tmp Card Admin Test.robot
