#!/bin/bash

if [ ! -d tmp ]
	then
		mkdir tmp
	fi

robot -d tmp Card Registration Admin Test.robot
